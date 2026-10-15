<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\SizeChart\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Stores > Configuration > Zero > Size Charts.
 */
class Config
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag('zero_size_chart/general/enabled', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function buttonText(?int $storeId = null): string
    {
        $text = trim((string) $this->scopeConfig->getValue('zero_size_chart/general/button_text', ScopeInterface::SCOPE_STORE, $storeId));

        return $text !== '' ? $text : (string) __('Size guide');
    }
}
