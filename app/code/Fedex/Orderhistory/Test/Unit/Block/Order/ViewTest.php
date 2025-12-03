<?php
namespace Fedex\Orderhistory\Test\Unit\Block\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\Registry;
use Fedex\Orderhistory\Block\Order\View;
use Fedex\Orderhistory\Helper\Data as OrderHistoryHelper;

class ViewTest extends \PHPUnit\Framework\TestCase
{
     /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $coreRegistryMock;
    protected $orderHistoryHelperMock;
    /**
     * @var (\Magento\Payment\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $paymentHelperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $viewMock;
    /**
     * @var string
     */
    protected $_template = 'Magento_Sales::order/view.phtml';

    /**
     * Core registry class
     *
     * @var Registry
     */
    protected $_coreRegistryMock = null;

    /**
     * @var HttpContext
     * @since 101.0.0
     */
    protected $httpContextMock;

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelperMock;

    /**
     * @var OrderHistoryHelper
     */
    protected $orderHistoryDataHelperMock;

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
            ->setMethods(['isPrintReceiptRetail'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentHelperMock = $this->getMockBuilder(PaymentHelper::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->httpContextMock = $this->getMockBuilder(HttpContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->viewMock = $this->objectManager->getObject(
            View::class,
            [
                'context' => $this->contextMock,
                'registry' => $this->coreRegistryMock,
                'paymentHelper' => $this->paymentHelperMock,
                'httpContext' => $this->httpContextMock,
                'orderHistoryDataHelper' => $this->orderHistoryHelperMock,
                'data' => []
            ]
        );
    } //end Setup

    /**
     * Assert getTemplate.
     *
     * @return string
     */
    public function testgetTemplate()
    {
        $testMethod = new \ReflectionMethod(View::class, 'getTemplate');
        $this->orderHistoryHelperMock->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);
        $expectedResult = $testMethod->invoke($this->viewMock);
        $this->assertEquals('' , $expectedResult);
    } //end testgetTemplate
}
