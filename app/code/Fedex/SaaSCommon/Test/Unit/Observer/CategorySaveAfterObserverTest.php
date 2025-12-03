<?php

namespace Fedex\SaaSCommon\Test\Unit\Observer;

use Fedex\SaaSCommon\Observer\CategorySaveAfterObserver;
use Fedex\SaaSCommon\Api\ConfigInterface;
use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterface;
use Fedex\SaaSCommon\Model\Queue\Publisher;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Category;

class CategorySaveAfterObserverTest extends TestCase
{
    public function testExecutePublishesWhenEnabledAndCategoryHasId()
    {
        $publisher = $this->createMock(Publisher::class);
        $config = $this->createMock(ConfigInterface::class);
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $category = $this->createMock(Category::class);

        $config->method('isTigerD200529Enabled')->willReturn(true);
        $category->method('getId')->willReturn(123);

        $request->expects($this->once())->method('setEntityId')->with(123);
        $request->expects($this->once())->method('setEntityType')->with(Category::ENTITY);
        $publisher->expects($this->once())->method('publish')->with($request);

        $eventMock = new class($category) {
            private $category;
            public function __construct($category) { $this->category = $category; }
            public function getCategory() { return $this->category; }
        };

        $observerMock = $this->createMock(Observer::class);
        $observerMock->method('getEvent')->willReturn($eventMock);

        $obs = new CategorySaveAfterObserver($publisher, $config, $request);
        $obs->execute($observerMock);
    }

    public function testExecuteDoesNothingWhenFeatureDisabled()
    {
        $publisher = $this->createMock(Publisher::class);
        $config = $this->createMock(ConfigInterface::class);
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $category = $this->createMock(Category::class);

        $config->method('isTigerD200529Enabled')->willReturn(false);

        $publisher->expects($this->never())->method('publish');
        $request->expects($this->never())->method('setEntityId');
        $request->expects($this->never())->method('setEntityType');

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->any())->method('getEvent')->willReturn(new class($category) {
            private $category;
            public function __construct($category) { $this->category = $category; }
            public function getCategory() { return $this->category; }
        });

        $obs = new CategorySaveAfterObserver($publisher, $config, $request);
        $obs->execute($observerMock);
    }

    public function testExecuteDoesNothingWhenNoCategory()
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
            public function getCategory() { return null; }
        });

        $obs = new CategorySaveAfterObserver($publisher, $config, $request);
        $obs->execute($observerMock);
    }

    public function testExecuteDoesNothingWhenCategoryHasNoId()
    {
        $publisher = $this->createMock(Publisher::class);
        $config = $this->createMock(ConfigInterface::class);
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $category = $this->createMock(Category::class);

        $config->method('isTigerD200529Enabled')->willReturn(true);
        $category->method('getId')->willReturn(null);

        $publisher->expects($this->never())->method('publish');
        $request->expects($this->never())->method('setEntityId');
        $request->expects($this->never())->method('setEntityType');

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->any())->method('getEvent')->willReturn(new class($category) {
            private $category;
            public function __construct($category) { $this->category = $category; }
            public function getCategory() { return $this->category; }
        });

        $obs = new CategorySaveAfterObserver($publisher, $config, $request);
        $obs->execute($observerMock);
    }
}

