<?php

/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Test\Unit;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Magento\Sales\Block\Order\History as BlockHistory;
use Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Block\Order\Info;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class InfoTest extends \PHPUnit\Framework\TestCase
{
    protected $shipToHelperMock;
    protected $httpRequestMock;
    protected $orderMock;
    protected $orderAddressMock;
    protected $orderInfoMock;
    protected $orderPaymentInterface;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $orderInfo;
    /**
     * @var \Fedex\Orderhistory\Helper\Data $helper
     */
    protected $helper;

    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(Data::class)
            ->setMethods(['isModuleEnabled', 'isEnhancementEnabeled', 'getQuoteById', 'isShipppingRecipientEnabled', 'formatAddress', 'isPrintReceiptRetail'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->shipToHelperMock = $this->getMockBuilder(\Fedex\Shipto\Helper\Data::class)
            ->setMethods(['getAddressByLocationId', 'formatAddress'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->httpRequestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->setMethods(['getFullActionName', 'getQuoteId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingMethod', 'getShippingDescription', 'getPickupAddress', 'getQuoteId', 'getShippingAddress', 'getData', 'getBillingAddress', 'getPayment'])
            ->getMock();

        $this->orderAddressMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAddressType'])
            ->getMock();

        $this->orderInfoMock = $this->getMockBuilder(\Magento\Sales\Block\Order\Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder'])
            ->getMock();
        $this->orderPaymentInterface = $this->getMockBuilder(OrderPaymentInterface::class)
            ->setMethods(['getSiteConfiguredPaymentUsed'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->orderInfo = $this->objectManager->getObject(
            Info::class,
            [
                'helper' => $this->helper,
                'shipToHelper' => $this->shipToHelperMock,
                'request' => $this->httpRequestMock
            ]
        );
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testAfterGetFormattedAddress()
    {
        $tempAddress = [];
        $tempAddress['address'] = '{"Id":"0999","address":{"address1":"1548 Weston Rd","address2":"","city":"Weston","stateOrProvinceCode":"FL","postalCode":"33326","countryCode":"US","addressType":""},"name":"Weston FL","phone":"9543491682","email":"usa0999@fedex.com","locationType":"OFFICE_PRINT","available":true,"availabilityReason":"AVAILABLE","pickupEnabled":true,"geoCode":{"latitude":"26.099526","longitude":"-80.36608"}}';
        $tempAddress['success'] = 1;

        $htmlAddress = 'Weston FL<br>1548 Weston Rd <br>Weston, Florida, 33326<br>United States<br>T: <a href="tel:9543491682">9543491682</a>';

        $this->orderInfoMock->expects($this->any())->method('getOrder')->willReturn($this->orderMock);

        $this->httpRequestMock->expects($this->any())->method('getFullActionName')->willReturn('sales_order_print');
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->helper->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);
        $this->helper->expects($this->any())->method('isEnhancementEnabeled')->willReturn(true);
        $this->helper->expects($this->any())->method('formatAddress')->willReturn('string');

        $this->orderMock->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->orderMock->expects($this->any())->method('getShippingDescription')->willReturn('1234');
        $this->orderAddressMock->expects($this->any())->method('getAddressType')->willReturn('shipping');
        $this->shipToHelperMock->expects($this->any())->method('getAddressByLocationId')->willReturn($tempAddress);
        $this->shipToHelperMock->expects($this->any())->method('formatAddress')->willReturn($htmlAddress);
        $this->orderMock->expects($this->any())->method('getPickupAddress')->willReturn(null);

        $shippingAddressArray = ['country_id' => 'US', 'firstname' => 'test', 'lastname' => 'test', 'street' => 'test', 'city' => 'New York', 'region' => 'NY', 'postcode' => '10001', 'email' => 'test@test.com', 'telephone' => '123456778'];

        $this->orderMock->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('getData')->willReturn($shippingAddressArray);
        $this->orderAddressMock->expects($this->any())->method('getAddressType')->willReturn('billing');
        $billingAddressArray = ['country_id' => 'US', 'street' => 'test', 'city' => 'New York', 'region' => 'NY', 'postcode' => '10001'];
        $this->orderMock->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('getData')->willReturn($billingAddressArray);

        $exectedResult = $this->orderInfo
            ->afterGetFormattedAddress(
                $this->orderInfoMock,
                $this->orderInfoMock,
                $this->orderAddressMock
            );
        $this->assertIsString($exectedResult);
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testAfterGetFormattedAddressElsePickup()
    {
        $tempAddress = [];
        $tempAddress['address'] = '{"Id":"0999","address":{"address1":"1548 Weston Rd","address2":"","city":"Weston","stateOrProvinceCode":"FL","postalCode":"33326","countryCode":"US","addressType":""},"name":"Weston FL","phone":"9543491682","email":"usa0999@fedex.com","locationType":"OFFICE_PRINT","available":true,"availabilityReason":"AVAILABLE","pickupEnabled":true,"geoCode":{"latitude":"26.099526","longitude":"-80.36608"}}';
        $tempAddress['success'] = 1;

        $htmlAddress = 'Weston FL<br>1548 Weston Rd <br>Weston, Florida, 33326<br>United States<br>T: <a href="tel:9543491682">9543491682</a>';

        $this->orderInfoMock->expects($this->any())->method('getOrder')->willReturn($this->orderMock);

        $this->httpRequestMock->expects($this->any())->method('getFullActionName')->willReturn('sales_order_print');
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->helper->expects($this->any())->method('isEnhancementEnabeled')->willReturn(false);
        $this->helper->expects($this->any())->method('formatAddress')->willReturn('string');

        $this->orderMock->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->orderMock->expects($this->any())->method('getShippingDescription')->willReturn('1234');
        $this->orderAddressMock->expects($this->any())->method('getAddressType')->willReturn('shipping');
        $this->shipToHelperMock->expects($this->any())->method('getAddressByLocationId')->willReturn($tempAddress);
        $this->shipToHelperMock->expects($this->any())->method('formatAddress')->willReturn($htmlAddress);
        $this->orderMock->expects($this->any())->method('getPickupAddress')->willReturn(null);

        $shippingAddressArray = ['country_id' => 'US', 'firstname' => 'test', 'lastname' => 'test', 'street' => 'test', 'city' => 'New York', 'region' => 'NY', 'postcode' => '10001', 'email' => 'test@test.com', 'telephone' => '123456778'];

        $this->orderMock->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('getData')->willReturn($shippingAddressArray);
        $this->orderAddressMock->expects($this->any())->method('getAddressType')->willReturn('billing');
        $billingAddressArray = ['country_id' => 'US', 'street' => 'test', 'city' => 'New York', 'region' => 'NY', 'postcode' => '10001'];

        $this->orderMock->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('getData')->willReturn($billingAddressArray);

        $exectedResult = $this->orderInfo
            ->afterGetFormattedAddress(
                $this->orderInfoMock,
                $this->orderInfoMock,
                $this->orderAddressMock
            );
        $this->assertIsString($exectedResult);
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testAfterGetFormattedAddressisShipppingRecipientEnabled()
    {
        $tempAddress = [];
        $tempAddress['address'] = '{"Id":"0999","address":{"address1":"1548 Weston Rd","address2":"","city":"Weston","stateOrProvinceCode":"FL","postalCode":"33326","countryCode":"US","addressType":""},"name":"Weston FL","phone":"9543491682","email":"usa0999@fedex.com","locationType":"OFFICE_PRINT","available":true,"availabilityReason":"AVAILABLE","pickupEnabled":true,"geoCode":{"latitude":"26.099526","longitude":"-80.36608"}}';
        $tempAddress['success'] = 1;

        $htmlAddress = 'Weston FL<br>1548 Weston Rd <br>Weston, Florida, 33326<br>United States<br>T: <a href="tel:9543491682">9543491682</a>';

        $this->orderInfoMock->expects($this->any())->method('getOrder')->willReturn($this->orderMock);

        $this->httpRequestMock->expects($this->any())->method('getFullActionName')->willReturn('sales_order_print');
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->helper->expects($this->any())->method('isEnhancementEnabeled')->willReturn(true);
        $this->helper->expects($this->any())->method('formatAddress')->willReturn('string');
        $this->helper->expects($this->any())->method('isShipppingRecipientEnabled')->willReturn(true);

        $this->orderMock->expects($this->any())->method('getShippingMethod')->willReturn('test');
        $this->orderMock->expects($this->any())->method('getShippingDescription')->willReturn('1234');
        $this->orderAddressMock->expects($this->any())->method('getAddressType')->willReturn('shipping');
        $this->shipToHelperMock->expects($this->any())->method('getAddressByLocationId')->willReturn($tempAddress);
        $this->shipToHelperMock->expects($this->any())->method('formatAddress')->willReturn($htmlAddress);
        $this->orderMock->expects($this->any())->method('getPickupAddress')->willReturn(null);

        $shippingAddressArray = ['country_id' => 'US', 'firstname' => 'test', 'lastname' => 'test', 'street' => 'test', 'city' => 'New York', 'region' => 'NY', 'postcode' => '10001', 'email' => 'test@test.com', 'telephone' => '123456778'];

        $this->orderMock->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('getData')->willReturn($shippingAddressArray);
        $quoteId = $this->orderMock->expects($this->any())->method('getQuoteId')->willReturn('0999');
        $this->helper->expects($this->any())->method('getQuoteById')->willReturn($this->orderMock);
        $this->orderMock->expects($this->any())->method('getShippingAddress')->willReturnSelf();

        $exectedResult = $this->orderInfo
            ->afterGetFormattedAddress(
                $this->orderInfoMock,
                $this->orderInfoMock,
                $this->orderAddressMock
            );
        $this->assertIsString($exectedResult);
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testAfterGetFormattedAddresswithBillingMethod()
    {
        $tempAddress = [];
        $tempAddress['address'] = '{"Id":"0999","address":{"address1":"1548 Weston Rd","address2":"","city":"Weston","stateOrProvinceCode":"FL","postalCode":"33326","countryCode":"US","addressType":""},"name":"Weston FL","phone":"9543491682","email":"usa0999@fedex.com","locationType":"OFFICE_PRINT","available":true,"availabilityReason":"AVAILABLE","pickupEnabled":true,"geoCode":{"latitude":"26.099526","longitude":"-80.36608"}}';
        $tempAddress['success'] = 1;

        $htmlAddress = 'Weston FL<br>1548 Weston Rd <br>Weston, Florida, 33326<br>United States<br>T: <a href="tel:9543491682">9543491682</a>';

        $this->orderInfoMock->expects($this->any())->method('getOrder')->willReturn($this->orderMock);

        $this->httpRequestMock->expects($this->any())->method('getFullActionName')->willReturn('sales_order_print');
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->helper->expects($this->any())->method('isEnhancementEnabeled')->willReturn(true);
        $this->helper->expects($this->any())->method('formatAddress')->willReturn('string');
        $this->helper->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);

        $this->orderAddressMock->expects($this->any())->method('getAddressType')->willReturn('billing');
        $billingAddressArray = ['country_id' => 'US', 'street' => 'test', 'city' => 'New York', 'region' => 'NY', 'postcode' => '10001'];

        $this->orderMock->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('getSiteConfiguredPaymentUsed')->willReturn(1);
        $this->orderMock->expects($this->any())->method('getData')->willReturn($billingAddressArray);

        $exectedResult = $this->orderInfo
            ->afterGetFormattedAddress(
                $this->orderInfoMock,
                $this->orderInfoMock,
                $this->orderAddressMock
            );
        $this->assertIsString($exectedResult);
    }
    /**
     *  test case for afterGetFormattedAddress
     */
    public function testAfterGetFormattedAddresswithBillingMethodWithSitePaymentFalse()
    {
        $tempAddress = [];
        $tempAddress['address'] = '{"Id":"0999","address":{"address1":"1548 Weston Rd","address2":"","city":"Weston","stateOrProvinceCode":"FL","postalCode":"33326","countryCode":"US","addressType":""},"name":"Weston FL","phone":"9543491682","email":"usa0999@fedex.com","locationType":"OFFICE_PRINT","available":true,"availabilityReason":"AVAILABLE","pickupEnabled":true,"geoCode":{"latitude":"26.099526","longitude":"-80.36608"}}';
        $tempAddress['success'] = 1;

        $htmlAddress = 'Weston FL<br>1548 Weston Rd <br>Weston, Florida, 33326<br>United States<br>T: <a href="tel:9543491682">9543491682</a>';

        $this->orderInfoMock->expects($this->any())->method('getOrder')->willReturn($this->orderMock);

        $this->httpRequestMock->expects($this->any())->method('getFullActionName')->willReturn('sales_order_print');
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->helper->expects($this->any())->method('isEnhancementEnabeled')->willReturn(true);
        $this->helper->expects($this->any())->method('formatAddress')->willReturn('string');
        $this->helper->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);

        $this->orderAddressMock->expects($this->any())->method('getAddressType')->willReturn('billing');
        $billingAddressArray = ['country_id' => 'US', 'street' => 'test', 'city' => 'New York', 'region' => 'NY', 'postcode' => '10001'];

        $this->orderMock->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->any())->method('getSiteConfiguredPaymentUsed')->willReturn(0);
        $this->orderMock->expects($this->any())->method('getData')->willReturn($billingAddressArray);

        $exectedResult = $this->orderInfo
            ->afterGetFormattedAddress(
                $this->orderInfoMock,
                $this->orderInfoMock,
                $this->orderAddressMock
            );
        $this->assertIsString($exectedResult);
    }
}
