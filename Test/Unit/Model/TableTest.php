<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\SizeChart\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Zero\SizeChart\Model\Table;

/**
 * The admin's text becomes columns and rows, and back; bad text is refused.
 */
class TableTest extends TestCase
{
    public function testLinesBecomeColumnsAndRows(): void
    {
        $table = Table::parse("Size | Chest (cm) | Waist (cm)\n S | 86–91 | 71–76 \n\n| M | 96–101 |\nL | 106–111 | 91–96 | extra");

        $this->assertSame(['Size', 'Chest (cm)', 'Waist (cm)'], $table['columns']);
        $this->assertSame([
            ['S', '86–91', '71–76'],
            ['M', '96–101', ''],
            ['L', '106–111', '91–96'],
        ], $table['rows']);
        $this->assertSame("Size | Chest (cm) | Waist (cm)\nS | 86–91 | 71–76\nM | 96–101 | \nL | 106–111 | 91–96", Table::format($table));
    }

    public function testStoredJsonComesBack(): void
    {
        $table = Table::parse("A | B\n1 | 2");

        $this->assertSame($table, Table::decode((string) json_encode($table)));
        $this->assertSame(['columns' => [], 'rows' => []], Table::decode('not json'));
    }

    public function testAHeadingAloneOrOneColumnIsRefused(): void
    {
        $this->expectException(LocalizedException::class);
        Table::parse("Size | Chest");
    }

    public function testOneColumnIsRefused(): void
    {
        $this->expectException(LocalizedException::class);
        Table::parse("Size\nS\nM");
    }
}
