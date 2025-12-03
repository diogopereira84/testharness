<?php

use Fedex\SubmitOrderSidebar\Model\BillingAddressBuilder;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use Fedex\Recaptcha\Model\PrintfulRecaptcha;
use Fedex\Recaptcha\Logger\RecaptchaLogger;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;

class PrintfulRecaptchaTest extends TestCase
{
    private $recaptchaLogger;
    private $scopeConfig;
    private $sessionManagerInterface;
    private $checkoutSession;
    private $printfulRecaptcha;
    private $requestInterface;
    private $addressFactory;
    private $regionFactory;

    protected function setUp(): void
    {
        $this->recaptchaLogger = $this->createMock(RecaptchaLogger::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->sessionManagerInterface = $this->createMock(SessionManagerInterface::class);
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addressFactory = $this->createMock(AddressInterfaceFactory::class);
        $this->regionFactory = $this->createMock(RegionFactory::class);


        $this->printfulRecaptcha = new PrintfulRecaptcha(
            $this->recaptchaLogger,
            $this->scopeConfig,
            $this->sessionManagerInterface,
            $this->checkoutSession,
            $this->requestInterface,
            $this->addressFactory,
            $this->regionFactory
        );
    }

    public function testCheckIfQuoteIsEligibleForPrintfulTransactionBlockReturnsFalseWhenRecaptchaDisabled()
    {
        $this->scopeConfig->method('getValue')
            ->with(PrintfulRecaptcha::XML_PATH_RECAPTCHA_PRINTFUL_ENABLED)
            ->willReturn(false);

        $this->checkoutSession->method('hasQuote')
            ->willReturn(true);

        $this->assertFalse($this->printfulRecaptcha->checkIfQuoteIsEligibleForPrintfulTransactionBlock());
    }

    public function testCheckIfQuoteIsEligibleForPrintfulTransactionBlockReturnsFalseWhenNoQuote()
    {
        $this->scopeConfig->method('getValue')
            ->with(PrintfulRecaptcha::XML_PATH_RECAPTCHA_PRINTFUL_ENABLED)
            ->willReturn(true);

        $this->checkoutSession->method('hasQuote')
            ->willReturn(false);

        $this->assertFalse($this->printfulRecaptcha->checkIfQuoteIsEligibleForPrintfulTransactionBlock());
    }

    public function testCheckIfQuoteIsEligibleForPrintfulTransactionBlockReturnsFalseWhenAddressesMatch()
    {
        $this->scopeConfig->method('getValue')
            ->willReturnMap(
            [
                [PrintfulRecaptcha::XML_PATH_RECAPTCHA_PRINTFUL_ENABLED, ScopeInterface::SCOPE_STORE, null, true],
                [PrintfulRecaptcha::XML_PATH_RECAPTCHA_PRINTFUL_LINE_QTY, ScopeInterface::SCOPE_STORE, null, 1],
            ]
        );

        $this->requestInterface
            ->method('getPost')
            ->willReturn('{"data": {"paymentData": "paymentData"}}');

        $quote = $this->createMock(Quote::class);
        $this->checkoutSession->method('getQuote')
            ->willReturn($quote);
        $this->checkoutSession->method('hasQuote')
            ->willReturn(true);

        $quote->method('getShippingAddress')
            ->willReturn($this->createAddress('Region1', 'City1', 'Postcode1', 'Street1'));
        $quote->method('getBillingAddress')
            ->willReturn($this->createAddress('Region1', 'City1', 'Postcode1-123', 'Street1'));

        $this->assertFalse($this->printfulRecaptcha->checkIfQuoteIsEligibleForPrintfulTransactionBlock());
    }

    public function testCheckIfQuoteIsEligibleForPrintfulTransactionBlockReturnsFalseWhenItemsDoNotMatch()
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            [PrintfulRecaptcha::XML_PATH_RECAPTCHA_PRINTFUL_ENABLED, ScopeInterface::SCOPE_STORE, null, true],
            [PrintfulRecaptcha::XML_PATH_RECAPTCHA_PRINTFUL_LINE_QTY, ScopeInterface::SCOPE_STORE, null, 2],
        ]);

        $this->checkoutSession->method('hasQuote')
            ->willReturn(true);

        $quote = $this->createMock(Quote::class);
        $this->checkoutSession->method('getQuote')
            ->willReturn($quote);

        $quote->method('getShippingAddress')
            ->willReturn($this->createAddress('Region1', 'City1', 'Postcode1', 'Street1'));
        $quote->method('getBillingAddress')
            ->willReturn($this->createAddress('Region2', 'City2', 'Postcode2', 'Street2'));

        $item = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMiraklShopName', 'getQty'])
            ->getMock();
        $item->method('getMiraklShopName')
            ->willReturn('OtherStore');
        $item->method('getQty')
            ->willReturn(1);

        $quote->method('getAllVisibleItems')
            ->willReturn([$item]);

        $this->assertFalse($this->printfulRecaptcha->checkIfQuoteIsEligibleForPrintfulTransactionBlock());
    }

    public function testCheckIfQuoteIsEligibleForPrintfulTransactionBlockReturnsTrueWhenAllConditionsMatch()
    {
        $paymentData = '{"paymentData":"{\"isBillingAddress\":true,\"billingAddress\":{\"state\":\"VA\",\"company\":\"\",\"address\":\"8229 Boone Boulevard\",\"addressTwo\":\"difference\",\"city\":\"Vienna\",\"zip\":\"22182\"}}"}';
        $billingData = (object)json_decode('{"isBillingAddress":true,"billingAddress":{"state":"VA","company":"","address":"8229 Boone Boulevard","addressTwo":"difference","city":"Vienna","zip":"22182"}}');
        $this->scopeConfig->method('getValue')
            ->withConsecutive(
                [PrintfulRecaptcha::XML_PATH_RECAPTCHA_PRINTFUL_ENABLED],
                [PrintfulRecaptcha::XML_PATH_RECAPTCHA_PRINTFUL_LINE_QTY]
            )
            ->willReturnOnConsecutiveCalls(true, 1);

        $this->checkoutSession->method('hasQuote')
            ->willReturn(true);

        $quote = $this->createMock(Quote::class);
        $this->checkoutSession->method('getQuote')
            ->willReturn($quote);

        $this->requestInterface->method('getPost')
            ->with('data')
            ->willReturn($paymentData);

        $address = $this->getMockBuilder(AddressInterface::class)
            ->addMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addressFactory->method('create')->willReturn($address);

        $quote->method('getShippingAddress')
            ->willReturn($this->createAddress('Region1', 'City1', 'Postcode1', 'Street1'));
        $quote->method('getBillingAddress')
            ->willReturn($this->createAddress('Region2', 'City2', 'Postcode2', 'Street2'));

        $item = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMiraklShopName', 'getQty'])
            ->getMock();
        $item->method('getMiraklShopName')
            ->willReturn(PrintfulRecaptcha::PRINTFUL_STORE_NAME);
        $item->method('getQty')
            ->willReturn(1);

        $quote->method('getAllVisibleItems')
            ->willReturn([$item]);

        $this->assertTrue($this->printfulRecaptcha->checkIfQuoteIsEligibleForPrintfulTransactionBlock());
    }

    private function createAddress($region, $city, $postcode, $street)
    {
        $address = $this->createMock(Address::class);
        $address->method('getRegionId')->willReturn($region);
        $address->method('getCity')->willReturn($city);
        $address->method('getPostcode')->willReturn($postcode);
        $address->method('getStreetFull')->willReturn($street);
        return $address;
    }

    public function testIsPrintfulRecaptchaTransactionBlockEnabled()
    {
        $this->scopeConfig->method('getValue')
            ->with(PrintfulRecaptcha::XML_PATH_RECAPTCHA_PRINTFUL_ENABLED)
            ->willReturn(true);

        $this->assertTrue($this->printfulRecaptcha->isPrintfulRecaptchaTransactionBlockEnabled());
    }

    public function testIsPrintfulRecaptchaTransactionBlockLineQty()
    {
        $this->scopeConfig->method('getValue')
            ->with(PrintfulRecaptcha::XML_PATH_RECAPTCHA_PRINTFUL_LINE_QTY)
            ->willReturn(5);

        $this->assertEquals(5, $this->printfulRecaptcha->isPrintfulRecaptchaTransactionBlockLineQty());
    }

    public function testIsPrintfulRecaptchaTransactionBlockThreshold()
    {
        $this->scopeConfig->method('getValue')
            ->with(PrintfulRecaptcha::XML_PATH_RECAPTCHA_PRINTFUL_SCORE_THRESHOLD)
            ->willReturn(0.5);

        $this->assertEquals(0.5, $this->printfulRecaptcha->isPrintfulRecaptchaTransactionBlockThreshold());
    }

    public function testBuildBillingAddress()
    {
        $paymentData = new \stdClass();
        $paymentData->billingAddress = new \stdClass();
        $paymentData->billingAddress->regionCode = 'CA';
        $paymentData->billingAddress->address = '123 Main St';
        $paymentData->billingAddress->addressTwo = 'Apt 4B';
        $paymentData->billingAddress->city = 'Los Angeles';
        $paymentData->billingAddress->postcode = '90001';

        $quote = $this->createMock(Quote::class);
        $quote->method('getBillingAddress')->willReturn($this->createMock(AddressInterface::class));

        $region = $this->getMockBuilder(Region::class)
            ->onlyMethods(['getName', 'loadByCode'])
            ->addMethods(['getRegionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $region->method('getName')->willReturn('California');
        $region->method('getRegionId')->willReturn(12);
        $region->method('loadByCode')->willReturn($region);
        $this->regionFactory->method('create')->willReturn($region);

        $address = $this->getMockBuilder(AddressInterface::class)
            ->addMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addressFactory->method('create')->willReturn($address);

        $address->expects($this->once())->method('setData')->with([
            'region' => 'California',
            'region_id' => 12,
            'street' => ['123 Main St', 'Apt 4B'],
            'city' => 'Los Angeles',
            'postcode' => '90001'
        ]);

        $result = $this->printfulRecaptcha->buildBillingAddress($paymentData, $quote);
        $this->assertSame($address, $result);
    }

    public function testClearAddressZipCode()
    {
        $address = $this->createMock(\Magento\Quote\Api\Data\AddressInterface::class);
        $address->method('getPostcode')->willReturn('12345-6789');
        $this->assertEquals('12345', $this->printfulRecaptcha->clearAddressZipCode($address));

        $address = $this->createMock(\Magento\Quote\Api\Data\AddressInterface::class);
        $address->method('getPostcode')->willReturn('12345');
        $this->assertEquals('12345', $this->printfulRecaptcha->clearAddressZipCode($address));

        $address = $this->createMock(\Magento\Quote\Api\Data\AddressInterface::class);
        $address->method('getPostcode')->willReturn('');
        $this->assertEquals('', $this->printfulRecaptcha->clearAddressZipCode($address));

        $address = $this->createMock(\Magento\Quote\Api\Data\AddressInterface::class);
        $address->method('getPostcode')->willReturn(null);
        $this->assertNull($this->printfulRecaptcha->clearAddressZipCode($address));
    }
}
