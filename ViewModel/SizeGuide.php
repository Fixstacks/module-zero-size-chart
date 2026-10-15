<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\SizeChart\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Zero\SizeChart\Model\Charts;
use Zero\SizeChart\Model\Config;

/**
 * The product page's size guide (templates/size-guide.phtml).
 */
class SizeGuide implements ArgumentInterface
{
    /** @var array<int, array|null> product id => chart, for the button and the dialog of one page */
    private array $resolved = [];

    public function __construct(
        private readonly Charts $charts,
        private readonly Config $config
    ) {
    }

    /**
     * The chart this product shows, or null for no button.
     */
    public function chart(Product $product): ?array
    {
        $id = (int) $product->getId();
        if (!array_key_exists($id, $this->resolved)) {
            $this->resolved[$id] = $this->config->isEnabled((int) $product->getStoreId())
                ? $this->charts->forProduct($product)
                : null;
        }

        return $this->resolved[$id];
    }

    public function buttonText(Product $product): string
    {
        return $this->config->buttonText((int) $product->getStoreId());
    }
}
