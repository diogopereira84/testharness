<?php

namespace Fedex\SaaSCommon\Test\Unit\Observer;

use Fedex\SaaSCommon\Observer\ProductSaveAfterObserver;
use Fedex\SaaSCommon\Api\ConfigInterface;
use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterface;
use Fedex\SaaSCommon\Model\Queue\Publisher;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Product;

class ProductSaveAfterObserverTest extends TestCase
{
    public function testExecutePublishesWhenEnabledAndProductHasId()
    {
        $publisher = $this->createMock(Publisher::class);
        $config = $this->createMock(ConfigInterface::class);
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $product = $this->createMock(Product::class);

        $config->method('isTigerD200529Enabled')->willReturn(true);
        $product->method('getId')->willReturn(456);

        $request->expects($this->once())->method('setEntityId')->with(456);
        $request->expects($this->once())->method('setEntityType')->with(Product::ENTITY);
        $publisher->expects($this->once())->method('publish')->with($request);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->any())->method('getEvent')->willReturn(new class($product) {
            private $product;
            public function __construct($product) { $this->product = $product; }
            public function getProduct() { return $this->product; }
        });

        $obs = new ProductSaveAfterObserver($publisher, $config, $request);
        $obs->execute($observerMock);
    }

    public function testExecuteDoesNothingWhenFeatureDisabled()
    {
        $publisher = $this->createMock(Publisher::class);
        $config = $this->createMock(ConfigInterface::class);
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $product = $this->createMock(Product::class);

        $config->method('isTigerD200529Enabled')->willReturn(false);

        $publisher->expects($this->never())->method('publish');
        $request->expects($this->never())->method('setEntityId');
        $request->expects($this->never())->method('setEntityType');

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->any())->method('getEvent')->willReturn(new class($product) {
            private $product;
            public function __construct($product) { $this->product = $product; }
            public function getProduct() { return $this->product; }
        });

        $obs = new ProductSaveAfterObserver($publisher, $config, $request);
        $obs->execute($observerMock);
    }

    public function testExecuteDoesNothingWhenNoProduct()
    {
        $publisher = $this->createMock(Publisher::class);
        $config = $this->createMock(ConfigInterface::class);
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);

        $config->method('isTigerD200529Enabled')->willReturn(true);

        $publisher->expects($this->never())->method('publish');
        $request->expects($this->never())->method('setEntityId');
        $request->expects($this->never())->method('setEntityType');

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->any())->method('getEvent')->willReturn(new class {
            public function getProduct() { return null; }
        });

        $obs = new ProductSaveAfterObserver($publisher, $config, $request);
        $obs->execute($observerMock);
    }

    public function testExecuteDoesNothingWhenProductHasNoId()
    {
        $publisher = $this->createMock(Publisher::class);
        $config = $this->createMock(ConfigInterface::class);
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $product = $this->createMock(Product::class);

        $config->method('isTigerD200529Enabled')->willReturn(true);
        $product->method('getId')->willReturn(null);

        $publisher->expects($this->never())->method('publish');
        $request->expects($this->never())->method('setEntityId');
        $request->expects($this->never())->method('setEntityType');

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->any())->method('getEvent')->willReturn(new class($product) {
            private $product;
            public function __construct($product) { $this->product = $product; }
            public function getProduct() { return $this->product; }
        });

        $obs = new ProductSaveAfterObserver($publisher, $config, $request);
        $obs->execute($observerMock);
    }
}

