<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model;

use Fedex\SharedCatalogCustomization\Model\CatalogEnableStoreSubscriber;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Api\SubscriberInterface;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Psr\Log\LoggerInterface;

class CatalogEnableStoreSubscriberTest extends TestCase
{
    protected $loggerInterfaceMock;
    protected $catalogEnableStoreSubscriberMock;
    /**
     * @var MessageInterface|MockObject
     */
    protected $messageInterfaceMock;

    /**
     * @var SubscriberInterface|MockObject
     */
    protected $subscriberInterfaceMock;

    /**
     * @var ManageCatalogItems|MockObject
     */
    private $manageCatalogItemsMock;

    /**
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->messageInterfaceMock = $this->getMockBuilder(MessageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMessage'])
            ->getMockForAbstractClass();

        $this->subscriberInterfaceMock = $this->getMockBuilder(SubscriberInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->manageCatalogItemsMock = $this
            ->getMockBuilder(ManageCatalogItems::class)
            ->setMethods(['itemEnableStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->catalogEnableStoreSubscriberMock = $this->objectManager->getObject(
            CatalogEnableStoreSubscriber::class,
            [
                'helperManageCatalogItems' => $this->manageCatalogItemsMock,
                'logger' => $this->loggerInterfaceMock
            ]
        );
    }

    /**
     * @test processMessage
     *
     * @return void
     */
    public function testProcessMessage()
    {
        $message = '{"catalogSyncQueueProcessId": 2, "productSku": "test", "storeId": 1}';
        $this->messageInterfaceMock->expects($this->any())->method('getMessage')->willReturn($message);
        $this->manageCatalogItemsMock->expects($this->any())->method('itemEnableStore')->willReturnSelf();

        $this->assertEquals(null, $this->catalogEnableStoreSubscriberMock->processMessage($this->messageInterfaceMock));
    }
}
