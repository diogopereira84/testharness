<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Observer\ETag;

use Fedex\CatalogMvp\Observer\ETag\CategorySaveAfterEtagUpdateObserver;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\Category;
use Fedex\CatalogMvp\Api\ConfigInterface as CatalogMvpConfigInterface;
use Fedex\CatalogMvp\Helper\EtagHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class CategorySaveAfterEtagUpdateObserverTest extends TestCase
{

     /**
      * @var CatalogMvpConfigInterface
      */
    private $catalogMvpConfigInterface;

     /**
      * @var CategorySaveAfterEtagUpdateObserver
      */
    private $categorySaveAfterEtagUpdateObserver;

     /**
      * @var Category
      */
    private $category;

     /**
      * @var Observer
      */
    private $observer;

    /**
     * @var EtagHelper
     */
    private $etagHelper;
    
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepositoryInterface;

    /**
     * Setup method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->catalogMvpConfigInterface = $this->getMockBuilder(CatalogMvpConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isB2371268ToggleEnabled'])
            ->getMockForAbstractClass();

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent', 'getCategory'])
            ->getMock();

        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'setEtag', 'getId','getParentId','setData','save'])
            ->getMock();

        $this->etagHelper = $this->getMockBuilder(EtagHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateEtag'])
            ->getMock();

        $this->categoryRepositoryInterface = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->categorySaveAfterEtagUpdateObserver = $objectManagerHelper->getObject(
            CategorySaveAfterEtagUpdateObserver::class,
            [
                'catalogMvpConfigInterface' => $this->catalogMvpConfigInterface,
                'etagHelper' => $this->etagHelper,
                'categoryRepository' => $this->categoryRepositoryInterface
            ]
        );
    }

    public function testExecuteWithFeatureToggleDisabled()
    {
        $this->observer->expects($this->any())
            ->method('getEvent')
            ->willReturnSelf();
        
        $this->observer->expects($this->any())
            ->method('getCategory')
            ->willReturn($this->category);

        $this->catalogMvpConfigInterface->expects($this->any())
            ->method('isB2371268ToggleEnabled')
            ->willReturn(false);

        $this->categorySaveAfterEtagUpdateObserver->execute($this->observer);
    }

    public function testExecuteWithETagGeneration()
    {

        $categoryId = 123;
        $categoryName = 'Test Category';
        $expectedEtag = substr(hash('sha256', $categoryName . $categoryId . time()), 0, 32);
        $parentCategoryId = 456;

        $this->observer->expects($this->any())
            ->method('getEvent')
            ->willReturnSelf();
        
        $this->observer->expects($this->any())
            ->method('getCategory')
            ->willReturn($this->category);

        $this->catalogMvpConfigInterface->expects($this->any())
            ->method('isB2371268ToggleEnabled')
            ->willReturn(true);

        $this->category->expects($this->any())
            ->method('getId')
            ->willReturn($categoryId);
        
        $this->category->expects($this->any())
            ->method('getData')
            ->with('name')
            ->willReturn($categoryName);

        $this->category->expects($this->any())
            ->method('getData')
            ->with('parent_id')
            ->willReturn($parentCategoryId);

        $this->etagHelper->expects($this->any())
            ->method('generateEtag')
            ->with($this->category)
            ->willReturn($expectedEtag);

        $this->category->expects($this->any())
            ->method('setEtag')
            ->with($this->equalTo($expectedEtag));
            
        $this->categorySaveAfterEtagUpdateObserver->execute($this->observer);
    }

    public function testExecuteWithParentCategoryEtagUpdate()
    {
        $categoryId = 123;
        $categoryName = 'Test Category';
        $parentCategoryId = 456;
        
        $this->observer->expects($this->any())
            ->method('getEvent')
            ->willReturnSelf();
            
        $this->observer->expects($this->any())
            ->method('getCategory')
            ->willReturn($this->category);
    
        $this->catalogMvpConfigInterface->expects($this->any())
            ->method('isB2371268ToggleEnabled')
            ->willReturn(true);
    
        $this->category->expects($this->any())
            ->method('getId')
            ->willReturn($categoryId);
    
        $this->category->expects($this->any())
            ->method('getData')
            ->with('name')
            ->willReturn($categoryName);
    
        $this->category->expects($this->any())
            ->method('getParentId')
            ->willReturn($parentCategoryId);
    
        $this->category->expects($this->any())
            ->method('getId')
            ->willReturn($parentCategoryId);
    
        $this->category->expects($this->any())
            ->method('getData')
            ->with('name')
            ->willReturn('Parent Category');
        
        $this->categoryRepositoryInterface->expects($this->any())
            ->method('get')
            ->with($parentCategoryId)
            ->willReturn($this->category);
    
        $this->etagHelper->expects($this->any())
            ->method('generateEtag')
            ->with($this->category)
            ->willReturn('test');
    
        $this->etagHelper->expects($this->any())
            ->method('generateEtag')
            ->with($this->category)
            ->willReturn('test');
    
        $this->category->expects($this->any())
            ->method('setData')
            ->with('etag', 'test');
    
        $this->category->expects($this->any())
            ->method('save');
    
        $this->categorySaveAfterEtagUpdateObserver->execute($this->observer);
    }
}
