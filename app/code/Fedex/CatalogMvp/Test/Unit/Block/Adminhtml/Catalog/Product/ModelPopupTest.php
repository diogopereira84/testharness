<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Block\Adminhtml\Catalog\Product;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Block\Adminhtml\Catalog\Product\ModelPopup;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Helper\Image;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ModelPopupTest extends TestCase
{
    /**
     * @var (\Magento\Backend\Block\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $productCollectionFactory;
    protected $catalogMvpHelper;
    protected $productCollection;
    protected $categoryCollectionFactory;
    protected $categorycollection;
    protected $categorymodel;
    protected $productmodel;
    protected $productImage;
    protected $categoryInterface;
    protected $categoryMock;
    protected $requestMock;
    protected $scopeConfig;
    protected $categoryFactoryMock;
    protected $modelPopup;
    public const B2B_ROOT_CATEGORY = 'B2B Root Category';
    protected  $toggleConfigMock;
    protected $categoryRepository;

    /**
     * @return void
     */
    public function categoryCollectionMocks(): void
    {
        $this->categoryCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->categorycollection);
        $this->categorycollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->categorycollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->categorycollection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categorymodel]));
        $this->categorymodel->expects($this->any())
            ->method('getId')
            ->willReturn('123');
        $this->categoryRepository->expects($this->any())
            ->method('get')
            ->withConsecutive(['123', ''])
            ->willReturn($this->categoryInterface, $this->categoryMock);
        $this->categoryInterface->expects($this->any())
            ->method('getChildrenCategories')
            ->willReturn($this->categorycollection);
    }

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->productCollectionFactory = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->catalogMvpHelper = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFxoMenuId','getRootCategoryDetailFromStore','getCatalogPendingReviewStatus', 'getAttrSetIdByName'])
            ->getMock();
        $this->productCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToSelect','addCategoriesFilter', 'addFieldToFilter'])
            ->getMock();
        $this->categoryCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->categorycollection = $this->getMockBuilder(CategoryCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToSelect','addFieldToFilter','getIterator'])
            ->getMock();
        $this->categorymodel = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
            $this->productmodel = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName'])
            ->getMock();
        $this->categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $this->productImage = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->categoryInterface = $this
            ->getMockBuilder(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getChildrenCategories'])
            ->getMockForAbstractClass();
        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllChildren'])
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParams'])
            ->getMockForAbstractClass();
        $this->scopeConfig = $this
            ->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();
        $this->categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load'])
            ->getMock();
        $this->toggleConfigMock= $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->modelPopup = $objectManagerHelper->getObject(
            ModelPopup::class,
                [
                    'context' => $this->context,
                    'categoryCollectionFactory' => $this->categoryCollectionFactory,
                    'categoryRepository' => $this->categoryRepository,
                    'productCollectionFactory' => $this->productCollectionFactory,
                    'categoryFactory' => $this->categoryFactoryMock,
                    'productImage' => $this->productImage,
                    'scopeConfig' => $this->scopeConfig,
                    'catalogMvpHelper' => $this->catalogMvpHelper,
                    '_request' => $this->requestMock,
                    'toggleConfig'=>$this->toggleConfigMock
                ]
            );
    }


    public function testGetCategoryCollection()
    {
        $this->categoryCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->categorycollection);
        $this->categorycollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->categorycollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->categorycollection->expects($this->any())
        ->method('getIterator') ->willReturn(new \ArrayIterator([$this->categorymodel]));
        $this->categorymodel->expects($this->any())->method('getId')->willReturn('123');
        $this->categoryRepository->expects($this->any())
        ->method('get')->with('123')->willReturn($this->categoryInterface);
        $this->modelPopup->getCategoryCollection();
    }

    public function testGetProductCollectionByCategories()
    {


        $this->categoryCollectionMocks();
        $this->productmodel->expects($this->any())->method('getName')->willReturn('Print Products');
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->categoryFactoryMock->expects($this->any())->method('load')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getAllChildren')->willReturn('12','15');
        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);
        $printproid= '';
        $this->productCollection->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturn($this->productCollection);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue') ->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())
            ->method('getAttrSetIdByName')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->productCollection);
        // $this->categoryRepository->expects($this->any())->method('get')->with('')->willReturn($this->categoryInterface);
        $this->modelPopup->getProductCollectionByCategories();
    }


    public function testGetProductImage()
    {
        $this->assertEquals($this->productImage, $this->modelPopup->getProductImage());
    }

    public function testGetConfiguratorUrl()
    {
        $configuratorUrl = "https://wwwtest.fedex.com/apps/ondemand";
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn($configuratorUrl);
        $this->assertEquals($configuratorUrl, $this->modelPopup->getConfiguratorUrl());
    }
    /**
     * Test getFxoMenuId
     */
    public function testGetFxoMenuId()
    {
        $fxoMenuId = "1582146604697-4";
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn(['id'=>'234']);
        $this->catalogMvpHelper->expects($this->any())
            ->method('getFxoMenuId')
            ->willReturn( $fxoMenuId );
        $this->assertEquals($fxoMenuId, $this->modelPopup->getFxoMenuId());
    }

    /**
     * Test getCatalogPendingReviewStatus
     */
    public function testGetCatalogPendingReviewStatus()
    {
        $pendingReviewStatus = 2;
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn(['id'=>'234']);
        $this->catalogMvpHelper->expects($this->any())
            ->method('getCatalogPendingReviewStatus')
            ->willReturn( $pendingReviewStatus );
        $this->assertEquals($pendingReviewStatus, $this->modelPopup->getCatalogPendingReviewStatus());
    }

    /**
     * Test getCatalogPendingReviewStatus
     */
    public function testGetCatalogPendingReviewStatusWithZero()
    {
        $pendingReviewStatus = 0;
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn(['id'=>'']);
        $this->catalogMvpHelper->expects($this->any())
            ->method('getCatalogPendingReviewStatus')
            ->willReturn( $pendingReviewStatus );
        $this->assertEquals($pendingReviewStatus, $this->modelPopup->getCatalogPendingReviewStatus());
    }

    /**
     * Test getConfigurationValue
     *
     * @return void
     */
    public function testGetConfigurationValue()
    {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn(true);

        $this->assertEquals(true, $this->modelPopup->getConfigurationValue('test'));
    }

    public function testGetProductCollectionByCategoriesWithToggleEnabled()
    {
        // Category collection mocks
        $this->categoryCollectionMocks();
        $this->categoryFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->categoryFactoryMock->expects($this->any())
            ->method('load')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())
            ->method('getAllChildren')
            ->willReturn('12', '15');

        // Product collection mocks
        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturn($this->productCollection);

        // Toggle config mock value
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(\Fedex\CatalogMvp\Block\Adminhtml\Catalog\Product\ModelPopup::TIGER_D_233890)
            ->willReturn(true);

        // Expect filter for mirakl_mcm_product_id
        $this->productCollection->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->withConsecutive(
                [
                    'mirakl_mcm_product_id',
                    ['or' => [
                        ['null' => true],
                        ['eq' => '']
                    ]]
                ]
            )
            ->willReturn($this->productCollection);

        $this->modelPopup->getProductCollectionByCategories();
    }

    public function testGetProductCollectionByCategoriesWithToggleDisabled()
    {
        // Category collection mocks
        $this->categoryCollectionMocks();
        $this->categoryFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->categoryFactoryMock->expects($this->any())
            ->method('load')
            ->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())
            ->method('getAllChildren')
            ->willReturn('12', '15');

        // Product collection mocks
        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturn($this->productCollection);

        // Toggle config mock
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(\Fedex\CatalogMvp\Block\Adminhtml\Catalog\Product\ModelPopup::TIGER_D_233890)
            ->willReturn(false);

        // Ensure filter for mirakl_mcm_product_id is never called
        $this->productCollection->expects($this->never())
            ->method('addFieldToFilter')
            ->with(
                'mirakl_mcm_product_id',
                $this->anything()
            );

        $this->modelPopup->getProductCollectionByCategories();
    }
}
