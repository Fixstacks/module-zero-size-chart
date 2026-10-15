<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\SizeChart\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Zero\SizeChart\Model\Attribute\Source\Chart;
use Zero\SizeChart\Model\Charts;

/**
 * The "Size Chart" product attribute: by category (empty), no size guide, or
 * a chart; in every attribute set's General / Product Details group.
 */
class AddSizeChartAttribute implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    public function apply(): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        if ($eavSetup->getAttributeId(Product::ENTITY, Charts::ATTRIBUTE)) {
            return;
        }
        $eavSetup->addAttribute(Product::ENTITY, Charts::ATTRIBUTE, [
            'type' => 'int',
            'label' => 'Size Chart',
            'input' => 'select',
            'source' => Chart::class,
            'global' => Attribute::SCOPE_GLOBAL,
            'required' => false,
            'user_defined' => true,
            'default' => '',
            'visible' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => false,
            'is_used_in_grid' => false,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => false,
            'note' => 'Empty: the chart of the product\'s category (Catalog > Size Charts).',
        ]);
        foreach ($eavSetup->getAllAttributeSetIds(Product::ENTITY) as $setId) {
            $group = $eavSetup->getAttributeGroup(Product::ENTITY, $setId, 'General')
                ?: $eavSetup->getAttributeGroup(Product::ENTITY, $setId, 'Product Details');
            if ($group) {
                $eavSetup->addAttributeToGroup(Product::ENTITY, $setId, (int) $group['attribute_group_id'], Charts::ATTRIBUTE, 95);
            }
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
