<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Plugin\Controller\Sidebar;

use PHPUnit\Framework\TestCase;
use Fedex\Cart\Plugin\Controller\Sidebar\UpdateItemQtyPlugin;
use Magento\Checkout\Controller\Sidebar\UpdateItemQty as Subject;
use Magento\Checkout\Model\Sidebar;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Quote\Model\Quote\Item;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Magento\Checkout\Model\Cart;
use Exception;

class UpdateItemQtyPluginTest extends TestCase
{
    /**
     * @var UpdateItemQtyPlugin
     */
    private $plugin;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Context
     */
    private $context;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Sidebar
     */
    private $sidebar;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Data
     */
    private $jsonHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Item
     */
    private $quoteItem;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ToggleConfig
     */
    private $toggleConfig;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Http
     */
    private $response;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|MarketplaceCheckoutHelper
     */
    private $marketplaceCheckoutHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Cart
     */
    private $cart;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->context = $this->createMock(Context::class);
        $this->sidebar = $this->createMock(Sidebar::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->jsonHelper = $this->createMock(Data::class);
        $this->quoteItem = $this->getMockBuilder(Item::class)
            ->setMethods(['load', 'getProductId', 'getMiraklOfferId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->stockRegistry = $this->getMockBuilder(StockRegistryInterface::class)
            ->setMethods(['getMaxSaleQty', 'getStockItem'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->response = $this->getMockBuilder(Http::class)
            ->setMethods(['representJson'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->marketplaceCheckoutHelper = $this->getMockBuilder(MarketplaceCheckoutHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isEssendantToggleEnabled'])
            ->getMock();
        $this->cart = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();

        $this->context->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->response);

        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            UpdateItemQtyPlugin::class,
            [
                'context' => $this->context,
                'sidebar' => $this->sidebar,
                'logger' => $this->logger,
                'jsonHelper' => $this->jsonHelper,
                'quoteItem' => $this->quoteItem,
                'stockRegistry' => $this->stockRegistry,
                'toggleConfig' => $this->toggleConfig,
                'marketplaceCheckoutHelper' => $this->marketplaceCheckoutHelper,
                'cart' => $this->cart
            ]
        );
    }

    /**
     * Test the successful execution of the afterExecute plugin.
     * @return void
     */
    public function testAfterExecuteSuccess()
    {
        $subject = $this->getMockBuilder(Subject::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->method('getRequest')
            ->willReturn($this->getMockRequest(1, 5));

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')
            ->willReturn(true);

        $this->quoteItem->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteItem->expects($this->any())->method('getProductId')->willReturnSelf(1);

        $this->stockRegistry->expects($this->any())->method('getStockItem')->willReturnSelf();
        $this->stockRegistry->expects($this->any())->method('getMaxSaleQty')->willReturn(1);

        $this->sidebar->expects($this->any())
            ->method('checkQuoteItem')
            ->with(1);

        $this->sidebar->expects($this->any())
            ->method('updateQuoteItem')
            ->with(1, 5);

        $this->jsonHelper->expects($this->any())
            ->method('jsonEncode')
            ->with($this->anything())
            ->willReturn(json_encode(['success' => true]));

        $this->response->expects($this->any())
            ->method('representJson')
            ->with(json_encode(['success' => true]));

        $result = $this->plugin->afterExecute($subject, $this->response);
        $this->assertNotNull($this->response);
    }

    /**
     * Helper method to create a mock request with specific item_id and item_qty parameters.
     *
     * @param int $itemId
     * @param int $itemQty
     * @return \PHPUnit\Framework\MockObject\MockObject|RequestInterface
     */
    private function getMockRequest($itemId, $itemQty)
    {
        $request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request->expects($this->any())->method('getParam')
            ->will($this->returnValueMap([
                ['item_id', null, $itemId],
                ['item_qty', null, $itemQty],
            ]));
        return $request;
    }

    /**
     * Test for ExecuteWithLocalizedException
     */
    public function testExecuteWithLocalizedException()
    {
        $phrase = new Phrase(__('Exception message'));
        $localizedException = new LocalizedException($phrase);
        $request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $subject = $this->getMockBuilder(Subject::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->method('getRequest')
            ->willReturn($this->getMockRequest(1, 5));
        $this->context
            ->method('getRequest')
            ->willReturn($request);
        $request->expects($this->any())->method('getParam')->willThrowException($localizedException);
        $this->response->expects($this->any())
            ->method('representJson')
            ->with(json_encode(['success' => true]));
        $this->assertNotNull($this->plugin->afterExecute($subject, $this->response));
    }

    /**
     * Test for ExecuteWithException
     */
    public function testExecuteWithException1()
    {
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception($phrase);
        $subject = $this->getMockBuilder(Subject::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->method('getRequest')
            ->willReturn($this->getMockRequest(1, 5));
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')
            ->willReturn(true);
        $this->quoteItem->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteItem->expects($this->any())->method('getProductId')->willReturnSelf(1);
        $this->stockRegistry->expects($this->any())->method('getStockItem')->willReturnSelf();
        $this->stockRegistry->expects($this->any())->method('getMaxSaleQty')->willReturn(1);

        $this->sidebar->expects($this->any())
            ->method('checkQuoteItem')
            ->with(1);

        $this->sidebar->expects($this->any())
            ->method('updateQuoteItem')
            ->with(1, 5);

        $this->jsonHelper->expects($this->any())
            ->method('jsonEncode')
            ->with($this->anything())
            ->willReturn(json_encode(['success' => true]));

        $this->response->expects($this->any())
            ->method('representJson')
            ->with(json_encode(['success' => true]));
        $request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->context
            ->method('getRequest')
            ->willReturn($request);
        $request->expects($this->any())->method('getParam')->willThrowException($exception);
        $this->assertNull($this->plugin->afterExecute($subject, $this->response));
    }

    /**
     * Test for ExecuteWithException
     */
    public function testAfterExecuteWith3pAdditionalData()
    {
        $itemId  = 42;
        $itemQty = 3;

        $requestMock = $this->getMockRequest($itemId, $itemQty);

        $subject = $this->getMockBuilder(Subject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subject->method('getRequest')
            ->willReturn($requestMock);

        $this->context
            ->method('getRequest')
            ->willReturn($requestMock);

        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->marketplaceCheckoutHelper
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);
        $this->quoteItem
            ->expects($this->once())
            ->method('load')
            ->with($itemId)
            ->willReturnSelf();
        $this->quoteItem
            ->method('getProductId')
            ->willReturn(99);
        $this->quoteItem
            ->method('getMiraklOfferId')
            ->willReturn('MIRAKL-XYZ');

        $stockItem = $this->createMock(\Magento\CatalogInventory\Api\Data\StockItemInterface::class);
        $stockItem->method('getMaxSaleQty')->willReturn(10);
        $this->stockRegistry
            ->method('getStockItem')
            ->with(99)
            ->willReturn($stockItem);

        $quote = $this->createMock(Quote::class);
        $cartItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAdditionalData', 'setAdditionalData', 'save', 'getItemId', 'getBaseRowTotal'])
            ->getMock();
        $cartItem->method('getAdditionalData')
            ->willReturn(json_encode(['foo' => 'bar', 'quantity' => 1, 'total' => 10]));
        $cartItem->method('getItemId')
            ->willReturn($itemId);
        $cartItem->method('getBaseRowTotal')
            ->willReturn(15.99);
        $cartItem->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use ($itemQty) {
                $arr = json_decode($json, true);
                return isset($arr['quantity']) && $arr['quantity'] === $itemQty;
            }))
            ->willReturnSelf();
        $cartItem->expects($this->once())
            ->method('save');

        $quote->method('getItemById')->willReturn($cartItem);
        $this->cart->method('getQuote')->willReturn($quote);

        $this->sidebar
            ->expects($this->once())
            ->method('getResponseData')
            ->with('')
            ->willReturn(['success' => true]);

        $this->jsonHelper
            ->method('jsonEncode')
            ->willReturn(json_encode(['success' => true]));

        $this->response
            ->expects($this->once())
            ->method('representJson')
            ->with(json_encode(['success' => true]))
            ->willReturn($this->response);

        $result = $this->plugin->afterExecute($subject, $this->response);

        $this->logger
            ->expects($this->never())
            ->method('critical');

        $this->assertSame($this->response, $result);
    }

    /**
     * Test for afterExecute method when a localized exception is thrown.
     */
    public function testAfterExecuteCatchesLocalizedExceptionAndReturnsJsonResponse()
    {
        $pluginMock = $this->getMockBuilder(UpdateItemQtyPlugin::class)
            ->setConstructorArgs([
                $this->context,
                $this->sidebar,
                $this->logger,
                $this->jsonHelper,
                $this->quoteItem,
                $this->stockRegistry,
                $this->toggleConfig,
                $this->cart,
                $this->marketplaceCheckoutHelper,
            ])
            ->onlyMethods(['jsonResponse'])
            ->getMock();

        $subject = $this->createMock(Subject::class);
        $response = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockRequest(42, 3);
        $subject->method('getRequest')->willReturn($request);
        $this->context->method('getRequest')->willReturn($request);
        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->marketplaceCheckoutHelper
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $msg = 'something went wrong (localized)';
        $exception = new LocalizedException(__($msg));

        $itemId = 42;
        $this->quoteItem
            ->method('load')
            ->with($itemId)
            ->willReturnSelf();
        $this->quoteItem
            ->method('getProductId')
            ->willReturn(99);
        $this->quoteItem
            ->method('getMiraklOfferId')
            ->willReturn('MIR-XYZ');

        $stockItem = $this->createMock(
            \Magento\CatalogInventory\Api\Data\StockItemInterface::class
        );
        $stockItem
            ->method('getMaxSaleQty')
            ->willReturn(10);
        $this->stockRegistry
            ->method('getStockItem')
            ->with(99)
            ->willReturn($stockItem);

        $this->cart
            ->method('getQuote')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains($msg));

        $pluginMock
            ->expects($this->once())
            ->method('jsonResponse')
            ->with($msg)
            ->willReturn($response);

        $result = $pluginMock->afterExecute($subject, $response);

        $this->assertSame($response, $result);
    }

    /**
     * Test for afterExecute method when a generic exception is thrown.
     */
    public function testAfterExecuteCatchesGenericExceptionAndReturnsJsonResponse()
    {
        $pluginMock = $this->getMockBuilder(UpdateItemQtyPlugin::class)
            ->setConstructorArgs([
                $this->context,
                $this->sidebar,
                $this->logger,
                $this->jsonHelper,
                $this->quoteItem,
                $this->stockRegistry,
                $this->toggleConfig,
                $this->cart,
                $this->marketplaceCheckoutHelper,
            ])
            ->onlyMethods(['jsonResponse'])
            ->getMock();

        $subject = $this->createMock(Subject::class);
        $response = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockRequest(42, 3);
        $subject->method('getRequest')->willReturn($request);
        $this->context->method('getRequest')->willReturn($request);
        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->marketplaceCheckoutHelper
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $msg = 'something really bad';
        $exception = new Exception($msg);

        $itemId = 42;
        $this->quoteItem
            ->method('load')
            ->with($itemId)
            ->willReturnSelf();
        $this->quoteItem
            ->method('getProductId')
            ->willReturn(99);
        $this->quoteItem
            ->method('getMiraklOfferId')
            ->willReturn('MIR-XYZ');

        $stockItem = $this->createMock(
            \Magento\CatalogInventory\Api\Data\StockItemInterface::class
        );
        $stockItem
            ->method('getMaxSaleQty')
            ->willReturn(10);
        $this->stockRegistry
            ->method('getStockItem')
            ->with(99)
            ->willReturn($stockItem);

        $this->cart
            ->method('getQuote')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains($msg));

        $pluginMock
            ->expects($this->once())
            ->method('jsonResponse')
            ->with($msg)
            ->willReturn($response);

        $result = $pluginMock->afterExecute($subject, $response);

        $this->assertSame($response, $result);
    }
}
