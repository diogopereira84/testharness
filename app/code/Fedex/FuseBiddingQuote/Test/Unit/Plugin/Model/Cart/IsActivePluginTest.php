<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\FuseBiddingQuote\Test\Unit\Plugin\Model\Cart;

use Fedex\FuseBiddingQuote\Plugin\Model\Cart\IsActivePlugin;
use Fedex\UploadToQuote\Helper\AdminConfigHelper as UploadToQuoteAdminConfigHelper;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Test class for IsActivePlugin
 */
class IsActivePluginTest extends TestCase
{
    protected $cartInterface;
    protected $isActivePlugin;
    /**
     * @var UploadToQuoteAdminConfigHelper $uploadToQuoteAdminConfigHelper
     */
    protected $uploadToQuoteAdminConfigHelper;

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
        $this->uploadToQuoteAdminConfigHelper = $this->getMockBuilder(UploadToQuoteAdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isQuoteNegotiated'])
            ->getMock();

        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFuseBidToggleEnabled'])
            ->getMock();

        $this->cartInterface = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getIsBid'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->isActivePlugin = $objectManagerHelper->getObject(
            IsActivePlugin::class,
            [
                'uploadToQuoteAdminConfigHelper' => $this->uploadToQuoteAdminConfigHelper,
                'fuseBidViewModel' => $this->fuseBidViewModel
            ]
        );
    }

    /**
     * Test afterExecute
     *
     * @return void
     */
    public function testAfterExecute()
    {
        $this->uploadToQuoteAdminConfigHelper->expects($this->once())->method('isQuoteNegotiated')->willReturn(true);
        $this->cartInterface->expects($this->any())->method('getIsBid')->willReturn(true);
        $this->fuseBidViewModel->expects($this->once())->method('isFuseBidToggleEnabled')->willReturn(true);

        $this->assertTrue($this->isActivePlugin->afterExecute('testSubject', false, $this->cartInterface));
    }
}
