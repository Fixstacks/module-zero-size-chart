<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\SizeChart\Controller\Adminhtml\Chart;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Exception\LocalizedException;
use Zero\SizeChart\Model\Charts;

/**
 * Saves or deletes a chart, then invalidates the product pages by the size
 * chart cache tag (Block\SizeGuide carries it; the page cache and Varnish
 * listen to clean_cache_by_tags).
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Zero_SizeChart::charts';

    public function __construct(
        Context $context,
        private readonly Charts $charts,
        private readonly TypeListInterface $cacheTypeList
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $request = $this->getRequest();
        $id = (int) $request->getParam('id') ?: null;
        try {
            if ($id && $request->getParam('delete')) {
                $this->charts->delete($id);
                $this->messageManager->addSuccessMessage(__('The size chart is deleted.'));
                $this->cleanCaches();

                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
            $id = $this->charts->save($id, (array) $request->getPostValue());
            $this->messageManager->addSuccessMessage(__('The size chart is saved.'));
            $this->cleanCaches();
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/edit', $id ? ['id' => $id] : []);
    }

    private function cleanCaches(): void
    {
        $this->_eventManager->dispatch('clean_cache_by_tags', ['object' => $this->charts]);
        // The Size Chart attribute's options (the chart names) live in the EAV cache.
        $this->cacheTypeList->cleanType('eav');
    }
}
