<?php

declare(strict_types=1);

namespace MauticPlugin\AcolonoBulkMailerBundle\RecipientSource;

use MauticPlugin\AcolonoBulkMailerBundle\Helper\SpreadsheetReader;
use MauticPlugin\AcolonoBulkMailerBundle\Model\ContactResolver;

/**
 * Recipient source backed by an uploaded CSV/XLSX/ODS file. Requires an
 * "email" column; "firstname"/"lastname" are used when present. Rows are
 * de-duplicated by email within the file. Counters are readable after
 * getContacts() has been consumed.
 */
class CsvSource implements RecipientSourceInterface
{
    private int $rowsRead = 0;
    private int $created = 0;
    private int $invalid = 0;

    public function __construct(
        private readonly string $filePath,
        private readonly SpreadsheetReader $reader,
        private readonly ContactResolver $resolver,
        private readonly bool $createMissing = true,
    ) {
    }

    public function getContacts(): iterable
    {
        $seen = [];

        foreach ($this->reader->read($this->filePath) as $row) {
            ++$this->rowsRead;

            $email = strtolower(trim($row['email'] ?? ''));

            if ('' === $email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                ++$this->invalid;
                continue;
            }

            if (isset($seen[$email])) {
                continue; // duplicate within the same file
            }
            $seen[$email] = true;

            $fields = [];
            if (!empty($row['firstname'])) {
                $fields['firstname'] = $this->sanitizeField($row['firstname']);
            }
            if (!empty($row['lastname'])) {
                $fields['lastname'] = $this->sanitizeField($row['lastname']);
            }

            [$lead, $wasCreated] = $this->resolver->resolve($email, $fields, $this->createMissing);

            if (null === $lead) {
                continue; // missing and creation disabled
            }

            if ($wasCreated) {
                ++$this->created;
            }

            yield $lead;
        }
    }

    public function getRowsRead(): int
    {
        return $this->rowsRead;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getInvalid(): int
    {
        return $this->invalid;
    }

    /**
     * Strips markup (ends up unescaped in email tokens) and defangs a
     * leading formula-trigger character (CSV/formula injection).
     */
    private function sanitizeField(string $value): string
    {
        $value = trim(strip_tags($value));

        if ('' !== $value && str_contains('=+-@', $value[0])) {
            $value = "'".$value;
        }

        return $value;
    }
}
