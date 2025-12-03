<?php

namespace Fedex\CatalogMvp\Observer;

use Fedex\CatalogMvp\Observer\CategoryView;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CategoryViewTest extends TestCase
{
    protected $observerMock;
    protected $catalogMvpHelper;
    protected $scopeInterface;
    protected $categoryFactory;
    protected $category;
    protected $request;
    protected $urlInterface;
    protected $responseMock;
    protected $categoryView;
    protected $categoryRepositoryInterfaceMock;
    protected  $toggleConfigMock;

    protected function setUp(): void
    {

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest','getControllerAction','getResponse','setRedirect'])
            ->getMock();

        $this->catalogMvpHelper = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegCustomerAdmin', 'isMvpSharedCatalogEnable'])
            ->getMock();

        $this->scopeInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->categoryFactory = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsPublish', 'getLevel','load'])
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMockForAbstractClass();

        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMockForAbstractClass();
        $this->responseMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRedirect'])
            ->getMock();
        $this->categoryRepositoryInterfaceMock= $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
         $this->toggleConfigMock= $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->categoryView = $objectManagerHelper->getObject(
            CategoryView::class,
            [
                'catalogMvpHelper' => $this->catalogMvpHelper,
                'categoryFactory' => $this->categoryFactory,
                'scopeInterface' => $this->scopeInterface,
                'urlInterface' => $this->urlInterface,
                'toggleConfig'=>$this->toggleConfigMock,
                'categoryRepositoryInterface'=> $this->categoryRepositoryInterfaceMock
            ]
        );
    }

    /**
     * Test Case For execute
     */
    public function testExecute()
    {

        $this->catalogMvpHelper->expects($this->any())
            ->method('isMvpSharedCatalogEnable')
            ->willReturn(true);
        $this->catalogMvpHelper->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(false);
        $this->observerMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->request->expects($this->any())
            ->method('getParams')
            ->willReturn(['id' => 23]);
        $this->categoryFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->category);
        $this->category->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->category->expects($this->any())
            ->method('getLevel')
            ->willReturn('3');
        $this->scopeInterface->expects($this->any())
            ->method('getValue')
            ->willReturn('cms/noroute/index');
        $this->urlInterface->expects($this->any())
            ->method('getUrl')
            ->willReturn('https://staging3.office.fedex.com/cms/noroute/index');
        $this->observerMock->expects($this->any())
            ->method('getControllerAction')
            ->willReturnSelf();

        $this->observerMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->any())
            ->method('setRedirect')
            ->willReturnSelf();
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue') ->willReturn(true);
        $this->categoryRepositoryInterfaceMock->expects($this->any())->method('get')->willReturn($this->category);
        $this->categoryView->execute($this->observerMock);
    }
}
