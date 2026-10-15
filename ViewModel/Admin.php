<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\SizeChart\ViewModel;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Zero\SizeChart\Model\Charts;
use Zero\SizeChart\Model\Table;

/**
 * Catalog > Size Charts: the list and the form.
 */
class Admin implements ArgumentInterface
{
    private const CATEGORY_DEPTH = 4;

    public function __construct(
        private readonly Charts $charts,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly UrlInterface $url,
        private readonly RequestInterface $request,
        private readonly FormKey $formKey
    ) {
    }

    /**
     * @return array<int, array>
     */
    public function all(): array
    {
        return $this->charts->all();
    }

    public function current(): ?array
    {
        $id = (int) $this->request->getParam('id');

        return $id ? $this->charts->get($id) : null;
    }

    public function tableText(?array $chart): string
    {
        return $chart ? Table::format($chart['table']) : "Size | Chest (cm) | Waist (cm)\nS | 86–91 | 71–76\nM | 96–101 | 81–86\nL | 106–111 | 91–96";
    }

    /**
     * Categories to assign a chart to: id, name, level (1 = a root), in tree order.
     *
     * @return array<int, array{id: int, name: string, level: int}>
     */
    public function categories(): array
    {
        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addFieldToFilter('level', ['gteq' => 1])
            ->addFieldToFilter('level', ['lteq' => self::CATEGORY_DEPTH])
            ->setOrder('path', 'ASC');
        $categories = [];
        foreach ($collection as $category) {
            $categories[] = ['id' => (int) $category->getId(), 'name' => (string) $category->getName(), 'level' => (int) $category->getLevel()];
        }

        return $categories;
    }

    /**
     * @return array<int, string> category id => name
     */
    public function categoryNames(): array
    {
        $names = [];
        foreach ($this->categories() as $category) {
            $names[$category['id']] = $category['name'];
        }

        return $names;
    }

    public function url(string $route, array $params = []): string
    {
        return $this->url->getUrl($route, $params);
    }

    public function formKey(): string
    {
        return $this->formKey->getFormKey();
    }
}
