<?php

declare(strict_types=1);

namespace MauticPlugin\AcolonoBulkMailerBundle\Model;

/**
 * Aggregated outcome of one bulk send, used for the result flash message.
 */
final class SendResult
{
    private int $sent = 0;
    private int $skipped = 0;
    private int $created = 0;
    private int $invalid = 0;
    private int $rowsRead = 0;

    /** @var array<int, string> leadId => error message */
    private array $failed = [];

    public function addSent(): void
    {
        ++$this->sent;
    }

    public function addSkipped(int $count = 1): void
    {
        $this->skipped += $count;
    }

    public function addFailed(int $leadId, string $error): void
    {
        $this->failed[$leadId] = $error;
    }

    public function setCreated(int $created): void
    {
        $this->created = $created;
    }

    public function setInvalid(int $invalid): void
    {
        $this->invalid = $invalid;
    }

    public function setRowsRead(int $rowsRead): void
    {
        $this->rowsRead = $rowsRead;
    }

    public function getSent(): int
    {
        return $this->sent;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getInvalid(): int
    {
        return $this->invalid;
    }

    public function getRowsRead(): int
    {
        return $this->rowsRead;
    }

    public function getFailedCount(): int
    {
        return count($this->failed);
    }

    /** @return array<int, string> */
    public function getFailed(): array
    {
        return $this->failed;
    }
}
