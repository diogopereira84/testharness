<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Plugin\Checkout\Model;

use Fedex\UploadToQuote\Plugin\Checkout\Model\Cart;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\UploadToQuote\Helper\QueueHelper;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Checkout\Model\Cart as CheckoutCart;

class CartTest extends TestCase
{   
    protected $checkoutCart;
    protected $cart;
    /**
     * @var UploadToQuoteViewModel $uploadToQuoteViewModel
     */
    protected $uploadToQuoteViewModel;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var QueueHelper $queueHelper
     */
    protected $queueHelper;
    
    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->uploadToQuoteViewModel = $this->getMockBuilder(UploadToQuoteViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMarkAsDeclinedEnabled', 'isUploadToQuoteEnable'])
            ->getMock();

        $this->queueHelper = $this->getMockBuilder(QueueHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateQuoteStatusByKey'])
            ->getMock();

        $this->checkoutCart = $this->getMockBuilder(CheckoutCart::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote', 'getAllVisibleItems', 'getId'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->cart = $objectManagerHelper->getObject(
            Cart::class,
            [
                'uploadToQuoteViewModel' => $this->uploadToQuoteViewModel,
                'queueHelper' => $this->queueHelper,
                'checkoutCart' => $this->checkoutCart
            ]
        );
    }

    /**
     * Test afterRemoveItem
     *
     * @return void
     */
    public function testAfterRemoveItem()
    {
        $arrData = [];
        $this->uploadToQuoteViewModel->expects($this->once())->method('isMarkAsDeclinedEnabled')->willReturn(true);
        $this->uploadToQuoteViewModel->expects($this->once())->method('isUploadToQuoteEnable')->willReturn(true);
        $this->checkoutCart->expects($this->any())->method('getQuote')->willReturnSelf();
        $this->checkoutCart->expects($this->once())->method('getAllVisibleItems')->willReturn($arrData);

        $this->assertIsObject($this->cart->afterRemoveItem($this->checkoutCart, $this->checkoutCart));
    }

    /**
     * Test afterRemoveItem with exception
     *
     * @return void
     */
    public function testAfterRemoveItemWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->uploadToQuoteViewModel->expects($this->once())->method('isMarkAsDeclinedEnabled')->willThrowException($exception);

        $this->assertIsObject($this->cart->afterRemoveItem($this->checkoutCart, $this->checkoutCart));
    }

    /**
     * Test afterTruncate
     *
     * @return void
     */
    public function testAfterTruncate()
    {
        $this->uploadToQuoteViewModel->expects($this->once())->method('isMarkAsDeclinedEnabled')->willReturn(true);
        $this->uploadToQuoteViewModel->expects($this->once())->method('isUploadToQuoteEnable')->willReturn(true);
        $this->checkoutCart->expects($this->any())->method('getQuote')->willReturnSelf();
        $this->checkoutCart->expects($this->once())->method('getId')->willReturn(123);

        $this->assertIsObject($this->cart->afterTruncate($this->checkoutCart, $this->checkoutCart));
    }

    /**
     * Test afterTruncate with exception
     *
     * @return void
     */
    public function testAfterTruncateWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->uploadToQuoteViewModel->expects($this->once())->method('isMarkAsDeclinedEnabled')->willThrowException($exception);

        $this->assertIsObject($this->cart->afterTruncate($this->checkoutCart, $this->checkoutCart));
    }
}
