<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Fedex\SharedCatalogCustomization\Model\Source\CategoryOption;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CategoryOptionTest extends TestCase
{

    protected $category;
    protected $categoryFactory;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $categoryOption;
    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfigMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->category = $this->createMock(Category::class);

        $this->categoryFactory = $this->getMockBuilder(CategoryFactory::class)
        ->setMethods(['create', 'getId', 'getName'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->categoryFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->category));
            
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
 
        $this->objectManager = new ObjectManager($this);
        $this->categoryOption = $this->objectManager->getObject(
            CategoryOption::class,
            [
                'categoryFactory' => $this->categoryFactory,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );
    }

    /**
     * Get Category Options Test
     *
     * @return void
     */
    public function testToOptionArray()
    {
        $categoryCollection = $this->createMock(Collection::class);

        $this->category->expects($this->once())
            ->method('getCollection')
            ->will($this->returnValue($categoryCollection));

        $categoryCollection->expects($this->once())
            ->method('addAttributeToSelect')
            ->will($this->returnSelf());

        $categoryCollection->expects($this->once())
            ->method('addAttributeToFilter')
            ->will($this->returnSelf());

        $categoryCollection->expects($this->once())
            ->method('setOrder')
            ->will($this->returnSelf());

        $categoryCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn(
                new \ArrayIterator([$this->category])
            );
        $this->category->expects($this->any())
            ->method('getName')
            ->willReturn('Select a category...');

        $this->category->expects($this->any())
            ->method('getId')
            ->willReturn(0);

        $this->categoryOption->toOptionArray();
    }

    /**
     * Get Category Options Test with Disabled Toggle
     *
     * @return void
     */
    public function testToOptionArrayToggleDisabled()
    {
        $categoryCollection = $this->createMock(Collection::class);
        $this->category->expects($this->once())
            ->method('getCollection')
            ->will($this->returnValue($categoryCollection));

        $categoryCollection->expects($this->once())
            ->method('addAttributeToSelect')
            ->will($this->returnSelf());

        $categoryCollection->expects($this->once())
            ->method('addAttributeToFilter')
            ->will($this->returnSelf());

        $categoryCollection->expects($this->once())
            ->method('setOrder')
            ->will($this->returnSelf());

        $categoryCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn(
                new \ArrayIterator([$this->category])
            );
        $this->category->expects($this->any())
            ->method('getName')
            ->willReturn('Select a category...');

        $this->category->expects($this->any())
            ->method('getId')
            ->willReturn(0);

        $this->categoryOption->toOptionArray();
    }
}
