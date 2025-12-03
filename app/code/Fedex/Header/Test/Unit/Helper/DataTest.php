<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Header\Test\Unit\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Header\Helper\Data;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Framework\App\Helper\Context as HelperContext;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use Fedex\Base\Helper\Auth;

/**
 * Data Test class for Data Helper
 */
class DataTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $customerSession;
    protected $session;
    protected $httpContext;
    protected $customerFactory;
    protected $customer;
    protected $customerRepositoryInterface;
    protected $customerInterface;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $toggleConfigMock;
    protected $data;
    protected Auth|MockObject $baseAuthMock;

    /**
     * Test setUp method
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(HelperContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBackUrl','getId', 'isLoggedIn', 'getCustomer'])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->httpContext = $this->getMockBuilder(HttpContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMock();

        $this->customerRepositoryInterface = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFirstName','getLastName'])
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();


        $objectManagerHelper = new ObjectManager($this);

        $this->data = $objectManagerHelper->getObject(
            Data::class,
            [
                'context' => $this->context,
                'customerSession' => $this->customerSession,
                'httpContext' => $this->httpContext,
                'customerFactory' => $this->customerFactory,
                'customerRepositoryInterface' => $this->customerRepositoryInterface,
                'logger' => $this->loggerMock,
                'authHelper' => $this->baseAuthMock,
                'toggleConfig' => $this->toggleConfigMock,
                'session' => $this->session
            ]
        );
    }

    /**
     * Test method for IsLoggedIn
     *
     * @return void
     */
    public function testIsLoggedIn()
    {
        $this->httpContext->expects($this->any())->method('getValue')->willReturn(true);

        $this->assertEquals(true, $this->data->isLoggedIn());
    }

    /**
     * Test method for GetLink
     *
     * @return void
     */
    public function testGetLink()
    {
        $this->customerSession->expects($this->any())->method('create')->willReturn($this->session);
        $this->session->expects($this->any())->method('getBackUrl')->willReturn("backurl");

        $this->assertEquals("backurl", $this->data->getLink());
    }

    /**
     * Test method for GetLabel
     *
     * @return void
     */
    public function testGetLabel()
    {
        $this->customerSession->expects($this->any())->method('create')->willReturn($this->session);
        $this->session->expects($this->any())->method('getBackUrl')->willReturn('backurl');

        $this->assertEquals("Back to eProcurement", $this->data->getLabel());
    }

    /**
     * Test method for GetLabelNull
     *
     * @return void
     */
    public function testGetLabelNull()
    {
        $this->customerSession->expects($this->any())->method('create')->willReturn($this->session);
        $this->session->expects($this->any())->method('getBackUrl')->willReturn("");

        $this->assertEquals("", $this->data->getLabel());
    }

    /**
     * Test method for getCustomer
     *
     * @return void
     */
    public function testGetCustomer()
    {
        $customerId = 2;
        $this->customerFactory->expects($this->any())->method('create')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('load')->willReturn($this->customer);
        $exception = new \Exception();
        $this->customer->expects($this->any())->method('load')->willThrowException($exception);

        $this->assertEquals(false, $this->data->getCustomer($customerId));
    }

    /**
     * Test method for getLogin user name
     *
     * @return void
     */
    public function testGetLoginUserName()
    {
        $this->customerSession->expects($this->any())->method('create')->willReturn($this->session);
        $this->baseAuthMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->session->expects($this->once())->method('getId')->willReturn(2);
        $this->customerRepositoryInterface->expects($this->any())
        ->method('getById')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getFirstName')->willReturn('getFirstName');
        $this->customerInterface->expects($this->any())->method('getLastName')->willReturn('getLastName');

        $this->assertEquals("getFirstName getLastName", $this->data->getLoginUserName());
    }

    /**
     * Test method for getLoginUserName without Name
     *
     * @return void
     */
    public function testGetLoginUserNameWithoutName()
    {
        $this->customerSession->expects($this->any())->method('create')->willReturn($this->session);
        $this->baseAuthMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->session->expects($this->once())->method('getId')->willReturn(2);
        $this->customerRepositoryInterface->expects($this->any())
        ->method('getById')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getFirstName')->willReturn('');
        $this->customerInterface->expects($this->any())->method('getLastName')->willReturn('');

        $this->assertEquals("", $this->data->getLoginUserName());
    }

    /**
     * Test getLoginUserName without id
     *
     * @return void
     */
    public function testGetLoginUserNameWithoutId()
    {
        $this->customerSession->expects($this->any())->method('create')->willReturn($this->session);
        $this->baseAuthMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->session->expects($this->once())->method('getId')->willReturn('');

        $this->assertEquals("", $this->data->getLoginUserName());
    }

    /**
     * testGetOrCreateCustomerSession
     * @return void
     */
    public function testGetOrCreateCustomerSession()
    {
        $this->session->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);
        $result = $this->data->getOrCreateCustomerSession();
        $this->assertSame($this->session, $result);
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
        $this->assertEquals(true, $this->data->getToggleStatusForPerformanceImprovmentPhasetwo());
    }

    /**
     * testGetToggleD193926Fix
     * @return void
     */
    public function testGetToggleD193926Fix()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->data->getToggleD193926Fix());
    }

}
