<?php
declare(strict_types=1);

namespace Fedex\Orderhistory\Test\Unit\Observer;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Orderhistory\Observer\AddBodyClassObserverSelfreg;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Page\Config as PageConfig;
use PHPUnit\Framework\TestCase;

class AddBodyClassObserverSelfregTest extends TestCase
{

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var AddBodyClassObserverSelfreg
     */
    private $observerObject;

    /**
     * @var PageConfig
     */
    protected $pageConfig;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * @var DeliveryHelper
     */
    private $deliveryHelper;

    protected function setUp(): void
    {
        $this->observer       = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $this->pageConfig     = $this->getMockBuilder(PageConfig::class)->disableOriginalConstructor()->getMock();
        $this->toggleConfig   = $this->getMockBuilder(ToggleConfig::class)->disableOriginalConstructor()->getMock();
        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)->disableOriginalConstructor()->getMock();

        $this->observerObject = new AddBodyClassObserverSelfreg(
            $this->pageConfig,
            $this->toggleConfig,
            $this->deliveryHelper,
        );

    }//end setUp()


    /**
     * Test execute() method if api config enabled.
     *
     * @return void
     */
    public function testExecuteWithToggleConfigEnabled(): void
    {
        $this->deliveryHelper->expects($this->once())->method('isCommercialCustomer')->willReturn(true);

        $this->pageConfig->expects($this->any())->method('addBodyClass');

        $this->observerObject->execute($this->observer);

    }//end testExecuteWithToggleConfigEnabled()
}//end class
