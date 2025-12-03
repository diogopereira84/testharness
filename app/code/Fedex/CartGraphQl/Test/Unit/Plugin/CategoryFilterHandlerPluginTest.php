<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Unit\Plugin;

use Fedex\CartGraphQl\Plugin\CategoryFilterHandlerPlugin;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\LiveSearchAdapter\Model\QueryArgumentProcessor\FilterHandler\CategoryFilterHandler;
use PHPUnit\Framework\TestCase;

class CategoryFilterHandlerPluginTest extends TestCase
{
    protected $categoryCollectionFactory;
    protected $categoryFilterHandlerPlugin;
    protected function setUp(): void
    {
        $this->categoryCollectionFactory = $this->createMock(CollectionFactory::class);

        $this->categoryFilterHandlerPlugin = new CategoryFilterHandlerPlugin(
            $this->categoryCollectionFactory
        );
    }

    public function testAfterGetFilterVariables()
    {
        $categoryFilterHandler = $this->createMock(CategoryFilterHandler::class);
        $result = [['in' => ['category_url_1', 'category_url_2']]];
        $categoryCollection = $this->createMock(CategoryCollection::class);
        $categoryCollection->expects($this->once())->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollection->expects($this->once())->method('addAttributeToFilter')->willReturnSelf();
        $categoryCollection->expects($this->once())->method('getAllIds')->willReturn([1, 2]);

        $this->categoryCollectionFactory->expects($this->once())
            ->method('create')->willReturn($categoryCollection);
        $result = $this->categoryFilterHandlerPlugin->afterGetFilterVariables($categoryFilterHandler, $result);

        $this->assertEquals([['in' => [1, 2], 'attribute' => 'categoryIds']], $result);
    }
}
