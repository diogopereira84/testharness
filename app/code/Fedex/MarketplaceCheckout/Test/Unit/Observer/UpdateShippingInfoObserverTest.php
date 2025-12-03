<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Observer\UpdateShippingInfoObserver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Framework\App\Request\Http as HttpRequest;

class UpdateShippingInfoObserverTest extends TestCase
{
    /** @var \Fedex\MarketplaceCheckout\Observer\UpdateShippingInfoObserver|\PHPUnit\Framework\MockObject\MockObject */
    private $observerInstance;

    /** @var \Fedex\MarketplaceCheckout\Model\ApiConfig|\PHPUnit\Framework\MockObject\MockObject */
    private $apiConfigMock;

    /** @var \Fedex\MarketplaceCheckout\Model\QuoteUpdater|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteUpdaterMock;

    /** @var \Fedex\MarketplaceCheckout\Helper\Quote|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteHelperMock;

    /**
     * Sets up the test environment before each test is executed.
     *
     * This method is used to initialize objects, set default values, or
     * perform any necessary pre-test configuration required for the tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // build mocks for the three protected dependencies
        $this->apiConfigMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['isEnabled'])
            ->getMock();
        $this->quoteUpdaterMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['synchronize'])
            ->getMock();
        $this->quoteHelperMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getQuote'])
            ->getMock();

        $this->observerInstance = $this->getMockBuilder(UpdateShippingInfoObserver::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $ref = new \ReflectionClass($this->observerInstance);
        foreach (['apiConfig', 'quoteUpdater', 'quoteHelper'] as $propName) {
            $prop = $ref->getProperty($propName);
            $prop->setAccessible(true);
            $prop->setValue(
                $this->observerInstance,
                $this->{$propName . 'Mock'}
            );
        }
    }

    /**
     * Tests that the execute method does nothing when the API is disabled.
     *
     * This test verifies that if the API is disabled through configuration,
     * invoking the observer's execute method results in no action being taken.
     *
     * @return void
     */
    public function testExecuteDoesNothingWhenApiDisabled(): void
    {
        $this->apiConfigMock
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->quoteUpdaterMock
            ->expects($this->never())
            ->method('synchronize');

        $observer = $this->createObserver(null);
        $this->observerInstance->execute($observer);
    }

    /**
     * Tests that the execute method does not perform any actions on non-GET requests.
     *
     * This unit test ensures that when a request is not a GET request, the observer
     * responsible for updating shipping information does not execute any logic or trigger
     * any side-effects. This helps in verifying that the application correctly handles
     * requests by ignoring those that do not meet the GET request criteria.
     *
     * @return void
     */
    public function testExecuteDoesNothingOnNonGetRequest(): void
    {
        $this->apiConfigMock
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $requestMock = $this->createMock(HttpRequest::class);
        $requestMock
            ->expects($this->once())
            ->method('isGet')
            ->willReturn(false);

        $this->quoteUpdaterMock
            ->expects($this->never())
            ->method('synchronize');

        $observer = $this->createObserver($requestMock);
        $this->observerInstance->execute($observer);
    }

    /**
     * Tests that the execute method properly triggers the synchronization process when both
     * "Get" mode is active and the API is enabled.
     *
     * This test verifies that the observer performs the expected shipping information update
     * under the conditions where both settings are enabled.
     *
     * @return void
     */
    public function testExecuteSynchronizesOnGetAndApiEnabled(): void
    {
        $this->apiConfigMock
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $dummyQuote = new \Magento\Framework\DataObject(['foo' => 'bar']);
        $this->quoteHelperMock
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($dummyQuote);

        $this->quoteUpdaterMock
            ->expects($this->once())
            ->method('synchronize')
            ->with($this->identicalTo($dummyQuote));

        $requestMock = $this->createMock(HttpRequest::class);
        $requestMock
            ->expects($this->once())
            ->method('isGet')
            ->willReturn(true);

        $observer = $this->createObserver($requestMock);
        $this->observerInstance->execute($observer);
    }

    /**
     * Creates an Observer instance.
     *
     * This method instantiates an Observer using the provided HTTP request,
     * if one is given.
     *
     * @param HttpRequest|null $request An optional HTTP request instance.
     * @return Observer The created Observer instance.
     */
    private function createObserver(?HttpRequest $request): Observer
    {
        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
        $eventMock
            ->method('getData')
            ->with('request')
            ->willReturn($request);

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvent'])
            ->getMock();

        $observerMock
            ->method('getEvent')
            ->willReturn($eventMock);

        return $observerMock;
    }
}
