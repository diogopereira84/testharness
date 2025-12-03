<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PageBuilderPromoBanner\Test\Unit\ViewModel;

use Fedex\PageBuilderPromoBanner\ViewModel\WidgetData;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Widget\Model\Widget\Instance;
use Magento\Cms\Model\BlockFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Cms\Model\Page;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\Delivery\Helper\Data;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class WidgetDataTest extends TestCase
{
    protected $catalogMvpHelper;
    /**
     * @var Instance
     */
    protected $widgetInstance;

    /**
     * @var BlockFactory
     */
    protected $blockFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var FilterProvider
     */
    protected $filterProvider;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Page
     */
    protected $cmsPage;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var WidgetData
     */
    protected $promoBannerWidgetData;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->widgetInstance = $this->getMockBuilder(Instance::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(['load'])
                                    ->getMock();

        $this->blockFactory = $this->getMockBuilder(blockFactory::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['create', 'setStoreId', 'load', 'getContent'])
                                ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getStore', 'getId'])
                                ->getMockForAbstractClass();

        $this->filterProvider = $this->getMockBuilder(FilterProvider::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getBlockFilter', 'setStoreId', 'filter'])
                                ->getMock();

        $this->request = $this->getMockBuilder(Http::class)
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getShowPromoBanner', 'registry'])
                                ->getMock();

        $this->cmsPage = $this->getMockBuilder(Page::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods(['getShowPromoBanner'])
                                        ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods(['critical'])
                                        ->getMockForAbstractClass();

        $this->helper = $this->getMockBuilder(Data::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods(['isCommercialCustomer'])
                                        ->getMock();
        $this->catalogMvpHelper = $this->getMockBuilder(CatalogMvp::class)
        ->disableOriginalConstructor()
        ->setMethods(['getCurrentCategory','getShowPromoBanner','getFilteredCategoryItem', 'getAttrSetIdByName', 'checkPrintCategory'])
        ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->promoBannerWidgetData = $this->objectManager->getObject(
            WidgetData::class,
            [
                'widgetInstance' => $this->widgetInstance,
                'blockFactory' => $this->blockFactory,
                'storeManager' => $this->storeManager,
                'filterProvider' => $this->filterProvider,
                'request' => $this->request,
                'cmsPage' => $this->cmsPage,
                'logger' => $this->logger,
                'catalogMvpHelper' => $this->catalogMvpHelper
            ]
        );
    }

    /**
     * Test Get Widget By Id
     *
     * @return void
     */
    public function testGetWidgetById()
    {
        $this->widgetInstance->expects($this->any())->method('load')->willReturnSelf();
        $this->assertNotNull($this->promoBannerWidgetData->getWidgetById('1'));
    }

    /**
     * Test Get Widget By Id With Exception
     *
     * @return void
     */
    public function testGetWidgetByIdWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->widgetInstance->expects($this->any())->method('load')->willThrowException($exception);
        $this->assertNull($this->promoBannerWidgetData->getWidgetById('1'));
    }

    /**
     * Test Get Block
     *
     * @return void
     */
    public function testgetBlock()
    {
        $this->request->expects($this->any())->method('getFullActionName')->willReturn('catalog_category_view');
        $this->catalogMvpHelper->expects($this->any())->method('getCurrentCategory')->willReturnSelf();
        $this->catalogMvpHelper->expects($this->any())->method('getShowPromoBanner')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('load')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('getContent')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('getBlockFilter')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('filter')->willReturnSelf();
        $this->assertNotNull($this->promoBannerWidgetData->getBlock('1'));
    }

    /**
     * Test Get With Cms
     *
     * @return void
     */
    public function testgetBlockWithCms()
    {
        $this->request->expects($this->any())->method('getFullActionName')->willReturn('cms_page_view');
        $this->cmsPage->expects($this->any())->method('getShowPromoBanner')->willReturn(false);
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('load')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('getContent')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('getBlockFilter')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('filter')->willReturnSelf();
        $this->assertNull($this->promoBannerWidgetData->getBlock('1'));
    }

    /**
     * Test Get With Configurator
     *
     * @return void
     */
    public function testgetBlockWithConfigurator()
    {
        $this->request->expects($this->any())->method('getFullActionName')->willReturn('configurator_index_index');
        $this->cmsPage->expects($this->any())->method('getShowPromoBanner')->willReturn(false);
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('load')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('getContent')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('getBlockFilter')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('filter')->willReturnSelf();
        $this->assertNull($this->promoBannerWidgetData->getBlock('1'));
    }

    /**
     * Test Get With Checkout
     *
     * @return void
     */
    public function testgetBlockWithCheckout()
    {
        $this->request->expects($this->any())->method('getFullActionName')->willReturn('checkout_index_index');
        $this->cmsPage->expects($this->any())->method('getShowPromoBanner')->willReturn(false);
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('load')->willReturnSelf();
        $this->blockFactory->expects($this->any())->method('getContent')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('getBlockFilter')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->filterProvider->expects($this->any())->method('filter')->willReturnSelf();
        $this->assertNull($this->promoBannerWidgetData->getBlock('1'));
    }

    /**
     * Test Get Block With Exception
     *
     * @return void
     */
    public function testgetBlockWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->storeManager->expects($this->any())->method('getStore')->willThrowException($exception);
        $this->assertNull($this->promoBannerWidgetData->getBlock('1'));
    }
}
