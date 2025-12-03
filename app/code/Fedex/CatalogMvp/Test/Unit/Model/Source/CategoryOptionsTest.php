<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
*/

namespace Fedex\Ondemand\Test\Unit\Model\Source;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\GroupFactory;
use Fedex\CatalogMvp\Model\Source\CategoryOptions;
use Magento\Catalog\Model\Category;
use Magento\Store\Model\Group;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Store\Model\StoreRepository;
use Magento\Store\Model\Store;

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
    protected $toggleConfigMock;
    protected $categoryRepositoryMock;
    protected $storeRepositoryMock;
    protected $storeMock;
    /**
     * Setup method
     *
     * @return void
     */
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
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();         
        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock(); 
        $this->storeRepositoryMock = $this->getMockBuilder(StoreRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock(); 
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRootCategoryId'])
            ->getMock(); 
        $this->categoryOptions = $this->objectManager->getObject(
            CategoryOptions::class,
            [
                'groupFactory' => $this->groupFactory,
                'categoryFactory' => $this->categoryFactory,
                'categoryRepository'=>$this->categoryRepositoryMock,
                'toggleConfig'=>$this->toggleConfigMock,
                'storeRepository'=> $this->storeRepositoryMock,
            ]
        );
    }

    /**
     * Test option array method
     *
     * @return void
     */
    public function testToOptionArray() : void
    {
        $this->groupFactory->expects($this->any())->method('create')->willReturn($this->group);
        $this->group->expects($this->any())->method('load')->willReturnSelf();
        $this->group->expects($this->any())->method('getRootCategoryId')->willReturn(2);
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturnSelf();
        $this->category->expects($this->any())->method('getChildrenCategories')->willReturn([$this->category]);
        $this->category->expects($this->any())->method('getId')->willReturn(2);
        $this->category->expects($this->any())->method('getName')->willReturn('Test');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);  
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->category);   
        $this->storeRepositoryMock->method('get')->willReturn($this->storeMock); 
        $this->storeMock->expects($this->any())->method('getRootCategoryId')->willReturn(2);  
        $this->assertIsArray($this->categoryOptions->toOptionArray());
    }

    public function testToOptionArrayIfToggleOff() : void
    {
        $this->groupFactory->expects($this->any())->method('create')->willReturn($this->group);
        $this->storeRepositoryMock->method('get')->willReturn($this->group);
        $this->group->expects($this->any())->method('getRootCategoryId')->willReturn(2);
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($this->category);
        $this->category->expects($this->any())->method('getChildrenCategories')->willReturn([$this->category]);
        $this->category->expects($this->any())->method('getId')->willReturn(2);
        $this->category->expects($this->any())->method('getName')->willReturn('Test');
        $this->assertIsArray($this->categoryOptions->toOptionArray());
    }    
}
