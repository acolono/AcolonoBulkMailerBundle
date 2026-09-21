# Bulk Mailer (Mautic 7 plugin)

Send a **template email** to an ad-hoc recipient list (CSV / XLSX / ODS),
repeatedly. Missing contacts are created automatically, and every send is
logged on the contact timeline. No segments required, and the same person can
receive the same template again on a later run.

- **Bundle:** `AcolonoBulkMailerBundle`
- **Namespace:** `MauticPlugin\AcolonoBulkMailerBundle`
- **Composer:** `acolono/mautic-bulk-mailer-bundle`
- **Requires:** PHP >= 8.2, Mautic ^7.0

## Install

Copy the folder into your Mautic `plugins/` directory (or `composer require`),
then:

```bash
php bin/console mautic:plugins:reload
php bin/console cache:clear
```

Open **Bulk Mailer** in the main menu.

## Recipient file format

A header row is required. Recognised columns (case-insensitive):

| Column      | Required | Notes                          |
|-------------|----------|--------------------------------|
| `email`     | yes      | used as the unique identifier  |
| `firstname` | no       | set on newly created contacts  |
| `lastname`  | no       | set on newly created contacts  |

Duplicate emails within one file are de-duplicated automatically. Invalid rows
are skipped and counted separately.

## How it works

Sends go through `EmailModel::sendEmail()` per contact (not the segment
broadcast path), so a `Stat` is logged per contact, Do Not Contact is
honoured, and repeats are allowed. Contact matching/creation uses Mautic's own
`LeadModel::import()` — the same method its native CSV importer uses.

"Skip recipients who already received this template" (optional) filters out
contacts with an existing stat for that email.

Requires the `email:emails:view` permission, checked both for the menu link
and inside the controller.

## Architecture

```
Controller ── BulkSendType (form)
     │
     ├── CsvSource ──> RecipientSourceInterface   (swap in SelectionSource / SegmentSource later)
     │        ├── SpreadsheetReader   (CSV/XLSX/ODS via PhpSpreadsheet)
     │        └── ContactResolver     (LeadModel::import())
     │
     └── BulkSender ── EmailModel::sendEmail()  +  email_stats skip query
              └── SendResult (counters for the result flash)
```

`BulkSender` is source-agnostic (`iterable<Lead>`), so future sources
(contacts-list selection, segment) are additive.

`DependencyInjection/AcolonoBulkMailerExtension.php` is required for
`Config/services.php` to be loaded at all — don't remove it.

## Status

MVP (steps 1–5): CSV/Excel screen, contact resolution, send + logging,
skip-already-sent, "create new template email" shortcut.

Next: contacts-list batch action with recipient-count confirmation; optional
default-template plugin setting; functional tests + packaging.

## Confirm against your exact 7.x point release

- `ContactResolver::resolve()` — `LeadModel::import()` signature/behaviour.
- `BulkSender::send()` — `EmailModel::sendEmail()` options/return shape.
- The "Create new template email" link's `mautic_email_action` route.
