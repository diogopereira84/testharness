<?php

declare(strict_types=1);

namespace Fedex\Ondemand\Test\Unit\Observer\Frontend;

use Magento\Framework\View\Layout;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Ondemand\Observer\Frontend\MoveBreadcrumbs;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class MoveBreadcrumbsTest extends TestCase
{
    protected $CatalogMvpMock;
    protected $event;
    protected $toggleConfig;
    protected $layout;
    protected $observer;
    protected $moveBreadcrumbs;
    protected function setUp(): void
    {
        $this->CatalogMvpMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable','isCommercialCustomer','isSharedCatalogPage'])
            ->getMock();

        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLayout'])
            ->getMock();
        
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->layout = $this->getMockBuilder(Layout::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'unsetElement',
                'setChild',
                'reorderChild'
            ])
            ->getMock();

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFullActionName','getEvent'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->moveBreadcrumbs = $objectManagerHelper->getObject(
            MoveBreadcrumbs::class,
            [
                'catalogMvpHelper' => $this->CatalogMvpMock
            ]
        );
    }

    /**
     * testExecute
     */
    public function testExecute()
    {
        $this->CatalogMvpMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->CatalogMvpMock->expects($this->any())->method('isSharedCatalogPage')->willReturn(true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->CatalogMvpMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->observer->expects($this->any())->method('getFullActionName')->willReturn('catalog_category_view');
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getLayout')->willReturn($this->layout);
        $this->layout->method('unsetElement');
        $this->layout->method('setChild');
        $this->layout->method('reorderChild');

        $this->assertInstanceOf(MoveBreadcrumbs::class, $this->moveBreadcrumbs->execute($this->observer));
    }

    public function testExecuteToggleOff()
    {
        $this->CatalogMvpMock->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(false);
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getLayout')->willReturn($this->layout);
        $this->CatalogMvpMock->expects($this->any())->method('isSharedCatalogPage')->willReturn(true);
        $this->assertInstanceOf(MoveBreadcrumbs::class, $this->moveBreadcrumbs->execute($this->observer));
    }
}
