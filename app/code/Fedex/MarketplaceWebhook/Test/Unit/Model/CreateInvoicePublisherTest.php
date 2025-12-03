<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Test\Unit\Model;

use Fedex\MarketplaceWebhook\Model\CreateInvoicePublisher;
use Fedex\MarketplaceWebhook\Api\Data\CreateInvoiceMessageInterfaceFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\TestCase;

class CreateInvoicePublisherTest extends TestCase
{
    private CreateInvoicePublisher $createInvoicePublisher;
    private PublisherInterface $publisher;
    private CreateInvoiceMessageInterfaceFactory $createInvoiceMessageFactory;

    /**
     * Setup method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->createInvoiceMessageFactory = $this
            ->getMockBuilder(CreateInvoiceMessageInterfaceFactory::class)
            ->setMethods(['setOrderId','create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->createInvoicePublisher = new CreateInvoicePublisher(
            $this->publisher,
            $this->createInvoiceMessageFactory
        );
    }

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $orderId = 123;

        $createInvoiceMessage = $this->createInvoiceMessageFactory;
        $createInvoiceMessage->expects($this->once())
            ->method('setOrderId')
            ->with($orderId);

        $this->createInvoiceMessageFactory->expects($this->once())
            ->method('create')
            ->willReturn($createInvoiceMessage);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(CreateInvoicePublisher::QUEUE_NAME, $createInvoiceMessage);

        $this->createInvoicePublisher->execute($orderId);
    }
}
