<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\SDE\Test\Unit\ViewModel;

use Fedex\SDE\ViewModel\SdeSsoConfiguration;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test class for SdeSsoConfiguration
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SdeSsoConfigurationTest extends TestCase
{
    protected $customerSessionMock;
    protected $customerSessionFactoryMock;
    protected $customerRepositoryMock;
    protected $customerInterfaceMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $sdeSsoConfigurationMock;
    /**
     * @var Session $customerSession
     */
    protected $customerSession;

    /**
     * @var Http $requestMock
     */
    protected $requestMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->setMethods(['getFullActionName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCustomer',
                    'getId',
                    'getCustomerCompany',
                    'getFirstname',
                    'getCustomerId',
                ]
            )
            ->getMock();

        $this->customerSessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerInterfaceMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->sdeSsoConfigurationMock = $this->objectManager->getObject(
            SdeSsoConfiguration::class,
            [
                'customerSession' => $this->customerSessionFactoryMock,
                'customerRepository' => $this->customerRepositoryMock,
                'request' => $this->requestMock,
            ]
        );
    }

    /**
     * @test testGetSdeCustomerName
     */
    public function testGetSdeCustomerName()
    {
        $customerName = 'Shivani Kanswal';

        $this->customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn($customerName);

        $this->assertEquals($customerName, $this->sdeSsoConfigurationMock->getSdeCustomerName());
    }

    /**
     * @test testGetSdeCustomerNameWithoutName
     */
    public function testGetSdeCustomerNameWithoutName()
    {
        $customerName = '';

        $this->customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $this->assertEquals($customerName, $this->sdeSsoConfigurationMock->getSdeCustomerName());
    }

    /**
     * @test testIsSdeCustomerWithTrue
     */
    public function testIsSdeCustomerWithTrue()
    {
        $this->customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(null);

        $this->assertEquals(true, $this->sdeSsoConfigurationMock->isSdeCustomer());
    }

    /**
     * @test testIsSdeCustomerWithFalse
     */
    public function testIsSdeCustomerWithFalse()
    {
        $this->customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $this->assertEquals(false, $this->sdeSsoConfigurationMock->isSdeCustomer());
    }

    /**
     * @test testCustomerSession
     */
    public function testCustomerSession()
    {
        $this->customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->assertEquals($this->customerSessionMock, $this->sdeSsoConfigurationMock->customerSession());
    }
}
