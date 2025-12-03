<?php
declare(strict_types=1);

namespace Fedex\Orderhistory\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Page\Config as PageConfig;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Orderhistory\Observer\AddBodyClassObserver;

class AddBodyClassObserverTest extends TestCase
{

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var AddBodyClassObserver
     */
    private $observerObject;

    /**
     * @var PageConfig
     */
    protected $pageConfig;

    /**
     * @var DeliveryHelper
     */
    private $deliveryHelper;

    protected function setUp(): void
    {
        $this->observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $this->pageConfig = $this->getMockBuilder(PageConfig::class)->disableOriginalConstructor()->getMock();
        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)->disableOriginalConstructor()->getMock();

        $this->observerObject = new AddBodyClassObserver(
            $this->pageConfig,
            $this->deliveryHelper
        );

    }//end setUp()

    /**
     * Test execute() method if customer is commercial.
     *
     * @return void
     */
    public function testExecuteWithCommercialCustomer(): void
    {
        $this->deliveryHelper->expects($this->once())->method('isCommercialCustomer')->willReturn(true);

        $this->pageConfig->expects($this->once())->method('addBodyClass');

        $this->observerObject->execute($this->observer);

    }//end testExecuteWithCommercialCustomer()

    /**
     * Test execute() method if customer is retail.
     *
     * @return void
     */
    public function testExecuteWithRetailCustomer(): void
    {
        $this->deliveryHelper->expects($this->once())->method('isCommercialCustomer')->willReturn(false);

        $this->pageConfig->expects($this->never())->method('addBodyClass');

        $this->observerObject->execute($this->observer);

    }//end testExecuteWithRetailCustomer()
}//end class
