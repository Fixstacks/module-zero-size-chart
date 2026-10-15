<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\SizeChart\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * The size charts (zero_size_chart) and which one a product gets: the one
 * its "Size Chart" attribute names, or else the chart of the most specific
 * category the product is in (a chart on "Men" covers everything under it),
 * ties broken by sort order.
 */
class Charts implements IdentityInterface
{
    public const ATTRIBUTE = 'size_chart';
    /** Cache tag of every page that shows, or could show, a size guide (Block\SizeGuide). */
    public const CACHE_TAG = 'zero_size_chart';
    /** The attribute value that hides the size guide on a product its categories would give one. */
    public const NONE = -1;

    /** @var array<int, array>|null */
    private ?array $all = null;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly CategoryCollectionFactory $categoryCollectionFactory
    ) {
    }

    /**
     * Every chart by id, active first, then by sort order and name.
     *
     * @return array<int, array{chart_id: int, name: string, heading: string, is_active: bool, table: array, note: string, category_ids: int[], sort_order: int, updated_at: string}>
     */
    public function all(): array
    {
        if ($this->all !== null) {
            return $this->all;
        }
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll($connection->select()
            ->from($this->resource->getTableName('zero_size_chart'))
            ->order(['is_active DESC', 'sort_order ASC', 'name ASC']));
        $charts = [];
        foreach ($rows as $row) {
            $charts[(int) $row['chart_id']] = [
                'chart_id' => (int) $row['chart_id'],
                'name' => (string) $row['name'],
                'heading' => (string) ($row['heading'] ?: $row['name']),
                'is_active' => (bool) $row['is_active'],
                'table' => Table::decode((string) $row['table_json']),
                'note' => (string) $row['note'],
                'category_ids' => array_values(array_filter(array_map('intval', explode(',', (string) $row['category_ids'])))),
                'sort_order' => (int) $row['sort_order'],
                'updated_at' => (string) $row['updated_at'],
            ];
        }

        return $this->all = $charts;
    }

    public function get(int $chartId): ?array
    {
        return $this->all()[$chartId] ?? null;
    }

    /**
     * The chart a product shows, or null.
     */
    public function forProduct(Product $product): ?array
    {
        $chosen = (int) $product->getData(self::ATTRIBUTE);
        if ($chosen === self::NONE) {
            return null;
        }
        if ($chosen > 0) {
            $chart = $this->get($chosen);

            return $chart && $chart['is_active'] ? $chart : null;
        }
        $active = array_filter($this->all(), static fn (array $chart) => $chart['is_active'] && $chart['category_ids']);
        if (!$active) {
            return null;
        }
        $best = null;
        $bestDepth = -1;
        foreach ($this->categoryPaths($product) as $path) {
            foreach ($path as $depth => $categoryId) {
                foreach ($active as $chart) {
                    if (in_array($categoryId, $chart['category_ids'], true) && $depth > $bestDepth) {
                        $best = $chart;
                        $bestDepth = $depth;
                    }
                }
            }
        }

        return $best;
    }

    /**
     * @throws LocalizedException
     */
    public function save(?int $chartId, array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new LocalizedException(__('A size chart needs a name.'));
        }
        $table = Table::parse((string) ($data['table'] ?? ''));
        $row = [
            'name' => mb_substr($name, 0, 255),
            'heading' => mb_substr(trim((string) ($data['heading'] ?? '')), 0, 255) ?: null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'table_json' => (string) json_encode($table, JSON_UNESCAPED_UNICODE),
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
            'category_ids' => implode(',', array_values(array_unique(array_filter(array_map('intval', (array) ($data['category_ids'] ?? [])))))) ?: null,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
        ];
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('zero_size_chart');
        if ($chartId) {
            $connection->update($table, $row, ['chart_id = ?' => $chartId]);
        } else {
            $connection->insert($table, $row);
            $chartId = (int) $connection->lastInsertId($table);
        }
        $this->all = null;

        return $chartId;
    }

    public function delete(int $chartId): void
    {
        $this->resource->getConnection()->delete($this->resource->getTableName('zero_size_chart'), ['chart_id = ?' => $chartId]);
        $this->all = null;
    }

    /**
     * The cache tag of the pages a chart change affects (clean_cache_by_tags).
     *
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG];
    }

    /**
     * The product's categories as id paths from the root down, e.g. [[2, 11, 13], [2, 11, 14]].
     *
     * @return array<int, int[]>
     */
    private function categoryPaths(Product $product): array
    {
        $ids = array_map('intval', (array) $product->getCategoryIds());
        if (!$ids) {
            return [];
        }
        $paths = [];
        foreach ($this->categoryCollectionFactory->create()->addFieldToFilter('entity_id', ['in' => $ids]) as $category) {
            $paths[] = array_map('intval', explode('/', (string) $category->getPath()));
        }

        return $paths;
    }
}
