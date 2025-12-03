<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CusomizedMegamenu\Test\Unit\Block\Html;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\Data\Tree;
use Magento\Framework\Data\TreeFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Fedex\CustomizedMegamenu\Block\Html\Topmenu;
use Fedex\CatalogDocumentUserSettings\Helper\Data as HelperData;
use Magedelight\Megamenu\Helper\Data;
use Magento\Customer\Model\SessionFactory;
use Magento\Cms\Model\Page;
use Magedelight\Megamenu\Model\MegamenuManagement;
use Magento\Catalog\Helper\Output;
use Magento\Catalog\Model\Category;
use Magento\Framework\UrlInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Manager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Tree\Node\Collection;
use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magedelight\Megamenu\Api\MegamenuInterface;
use Magedelight\Megamenu\Api\Data\ConfigInterface;
use Magedelight\Megamenu\Model\MenuItems;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Ondemand\Helper\Ondemand;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Base\Helper\Auth;
use Fedex\CustomizedMegamenu\Model\MenuTreeManagement;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TopmenuTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Event\Manager & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eventManagerMockObj;
    protected $nodeMock;
    protected $treeMock;
    protected $megamenuManagementMock;
    protected $megamenuInterfaceMock;
    protected $configInterfaceMock;
    protected $menuItems;
    protected $orderHistoryDataHelper;
    protected $sdeHelper;
    protected $deliveryHelperMock;
    protected $templateMock;
    protected $ondemandHelper;
    protected $topMenuBlock;
    const MEGA_MENU_TEMPLATE = 'Fedex_CustomizedMegamenu::menu/new-topmenu.phtml';
    const BURGER_MENU_TEMPLATE = 'Magedelight_Megamenu::menu/burger.phtml';
    const XML_PATH_TOP_MEGAMENU_FEATURE_TOGGLE =
    'environment_toggle_configuration/environment_toggle/xmen_megamenu_administration_improvement';

    /**
     * @var AbstractElement|MockObject
     */
    protected $abstractElementMock;

    /**
     * @var BlockInterface|MockObject
     */
    protected $blockInterfaceMock;

    /**
     * @var LayoutInterface|MockObject
     */
    protected $layoutMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var Session|MockObject
     */
    protected $customerSession;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var NodeFactory|MockObject
     */
    protected $nodeFactoryMock;

    /**
     * @var TreeFactory|MockObject
     */
    protected $treeFactoryMock;

    /**
     * @var Topmenu|MockObject
     */
    protected $topMenuMock;

    /**
     * @var SessionFactory|MockObject
     */
    protected $sessionFactoryMock;

    /**
     * @var Page|MockObject
     */
    protected $pageMock;

    /**
     * @var MegamenuManagement|MockObject
     */
    protected $megaMenuManagementMock;

    /**
     * @var Output|MockObject
     */
    protected $outputMock;

    /**
     * @var Category|MockObject
     */
    protected $categoryMock;

    /**
     * @var HelperData|MockObject
     */
    protected $helperDataMock;

    /**
     * @var Data|MockObject
     */
    protected $dataMock;

    /**
     * @var ManagerInterface|MockObject
     */
    private $eventManagerMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var CategoryFactory
     */
    private $categoryFactoryMock;

    /**
     * @var CatalogMvp
     */
    private $catalogMvpMock;

    protected $html;

    protected $customerMock;

    /**
     * @var ToggleConfig $toggleConfigMock
     */
    private $toggleConfigMock;

    protected $categoryCollectionMock;
    protected $productCollectionMock;

    protected Auth|MockObject $baseAuthMock;
    protected $menuTreeManagement;

  // @codingStandardsIgnoreStart

/** @var string  */
protected $htmlWithCategory = <<<HTML
<li  class="level0 nav-1 first"><a href="http://magento2/print-products.html"  class="level-top" ><span>Print Products</span></a></li><li  class="level0 nav-2"><a href="http://magento2/browse-catalog.html"  class="level-top" ><span>Browse Catalog</span></a></li><li  class="level0 nav-3"><a href="http://magento2/category-2.html"  class="level-top" ><span>Category 2</span></a></li>
HTML;

// @codingStandardsIgnoreEnd
    private MockObject|NodeFactory $nodeFactory;

    protected function setUp(): void
    {
        $this->eventManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->eventManagerMockObj = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();

        $this->nodeFactoryMock = $this->getMockBuilder(NodeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->nodeMock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->setMethods(['getChildren', '__call', 'setOutermostClass', 'getOutermostClass'])
            ->getMock();

        $this->treeFactoryMock = $this->getMockBuilder(TreeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->treeMock = $this->getMockBuilder(Tree::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->menuTreeManagement = $this->getMockBuilder(MenuTreeManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperDataMock = $this->getMockBuilder(HelperData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyConfiguration'])
            ->getMock();

        $this->sessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer','isLoggedIn', 'getCustomerId','getOndemandCompanyInfo'])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getData'])
            ->getMock();

        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataMock = $this->getMockBuilder(Data::class)
            ->setMethods(['isEnabled', 'isHumbergerMenu'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->megamenuManagementMock = $this->getMockBuilder(MegamenuManagement::class)
            ->setMethods(['getMenuData', 'getMenu', 'loadMenuItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->megamenuInterfaceMock = $this->getMockBuilder(MegamenuInterface::class)
            ->setMethods(['getMenu'])
            ->getMockForAbstractClass();

        $this->configInterfaceMock = $this->getMockBuilder(ConfigInterface::class)
            ->setMethods(['getMenuId', 'getIsActive', 'getMenuType'])
            ->getMockForAbstractClass();

        $this->menuItems = $this->getMockBuilder(MenuItems::class)
            ->setMethods(['getItemName', 'getItemType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->outputMock = $this->getMockBuilder(Output::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->setMethods(['createBlock'])
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->setMethods(['getLayout'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockInterfaceMock = $this->getMockBuilder(BlockInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData', 'toHtml', 'setTemplate'])
            ->getMockForAbstractClass();

        $this->abstractElementMock = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->orderHistoryDataHelper = $this->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSDEHomepageEnable'])
            ->getMock();

        $this->sdeHelper = $this->getMockBuilder(\Fedex\SDE\Helper\SdeHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsSdeStore'])
            ->getMock();

        $this->categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','load'])
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','getData','getChildrenCount','getLevel','getUrl','getId','getName','getProductCollection'])
            ->getMock();

        $this->categoryCollectionMock = $this->getMockBuilder(CategoryCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter','addAttributeToFilter','addAttributeToSelect','setOrder','getIterator','count'])
            ->getMock();

        $this->productCollectionMock = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['count'])
            ->getMock();



        $this->catalogMvpMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdFromNode'])
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue','getToggleConfig'])
            ->getMock();

        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCommercialCustomer', 'toggleEnableIcons'])
            ->getMock();

        $this->contextMock->expects($this->any())->method('getLayout')->willReturn($this->layoutMock);
        $this->templateMock = $this->createMock(Template::class);

        $this->ondemandHelper = $this->getMockBuilder(Ondemand::class)
            ->disableOriginalConstructor()
            ->setMethods(['isProductAvailable','isPublishCategory'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->topMenuBlock = $objectManager->getObject(
            Topmenu::class,
                [
                    'context' => $this->contextMock,
                    'nodeFactory' => $this->nodeFactoryMock,
                    'treeFactory' => $this->treeFactoryMock,
                    'registry' => $this->registryMock,
                    'page' => $this->pageMock,
                    'helper' => $this->dataMock,
                    'megamenuManagement' => $this->megamenuManagementMock,
                    'output' => $this->outputMock,
                    'data' => [],
                    'helperData' => $this->helperDataMock,
                    'deliveryHelper' => $this->deliveryHelperMock,
                    'categoryFactory' => $this->categoryFactoryMock,
                    'toggleConfig' => $this->toggleConfigMock,
                    '_eventManager' => $this->eventManagerMock,
                    'request' => $this->requestMock,
                    '_menu' => $this->nodeMock,
                    'orderHistoryDataHelper' => $this->orderHistoryDataHelper,
                    'sdeHelper' => $this->sdeHelper,
                    'primaryMenu' => $this->configInterfaceMock,
                    'ondemandHelper' => $this->ondemandHelper,
                    'session' => $this->sessionFactoryMock,
                    'authHelper' => $this->baseAuthMock,
                    'customerSession' => $this->customerSession,
                    'MenuTreeManagement' => $this->menuTreeManagement
                ]
        );
    }

    public function testGetMegaMenuHtml()
    {
        $outermostClass = "level-top";
        $childrenWrapClass = "level0 nav-1 first parent main-parent";
        $limit = 1;
        $html = $this->htmlWithCategory;
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $this->nodeFactoryMock->expects($this->any())->method('create')->willReturn($this->nodeMock);
        $this->nodeMock->expects($this->once())->method('setOutermostClass');

        $this->dataMock->expects($this->any())->method('isEnabled')->willReturn(FALSE);
        $this->megamenuManagementMock->expects($this->any())
            ->method('getMenuData')
            ->willReturn($this->megamenuInterfaceMock);
        $this->megamenuInterfaceMock->expects($this->any())->method('getMenu')->willReturn($this->configInterfaceMock);
        $this->configInterfaceMock->expects($this->any())->method('getMenuId')->willReturn(5);
        $this->configInterfaceMock->expects($this->any())->method('getIsActive')->willReturn(FALSE);
        $this->configInterfaceMock->expects($this->any())->method('getMenuType')->willReturn(2);
        $companyConfigObject = new \Magento\Framework\DataObject(['allow_own_document' => true, 'allow_shared_catalog' => true,'shared_catalog_id'=>'']);
        $this->helperDataMock->expects($this->any())->method('getCompanyConfiguration')->willReturn($companyConfigObject);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfig')->willReturn(15);
        //$this->companyConfigObject->expects($this->any())->method('getData')->willReturn(9);

        $treeNode = $this->buildTree(true);
        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

        $this->eventManagerMock->expects($this->any())
            ->method('dispatch')
            ->willReturnMap([
                [
                    'page_block_html_topmenu_gethtml_before',
                    [
                        'menu' => $treeNode,
                        'block' => $this->topMenuBlock,
                        'request' => $this->requestMock,
                    ],
                    $this->eventManagerMock
                ],
                [
                    'page_block_html_topmenu_gethtml_after',
                    [
                        'menu' => $treeNode,
                        'transportObject' => $transportObject,
                    ],
                    $this->eventManagerMock
                ],
            ]);
        $this->topMenuBlock->getMegaMenuHtml($outermostClass, $childrenWrapClass, $limit);
    }

    public function testGetMegaMenuHtmlWithoutSDE()
    {
        $outermostClass = "level-top";
        $childrenWrapClass = "level0 nav-1 first parent main-parent";
        $limit = 1;
        $html = $this->htmlWithCategory;
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->nodeFactoryMock->expects($this->any())->method('create')->willReturn($this->nodeMock);
        $this->nodeMock->expects($this->any())->method('setOutermostClass');

        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSession);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getData')->willReturn(23);
        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn(['company_type' => "selfreg"]);
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getCollection')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->categoryCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->categoryCollectionMock->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->categoryCollectionMock->expects($this->any())->method('count')->willReturn(2);
        $this->categoryCollectionMock->expects($this->any())
             ->method('getIterator')
             ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())->method('getLevel')->willReturn(3);
        // $this->categoryMock->expects($this->any())->method('getChildrenCount')->willReturn(0);
        // $this->categoryMock->expects($this->any())->method('getChildrenCount')->willReturn(1);

        $this->categoryMock
        ->method('getChildrenCount')
        ->withConsecutive([],[],[])
        ->willReturnOnConsecutiveCalls(
            1,
            1,
            0
        );

        $this->categoryMock->expects($this->any())->method('getId')->willReturn('23');
        $this->categoryMock->expects($this->any())->method('getName')->willReturn('Catalog Items');
        $this->categoryMock->expects($this->any())->method('getUrl')->willReturn('https://staging3.office.fedex.com/');
        $this->categoryMock->expects($this->any())->method('getProductCollection')->willReturn($this->productCollectionMock);
        $this->productCollectionMock->expects($this->any())->method('count')->willReturn(23);


        $this->dataMock->expects($this->any())->method('isEnabled')->willReturn(FALSE);
        $this->megamenuManagementMock->expects($this->any())
            ->method('getMenuData')
            ->willReturn($this->megamenuInterfaceMock);
        $this->megamenuInterfaceMock->expects($this->any())->method('getMenu')->willReturn($this->configInterfaceMock);
        $this->configInterfaceMock->expects($this->any())->method('getMenuId')->willReturn(5);
        $this->configInterfaceMock->expects($this->any())->method('getIsActive')->willReturn(FALSE);
        $this->configInterfaceMock->expects($this->any())->method('getMenuType')->willReturn(2);
        $companyConfigObject = new \Magento\Framework\DataObject(['allow_own_document' => true, 'allow_shared_catalog' => true,'shared_catalog_id'=>'']);
        $this->helperDataMock->expects($this->any())->method('getCompanyConfiguration')->willReturn($companyConfigObject);
        $this->deliveryHelperMock ->expects($this->any())->method('toggleEnableIcons')->willReturn(false);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfig')->willReturn(15);
        $this->catalogMvpMock->expects($this->any())->method('getIdFromNode')->willReturn('category-node-3456');
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(0);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->megamenuManagementMock->expects($this->any())->method('loadMenuItems')->with(0, 'ASC')->willReturn( [$this->menuItems]);
        //$this->companyConfigObject->expects($this->any())->method('getData')->willReturn(9);

        $treeNode = $this->buildTree(true);
        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

        $this->eventManagerMock->expects($this->any())
            ->method('dispatch')
            ->willReturnMap([
                [
                    'page_block_html_topmenu_gethtml_before',
                    [
                        'menu' => $treeNode,
                        'block' => $this->topMenuBlock,
                        'request' => $this->requestMock,
                    ],
                    $this->eventManagerMock
                ],
                [
                    'page_block_html_topmenu_gethtml_after',
                    [
                        'menu' => $treeNode,
                        'transportObject' => $transportObject,
                    ],
                    $this->eventManagerMock
                ],
            ]);
        $this->topMenuBlock->getMegaMenuHtml($outermostClass, $childrenWrapClass, $limit);
    }
    /**
     * Test _getHtml for HomePage Changes for SDE
     * B-1145888
     */
    public function testGetMegaMenuHtmlForSDEUploadOnly()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $outermostClass = "level-top";
        $childrenWrapClass = "level0 nav-1 first parent main-parent";
        $limit = 1;
        $html = $this->htmlWithCategory;

        $this->nodeFactoryMock->expects($this->any())->method('create')->willReturn($this->nodeMock);
        $this->nodeMock->expects($this->any())->method('setOutermostClass');


        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSession);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getData')->willReturn(23);
        $this->customerSession->expects($this->any())->method('getOndemandCompanyInfo')->willReturn(['company_type' => "selfreg"]);
        $this->categoryFactoryMock->expects($this->any())->method('create')->willReturn($this->categoryMock);
        $this->categoryMock->expects($this->any())->method('getCollection')->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->categoryCollectionMock->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->categoryCollectionMock->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->categoryCollectionMock->expects($this->any())->method('count')->willReturn(2);
        $this->categoryCollectionMock->expects($this->any())
             ->method('getIterator')
             ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())->method('getLevel')->willReturn(3);
        // $this->categoryMock->expects($this->any())->method('getChildrenCount')->willReturn(0);
        // $this->categoryMock->expects($this->any())->method('getChildrenCount')->willReturn(1);

        $this->categoryMock
        ->method('getChildrenCount')
        ->withConsecutive([],[],[])
        ->willReturnOnConsecutiveCalls(
            1,
            1,
            0
        );

        $this->categoryMock->expects($this->any())->method('getId')->willReturn('23');
        $this->categoryMock->expects($this->any())->method('getName')->willReturn('Catalog Items');
        $this->categoryMock->expects($this->any())->method('getUrl')->willReturn('https://staging3.office.fedex.com/');
        $this->categoryMock->expects($this->any())->method('getProductCollection')->willReturn($this->productCollectionMock);
        $this->productCollectionMock->expects($this->any())->method('count')->willReturn(23);

        $this->dataMock->expects($this->any())->method('isEnabled')->willReturn(FALSE);
        $this->megamenuManagementMock->expects($this->any())->method('getMenuData')->willReturn($this->megamenuInterfaceMock);
        $this->megamenuInterfaceMock->expects($this->any())->method('getMenu')->willReturn($this->configInterfaceMock);
        $this->configInterfaceMock->expects($this->any())->method('getMenuId')->willReturn(5);
        $this->configInterfaceMock->expects($this->any())->method('getIsActive')->willReturn(FALSE);
        $this->configInterfaceMock->expects($this->any())->method('getMenuType')->willReturn(2);
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(1);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(1);
        $this->menuItems->expects($this->any())->method('getItemName')->willReturn('Print Products');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->megamenuManagementMock->expects($this->any())->method('loadMenuItems')->with(0, 'ASC')->willReturn( [$this->menuItems]);

        $treeNode = $this->buildTree1(true);
        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

        $this->eventManagerMock->expects($this->any())
            ->method('dispatch')
            ->willReturnMap([
                [
                    'page_block_html_topmenu_gethtml_before',
                    [
                        'menu' => $treeNode,
                        'block' => $this->topMenuBlock,
                        'request' => $this->requestMock,
                    ],
                    $this->eventManagerMock
                ],
                [
                    'page_block_html_topmenu_gethtml_after',
                    [
                        'menu' => $treeNode,
                        'transportObject' => $transportObject,
                    ],
                    $this->eventManagerMock
                ],
            ]);
        $this->topMenuBlock->getMegaMenuHtml($outermostClass, $childrenWrapClass, $limit);
    }

    /**
     * Test _getHtml for HomePage Changes for SDE
     * B-1145888
     */
    public function testGetMegaMenuHtmlForCproMenuToggleOff()
    {
        $outermostClass = "level-top";
        $childrenWrapClass = "level0 nav-1 first parent main-parent";
        $limit = 1;
        $html = $this->htmlWithCategory;

        $this->nodeFactoryMock->expects($this->any())->method('create')->willReturn($this->nodeMock);
        $this->nodeMock->expects($this->once())->method('setOutermostClass');

        $this->dataMock->expects($this->any())->method('isEnabled')->willReturn(FALSE);
        $this->megamenuManagementMock->expects($this->any())->method('getMenuData')->willReturn($this->megamenuInterfaceMock);
        $this->megamenuInterfaceMock->expects($this->any())->method('getMenu')->willReturn($this->configInterfaceMock);
        $this->configInterfaceMock->expects($this->any())->method('getMenuId')->willReturn(5);
        $this->configInterfaceMock->expects($this->any())->method('getIsActive')->willReturn(FALSE);
        $this->configInterfaceMock->expects($this->any())->method('getMenuType')->willReturn(2);
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(1);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(1);
        $this->menuItems->expects($this->any())->method('getItemName')->willReturn('Print Products');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->megamenuManagementMock->expects($this->any())->method('loadMenuItems')->with(0, 'ASC')->willReturn( [$this->menuItems]);

        $treeNode = $this->buildTree1(true);
        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

        $this->eventManagerMock->expects($this->any())
            ->method('dispatch')
            ->willReturnMap([
                [
                    'page_block_html_topmenu_gethtml_before',
                    [
                        'menu' => $treeNode,
                        'block' => $this->topMenuBlock,
                        'request' => $this->requestMock,
                    ],
                    $this->eventManagerMock
                ],
                [
                    'page_block_html_topmenu_gethtml_after',
                    [
                        'menu' => $treeNode,
                        'transportObject' => $transportObject,
                    ],
                    $this->eventManagerMock
                ],
            ]);

        $this->topMenuBlock->getMegaMenuHtml($outermostClass, $childrenWrapClass, $limit);
    }


    public function  testGetMegaMenuHtmlForSDECatalogOnly()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $outermostClass = "level-top";
        $childrenWrapClass = "level0 nav-1 first parent main-parent";
        $limit = 1;
        $html = $this->htmlWithCategory;

        $this->nodeFactoryMock->expects($this->any())->method('create')->willReturn($this->nodeMock);
        $this->nodeMock->expects($this->once())->method('setOutermostClass');

        $this->dataMock->expects($this->any())->method('isEnabled')->willReturn(FALSE);
        $this->megamenuManagementMock->expects($this->any())->method('getMenuData')->willReturn($this->megamenuInterfaceMock);
        $this->megamenuInterfaceMock->expects($this->any())->method('getMenu')->willReturn($this->configInterfaceMock);
        $this->configInterfaceMock->expects($this->any())->method('getMenuId')->willReturn(5);
        $this->configInterfaceMock->expects($this->any())->method('getIsActive')->willReturn(FALSE);
        $this->configInterfaceMock->expects($this->any())->method('getMenuType')->willReturn(2);
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(1);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(1);
        $this->menuItems->expects($this->any())->method('getItemName')->willReturn('Browse Catalog');
        $this->menuItems->expects($this->any())->method('getItemType')->willReturn('megamenuwithout');
        $this->megamenuManagementMock->expects($this->any())->method('loadMenuItems')->with(0, 'ASC')->willReturn( [$this->menuItems]);

        $megaMenuItemData = [
            'menu_block' => $this->topMenuBlock,
            'menu_item' => $this->menuItems,
            'menu_management' => $this->megamenuManagementMock
        ];

        $this->layoutMock->expects($this->any())->method('createBlock')->with('Magento\Framework\View\Element\Template')->willReturn($this->blockInterfaceMock);

        $treeNode = $this->buildTree1(true);
        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

        $this->eventManagerMock->expects($this->any())
            ->method('dispatch')
            ->willReturnMap([
                [
                    'page_block_html_topmenu_gethtml_before',
                    [
                        'menu' => $treeNode,
                        'block' => $this->topMenuBlock,
                        'request' => $this->requestMock,
                    ],
                    $this->eventManagerMock
                ],
                [
                    'page_block_html_topmenu_gethtml_after',
                    [
                        'menu' => $treeNode,
                        'transportObject' => $transportObject,
                    ],
                    $this->eventManagerMock
                ],
            ]);

        $this->topMenuBlock->getMegaMenuHtml($outermostClass, $childrenWrapClass, $limit);
    }


    public function testGetMegaMenuHtmlWithActiveMenu()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $outermostClass = "level-top";
        $childrenWrapClass = "level0 nav-1 first parent main-parent";
        $limit = 1;
        $html = $this->htmlWithCategory;

        $this->nodeFactoryMock->expects($this->any())->method('create')->willReturn($this->nodeMock);
        $this->nodeMock->expects($this->once())->method('setOutermostClass');
        $this->dataMock->expects($this->any())->method('isEnabled')->willReturn(TRUE);
        $this->megamenuManagementMock->expects($this->any())->method('getMenuData')->willReturn($this->megamenuInterfaceMock);
        $this->megamenuInterfaceMock->expects($this->any())->method('getMenu')->willReturn($this->configInterfaceMock);
        $this->configInterfaceMock->expects($this->any())->method('getMenuId')->willReturn(5);
        $this->configInterfaceMock->expects($this->any())->method('getIsActive')->willReturn(TRUE);
        $this->configInterfaceMock->expects($this->any())->method('getMenuType')->willReturn(2);
        $this->menuItems->expects($this->any())->method('getItemName')->willReturn('Print Products');
        $this->megamenuManagementMock->expects($this->any())->method('loadMenuItems')->with(0, 'ASC')->willReturn( [$this->menuItems]);

        $treeNode = $this->buildTree1(true);
        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

        $this->eventManagerMock->expects($this->any())
            ->method('dispatch')
            ->willReturnMap([
                [
                    'page_block_html_topmenu_gethtml_before',
                    [
                        'menu' => $treeNode,
                        'block' => $this->topMenuBlock,
                        'request' => $this->requestMock,
                    ],
                    $this->eventManagerMock
                ],
                [
                    'page_block_html_topmenu_gethtml_after',
                    [
                        'menu' => $treeNode,
                        'transportObject' => $transportObject,
                    ],
                    $this->eventManagerMock
                ],
            ]);

        $this->topMenuBlock->getMegaMenuHtml($outermostClass, $childrenWrapClass, $limit);
    }

    public function testGetMegaMenuHtmlWithActiveMenu1()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $outermostClass = "level-top";
        $childrenWrapClass = "level0 nav-1 first parent main-parent";
        $limit = 1;
        $html = $this->htmlWithCategory;

        $this->nodeFactoryMock->expects($this->any())->method('create')->willReturn($this->nodeMock);
        $this->nodeMock->expects($this->once())->method('setOutermostClass');
        $this->dataMock->expects($this->any())->method('isEnabled')->willReturn(TRUE);
        $this->megamenuManagementMock->expects($this->any())->method('getMenuData')->willReturn($this->megamenuInterfaceMock);
        $this->megamenuInterfaceMock->expects($this->any())->method('getMenu')->willReturn($this->configInterfaceMock);
        $this->configInterfaceMock->expects($this->any())->method('getMenuId')->willReturn(5);
        $this->configInterfaceMock->expects($this->any())->method('getIsActive')->willReturn(TRUE);
        $this->configInterfaceMock->expects($this->any())->method('getMenuType')->willReturn(2);
        $this->menuItems->expects($this->any())->method('getItemName')->willReturn('Dummy Product');
        $this->menuItems->expects($this->any())->method('getItemType')->willReturn('megamenu');
        $this->megamenuManagementMock->expects($this->any())->method('loadMenuItems')->with(0, 'ASC')->willReturn( [$this->menuItems]);

        $megaMenuItemData = [
            'menu_block' => $this->topMenuBlock,
            'menu_item' => $this->menuItems,
            'menu_management' => $this->megamenuManagementMock
        ];

        $this->layoutMock->expects($this->any())->method('createBlock')->with('Magento\Framework\View\Element\Template')->willReturn($this->blockInterfaceMock);

        $treeNode = $this->buildTree(true);
        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

        $this->eventManagerMock->expects($this->any())
            ->method('dispatch')
            ->willReturnMap([
                [
                    'page_block_html_topmenu_gethtml_before',
                    [
                        'menu' => $treeNode,
                        'block' => $this->topMenuBlock,
                        'request' => $this->requestMock,
                    ],
                    $this->eventManagerMock
                ],
                [
                    'page_block_html_topmenu_gethtml_after',
                    [
                        'menu' => $treeNode,
                        'transportObject' => $transportObject,
                    ],
                    $this->eventManagerMock
                ],
            ]);

        $this->topMenuBlock->getMegaMenuHtml($outermostClass, $childrenWrapClass, $limit);
    }

    public function testGetMegaMenuHtmlWithActiveMenu2()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $outermostClass = "level-top";
        $childrenWrapClass = "level0 nav-1 first parent main-parent";
        $limit = 1;
        $html = $this->htmlWithCategory;

        $this->nodeFactoryMock->expects($this->any())->method('create')->willReturn($this->nodeMock);
        $this->nodeMock->expects($this->once())->method('setOutermostClass');
        $this->dataMock->expects($this->any())->method('isEnabled')->willReturn(TRUE);
        $this->megamenuManagementMock->expects($this->any())->method('getMenuData')->willReturn($this->megamenuInterfaceMock);
        $this->megamenuInterfaceMock->expects($this->any())->method('getMenu')->willReturn($this->configInterfaceMock);
        $this->configInterfaceMock->expects($this->any())->method('getMenuId')->willReturn(5);
        $this->configInterfaceMock->expects($this->any())->method('getIsActive')->willReturn(TRUE);
        $this->configInterfaceMock->expects($this->any())->method('getMenuType')->willReturn(2);
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(1);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(1);

        $this->menuItems->expects($this->any())->method('getItemName')->willReturn('Browse Catalog');
        $this->menuItems->expects($this->any())->method('getItemType')->willReturn('megamenuwithout');
        $this->megamenuManagementMock->expects($this->any())->method('loadMenuItems')->with(0, 'ASC')->willReturn( [$this->menuItems]);

        $megaMenuItemData = [
            'menu_block' => $this->topMenuBlock,
            'menu_item' => $this->menuItems,
            'menu_management' => $this->megamenuManagementMock
        ];

        $this->layoutMock->expects($this->any())->method('createBlock')->with('Magento\Framework\View\Element\Template')->willReturn($this->blockInterfaceMock);

        $treeNode = $this->buildTree1(true);
        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

        $this->eventManagerMock->expects($this->any())
            ->method('dispatch')
            ->willReturnMap([
                [
                    'page_block_html_topmenu_gethtml_before',
                    [
                        'menu' => $treeNode,
                        'block' => $this->topMenuBlock,
                        'request' => $this->requestMock,
                    ],
                    $this->eventManagerMock
                ],
                [
                    'page_block_html_topmenu_gethtml_after',
                    [
                        'menu' => $treeNode,
                        'transportObject' => $transportObject,
                    ],
                    $this->eventManagerMock
                ],
            ]);

        $this->topMenuBlock->getMegaMenuHtml($outermostClass, $childrenWrapClass, $limit);
    }

    public function testGetMegaMenuHtmlWithoutMenuType()
    {
        $this->ondemandHelper->expects($this->any())->method('isProductAvailable')->willReturn(true);
        $this->ondemandHelper->expects($this->any())->method('isPublishCategory')->willReturn(true);
        $outermostClass = "level-top";
        $childrenWrapClass = "level0 nav-1 first parent main-parent";
        $limit = 1;
        $html = $this->htmlWithCategory;

        $this->nodeFactoryMock->expects($this->any())->method('create')->willReturn($this->nodeMock);
        $this->nodeMock->expects($this->once())->method('setOutermostClass');
        $this->dataMock->expects($this->any())->method('isEnabled')->willReturn(TRUE);
        $this->megamenuManagementMock->expects($this->any())->method('getMenuData')->willReturn($this->megamenuInterfaceMock);
        $this->megamenuInterfaceMock->expects($this->any())->method('getMenu')->willReturn($this->configInterfaceMock);
        $this->configInterfaceMock->expects($this->any())->method('getMenuId')->willReturn(5);
        $this->configInterfaceMock->expects($this->any())->method('getIsActive')->willReturn(TRUE);
        $this->configInterfaceMock->expects($this->any())->method('getMenuType')->willReturn('default');
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(1);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(1);

        $this->menuItems->expects($this->any())->method('getItemName')->willReturn('Browse Catalog');
        $this->megamenuManagementMock->expects($this->any())->method('loadMenuItems')->with(0, 'ASC')->willReturn( [$this->menuItems]);

        $megaMenuItemData = [
            'menu_block' => $this->topMenuBlock,
            'menu_item' => $this->menuItems,
            'menu_management' => $this->megamenuManagementMock
        ];

        $this->layoutMock->expects($this->any())->method('createBlock')->with('Magento\Framework\View\Element\Template')->willReturn($this->blockInterfaceMock);

        $treeNode = $this->buildTree(true);
        $transportObject = new \Magento\Framework\DataObject(['html' => $html]);

        $this->eventManagerMock->expects($this->any())
            ->method('dispatch')
            ->willReturnMap([
                [
                    'page_block_html_topmenu_gethtml_before',
                    [
                        'menu' => $treeNode,
                        'block' => $this->topMenuBlock,
                        'request' => $this->requestMock,
                    ],
                    $this->eventManagerMock
                ],
                [
                    'page_block_html_topmenu_gethtml_after',
                    [
                        'menu' => $treeNode,
                        'transportObject' => $transportObject,
                    ],
                    $this->eventManagerMock
                ],
            ]);

        $this->topMenuBlock->getMegaMenuHtml($outermostClass, $childrenWrapClass, $limit);
    }

    /**
     * Create Tree Node mock object
     *
     * Helper method, that provides unified logic of creation of Tree Node mock objects.
     *
     * @param bool $isCurrentItem
     * @return MockObject
     */
    private function buildTree($isCurrentItem)
    {
        $outermostClass = "level-top";
        $container = $this->createMock(\Magento\Catalog\Model\ResourceModel\Category\Tree::class);

        $children = $this->getMockBuilder(Collection::class)
            ->setMethods(['count', 'delete'])
            ->setConstructorArgs(['container' => $container])
            ->getMock();

        for ($i = 0; $i < 4; $i++) {
            if ($i == 0 || $i == 1) {
                $id = $i == 0 ? "print-products" : "browse-catalog";
                $categoryNode = $this->createPartialMock(Node::class, ['getId', 'hasChildren']);
                $categoryNode->expects($this->any())->method('getId')->willReturn($id);
                $categoryNode->expects($this->any())->method('hasChildren')->willReturn(false);
                $categoryNode->setData(
                    [
                        'name' => $i == 0 ? "Print Products" : "Browse Catalog",
                        'id' => $id,
                        'url' => "http://magento2/$id.html",
                        'is_active' => $i == 0 ? $isCurrentItem : false,
                        'is_current_item' => $i == 0 ? $isCurrentItem : false,
                        'is_parent_active' => false,
                        'children' => 1
                    ]
                );
            } else {
                $id = "category-$i";
                $categoryNode = $this->createPartialMock(Node::class, ['getId', 'hasChildren']);
                $categoryNode->expects($this->any())->method('getId')->willReturn($id);
                $categoryNode->expects($this->any())->method('hasChildren')->willReturn(false);
                $categoryNode->setData(
                    [
                        'name' => "Category $i",
                        'id' => $id,
                        'url' => "http://magento2/$id.html",
                        'is_active' => $i == 0 ? $isCurrentItem : false,
                        'is_current_item' => $i == 0 ? $isCurrentItem : false,
                        'children' => 1
                    ]
                );
            }
            $children->add($categoryNode);
        }

        $children->expects($this->any())->method('count')->willReturn(3);
        $this->nodeMock->expects($this->any())->method('getChildren')->willReturn($children);
        $this->nodeMock->expects($this->any())->method('getOutermostClass')->willReturn($outermostClass);
        $companyConfigObject = new \Magento\Framework\DataObject(['allow_own_document' => true, 'allow_shared_catalog' => true]);
        $this->helperDataMock->expects($this->any())->method('getCompanyConfiguration')->willReturn($companyConfigObject);
        $nodeMockData = [
            'data' => [],
            'idField' => 'root',
            'tree' => $this->treeMock,
        ];

        $this->nodeFactoryMock->expects($this->any())->method('create')->with($nodeMockData)->willReturn($this->nodeMock);
        $this->treeFactoryMock->expects($this->any())->method('create')->willReturn($this->treeMock);

        return $this->nodeMock;
    }

    /**
     * Create Tree Node mock object
     *
     * Helper method, that provides unified logic of creation of Tree Node mock objects.
     *
     * @param bool $isCurrentItem
     * @return MockObject
     */
    private function buildTree1($isCurrentItem)
    {
        $outermostClass = "level-top";
        $container = $this->createMock(\Magento\Catalog\Model\ResourceModel\Category\Tree::class);

        $children = $this->getMockBuilder(Collection::class)
            ->setMethods(['count', 'delete'])
            ->setConstructorArgs(['container' => $container])
            ->getMock();

        for ($i = 0; $i < 4; $i++) {
            if ($i == 0 || $i == 1) {
                $id = $i == 0 ? "print-products" : "browse-catalog";
                $categoryNode = $this->createPartialMock(Node::class, ['getId', 'hasChildren']);
                $categoryNode->expects($this->any())->method('getId')->willReturn($id);
                $categoryNode->expects($this->any())->method('hasChildren')->willReturn(false);
                $categoryNode->setData(
                    [
                        'name' => $i == 0 ? "Print Products" : "Browse Catalog",
                        'id' => $id,
                        'url' => "http://magento2/$id.html",
                        'is_active' => $i == 0 ? $isCurrentItem : false,
                        'is_current_item' => $i == 0 ? $isCurrentItem : false,
                        'is_parent_active' => false,
                        'children' => 1
                    ]
                );
            } else {
                $id = "category-$i";
                $categoryNode = $this->createPartialMock(Node::class, ['getId', 'hasChildren']);
                $categoryNode->expects($this->any())->method('getId')->willReturn($id);
                $categoryNode->expects($this->any())->method('hasChildren')->willReturn(false);
                $categoryNode->setData(
                    [
                        'name' => "Category $i",
                        'id' => $id,
                        'url' => "http://magento2/$id.html",
                        'is_active' => $i == 0 ? $isCurrentItem : false,
                        'is_current_item' => $i == 0 ? $isCurrentItem : false,
                        'children' => 1
                    ]
                );
            }
            $children->add($categoryNode);
        }

        $children->expects($this->any())->method('count')->willReturn(3);
        $this->nodeMock->expects($this->any())->method('getChildren')->willReturn($children);
        $this->nodeMock->expects($this->any())->method('getOutermostClass')->willReturn($outermostClass);
        $companyConfigObject = new \Magento\Framework\DataObject(['allow_own_document' => false, 'allow_shared_catalog' => false ,'enable_upload_section'=> true,'enable_catalog_section'=> true]);
        $this->helperDataMock->expects($this->any())->method('getCompanyConfiguration')->willReturn($companyConfigObject);
        $nodeMockData = [
            'data' => [],
            'idField' => 'root',
            'tree' => $this->treeMock,
        ];

        $this->nodeFactoryMock->expects($this->any())->method('create')->with($nodeMockData)->willReturn($this->nodeMock);
        $this->treeFactoryMock->expects($this->any())->method('create')->willReturn($this->treeMock);

        return $this->nodeMock;
    }

    /**
     * @param $template
     */
    public function testSetCustomTemplate()
    {
        $this->templateMock->expects($this->any())->method('setTemplate')->willReturnSelf();
        $this->dataMock->expects($this->any())->method('isEnabled')->willReturn(TRUE);
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSession);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(TRUE);
        $this->customerSession->expects($this->any())->method('getCustomerId')->willReturn(2);
        $this->megamenuManagementMock->expects($this->any())->method('getMenuData')->with(2)->willReturn($this->megamenuInterfaceMock);
        $this->megamenuInterfaceMock->expects($this->any())->method('getMenu')->willReturn($this->configInterfaceMock);
        $this->configInterfaceMock->expects($this->any())->method('getMenuId')->willReturn(5);
        $this->configInterfaceMock->expects($this->any())->method('getIsActive')->willReturn(TRUE);
        $this->configInterfaceMock->expects($this->any())->method('getMenuType')->willReturn(2);
        $this->dataMock->expects($this->any())->method('isHumbergerMenu')->willReturn(TRUE);
        $this->topMenuBlock->setCustomTemplate($this->templateMock);
    }

    /**
     * @param $template
     */
    public function testSetCustomTemplateWithoutLoggedin()
    {
        $this->templateMock->expects($this->any())->method('setTemplate')->willReturnSelf();
        $this->dataMock->expects($this->any())->method('isEnabled')->willReturn(TRUE);
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSession);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(FALSE);
        $this->megamenuManagementMock->expects($this->any())->method('getMenuData')->willReturn($this->megamenuInterfaceMock);
        $this->megamenuInterfaceMock->expects($this->any())->method('getMenu')->willReturn($this->configInterfaceMock);
        $this->configInterfaceMock->expects($this->any())->method('getMenuId')->willReturn(5);
        $this->configInterfaceMock->expects($this->any())->method('getIsActive')->willReturn(TRUE);
        $this->configInterfaceMock->expects($this->any())->method('getMenuType')->willReturn(2);
        $this->topMenuBlock->setCustomTemplate($this->templateMock);
    }

    /**
     * @param $childCategoryObject
     */
    public function testgetReorderCateogry(){
        $categoryNode = $this->createPartialMock(Node::class, ['getId', 'hasChildren']);

        $this->nodeFactory = $this->getMockBuilder(NodeFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['getChildren','getName'])
        ->getMock();

       $child = array(
        $categoryNode->setData(
            [
                'name' =>  "Print Products",
                'id' => '256',
                'url' => "http://magento2/1.html",
                'is_active' =>  false,
                'is_current_item' =>  false,
                'is_parent_active' => false,
                'children' => 1
            ]
        )
       );
         $this->nodeFactory->expects($this->any())->method('getChildren')->willReturnSelf();
         $this->nodeFactory->expects($this->any())->method('getName')->willReturn('Print Product');
         $this->assertEquals($child, $this->topMenuBlock->getReorderCateogry($child));
    }

    /**
     * Test getToggleValue
     *
     * @return void
     */
    public function testGetToggleValue()
    {
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')
        ->with(self::XML_PATH_TOP_MEGAMENU_FEATURE_TOGGLE)
        ->willReturn(true);

        $this->assertTrue($this->topMenuBlock->getToggleValue(self::XML_PATH_TOP_MEGAMENU_FEATURE_TOGGLE));
    }

    /**
     * testGetOrCreateCustomerSession
     * @return void
     */
    public function testGetOrCreateCustomerSession()
    {
        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $result = $this->topMenuBlock->getOrCreateCustomerSession();
        $this->assertSame($this->customerSession, $result);
    }

    /**
     * testGetToggleStatusForPerformanceImprovmentPhasetwo
     * @return void
     */
    public function testGetToggleStatusForPerformanceImprovmentPhasetwo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->topMenuBlock->getToggleStatusForPerformanceImprovmentPhasetwo());
    }



}
