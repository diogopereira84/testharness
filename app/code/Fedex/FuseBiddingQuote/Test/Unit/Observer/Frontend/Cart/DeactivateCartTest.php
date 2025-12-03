<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\FuseBiddingQuote\Test\Unit\Observer\Frontend\Cart;

use Fedex\FuseBiddingQuote\Observer\Frontend\Cart\DeactivateCart;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Request\Http;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Test class for DeactivateCart Observer
 */
class DeactivateCartTest extends TestCase
{
    protected $observer;
    protected $objDeactivateCart;
    /**
     * @var Http $request
     */
    protected $request;

    /**
     * @var FuseBidViewModel $fuseBidViewModel
     */
    protected $fuseBidViewModel;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getModuleName', 'getControllerName', 'isXmlHttpRequest'])
            ->getMock();

        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFuseBidToggleEnabled', 'deactivateQuote'])
            ->getMock();

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->objDeactivateCart = $objectManagerHelper->getObject(
            DeactivateCart::class,
            [
                'request' => $this->request,
                'fuseBidViewModel' => $this->fuseBidViewModel
            ]
        );
    }

    /**
     * Test afterExecute
     *
     * @return void
     */
    public function testExecute()
    {
        $this->request->expects($this->once())->method('getModuleName')->willReturn('cms');
        $this->request->expects($this->once())->method('getControllerName')->willReturn('index');
        $this->request->expects($this->once())->method('isXmlHttpRequest')->willReturn(false);
        $this->fuseBidViewModel->expects($this->once())->method('isFuseBidToggleEnabled')->willReturn(true);
        $this->fuseBidViewModel->expects($this->once())->method('deactivateQuote')->willReturn(NULL);

        $this->assertNULL($this->objDeactivateCart->execute($this->observer));
    }
}
