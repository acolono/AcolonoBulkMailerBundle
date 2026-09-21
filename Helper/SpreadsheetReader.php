<?php

declare(strict_types=1);

namespace MauticPlugin\AcolonoBulkMailerBundle\Helper;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Reads a CSV, XLSX or ODS file into a list of associative rows keyed by the
 * (lower-cased, trimmed) header row. PhpSpreadsheet ships with Mautic core, so
 * this adds no new dependency and handles all three formats with one code path.
 */
class SpreadsheetReader
{
    /**
     * @return array<int, array<string, string>>
     */
    public function read(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if ([] === $rows) {
            return [];
        }

        $header = array_map(
            static fn ($h): string => strtolower(trim((string) $h)),
            array_shift($rows)
        );

        $result = [];
        foreach ($rows as $row) {
            $assoc = [];
            foreach ($header as $index => $name) {
                if ('' === $name) {
                    continue;
                }
                $assoc[$name] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            // Skip fully empty rows.
            if ('' === implode('', $assoc)) {
                continue;
            }

            $result[] = $assoc;
        }

        return $result;
    }
}
