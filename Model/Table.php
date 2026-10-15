<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\SizeChart\Model;

use Magento\Framework\Exception\LocalizedException;

/**
 * A chart's table as the admin types it: one row per line, cells separated
 * by "|", the first line the column headings:
 *
 *     Size | Chest (cm) | Waist (cm)
 *     S    | 86–91      | 71–76
 *     M    | 96–101     | 81–86
 *
 * Stored as {"columns": [...], "rows": [[...], ...]}; short rows are padded,
 * long rows cut to the columns.
 */
class Table
{
    private const MAX_COLUMNS = 12;
    private const MAX_ROWS = 60;

    /**
     * @return array{columns: string[], rows: array<int, string[]>}
     * @throws LocalizedException
     */
    public static function parse(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: []), static fn ($l) => $l !== ''));
        if (count($lines) < 2) {
            throw new LocalizedException(__('The table needs a heading line and at least one row.'));
        }
        $cells = static fn (string $line) => array_map('trim', explode('|', trim($line, '| ')));
        $columns = $cells(array_shift($lines));
        if (count($columns) < 2 || count($columns) > self::MAX_COLUMNS) {
            throw new LocalizedException(__('The heading line needs 2 to %1 columns separated by "|".', self::MAX_COLUMNS));
        }
        if (count($lines) > self::MAX_ROWS) {
            throw new LocalizedException(__('At most %1 rows.', self::MAX_ROWS));
        }
        $width = count($columns);
        $rows = array_map(
            static fn (string $line) => array_slice(array_pad($cells($line), $width, ''), 0, $width),
            $lines
        );

        return ['columns' => $columns, 'rows' => $rows];
    }

    /**
     * The stored table back as the admin types it.
     *
     * @param array{columns: string[], rows: array<int, string[]>} $table
     */
    public static function format(array $table): string
    {
        $lines = [implode(' | ', $table['columns'] ?? [])];
        foreach ($table['rows'] ?? [] as $row) {
            $lines[] = implode(' | ', $row);
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{columns: string[], rows: array<int, string[]>}
     */
    public static function decode(string $json): array
    {
        $table = json_decode($json, true);
        if (!is_array($table) || !isset($table['columns'], $table['rows'])) {
            return ['columns' => [], 'rows' => []];
        }

        return [
            'columns' => array_map('strval', (array) $table['columns']),
            'rows' => array_map(static fn ($row) => array_map('strval', (array) $row), (array) $table['rows']),
        ];
    }
}
