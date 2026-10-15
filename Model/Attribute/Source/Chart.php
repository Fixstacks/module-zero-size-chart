<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\SizeChart\Model\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Zero\SizeChart\Model\Charts;

/**
 * The product attribute's options: by category (the default), none, or one
 * of the charts.
 */
class Chart extends AbstractSource
{
    public function __construct(
        private readonly Charts $charts
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '', 'label' => __('By category')],
                ['value' => (string) Charts::NONE, 'label' => __('No size guide')],
            ];
            foreach ($this->charts->all() as $chart) {
                $this->_options[] = ['value' => (string) $chart['chart_id'], 'label' => $chart['name']];
            }
        }

        return $this->_options;
    }
}
