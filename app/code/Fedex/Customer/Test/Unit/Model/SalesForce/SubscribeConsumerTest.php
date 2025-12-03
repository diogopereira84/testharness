<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Customer\Test\Unit\Model;

use Fedex\Customer\Api\Data\ConfigInterface;
use Fedex\Customer\Api\Data\SalesForceCustomerSubscriberInterface;
use Fedex\Customer\Api\Data\SalesForceResponseInterface;
use Fedex\Customer\Api\SalesForceInterface;
use Fedex\Customer\Model\SalesForce\SubscribeConsumer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * SubscribeConsumerTest Model
 */
class SubscribeConsumerTest extends TestCase
{
    /**
     * @var ConfigInterface|MockObject
     */
    private $configInterfaceMock;

    /**
     * @var SalesForceInterface|MockObject
     */
    private $salesForceApiInterfaceMock;

    /**
     * @var SalesForceCustomerSubscriberInterface|MockObject
     */
    private $salesForceCustomerSubscriberInterfaceMock;

    /**
     * @var SalesForceResponseInterface|MockObject
     */
    private $salesForceResponseInterfaceMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerInterfaceMock;

    /**
     * @var SubscribeConsumer $subscribeConsumer
     */
    protected $subscribeConsumer;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->configInterfaceMock = $this->createMock(ConfigInterface::class);
        $this->salesForceApiInterfaceMock = $this->createMock(SalesForceInterface::class);
        $this->salesForceCustomerSubscriberInterfaceMock = $this->createMock(SalesForceCustomerSubscriberInterface::class);
        $this->salesForceResponseInterfaceMock = $this->createMock(SalesForceResponseInterface::class);
        $this->loggerInterfaceMock = $this->createMock(LoggerInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->subscribeConsumer = $objectManagerHelper->getObject(
            SubscribeConsumer::class,
            [
                'configInterface' => $this->configInterfaceMock,
                'salesForceApiInterface' => $this->salesForceApiInterfaceMock,
                'logger' => $this->loggerInterfaceMock
            ]
        );
    }

    public function testProcessMessage()
    {
        $this->configInterfaceMock->expects($this->once())->method('isMarketingOptInEnabled')
            ->willReturn(true);
        $this->salesForceApiInterfaceMock->expects($this->once())->method('subscribe')
            ->with($this->salesForceCustomerSubscriberInterfaceMock)->willReturn($this->salesForceResponseInterfaceMock);

        $this->subscribeConsumer->processMessage($this->salesForceCustomerSubscriberInterfaceMock);
    }

    public function testProcessMessageFeatureDisabled()
    {
        $this->configInterfaceMock->expects($this->once())->method('isMarketingOptInEnabled')
            ->willReturn(false);
        $this->loggerInterfaceMock->expects($this->once())->method('info')
            ->with('Marketing Opt-In Feature is Disabled!');

        $this->subscribeConsumer->processMessage($this->salesForceCustomerSubscriberInterfaceMock);
    }
}
