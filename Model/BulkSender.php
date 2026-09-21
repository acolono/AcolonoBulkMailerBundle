<?php

declare(strict_types=1);

namespace MauticPlugin\AcolonoBulkMailerBundle\Model;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Sends one template email to an arbitrary set of contacts.
 *
 * Source-agnostic: it takes any iterable<Lead>. It sends per contact via
 * EmailModel::sendEmail(), which records an email Stat per contact (so the send
 * shows up on the contact timeline) and honours Do Not Contact by itself.
 *
 * Because it does NOT use the segment-broadcast path, the same contact can
 * receive the same template again on a later run. The optional
 * "skip already sent" filter re-introduces the once-per-contact behaviour on
 * demand, using the same email_stats data Mautic uses internally.
 */
class BulkSender
{
    public function __construct(
        private readonly EmailModel $emailModel,
        private readonly Connection $connection,
    ) {
    }

    /**
     * @param iterable<Lead> $contacts
     */
    public function send(Email $email, iterable $contacts, bool $skipAlreadySent = false): SendResult
    {
        $result = new SendResult();

        // Materialise contacts (also triggers find-or-create in the source).
        $leads = [];
        foreach ($contacts as $lead) {
            if ($lead instanceof Lead && null !== $lead->getId()) {
                $leads[$lead->getId()] = $lead;
            }
        }

        if ($skipAlreadySent && [] !== $leads) {
            foreach ($this->findAlreadySent($email->getId(), array_keys($leads)) as $leadId) {
                unset($leads[$leadId]);
                $result->addSkipped();
            }
        }

        if ([] === $leads) {
            return $result;
        }

        // EmailModel::sendEmail() expects [leadId => profileFields].
        $sendTo = [];
        foreach ($leads as $id => $lead) {
            $sendTo[$id] = $lead->getProfileFields();
        }

        /*
         * CONFIRM ON YOUR 7.x: 'transactional' marks this as a non-marketing
         * send so it isn't subject to the segment "send once" treatment and can
         * legitimately repeat. sendEmail() returns an array of errors keyed by
         * contact id; contacts absent from it were sent successfully.
         */
        $errors = $this->emailModel->sendEmail($email, $sendTo, ['email_type' => 'transactional']);

        foreach ($sendTo as $id => $fields) {
            if (isset($errors[$id])) {
                $result->addFailed((int) $id, (string) $errors[$id]);
            } else {
                $result->addSent();
            }
        }

        return $result;
    }

    /**
     * Contact ids that already have a stat for this email.
     *
     * @param array<int> $leadIds
     *
     * @return array<int>
     */
    private function findAlreadySent(int $emailId, array $leadIds): array
    {
        if ([] === $leadIds) {
            return [];
        }

        $qb = $this->connection->createQueryBuilder();
        $qb->select('DISTINCT es.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
            ->where($qb->expr()->eq('es.email_id', ':emailId'))
            ->andWhere($qb->expr()->in('es.lead_id', ':leadIds'))
            ->setParameter('emailId', $emailId)
            ->setParameter('leadIds', $leadIds, ArrayParameterType::INTEGER);

        return array_map('intval', $qb->executeQuery()->fetchFirstColumn());
    }
}
