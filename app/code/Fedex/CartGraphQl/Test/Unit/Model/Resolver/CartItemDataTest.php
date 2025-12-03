<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Model\Resolver\CartItemData;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem\Collection as IntegrationItemCollection;
use Fedex\Cart\Model\Quote\IntegrationItem;
use PHPUnit\Framework\TestCase;

class CartItemDataTest extends TestCase
{
    private $integrationItemCollectionMock;
    private $requestCommandFactoryMock;
    private $batchResponseFactoryMock;
    private $loggerHelperMock;
    private $validationCompositeMock;
    private $newRelicHeadersMock;

    private CartItemData $cartItemData;

    protected function setUp(): void
    {
        $this->integrationItemCollectionMock = $this->createMock(IntegrationItemCollection::class);
        $this->requestCommandFactoryMock = $this->createMock(GraphQlBatchRequestCommandFactory::class);
        $this->batchResponseFactoryMock = $this->createMock(BatchResponseFactory::class);
        $this->loggerHelperMock = $this->createMock(LoggerHelper::class);
        $this->validationCompositeMock = $this->createMock(ValidationBatchComposite::class);
        $this->newRelicHeadersMock = $this->createMock(NewRelicHeaders::class);

        $this->cartItemData = new CartItemData(
            $this->integrationItemCollectionMock,
            $this->requestCommandFactoryMock,
            $this->batchResponseFactoryMock,
            $this->loggerHelperMock,
            $this->validationCompositeMock,
            $this->newRelicHeadersMock
        );
    }

    public function testResolve(): void
    {
        $contextMock = $this->createMock(ContextInterface::class);
        $fieldMock = $this->createMock(Field::class);

        $headerArray = ['test' => 'header'];

        // Mock Model with integration item data already present
        $modelWithData = new class {
            public function getId() { return 1; }
            public function getExtensionAttributes() {
                return new class {
                    public function getIntegrationItemData() {
                        return new class {
                            public function getItemData() { return 'cached-data'; }
                        };
                    }
                };
            }
        };

        // Mock Model without integration item data (to trigger DB load)
        $modelWithoutData = new class {
            public function getId() { return 2; }
            public function getExtensionAttributes() {
                return new class {
                    public function getIntegrationItemData() {
                        return null;
                    }
                };
            }
        };

        // Create ResolveRequest mocks
        $requestWithData = $this->createMock(ResolveRequest::class);
        $requestWithData->method('getValue')->willReturn(['model' => $modelWithData]);

        $requestWithoutData = $this->createMock(ResolveRequest::class);
        $requestWithoutData->method('getValue')->willReturn(['model' => $modelWithoutData]);

        $requests = [$requestWithData, $requestWithoutData];

        // Mock DB result for item without cached data
        $integrationItem = $this->createMock(IntegrationItem::class);
        $integrationItem->method('getItemId')->willReturn(2);
        $integrationItem->method('getItemData')->willReturn('db-loaded-data');

        $this->integrationItemCollectionMock
            ->expects($this->once())
            ->method('addFieldToFilter')
            ->with('item_id', ['in' => [2]])
            ->willReturn([$integrationItem]);

        // Expect logger info and error not to be triggered here
        $this->loggerHelperMock
            ->expects($this->once())
            ->method('info');

        // Run
        $response = $this->cartItemData->proceed($contextMock, $fieldMock, $requests, $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $response);
    }

    public function testProceedHandlesException(): void
    {
        $contextMock = $this->createMock(ContextInterface::class);
        $fieldMock = $this->createMock(Field::class);
        $headerArray = ['test' => 'header'];

        $modelWithoutData = new class {
            public function getId() { return 10; }
            public function getExtensionAttributes() {
                return new class {
                    public function getIntegrationItemData() {
                        return null;
                    }
                };
            }
        };

        $request = $this->createMock(ResolveRequest::class);
        $request->method('getValue')->willReturn(['model' => $modelWithoutData]);

        $this->integrationItemCollectionMock
            ->expects($this->once())
            ->method('addFieldToFilter')
            ->willThrowException(new \Exception("Simulated DB error"));

        $this->loggerHelperMock
            ->expects($this->once())
            ->method('error');

        $response = $this->cartItemData->proceed($contextMock, $fieldMock, [$request], $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $response);
    }
}
