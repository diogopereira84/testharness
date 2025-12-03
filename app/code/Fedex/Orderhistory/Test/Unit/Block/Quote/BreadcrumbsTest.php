<?php
namespace Fedex\Orderhistory\Test\Unit\Block\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Orderhistory\Block\Quote\Breadcrumbs;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Framework\App\RequestInterface;

class BreadcrumbsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $quoteRepository;
    protected $storeManager;
    protected $storeMock;
    protected $helper;
    protected $layout;
    protected $abstractBlock;
    protected $breadcrumbMock;
    protected $requestMock;
    protected $quote;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $breadcrumbsObj;
    /**
     * setup method
     */
    protected function setUp(): void
    {
        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteRepository = $this->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->setMethods(['getBaseUrl'])
            ->getMockForAbstractClass();
        
        $this->helper = $this
            ->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
            ->setMethods(['isModuleEnabled','isEnhancementEnabeled'])//B-1112160 - View Quote Details.
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->layout = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBlock','addCrumb','toHtml'])
            ->getMockForAbstractClass();

        $this->abstractBlock = $this->getMockBuilder(\Magento\Framework\View\Element\AbstractBlock::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLayout'])
            ->getMockForAbstractClass();

        $this->breadcrumbMock = $this->getMockBuilder(BlockInterface::class)
            ->setMethods(['addCrumb'])
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->quote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteName'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->breadcrumbsObj = $this->objectManager->getObject(
            Breadcrumbs::class,
            [
                'context' => $this->contextMock,
                'quoteRepository' => $this->quoteRepository,
                'helper' => $this->helper,
                'storeManager' => $this->storeManager,
                '_layout' => $this->layout,
                '_request' => $this->requestMock
            ]
        );
    }
    
    /**
     * Assert getOrderviewbreadcrumbs When Module is enambled.
     *
     */
    public function testGetBreadcrumbs()
    {
        $baseUrl = 'base-url';
        $this->requestMock->expects($this->once())->method('getParam')->willReturn(77);
        
        $this->quoteRepository->expects($this->once())->method('getById')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getQuoteName')->willReturn('Quote');
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);

        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->abstractBlock->expects($this->any())->method('getLayout')->willReturn($this->layout);
        /* B-1112160 - View Quote Details. */
        $this->helper->expects($this->any())->method('isEnhancementEnabeled')->willReturn(true);
        $this->layout->expects($this->any())->method('getBlock')->with('breadcrumbs')->willReturnSelf();
        $this->breadcrumbMock->expects($this->any())
            ->method('addCrumb')
            ->willReturnMap([]);
           
        $this->assertEquals(null, $this->breadcrumbsObj->getBreadcrumbs());
    }
    /**
     * Assert getOrderviewbreadcrumbs When Module is Disabled.
     *
     */
    public function testGetBreadcrumbsohdisabled()
    {
        $baseUrl = 'base-url';
        $this->requestMock->expects($this->once())->method('getParam')->willReturn(77);
        
        $this->quoteRepository->expects($this->once())->method('getById')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getQuoteName')->willReturn('Quote');
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);

        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->abstractBlock->expects($this->any())->method('getLayout')->willReturn($this->layout);
        /* B-1112160 - View Quote Details. */
        $this->helper->expects($this->any())->method('isEnhancementEnabeled')->willReturn(false);
        $this->layout->expects($this->any())->method('getBlock')->with('breadcrumbs')->willReturnSelf();
        $this->breadcrumbMock->expects($this->any())
            ->method('addCrumb')
            ->willReturnMap([]);
           
        $this->assertEquals(null, $this->breadcrumbsObj->getBreadcrumbs());
    }

    public function testGetBreadcrumbsModuleDisable()
    {
        $this->requestMock->expects($this->once())->method('getParam')->willReturn(null);
        $this->assertEquals(null, $this->breadcrumbsObj->getBreadcrumbs());
    }
}
