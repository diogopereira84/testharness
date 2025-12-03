<?php

namespace Fedex\Orderhistory\Test\Unit\Block\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Fedex\Orderhistory\Helper\Data as OrderHistoryHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Fedex\Orderhistory\Block\Order\RetailInfo;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Payment\Model\InfoInterface;

class RetailInfoTest extends \PHPUnit\Framework\TestCase
{
    protected $contextMock;
    protected $coreRegistryMock;
    protected $template;
    protected $infoInterface;
    protected $pageConfig;
    protected $pageTitleMock;
    /**
     * @var (\Magento\Framework\View\LayoutInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $Layout;
    /**
     * @var (\Magento\Framework\View\Element\AbstractBlock & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $AbstractBlock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $retailInfoMock;
    /**
     * @var string
     */
    protected $_retailTemplate = 'Fedex_Orderhistory::order/retail-view.phtml';

    /**
     * Core
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelperMock;

    /**
     * @var AddressRenderer
     */
    protected $addressRendererMock;

     /**
      * @var OrderHistoryHelper
      */
    protected $orderHistoryHelperMock;

    /**
     * setup method
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(TemplateContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->coreRegistryMock = $this->getMockBuilder(Registry::class)
            ->setMethods(['registry'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderHistoryHelperMock = $this->getMockBuilder(OrderHistoryHelper::class)
            ->setMethods(['isPrintReceiptRetail','isEnhancementEnabeled','isModuleEnabled'
                , 'getIsSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentHelperMock = $this
            ->getMockBuilder(PaymentHelper::class)
            ->setMethods(['getInfoBlock'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->template = $this
            ->getMockBuilder(Template::class)
            ->setMethods(['payment_info'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->infoInterface = $this
            ->getMockBuilder(InfoInterface::class)
            ->getMockForAbstractClass();
        $this->addressRendererMock = $this->getMockBuilder(AddressRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageConfig = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->setMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->infoInterface = $this
            ->getMockBuilder(InfoInterface::class)
            ->getMockForAbstractClass();
        $this->pageTitleMock = $this->getMockBuilder(Title::class)
            ->setMethods(['set'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->Layout = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->AbstractBlock = $this->getMockBuilder(\Magento\Framework\View\Element\AbstractBlock::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLayout'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->retailInfoMock = $this->objectManager->getObject(
            RetailInfo::class,
            [
                'context' => $this->contextMock,
                'coreRegistry' => $this->coreRegistryMock,
                'paymentHelper' => $this->paymentHelperMock,
                'pageConfig' => $this->pageConfig,
                'title' => $this->pageTitleMock,
                'addressRenderer' => $this->addressRendererMock,
                'orderHistoryHelper' => $this->orderHistoryHelperMock,
                '_layout' => $this->Layout,
                'info' => $this->infoInterface,
                'data' => []
            ]
        );
    } //end Setup

    /**
     * Assert _prepareLayout.
     *
     * @return null
     */
     public function testPrepareLayout()
    {
        $testMethod = new \ReflectionMethod(RetailInfo::class, '_prepareLayout');
        $this->orderHistoryHelperMock->expects($this->any())->method('isEnhancementEnabeled')->willReturn(true);
        $this->orderHistoryHelperMock->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->orderHistoryHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->contextMock->expects($this->any())
            ->method('getPageConfig')
            ->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())
            ->method('getTitle')
            ->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->any())
            ->method('set')
            ->willReturn('test');
        $this->pageTitleMock->expects($this->any())
            ->method('set')
            ->willReturn('test');
        $order = $this
            ->getMockBuilder(OrderInterface::class)
            ->setMethods(['getRealOrderId','getPayment'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->coreRegistryMock
                ->method('registry')
                ->withConsecutive(
                    ['current_order'],
                    ['current_order']
                )
                ->willReturnOnConsecutiveCalls(
                    $order,
                    $order
            );
        $order->expects($this->any())->method('getPayment')->willReturn($this->infoInterface);
        $this->paymentHelperMock->expects($this->any())->method('getInfoBlock')
        ->with($this->infoInterface)->willReturn($this->template);
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->retailInfoMock);
        $this->assertEquals(null, $expectedResult);
    } //end testPrepareLayout

    /**
     * Assert _prepareLayout.
     *
     * @return null
     */
    public function testPrepareLayoutDisabled()
    {
        $testMethod = new \ReflectionMethod(RetailInfo::class, '_prepareLayout');
        $this->orderHistoryHelperMock->expects($this->any())->method('isEnhancementEnabeled')->willReturn(false);
        $this->orderHistoryHelperMock->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $this->contextMock->expects($this->any())
            ->method('getPageConfig')
            ->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())
            ->method('getTitle')
            ->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->any())
            ->method('set')
            ->willReturn('test');
        $this->pageTitleMock->expects($this->any())
            ->method('set')
            ->willReturn('test');
        $order = $this
            ->getMockBuilder(OrderInterface::class)
            ->setMethods(['getRealOrderId','getPayment'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->coreRegistryMock
                ->method('registry')
                ->withConsecutive(
                    ['current_order'],
                    ['current_order']
                )
                ->willReturnOnConsecutiveCalls(
                    $order,
                    $order
            );
        $order->expects($this->any())->method('getPayment')->willReturn($this->infoInterface);
        $this->paymentHelperMock->expects($this->any())->method('getInfoBlock')
        ->with($this->infoInterface)->willReturn($this->template);
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->retailInfoMock);
        $this->assertEquals(null, $expectedResult);
    } //end testPrepareLayoutDisabled

    /**
     * Assert _prepareLayout.
     *
     * @return null
     */
    public function testPrepareLayoutForRetail()
    {
        $testMethod = new \ReflectionMethod(RetailInfo::class, '_prepareLayout');
        $this->orderHistoryHelperMock->expects($this->any())->method('isEnhancementEnabeled')->willReturn(false);
        $this->orderHistoryHelperMock->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $this->orderHistoryHelperMock->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);
        $this->contextMock->expects($this->any())
            ->method('getPageConfig')
            ->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())
            ->method('getTitle')
            ->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->any())
            ->method('set')
            ->willReturn('test');
        $this->pageTitleMock->expects($this->any())
            ->method('set')
            ->willReturn('test');
        $order = $this
            ->getMockBuilder(OrderInterface::class)
            ->setMethods(['getRealOrderId','getPayment'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->coreRegistryMock
                ->method('registry')
                ->withConsecutive(
                    ['current_order'],
                    ['current_order']
                )
                ->willReturnOnConsecutiveCalls(
                    $order,
                    $order
            );
        $order->expects($this->any())->method('getPayment')->willReturn($this->infoInterface);
        $this->paymentHelperMock->expects($this->any())->method('getInfoBlock')
        ->with($this->infoInterface)->willReturn($this->template);
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->retailInfoMock);
        $this->assertEquals(null, $expectedResult);
    }

    /**
     * Assert getTemplate.
     *
     * @return string
     */
    public function testgetTemplate()
    {
        $testMethod = new \ReflectionMethod(RetailInfo::class, 'getTemplate');
        $this->orderHistoryHelperMock->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);
        $expectedResult = $testMethod->invoke($this->retailInfoMock);
        $this->assertEquals($this->_retailTemplate , $expectedResult);
    } //end testgetTemplate
}
