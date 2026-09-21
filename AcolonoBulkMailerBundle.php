<?php

declare(strict_types=1);

namespace MauticPlugin\AcolonoBulkMailerBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;

/**
 * Bulk Mailer.
 *
 * Sends a template email to an ad-hoc recipient list (CSV/Excel) repeatedly.
 * Recipients may repeat between runs; every send is logged on the contact.
 */
class AcolonoBulkMailerBundle extends PluginBundleBase
{
}
