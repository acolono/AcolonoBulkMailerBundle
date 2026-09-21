<?php

declare(strict_types=1);

namespace MauticPlugin\AcolonoBulkMailerBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\AcolonoBulkMailerBundle\Form\Type\BulkSendType;
use MauticPlugin\AcolonoBulkMailerBundle\Helper\SpreadsheetReader;
use MauticPlugin\AcolonoBulkMailerBundle\Model\BulkSender;
use MauticPlugin\AcolonoBulkMailerBundle\Model\ContactResolver;
use MauticPlugin\AcolonoBulkMailerBundle\Model\SendResult;
use MauticPlugin\AcolonoBulkMailerBundle\RecipientSource\CsvSource;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The 2-click send screen: pick a template, drop a CSV/Excel file, send.
 */
class BulkMailerController extends AbstractFormController
{
    public function indexAction(
        Request $request,
        FormFactoryInterface $formFactory,
        EmailModel $emailModel,
        SpreadsheetReader $reader,
        ContactResolver $resolver,
        BulkSender $sender
    ): Response {
        // The menu's 'access' setting only hides the link, it doesn't guard the route.
        if (!$this->security->isGranted('email:emails:view')) {
            return $this->accessDenied();
        }

        $form = $formFactory->create(BulkSendType::class, [], [
            'action'        => $this->generateUrl('mautic_bulkmailer_index'),
            'email_choices' => $this->getTemplateEmailChoices($emailModel),
        ]);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data  = $form->getData();
                $email = $emailModel->getEntity((int) $data['email']);

                if (null === $email) {
                    $this->addFlashMessage('mautic.bulkmailer.flash.email_missing', [], 'error');

                    return $this->redirect($this->generateUrl('mautic_bulkmailer_index'));
                }

                $uploaded = $form->get('file')->getData();
                $tmpPath  = $uploaded->getPathname();

                $source = new CsvSource($tmpPath, $reader, $resolver, true);
                $result = $sender->send($email, $source->getContacts(), (bool) $data['skipAlreadySent']);

                // Counters populated while the generator was consumed by send().
                $result->setCreated($source->getCreated());
                $result->setInvalid($source->getInvalid());
                $result->setRowsRead($source->getRowsRead());

                $this->flashResult($result);

                return $this->redirect($this->generateUrl('mautic_bulkmailer_index'));
            }
        }

        return $this->delegateView([
            'viewParameters'  => ['form' => $form->createView()],
            'contentTemplate' => '@AcolonoBulkMailer/BulkMailer/send.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_bulkmailer_index',
                'mauticContent' => 'bulkMailer',
                'route'         => $this->generateUrl('mautic_bulkmailer_index'),
            ],
        ]);
    }

    /**
     * @return array<string, int> label => email id, template emails only
     */
    private function getTemplateEmailChoices(EmailModel $emailModel): array
    {
        $emails = $emailModel->getEntities([
            'filter' => [
                'force' => [
                    ['column' => 'e.emailType', 'expr' => 'eq', 'value' => 'template'],
                ],
            ],
            'orderBy'    => 'e.name',
            'orderByDir' => 'ASC',
            'ignore_paginator' => true,
        ]);

        $choices = [];
        foreach ($emails as $email) {
            $choices[$email->getName()] = $email->getId();
        }

        return $choices;
    }

    private function flashResult(SendResult $result): void
    {
        $this->addFlashMessage('mautic.bulkmailer.flash.result', [
            '%sent%'    => $result->getSent(),
            '%created%' => $result->getCreated(),
            '%skipped%' => $result->getSkipped(),
            '%failed%'  => $result->getFailedCount(),
            '%invalid%' => $result->getInvalid(),
        ]);
    }
}
