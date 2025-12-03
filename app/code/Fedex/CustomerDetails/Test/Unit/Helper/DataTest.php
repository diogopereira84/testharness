<?php

namespace Fedex\CustomerDetails\Test\Unit\Helper;

use Fedex\CustomerDetails\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class DataTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $customerSession;
    protected $customerSessionMock;
    protected $httpContextMock;
    protected $toggleConfig;
    protected $data;
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','getCompanyName'])
            ->getMock();

        $this->httpContextMock = $this->getMockBuilder(\Magento\Framework\App\Http\Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->data = $objectManagerHelper->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'customerSession' => $this->customerSessionMock,
                'httpContext' => $this->httpContextMock,
                'toggle' => $this->toggleConfig
            ]
        );
    }

    /**
     * Test isLoggedIn
     *
     */
    public function testIsLoggedIn()
    {
        $boolean=true;
        $this->httpContextMock->expects($this->any())
                                 ->method('getValue')
                                 ->with(\Magento\Customer\Model\Context::CONTEXT_AUTH)
                                 ->willReturn($boolean);

        $this->assertEquals($boolean, $this->data->isLoggedIn());
    }

    /**
     * Test getLoggedinCustomerDetails
     *
     */
    public function testGetLoggedinCustomerDetails()
    {
        $msg = "Hello ," ;
        $this->customerSessionMock->expects($this->any())
                                 ->method('create')
                                 ->willReturn($this->customerSession);

        $this->customerSessionMock->expects($this->any())
                                 ->method('getCompanyName')
                                 ->willReturn($msg);

        $this->assertEquals($msg, $this->data->getLoggedinCustomerDetails());
    }

    /**
     *  Test Get Toggle Config Valye for FCL feature
     */
    public function testGetToggleConfig()
    {
        $key = "test";
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->data->getToggleConfig($key);
    }
}
