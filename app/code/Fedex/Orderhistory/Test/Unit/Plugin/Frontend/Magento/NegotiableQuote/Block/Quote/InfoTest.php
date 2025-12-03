<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Test\Unit\Plugin\Frontend\Magento\NegotiableQuote\Block\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Magento\Sales\Block\Order\History as BlockHistory;
use Fedex\Orderhistory\Plugin\Frontend\Magento\NegotiableQuote\Block\Quote\Info;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

class InfoTest extends \PHPUnit\Framework\TestCase
{
   protected $shipToHelperMock;
    protected $httpRequestMock;
    protected $regionMock;
    protected $countryMock;
    protected $quoteMock;
    protected $quoteAddressMock;
    protected $quoteInfoMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
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
                                        ->setMethods(['isModuleEnabled'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->shipToHelperMock = $this->getMockBuilder(\Fedex\Shipto\Helper\Data::class)
                                        ->setMethods(['getAddressByLocationId', 'formatAddress'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->httpRequestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
                                        ->setMethods(['getFullActionName'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->regionMock = $this->getMockBuilder(\Magento\Directory\Model\Region::class)
                                        ->setMethods(['loadByCode', 'getName'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
                                        
        $this->countryMock = $this->getMockBuilder(\Magento\Directory\Model\Country::class)
                                        ->setMethods(['loadByCode', 'getName'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
                                        
        $this->quoteMock = $this->getMockBuilder(\Magento\Sales\Model\Quote::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods(['getShippingAddress','getBillingAddress'])
                                        ->getMock();

        $this->quoteAddressMock = $this->getMockBuilder(\Magento\Sales\Model\Quote\Address::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods(['getShippingMethod', 'getShippingDescription', 'getPickupAddress'])
                                        ->getMock();
                                        
        $this->quoteInfoMock = $this->getMockBuilder(\Magento\NegotiableQuote\Block\Quote\Info::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods(['getQuote'])
                                        ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
			->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->orderInfo = $this->objectManager->getObject(
            Info::class,
            [
                'helper' => $this->helper,
                'shipToHelper' => $this->shipToHelperMock,
                'request' => $this->httpRequestMock,
                'region' => $this->regionMock,
                'country' => $this->countryMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testAfterGetAddressHtml()
    {
        $tempAddress = [];
        $tempAddress['address'] = '{"Id":"0999","address":{"address1":"1548 Weston Rd","address2":"","city":"Weston","stateOrProvinceCode":"FL","postalCode":"33326","countryCode":"US","addressType":""},"name":"Weston FL","phone":"9543491682","email":"usa0999@fedex.com","locationType":"OFFICE_PRINT","available":true,"availabilityReason":"AVAILABLE","pickupEnabled":true,"geoCode":{"latitude":"26.099526","longitude":"-80.36608"}}';
        $tempAddress['success'] = 1;
        
        $htmlAddress = 'Weston FL<br>1548 Weston Rd <br>Weston, Florida, 33326<br>United States<br>T: <a href="tel:9543491682">9543491682</a>';
         
        $this->quoteInfoMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        
        $this->httpRequestMock->expects($this->any())->method('getFullActionName')->willReturn('negotiable_quote_quote_view');
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        
        $this->quoteMock->expects($this->any())->method('getBillingAddress')->willReturn($this->quoteAddressMock);

        $this->quoteAddressMock->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->quoteAddressMock->expects($this->any())->method('getShippingDescription')->willReturn('1234');
        $this->quoteAddressMock->expects($this->any())->method('getPickupAddress')->willReturn($tempAddress['address']);
         
        $this->regionMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('getName')->willReturn('Test NY');
        
        $this->countryMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->countryMock->expects($this->any())->method('getName')->willReturn('US');
        
        $exectedResult = $this->orderInfo
                            ->afterGetAddressHtml(
                                $this->quoteInfoMock,
                                $this->quoteInfoMock
                            );
                       
       $this->assertEquals(null, $exectedResult);
    }

    /**
     *  test case for afterGetFormattedAddressFromApi
     */
    public function testAfterGetAddressHtmlFromApi()
    {
        $tempAddress = [];
        $tempAddress['address'] = '{"Id":"0999","address":{"address1":"1548 Weston Rd","address2":"","city":"Weston","stateOrProvinceCode":"FL","postalCode":"33326","countryCode":"US","addressType":""},"name":"Weston FL","phone":"9543491682","email":"usa0999@fedex.com","locationType":"OFFICE_PRINT","available":true,"availabilityReason":"AVAILABLE","pickupEnabled":true,"geoCode":{"latitude":"26.099526","longitude":"-80.36608"}}';
        $tempAddress['success'] = 1;
        
        $htmlAddress = 'Weston FL<br>1548 Weston Rd <br>Weston, Florida, 33326<br>United States<br>T: <a href="tel:9543491682">9543491682</a>';
         
        $this->quoteInfoMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        
        $this->httpRequestMock->expects($this->any())->method('getFullActionName')->willReturn('negotiable_quote_quote_view');
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        
        $this->quoteMock->expects($this->any())->method('getBillingAddress')->willReturn($this->quoteAddressMock);

        $this->quoteAddressMock->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        
        $this->quoteAddressMock->expects($this->any())->method('getShippingDescription')->willReturn('1234');
        $this->quoteAddressMock->expects($this->any())->method('getPickupAddress')->willReturn('');
        
        $this->shipToHelperMock->expects($this->any())->method('getAddressByLocationId')->willReturn($tempAddress);
        $this->shipToHelperMock->expects($this->any())->method('formatAddress')->willReturn($htmlAddress);
         
        $this->regionMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('getName')->willReturn('Test NY');
        
        $this->countryMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->countryMock->expects($this->any())->method('getName')->willReturn('US');
        
        $exectedResult = $this->orderInfo
                            ->afterGetAddressHtml(
                                $this->quoteInfoMock,
                                $this->quoteInfoMock
                            );
                            
       $this->assertIsString($exectedResult);
    }

    /**
     *  test case for afterGetFormattedAddress with exception
     */
    public function testAfterGetAddressHtmlWithException()
    {
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception($phrase);

        $tempAddress = [];
        $tempAddress['address'] = '{"Id":"0999","address":{"address1":"1548 Weston Rd","address2":"","city":"Weston","stateOrProvinceCode":"FL","postalCode":"33326","countryCode":"US","addressType":""},"name":"Weston FL","phone":"9543491682","email":"usa0999@fedex.com","locationType":"OFFICE_PRINT","available":true,"availabilityReason":"AVAILABLE","pickupEnabled":true,"geoCode":{"latitude":"26.099526","longitude":"-80.36608"}}';
        $tempAddress['success'] = 1;
        
        $htmlAddress = 'Weston FL<br>1548 Weston Rd <br>Weston, Florida, 33326<br>United States<br>T: <a href="tel:9543491682">9543491682</a>';
         
        
        $this->quoteInfoMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        
        $this->httpRequestMock->expects($this->any())->method('getFullActionName')->willReturn('negotiable_quote_quote_view');
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        
        $this->quoteMock->expects($this->any())->method('getBillingAddress')->willReturn($this->quoteAddressMock);

        $this->quoteAddressMock->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->quoteAddressMock->expects($this->any())->method('getShippingDescription')->willReturn('1234');
        $this->quoteAddressMock->expects($this->any())->method('getPickupAddress')->willThrowException($exception);
        
        $this->shipToHelperMock->expects($this->any())->method('getAddressByLocationId')->willThrowException($exception);
       
        $exectedResult = $this->orderInfo
                            ->afterGetAddressHtml(
                                $this->quoteInfoMock,
                                $this->quoteInfoMock
                            );
                            
        $this->assertEquals($this->quoteInfoMock, $exectedResult);
    }
}
