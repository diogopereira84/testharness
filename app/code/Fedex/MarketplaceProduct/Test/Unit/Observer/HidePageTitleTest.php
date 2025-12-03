<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Observer;

use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Fedex\MarketplaceProduct\Helper\Data as MiraklHelper;
use Fedex\MarketplaceProduct\Observer\HidePageTitle;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;

class HidePageTitleTest extends TestCase
{
    /**
     * @var CatalogHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $catalogHelper;

    /**
     * @var MarketplaceHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $marketplaceHelper;

    /**
     * @var MiraklHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $miraklHelper;

    /**
     * @var Observer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $observer;

    /**
     * @var Event|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event;

    /**
     * @var LayoutInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $layout;

    /**
     * A dummy product object returned by the catalog helper.
     *
     * @var object
     */
    private $product;

    protected function setUp(): void
    {
        $this->catalogHelper = $this->createMock(CatalogHelper::class);
        $this->marketplaceHelper = $this->createMock(MarketplaceHelper::class);
        $this->miraklHelper = $this->createMock(MiraklHelper::class);
        $this->observer = $this->getMockBuilder(Observer::class)
            ->addMethods(['getFullActionName'])
            ->onlyMethods(['getEvent'])
            ->getMock();
        $this->event = $this->getMockBuilder(Event::class)
            ->addMethods(['getLayout'])
            ->getMock();
        $this->layout = $this->createMock(LayoutInterface::class);
        $this->product = new \stdClass();
    }

    /**
     * Test that the page title is hidden when all conditions are met.
     */
    public function testExecuteHidesPageTitleWhenCanMovePageTitleToNewLocation(): void
    {
        $this->observer
            ->method('getFullActionName')
            ->willReturn('catalog_product_view');

        $this->miraklHelper
            ->method('canMovePageTitleToNewLocation')
            ->willReturn(true);

        $this->event
            ->method('getLayout')
            ->willReturn($this->layout);

        $this->observer
            ->method('getEvent')
            ->willReturn($this->event);

        $pageTitleBlock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['addData'])
            ->getMock();

        $pageTitleBlock
            ->expects($this->once())
            ->method('addData')
            ->with(['css_class' => 'hidden-m-l-xl']);

        $this->layout
            ->expects($this->once())
            ->method('getBlock')
            ->with('page.main.title')
            ->willReturn($pageTitleBlock);

        $observerInstance = new HidePageTitle(
            $this->miraklHelper
        );
        $result = $observerInstance->execute($this->observer);

        $this->assertSame($observerInstance, $result);
    }

    /**
     * Test that nothing happens when the full action name is not 'catalog_product_view'.
     */
    public function testExecuteDoesNothingWhenFullActionNameNotCatalogProductView(): void
    {
        $this->observer
            ->method('getFullActionName')
            ->willReturn('some_other_action');

        $this->miraklHelper
            ->method('canMovePageTitleToNewLocation')
            ->willReturn(true);

        $observerInstance = new HidePageTitle(
            $this->miraklHelper
        );
        $result = $observerInstance->execute($this->observer);

        $this->assertSame($observerInstance, $result);
    }

    /**
     * Test that nothing happens when canMovePageTitleToNewLocation returns false.
     */
    public function testExecuteDoesNothingWhenCannotMovePageTitleToNewLocation(): void
    {
        $this->observer
            ->method('getFullActionName')
            ->willReturn('catalog_product_view');

        $this->miraklHelper
            ->method('canMovePageTitleToNewLocation')
            ->willReturn(false);

        $observerInstance = new HidePageTitle(
            $this->miraklHelper
        );
        $result = $observerInstance->execute($this->observer);

        $this->assertSame($observerInstance, $result);
    }

    /**
     * Test that nothing happens when page title block is not found.
     */
    public function testExecuteDoesNothingWhenPageTitleBlockNotFound(): void
    {
        $this->observer
            ->method('getFullActionName')
            ->willReturn('catalog_product_view');

        $this->miraklHelper
            ->method('canMovePageTitleToNewLocation')
            ->willReturn(true);

        $this->event
            ->method('getLayout')
            ->willReturn($this->layout);

        $this->observer
            ->method('getEvent')
            ->willReturn($this->event);

        $this->layout
            ->expects($this->once())
            ->method('getBlock')
            ->with('page.main.title')
            ->willReturn(null);

        $observerInstance = new HidePageTitle(
            $this->miraklHelper
        );
        $result = $observerInstance->execute($this->observer);

        $this->assertSame($observerInstance, $result);
    }
}
