<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
declare(strict_types=1);

namespace Fedex\Cart\Plugin;

use Fedex\Cart\Plugin\CartPlugin;
use Fedex\Cart\Helper\Data;
use Magento\Checkout\CustomerData\Cart;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel;

class CartPluginTest extends TestCase
{
    protected $dataHelperMock;
    protected $customerCartDataMock;
    /**
     * @var (\Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $handleMktCheckoutMock;
    /**
     * @var (\Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $CheckProductAvailabilityDataModelMock;
    protected $cartPlugin;
    /**
     * Setup
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerCartDataMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handleMktCheckoutMock = $this->getMockBuilder(HandleMktCheckout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->CheckProductAvailabilityDataModelMock = $this->getMockBuilder(CheckProductAvailabilityDataModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->cartPlugin = $objectManager->getObject(
            CartPlugin::class,
            [
                'dataHelper' => $this->dataHelperMock,
                'handleMktCheckout' => $this->handleMktCheckoutMock,
                'CheckProductAvailabilityDataModelMock' =>  $this->CheckProductAvailabilityDataModelMock,
            ]
        );
    }

    /**
     * Test for afterGetSectionData()
     *
     */
    public function testAfterGetSectionData()
    {
        $maxCartItemLimit = 3;
        $minCartItemThreshold = 1;

        $result = [
            'cartThresholdLimit' => (int)$minCartItemThreshold,
            'maxCartLimit' => (int)$maxCartItemLimit,
        ];

        $cartLimit = [
            'maxCartItemLimit' => $maxCartItemLimit,
            'minCartItemThreshold' => $minCartItemThreshold
        ];

        $this->dataHelperMock->expects($this->once())->method('getMaxCartLimitValue')->willReturn($cartLimit);

        $this->cartPlugin->afterGetSectionData($this->customerCartDataMock, $result);
    }
}
