<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\Orderhistory\Test\Unit\ViewModel;

use Fedex\Orderhistory\ViewModel\OrderView;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Test class for OrderViewTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class OrderViewTest extends TestCase
{
    protected $sdeHelperMock;
    protected $orderMock;
    protected $shipmentCollectionMock;
    protected $shipmentMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $orderViewMock;
    /**
     * Test Set up
     */
    protected function setUp(): void
    {
        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore', 'getSdeMaskSecureImagePath'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->setMethods(['getShipmentsCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentCollectionMock = $this->getMockBuilder(Collection::class)
            ->setMethods(['getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentMock = $this->getMockBuilder(Shipment::class)
            ->setMethods(['getShippingAccountNumber'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->orderViewMock = $this->objectManager->getObject(
            OrderView::class,
            [
                'sdeHelper' => $this->sdeHelperMock
            ]
        );
    }

    /**
     * @test testGetIsSdeStore
     */
    public function testGetIsSdeStore()
    {
        $isSdeStore = true;
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn($isSdeStore);
        $this->assertEquals($isSdeStore, $this->orderViewMock->getIsSdeStore());
    }

    /**
     *  @test testGetSdeMaskSecureImagePath
     */
    public function testGetSdeMaskSecureImagePath()
    {
        $maskImageUrl = 'test-url';
        $this->sdeHelperMock->expects($this->any())->method('getSdeMaskSecureImagePath')->willReturn($maskImageUrl);
        $this->assertEquals($maskImageUrl, $this->orderViewMock->getSdeMaskSecureImagePath());
    }

    /**
     * @test testGetShippingAccountNumberFromOrderWithValue
     */
    public function testGetShippingAccountNumberFromOrderWithValue()
    {
        $shippingAccountNumber = '1234';
        $this->orderMock->expects($this->any())->method('getShipmentsCollection')
        ->willReturn($this->shipmentCollectionMock);

        $this->shipmentCollectionMock->expects($this->any())->method('getFirstItem')->willReturn($this->shipmentMock);
        $this->shipmentMock->expects($this->any())->method('getShippingAccountNumber')
        ->willReturn($shippingAccountNumber);

        $this->assertEquals(
            $shippingAccountNumber,
            $this->orderViewMock->getShippingAccountNumberFromOrder($this->orderMock)
        );
    }

    /**
     * @test testGetShippingAccountNumberFromOrderWithoutValue
     */
    public function testGetShippingAccountNumberFromOrderWithoutValue()
    {
        $shippingAccountNumber = '';
        $this->orderMock->expects($this->any())->method('getShipmentsCollection')
        ->willReturn($this->shipmentCollectionMock);
        $this->shipmentCollectionMock->expects($this->any())->method('getFirstItem')->willReturn(null);

        $this->assertEquals(
            $shippingAccountNumber,
            $this->orderViewMock->getShippingAccountNumberFromOrder($this->orderMock)
        );
    }

    /**
     * Assert getSortedDiscounts
     *
     * @return bool
     */
    public function testGetSortedDiscounts()
    {
        $data = [
            ['label'=>"Account Discount",'price'=>1.00],
            ['label'=>"Volume Discount",'price'=>2.00],
            ['label'=>"Promo Discount",'price'=>3.00]
        ];
        $expecteddata = [
            ['label'=>"Promo Discount",'price'=>3],
            ['label'=>"Volume Discount",'price'=>2],
            ['label'=>"Account Discount",'price'=>1]
        ];

        $this->assertEquals($expecteddata, $this->orderViewMock->getSortedDiscounts($data));
    }
}
