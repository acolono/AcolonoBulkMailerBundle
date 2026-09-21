<?php

declare(strict_types=1);

namespace MauticPlugin\AcolonoBulkMailerBundle\Model;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

/**
 * Resolves a raw recipient to a Mautic contact via `LeadModel::import()` —
 * the same method Mautic's own CSV importer calls per row. The ONLY place
 * this touchpoint lives, so a version bump is a one-spot fix.
 */
class ContactResolver
{
    public function __construct(private readonly LeadModel $leadModel)
    {
    }

    /**
     * @param array<string, string> $fields extra profile fields, e.g. firstname/lastname
     *
     * @return array{0: ?Lead, 1: bool} the contact (or null if missing and $createMissing is false),
     *                                   and whether it was newly created
     */
    public function resolve(string $email, array $fields, bool $createMissing): array
    {
        $data = array_merge(['email' => $email], $fields);

        // CONFIRM ON YOUR 7.x: LeadModel::import() signature/order. $list
        // stays null so contacts are never added to a segment. Returns true
        // if an existing contact was matched, false if created OR (when
        // $createMissing is false) nothing matched — disambiguated below via
        // the lookup, since import() only returns a bool.
        $fieldMap = array_combine(array_keys($data), array_keys($data));

        $wasMerged = $this->leadModel->import(
            $fieldMap,
            $data,
            null,           // owner
            null,           // list — no segment
            null,           // tags
            true,           // persist
            null,           // eventLog
            null,           // importId
            false,          // skipIfExists
            $createMissing, // createNew
        );

        $lead = $this->leadModel->checkForDuplicateContact(['email' => $email]);

        if (!$lead instanceof Lead || !$lead->getId()) {
            return [null, false];
        }

        return [$lead, !$wasMerged];
    }
}
