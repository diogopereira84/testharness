<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\ViewModel;

use Magento\Framework\View\Asset\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
use Fedex\EnhancedProfile\ViewModel\CompanyPaymentData;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\Company\Api\Data\CompanyInterface;

class CompanyPaymentDataTest extends TestCase
{
    protected $jsonMock;
    protected $additionalDataFactory;
    protected $additionalData;
    protected $additionalDataCollection;
    protected $companyMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    private $logger;
    private $companyHelper;
    private $enhancedProfile;
    private $companyPaymentData;

    /**
     * @var Repository|MockObject
     */
    protected $repositoryMock;

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyHelper = $this->getMockBuilder(CompanyHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->enhancedProfile = $this->getMockBuilder(EnhancedProfile::class)
            ->setMethods(['getRegionsOfCountry', 'getTokenIsExpired', 'getMediaUrl'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->setMethods(['unserialize', 'serialize'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->additionalDataFactory = $this->getMockBuilder(AdditionalDataFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->additionalData = $this->getMockBuilder(AdditionalData::class)
            ->setMethods([
                'getStoreViewId',
                'getCollection',
                'getNewStoreViewId',
                'isEmpty',
                'getRegionsOfCountry',
                'getTokenIsExpired',
                'getCcToken',
                'getCcData',
                'getCcTokenExpiryDateTime',
                'getDefaultPaymentMethod'
            ])->disableOriginalConstructor()
            ->getMock();
        $this->additionalDataCollection = $this->getMockBuilder(AdditionalDataCollection::class)
            ->setMethods(['addFieldToSelect', 'addFieldToFilter', 'getFirstItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->repositoryMock = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();
        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods([
                'getFedexAccountNumber',
                'getShippingAccountNumber',
                'getDiscountAccountNumber',
                'getShippingAccountNumberEditable',
                'getFxoAccountNumberEditable',
                'getDiscountAccountNumberEditable'
            ])->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManager = new ObjectManager($this);
        $this->companyPaymentData = $this->objectManager->getObject(
            CompanyPaymentData::class,
            [
                'logger' => $this->logger,
                'enhancedProfile' => $this->enhancedProfile,
                'additionalDataFactory' => $this->additionalDataFactory,
                'companyHelper' => $this->companyHelper,
                'json' => $this->jsonMock,
                'assetRepo' => $this->repositoryMock,
            ]
        );
    }

    public function testGetCompanyDataById()
    {
        $this->testGetCompanyId();
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);

        $this->additionalData->expects($this->any())->method('getCollection')
        ->willReturn($this->additionalDataCollection);

        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')
        ->willReturn($this->additionalData);

        $this->assertNotNull($this->companyPaymentData->getCompanyDataById());
    }

    public function testGetCompanyCcData()
    {
        $creditCardData = [
            'ccCompanyName' => 'Fedex',
            'country' => 'US',
            'state' => 'TX',
            'label' => 'VISA_00010',
            'ccType' => 'VISA',
            'ccExpiryMonth' => '08',
            'ccExpiryYear' => '2025',
            'token' => '80addce1-1d40-454e',
            'tokenExpirationDate' => '2000-06-26T09:50:35Z',
            'DefaultPayMethod' => 'credit card',
            'ccNumber' => '00010',
            'nameOnCard' => 'STUART BROAD',
            'addressLine1' => 'Legacy',
            'addressLine2' => 'ABC',
            'city' => 'PLANO',
            'zipCode' => '75024'
        ];
        $this->testGetCompanyId();
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')
        ->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')
        ->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('isEmpty')->willReturn(false);
        $this->additionalData->expects($this->any())->method('getCcData')->willReturn($creditCardData);
        $this->additionalData->expects($this->any())->method('getCcToken')->willReturn(true);
        $this->jsonMock->expects($this->any())->method('unserialize')->with($creditCardData)
        ->willReturn($creditCardData);
        $this->additionalData->expects($this->any())->method('getCcTokenExpiryDateTime')
        ->willReturn("2026-01-17T00:00:00Z");
        $this->additionalData->expects($this->any())->method('getDefaultPaymentMethod')->willReturn('credit card');

        $this->assertNotNull($this->companyPaymentData->getCompanyCcData());
    }

    public function testGetCompanyCcDataWithUKCountry()
    {
        $creditCardData = [
            'ccCompanyName' => 'Fedex',
            'country' => 'UK',
            'state' => 'TX',
            'label' => 'VISA_00010',
            'ccType' => 'VISA',
            'ccExpiryMonth' => '08',
            'ccExpiryYear' => '2025',
            'token' => '80addce1-1d40-454e',
            'tokenExpirationDate' => '2000-06-26T09:50:35Z',
            'DefaultPayMethod' => 'credit card',
            'ccNumber' => '00010',
            'nameOnCard' => 'STUART BROAD',
            'addressLine1' => 'Legacy',
            'addressLine2' => 'ABC',
            'city' => 'PLANO',
            'zipCode' => '75024'
        ];
        $this->testGetCompanyId();
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')
        ->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')
        ->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('isEmpty')->willReturn(false);
        $this->additionalData->expects($this->any())->method('getCcData')->willReturn($creditCardData);
        $this->additionalData->expects($this->any())->method('getCcToken')->willReturn(true);
        $this->jsonMock->expects($this->any())->method('unserialize')->with($creditCardData)
        ->willReturn($creditCardData);
        $this->additionalData->expects($this->any())->method('getCcTokenExpiryDateTime')
        ->willReturn("2026-01-17T00:00:00Z");
        $this->additionalData->expects($this->any())->method('getDefaultPaymentMethod')->willReturn('credit card');

        $this->assertNotNull($this->companyPaymentData->getCompanyCcData());
    }

    public function testGetCompanyCcDataWithoutData()
    {
        $creditCardData = [];
        $this->testGetCompanyId();
        $this->additionalDataFactory->expects($this->any())->method('create')->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('getCollection')
        ->willReturn($this->additionalDataCollection);
        $this->additionalDataCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollection->expects($this->any())->method('getFirstItem')
        ->willReturn($this->additionalData);
        $this->additionalData->expects($this->any())->method('isEmpty')->willReturn(false);
        $this->additionalData->expects($this->any())->method('getCcData')->willReturn($creditCardData);
        $this->additionalData->expects($this->any())->method('getCcToken')->willReturn(true);
        $this->jsonMock->expects($this->any())->method('unserialize')->with($creditCardData)
        ->willReturn($creditCardData);
        $this->additionalData->expects($this->any())->method('getCcTokenExpiryDateTime')
        ->willReturn("2026-01-17T00:00:00Z");
        $this->additionalData->expects($this->any())->method('getDefaultPaymentMethod')->willReturn('credit card');

        $this->assertNotNull($this->companyPaymentData->getCompanyCcData());
    }

    public function testGetCompanyId()
    {
        $this->companyHelper->expects($this->any())->method('getCompanyId')->willReturn(48);

        $this->assertNotNull($this->companyPaymentData->getCompanyId());
    }

    public function testGetIsNonEditablePaymentMethod()
    {
        $this->companyHelper->expects($this->once())->method('getNonEditableCompanyCcPaymentMethod')->willReturn(true);

        $this->assertTrue($this->companyPaymentData->getIsNonEditablePaymentMethod());
    }

    /**
     * Test make html for credit card
     *
     * @return void
     */
    public function testMakeCreditCardHtml()
    {
        $this->testGetCompanyCcData();
        $this->jsonMock->expects($this->any())->method('serialize')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('getRegionsOfCountry')->with('us')
        ->willReturn([['label' => 'TX', 'title' => 'Texas']]);
        $this->enhancedProfile->expects($this->any())->method('getMediaUrl')->willReturn('test');
        $this->enhancedProfile->expects($this->any())->method('getTokenIsExpired')->willReturn('2000-06-26T09:50:35Z');

        $this->assertNotNull($this->companyPaymentData->makeCreditCardViewHtml());
    }

    /**
     * Test make html for credit card
     *
     * @return void
     */
    public function testMakeCreditCardHtmlWithEmptyExpiryDate()
    {
        $this->testGetCompanyCcData();
        $this->jsonMock->expects($this->any())->method('serialize')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('getRegionsOfCountry')->with('us')
        ->willReturn([['label' => 'TX', 'title' => 'Texas']]);
        $this->enhancedProfile->expects($this->any())->method('getMediaUrl')->willReturn('test');
        $this->enhancedProfile->expects($this->any())->method('getTokenIsExpired')->willReturn('');

        $this->assertNotNull($this->companyPaymentData->makeCreditCardViewHtml());
    }

    /**
     * Test make html for credit card
     *
     * @return void
     */
    public function testMakeCreditCardHtmlWithUKCountry()
    {
        $this->testGetCompanyCcDataWithUKCountry();
        $this->jsonMock->expects($this->any())->method('serialize')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('getRegionsOfCountry')->with('us')
        ->willReturn([['label' => 'TX', 'title' => 'Texas']]);
        $this->enhancedProfile->expects($this->any())->method('getMediaUrl')->willReturn('test');
        $this->enhancedProfile->expects($this->any())->method('getTokenIsExpired')->willReturn('2000-06-26T09:50:35Z');

        $this->assertNotNull($this->companyPaymentData->makeCreditCardViewHtml());
    }

    /**
     * Test make html for credit card
     *
     * @return void
     */
    public function testMakeCreditCardHtmlWithoutData()
    {
        $this->testGetCompanyCcDataWithoutData();
        $this->jsonMock->expects($this->any())->method('serialize')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('getRegionsOfCountry')->with('us')
        ->willReturn([['label' => 'TX', 'title' => 'Texas']]);
        $this->enhancedProfile->expects($this->any())->method('getMediaUrl')->willReturn('test');
        $this->enhancedProfile->expects($this->any())->method('getTokenIsExpired')->willReturn('2000-06-26T09:50:35Z');

        $this->assertFalse($this->companyPaymentData->makeCreditCardViewHtml());
    }

    /**
     * Test siteLevel html for credit card
     *
     * @return void
     */
    public function testSiteLevelCreditCardHtml()
    {
        $this->testGetCompanyCcData();
        $this->jsonMock->expects($this->any())->method('serialize')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('getRegionsOfCountry')->with('us')
            ->willReturn([['label' => 'TX', 'title' => 'Texas']]);
        $this->enhancedProfile->expects($this->any())->method('getTokenIsExpired')->willReturn('2000-06-26T09:50:35Z');

        $this->assertNotNull($this->companyPaymentData->siteLevelCreditCardViewHtml());
    }

    /**
     * Test siteLevel html for credit card
     *
     * @return void
     */
    public function testSiteLevelCreditCardHtmlWithEmptyExpiryDate()
    {
        $this->testGetCompanyCcData();
        $this->jsonMock->expects($this->any())->method('serialize')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('getRegionsOfCountry')->with('us')
            ->willReturn([['label' => 'TX', 'title' => 'Texas']]);
        $this->enhancedProfile->expects($this->any())->method('getTokenIsExpired')->willReturn('');

        $this->assertNotNull($this->companyPaymentData->siteLevelCreditCardViewHtml());
    }

    /**
     * Test siteLevel html for credit card
     *
     * @return void
     */
    public function testSiteLevelCreditCardHtmlWithUKCountry()
    {
        $this->testGetCompanyCcDataWithUKCountry();
        $this->jsonMock->expects($this->any())->method('serialize')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('getRegionsOfCountry')->with('us')
            ->willReturn([['label' => 'TX', 'title' => 'Texas']]);
        $this->enhancedProfile->expects($this->any())->method('getTokenIsExpired')->willReturn('2000-06-26T09:50:35Z');

        $this->assertNotNull($this->companyPaymentData->siteLevelCreditCardViewHtml());
    }

    /**
     * Test siteLevel html for credit card
     *
     * @return void
     */
    public function testSiteLevelCreditCardHtmlWithoutData()
    {
        $this->testGetCompanyCcDataWithoutData();
        $this->jsonMock->expects($this->any())->method('serialize')->willReturnSelf();
        $this->enhancedProfile->expects($this->any())->method('getRegionsOfCountry')->with('us')
            ->willReturn([['label' => 'TX', 'title' => 'Texas']]);
        $this->enhancedProfile->expects($this->any())->method('getTokenIsExpired')->willReturn('2000-06-26T09:50:35Z');

        $this->assertFalse($this->companyPaymentData->siteLevelCreditCardViewHtml());
    }

    /**
     * Test Fedex Print And Ship Account Numbers
     *
     * @return void
     */
    public function testGetFedexPrintShipAccounts()
    {
        $this->companyHelper->expects($this->any())->method('getFedexPrintShipAccounts')->willReturn(true);

        $this->assertTrue($this->companyPaymentData->getFedexPrintShipAccounts());
    }

    public function testGetCompanyAccountNumbersWithBothAccounts()
    {
        $this->companyMock->method('getFedexAccountNumber')->willReturn('123456789');
        $this->companyMock->method('getShippingAccountNumber')->willReturn('987654321');
        $this->companyMock->method('getDiscountAccountNumber')->willReturn(null);
        $this->companyMock->method('getFxoAccountNumberEditable')->willReturn(true);
        $this->companyMock->method('getShippingAccountNumberEditable')->willReturn(false);

        $this->companyHelper->method('getCompanyId')->willReturn(1);
        $this->companyHelper->method('getCustomerCompany')->willReturn($this->companyMock);

        $expected = [
            ['label' => 'FedEx Account 6789', 'account_number' => '123456789', 'type' => 'Print', 'editable' => 1, 'maskednumber' => '*6789'],
            ['label' => 'FedEx Account 4321', 'account_number' => '987654321', 'type' => 'Ship', 'editable' => 0, 'maskednumber' => '*4321']
        ];

        $result = $this->companyPaymentData->getCompanyAccountNumbers();
        $this->assertEquals($expected, $result);
    }

    public function testGetCompanyAccountNumbersWithFedExOnly()
    {
        $this->companyMock->method('getFedexAccountNumber')->willReturn('123456789');
        $this->companyMock->method('getShippingAccountNumber')->willReturn(null);
        $this->companyMock->method('getDiscountAccountNumber')->willReturn(null);
        $this->companyMock->method('getFxoAccountNumberEditable')->willReturn(true);

        $this->companyHelper->method('getCompanyId')->willReturn(1);
        $this->companyHelper->method('getCustomerCompany')->willReturn($this->companyMock);

        $expected = [
            ['label' => 'FedEx Account 6789', 'account_number' => '123456789', 'type' => 'Print', 'editable' => 1, 'maskednumber' => '*6789']
        ];

        $result = $this->companyPaymentData->getCompanyAccountNumbers();
        $this->assertEquals($expected, $result);
    }

    public function testGetCompanyAccountNumbersWithNoAccounts()
    {
        $this->companyMock->method('getFedexAccountNumber')->willReturn(null);
        $this->companyMock->method('getShippingAccountNumber')->willReturn(null);
        $this->companyMock->method('getDiscountAccountNumber')->willReturn(null);

        $this->companyHelper->method('getCompanyId')->willReturn(1);
        $this->companyHelper->method('getCustomerCompany')->willReturn($this->companyMock);

        $expected = [];

        $result = $this->companyPaymentData->getCompanyAccountNumbers();
        $this->assertEquals($expected, $result);
    }
}
