<?php

namespace Fedex\Ondemand\Test\Unit\Observer\Frontend;

use Fedex\LiveSearch\Api\Data\SharedCatalogSkipInterface;
use Fedex\Ondemand\Api\Data\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Ondemand\Observer\Frontend\BlockBefore;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Category;
use Magento\Framework\Event;
use Magento\Framework\View\Layout;
use Magento\Theme\Block\Html\Title as HtmlTitle;
use Magento\Theme\Block\Html\Breadcrumbs;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Model\Config as OndemandConfig;

class BlockBeforeTest extends TestCase
{
    protected $pageConfig;
    protected $storeManager;
    protected $store;
    /**
     * @var (\Fedex\LiveSearch\Api\Data\SharedCatalogSkipInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $sharedCatalogSkip;
    protected $ondemandConfig;
    protected $title;
    protected $breadcrumbs;
    protected $htmlTitle;
    protected $event;
    protected $layout;
    protected $registry;
    protected $observer;
    protected $category;
    protected $catalogMvpMock;
    protected $toggleConfigMock;
    protected $ondemandConfigMock;
    protected $blockBefore;
    protected function setUp(): void
    {
        $this->pageConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTitle'])
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode'])
            ->getMock();

        $this->sharedCatalogSkip = $this->getMockBuilder(SharedCatalogSkipInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLivesearchProductListingEnable'])
            ->getMockForAbstractClass();

        $this->ondemandConfig = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getB2bPrintProductsCategory'])
            ->getMockForAbstractClass();

        $this->title = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->setMethods(['setTitle'])
            ->getMock();

        $this->breadcrumbs = $this->getMockBuilder(Breadcrumbs::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCacheKeyInfo','addCrumb'])
            ->getMock();

        $this->htmlTitle = $this->getMockBuilder(HtmlTitle::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPageTitle'])
            ->getMock();

        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLayout'])
            ->getMock();

        $this->layout = $this->getMockBuilder(Layout::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBlock'])
            ->getMock();

		$this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['registry','unregister','register'])
            ->getMock();

        $this->observer = $this->getMockBuilder(Observer::class)
			->disableOriginalConstructor()
			->setMethods(['getFullActionName','getEvent'])
			->getMock();

        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogMvpMock = $this
            ->getMockBuilder(CatalogMvp::class)
            ->setMethods(['getCompanySharedCatId','getCurrentCategory'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getToggleConfigValue'])
            ->getMock();

        $this->ondemandConfigMock = $this->getMockBuilder(OndemandConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getHomepageTabNameValue',
                    'getOndemandHomepageTabNameValue',
                    'getBrowsePrintProductsTabNameValue',
                    'getFedexSharedCatalogTabNameValue',
                    'getSharedCatalogTabNameValue'
                ]
            )->getMock();
        

        $objectManagerHelper = new ObjectManager($this);
        $this->blockBefore = $objectManagerHelper->getObject(
            BlockBefore::class,
            [
                'pageConfig' => $this->pageConfig,
                'catalogMvpHelper' => $this->catalogMvpMock,
                'storeManager' => $this->storeManager,
                'sharedCatalogSkip' => $this->sharedCatalogSkip,
                'ondemandConfig' => $this->ondemandConfig,
                'toggleConfig' => $this->toggleConfigMock,
                'config' => $this->ondemandConfigMock

            ]
        );
    }
    /**
     * testGetOndemandCompanyDataForEpro
     */
    public function testExecute()
    {
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getCode')->willReturn('ondemand');
		$this->observer->expects($this->any())->method('getFullActionName')->willReturn('catalog_category_view');
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getLayout')->willReturn($this->layout);
        $this->catalogMvpMock->expects($this->any())->method('getCurrentCategory')->willReturn($this->category);
        $this->category->expects($this->any())->method('getId')->willReturn(15);
        $this->category->expects($this->any())->method('getName')->willReturn('SDE Print Products');
        $this->category->expects($this->any())->method('setName')->willReturn('Print Products');
        $this->registry->expects($this->any())->method('registry')
            ->with('current_category')->willReturnSelf();
        $this->registry->expects($this->any())->method('register')
            ->with('current_category')->willReturnSelf();
        $this->catalogMvpMock->expects($this->any())
            ->method('getCompanySharedCatId')
            ->willReturn(440);
        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->title);
        $this->ondemandConfig->expects($this->any())->method('getB2bPrintProductsCategory')->willReturn(15);
        $this->layout
            ->method('getBlock')
            ->withConsecutive(
                ['page.main.title'],
                ['page.main.title'],
                ['breadcrumbs']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                $this->htmlTitle,
                $this->breadcrumbs
            );
        $crumbs = [];
        $crumbs['home'] = ['label' => 'Home','link'=>'https://stahimg3.office.fedex.com'];
        $crumbs['category414'] = ['label' => 'SDE Print Products',
            'link'=>'https://stahimg3.office.fedex.com/sde-prrint-products.html'];
        $returncrumbs['crumbs'] = base64_encode(json_encode($crumbs));

        $this->breadcrumbs->expects($this->any())->method('getCacheKeyInfo')->willReturn($returncrumbs);
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->ondemandConfigMock->expects($this->any())
            ->method('getHomepageTabNameValue')
            ->willReturn('Homepage | FedEx Office');
        $this->ondemandConfigMock->expects($this->any())
            ->method('getOndemandHomepageTabNameValue')
            ->willReturn('Ondemand Home Page');
        $this->ondemandConfigMock->expects($this->any())
            ->method('getBrowsePrintProductsTabNameValue')
            ->willReturn('Browse All Print Products | FedEx Office');
        $this->ondemandConfigMock->expects($this->any())
            ->method('getFedexSharedCatalogTabNameValue')
            ->willReturn('Shared Catalog | FedEx Office');
        $this->ondemandConfigMock->expects($this->any())
            ->method('getSharedCatalogTabNameValue')
            ->willReturn('Shared Catalog');

		$this->assertInstanceOf(BlockBefore::class, $this->blockBefore->execute($this->observer));
    }

    /**
     * testGetOndemandCompanyDataForEproWithoutId
     */
    public function testExecuteWithoutId()
    {
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getCode')->willReturn('ondemand');
		$this->observer->expects($this->any())->method('getFullActionName')->willReturn('catalog_category_view');
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getLayout')->willReturn($this->layout);

        $this->catalogMvpMock->expects($this->any())->method('getCurrentCategory')->willReturn($this->category);     
        $this->category->expects($this->any())->method('getId')->willReturn(15);
        $this->category->expects($this->any())->method('getName')->willReturn('SDE Print Products');
        $this->category->expects($this->any())->method('setName')->willReturn('Print Products');
        $this->registry->expects($this->any())->method('registry')
            ->with('current_category')->willReturnSelf();
        $this->registry->expects($this->any())->method('register')
            ->with('current_category')->willReturnSelf();
        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->title);
        $this->ondemandConfig->expects($this->any())->method('getB2bPrintProductsCategory')->willReturn(15);
        $this->layout
            ->method('getBlock')
            ->withConsecutive(
                ['page.main.title'],
                ['page.main.title'],
                ['breadcrumbs']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                $this->htmlTitle,
                $this->breadcrumbs
            );
        $this->catalogMvpMock->expects($this->any())
            ->method('getCompanySharedCatId')
            ->willReturn(0);
        $crumbs = [];
        $crumbs['home'] = ['label' => 'Home','link'=>'https://stahimg3.office.fedex.com'];
        $crumbs['category414'] = ['label' => 'SDE Print Products',
            'link'=>'https://stahimg3.office.fedex.com/sde-prrint-products.html'];
        $returncrumbs['crumbs'] = base64_encode(json_encode($crumbs));

        $this->breadcrumbs->expects($this->any())->method('getCacheKeyInfo')->willReturn($returncrumbs);

		$this->assertInstanceOf(BlockBefore::class, $this->blockBefore->execute($this->observer));
    }

    public function testExecuteCmsPage()
    {
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->store);
        $this->store->expects($this->any())->method('getCode')->willReturn('ondemand');
        $this->observer->expects($this->any())->method('getFullActionName')->willReturn('cms_index_index');
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getLayout')->willReturn($this->layout);
        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->title);
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertInstanceOf(BlockBefore::class, $this->blockBefore->execute($this->observer));
    }
}
