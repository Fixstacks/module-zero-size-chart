<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\SizeChart\Block;

use Magento\Catalog\Block\Product\View;
use Zero\SizeChart\Model\Charts;

/**
 * The size guide's button and dialog blocks: product page blocks (the
 * product and its identities) that also carry the size chart cache tag, so
 * saving or deleting a chart invalidates every product page it could show on.
 */
class SizeGuide extends View
{
    /**
     * @inheritDoc
     */
    public function getIdentities()
    {
        return array_merge((array) parent::getIdentities(), [Charts::CACHE_TAG]);
    }
}
