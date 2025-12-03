<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Test\Unit\Model\Config\Source;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\GroupFactory;
use Fedex\Ondemand\Model\Config\Source\CategoryOptions;
use Magento\Catalog\Model\Category;
use Magento\Store\Model\Group;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CategoryOptionsTest extends TestCase

{
    protected $groupFactory;
    protected $group;
    protected $categoryFactory;
    protected $category;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $categoryOptions;
    protected function setUp(): void
    {
        $this->groupFactory = $this->getMockBuilder(GroupFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->group = $this->getMockBuilder(Group::class)
            ->setMethods(['load', 'getRootCategoryId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryFactory = $this->getMockBuilder(CategoryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->category = $this->getMockBuilder(Category::class)
            ->setMethods(['load', 'getChildrenCategories','getId','getName'])
            ->disableOriginalConstructor()
            ->getMock();

            $this->objectManager = new ObjectManager($this);

        $this->categoryOptions = $this->objectManager->getObject(
            CategoryOptions::class,
            [
                'groupFactory' => $this->groupFactory,
                'categoryFactory' => $this->categoryFactory,
            ]
        );
    }

    public function testToOptionArray()
    {
        $this->groupFactory->expects($this->any())->method('create')->willReturn($this->group);
        $this->group->expects($this->any())->method('load')->willReturnSelf();
        $this->group->expects($this->any())->method('getRootCategoryId')->willReturn(2);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturnSelf();
        $this->category->expects($this->any())->method('getChildrenCategories')->willReturn([$this->category]);
        $this->category->expects($this->any())->method('getId')->willReturn(2);
        $this->category->expects($this->any())->method('getName')->willReturn('Test');

        $this->assertIsArray($this->categoryOptions->toOptionArray());
    }
}
