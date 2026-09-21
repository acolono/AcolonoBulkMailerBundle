<?php

declare(strict_types=1);

namespace MauticPlugin\AcolonoBulkMailerBundle\RecipientSource;

use Mautic\LeadBundle\Entity\Lead;

/**
 * A recipient source turns some input (a CSV/Excel file, a manual selection,
 * a segment, ...) into a stream of Mautic contacts to send to.
 *
 * The sender is source-agnostic: adding a new source (SelectionSource,
 * SegmentSource) means implementing this interface, not touching BulkSender.
 */
interface RecipientSourceInterface
{
    /**
     * @return iterable<Lead>
     */
    public function getContacts(): iterable;
}
