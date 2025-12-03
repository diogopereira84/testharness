<?php

declare(strict_types=1);

namespace Fedex\CatalogMigration\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\CatalogMigration\Helper\CatalogMigrationHelper;
use Psr\Log\LoggerInterface;

class ProductImageRemoveSubscriberTest extends TestCase
{
    protected $catalogMigrationHelperMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /** @var ProductImageRemoveSubscriber */
    private $productImageRemoveSubscriber;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        // Mock dependencies
        $this->catalogMigrationHelperMock = $this->createMock(CatalogMigrationHelper::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        // Instantiate the class with mocked dependencies
        $this->productImageRemoveSubscriber = $objectManager->getObject(
            ProductImageRemoveSubscriber::class,
            [
                'catalogMigrationHelper' => $this->catalogMigrationHelperMock,
                'logger' => $this->loggerMock,
            ]
        );
    }

    /**
     * Test ProcessMessage
     */
    public function testProcessMessage()
    {
        $messageMock = $this->createMock(MessageInterface::class);

        // Set up expectations for the message mock
        $messageMock->expects($this->once())
            ->method('getMessage')
            ->willReturn('TEST_SKU');

        // Set up expectations for the catalog migration helper
        $this->catalogMigrationHelperMock->expects($this->once())
            ->method('removeProductImage')
            ->with($this->equalTo('TEST_SKU'));

        // Call the method to test
        $this->productImageRemoveSubscriber->processMessage($messageMock);
    }

    /**
     * Test processMessage With Error
     */
    public function testProcessMessageWithError()
    {
        $messageMock = $this->createMock(MessageInterface::class);

        // Set up expectations for the message mock
        $messageMock->expects($this->once())
            ->method('getMessage')
            ->willReturn('TEST_SKU');

        // Set up expectations for the catalog migration helper to throw an exception
        $this->catalogMigrationHelperMock->expects($this->once())
            ->method('removeProductImage')
            ->with('TEST_SKU')
            ->willThrowException(new \Exception('Test exception'));

        // Call the method to test
        $this->productImageRemoveSubscriber->processMessage($messageMock);
    }
}
