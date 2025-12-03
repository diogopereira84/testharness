<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\ViewModel;

use Fedex\Cart\ViewModel\CheckoutConfig;
use Fedex\Shipment\ViewModel\ShipmentConfig;
use Fedex\CustomizedMegamenu\Helper\Data as CustomizedMegamenuDataHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\CartFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Company\Helper\Data as CompanyHelper;

/**
 * Prepare test objects.
 */
class CheckoutConfigTest extends TestCase
{
    protected $cartFactory;
    protected $selfregHelper;
    protected $cart;
    protected $quote;
    protected $cartDataHelper;
    protected $companyHelperMock;
    protected $customerSessionMock;
    protected $checkoutSessionMock;
    protected $checkoutConfig;
    public const DOCUMENT_OFFICE_API_URL_ID = 'fedex/duncoffice/dunc_office_api_url';

    /**  B-1109907: Optimize Configurations  */
    public const GENERAL_DOCUMENT_OFFICE_API_URL_ID = 'fedex/general/dunc_office_api_url';
    public const GENERAL_DOCUMENT_PREVIEW_IMAGE_URL = 'fedex/catalogmvp/preview_api_url';

    /**
     * @var ShipmentConfig|MockObject
     */
    private $shipmentConfigMock;

    /**
     * @var ToggleConfig $toggleConfigMock
     */
    protected $toggleConfigMock;

    /**
     * @var CustomizedMegamenuDataHelper|MockObj
     */
    private $customizedMegamenuDataHelperMock;

    protected const TEST_DATA = 'Some Text';
    protected const GET_CONFIG_VALUE = 'getConfigValue';
    protected const GET_STORE_ID = 'getStoreId';
    protected const GET_STORE_GROUP_ID = 'getStoreGroupId';
    protected const GET_STORE_CODE = 'getStoreCode';

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->shipmentConfigMock = $this->getMockBuilder(ShipmentConfig::class)
            ->disableOriginalConstructor()
            ->setMethods([self::GET_CONFIG_VALUE])
            ->getMock();
        $this->customizedMegamenuDataHelperMock = $this->getMockBuilder(CustomizedMegamenuDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([self::GET_STORE_ID, self::GET_STORE_GROUP_ID, self::GET_STORE_CODE])
            ->getMock();
        $this->cartFactory = $this->getMockBuilder(\Magento\Checkout\Model\CartFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->selfregHelper = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['isSelfRegCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cart = $this->getMockBuilder(\Magento\Checkout\Model\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getFedexAccountNumber'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartDataHelper = $this->getMockBuilder(CartDataHelper::class)
            ->setMethods(['decryptData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyHelperMock = $this->getMockBuilder(CompanyHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyLevelConfig'])
            ->getMock();
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'unsPromoErrorMessage',
                'getPromoErrorMessage',
                'getFedexAccountWarning',
                'unsFedexAccountWarning'
            ])->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getWarningMessageFlag',
                'unsWarningMessageFlag',
                'getQuote',
                'getAccountDiscountWarningFlag'
            ])->getMock();
        
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->checkoutConfig = $objectManagerHelper->getObject(
            CheckoutConfig::class,
            [
                'shipmentConfig' => $this->shipmentConfigMock,
                'customizedMegamenuDataHelper' => $this->customizedMegamenuDataHelperMock,
                'customerSession' => $this->customerSessionMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'cartFactory' => $this->cartFactory,
                'cartDataHelper' => $this->cartDataHelper,
                'selfregHelper' => $this->selfregHelper,
                'toggleConfig' => $this->toggleConfigMock,
                'companyHelper' => $this->companyHelperMock
            ]
        );
    }

    /**
     * Test getDocumentOfficeApiUrl
     *
     * @return void
     */
    public function testGetDocumentOfficeApiUrl()
    {
        $apiBaseUrl = 'https://dunc.dmz.fedex.com/document/fedexoffice/v1/documents/';
        $documentOfficeApiUrl = $apiBaseUrl.'contentReferenceId/preview?pageNumber=1&zoomFactor=0.2';
        $this->customizedMegamenuDataHelperMock->expects($this->any())->method(self::GET_STORE_ID)->willReturn(1);

        $this->shipmentConfigMock->expects($this->any())->method(self::GET_CONFIG_VALUE)
            ->with(self::GENERAL_DOCUMENT_OFFICE_API_URL_ID, 1)
            ->willReturn($documentOfficeApiUrl);

        $this->assertEquals($documentOfficeApiUrl, $this->checkoutConfig->getDocumentOfficeApiUrl());
    }

    /**
     * Test getDocumentImagePreviewUrl
     *
     * @return void
     */
    public function testGetDocumentImagePreviewUrl()
    {
        $documentImageUrl = 'https://documentapitest.prod.fedex.com/document/fedexoffice/';
        $this->customizedMegamenuDataHelperMock->expects($this->any())->method(self::GET_STORE_ID)->willReturn(1);

        $this->shipmentConfigMock->expects($this->any())->method(self::GET_CONFIG_VALUE)
            ->with(self::GENERAL_DOCUMENT_PREVIEW_IMAGE_URL, 1)
            ->willReturn($documentImageUrl);

        $this->assertEquals($documentImageUrl, $this->checkoutConfig->getDocumentImagePreviewUrl());
    }

    /**
     * Test case for getPromoWarnings
     */
    public function testgetPromoWarnings()
    {
        $this->customerSessionMock->expects($this->any())->method('getPromoErrorMessage')
        ->willReturn(self::TEST_DATA);
        $this->checkoutConfig->getPromoWarnings();
    }

    /**
     * Test case for unSetPromoWarnings
     */
    public function testunSetPromoWarnings()
    {
        $this->customerSessionMock->expects($this->any())->method('unsPromoErrorMessage')
        ->willReturn(self::TEST_DATA);
        $this->checkoutConfig->unSetPromoWarnings();
    }

    /**
     * Test case for getFedexAccountWarning
     */
    public function testgetAccountWarnings()
    {
        $this->customerSessionMock->expects($this->any())->method('getFedexAccountWarning')
        ->willReturn(self::TEST_DATA);
        $this->checkoutConfig->getAccountWarnings();
    }

    /**
     * Test case for unSetAccountWarnings
     */
    public function testunSetAccountWarnings()
    {
        $this->customerSessionMock->expects($this->any())->method('unsFedexAccountWarning')
        ->willReturn(self::TEST_DATA);
        $this->checkoutConfig->unSetAccountWarnings();
    }

    /**
     * Test case for getWarningMessage
     */
    public function testgetWarningMessage()
    {
        $this->checkoutSessionMock->expects($this->any())->method('getWarningMessageFlag')->willReturn(self::TEST_DATA);
        $this->checkoutConfig->getWarningMessage();
    }

    /**
     * Test case for unSetWarningMessage
     */
    public function testUnSetWarningMessage()
    {
        $this->checkoutSessionMock->expects($this->any())->method('unsWarningMessageFlag')->willReturn(self::TEST_DATA);
        $this->checkoutConfig->unSetWarningMessage();
    }

    /**
     * Test case for getAppliedFedexAccountNumber
     */
    public function testgetAppliedFedexAccountNumber()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getFedexAccountNumber')->willReturn('4111 1111 1111 1111');
        $this->cartDataHelper->expects($this->any())->method('decryptData')->willReturnSelf();
        $this->checkoutConfig->getAppliedFedexAccountNumber();
    }

    /**
     * Test case for isSelfRegCustomer
     */
    public function testisSelfRegCustomer()
    {
        $this->selfregHelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->checkoutConfig->isSelfRegCustomer();
    }

    /**
     * Test case for getCurrentActiveQuote
     */
    public function testGetCurrentActiveQuote()
    {
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturnSelf();

        $this->assertNotNull($this->checkoutConfig->getCurrentActiveQuote());
    }

    /**
     * Test isPromoDiscountEnabled
     *
     * @return void
     */
    public function testIsPromoDiscountEnabledWithOnDemand()
    {
        $promoAccountConfig = [
            "promo_discount" => true,
            "account_discount" => true
        ];
        $this->customizedMegamenuDataHelperMock
            ->expects($this->any())
            ->method(self::GET_STORE_ID)
            ->willReturn('ondemand');

        $this->companyHelperMock->expects($this->any())
            ->method('getCompanyLevelConfig')
            ->willReturn($promoAccountConfig);
    
        $this->asserttrue($this->checkoutConfig->isPromoDiscountEnabled());

    }

    /**
     * Test isPromoDiscountEnabled
     *
     * @return void
     */
    public function testIsPromoDiscountEnabledElseIf1()
    {
        $promoAccountConfig = [
            "promo_discount" => false,
            "account_discount" => true
        ];
        $this->companyHelperMock->expects($this->any())
            ->method('getCompanyLevelConfig')
            ->willReturn($promoAccountConfig);

        $this->assertFalse($this->checkoutConfig->isPromoDiscountEnabled());
    }

    /**
     * Test isAccountDiscountEnabled
     *
     * @return void
     */
    public function testIsAccountDiscountEnabledForDefault()
    {
        $promoAccountConfig = [
            "promo_discount" => true,
            "account_discount" => true
        ];

        $this->customizedMegamenuDataHelperMock
            ->expects($this->any())
            ->method(self::GET_STORE_ID)
            ->willReturn('default');

        $this->companyHelperMock->expects($this->any())
            ->method('getCompanyLevelConfig')
            ->willReturn($promoAccountConfig);
    
        $this->assertTrue($this->checkoutConfig->isAccountDiscountEnabled());
    }

    /**
     * Test isReorderEnabled
     *
     * @return void
     */
    public function testIsReorderEnabled()
    {
        /*$this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);*/
        $companyLevelConfig = ["reorder" => true];
        $this->customizedMegamenuDataHelperMock->expects($this->any())->method(self::GET_STORE_ID)
        ->willReturn('ondemand');
        $this->companyHelperMock->expects($this->any())->method('getCompanyLevelConfig')
        ->willReturn($companyLevelConfig);
        $this->assertTrue($this->checkoutConfig->isReorderEnabled());
    }

    /**
     * Test isReorderToggleDisabled
     *
     * @return void
     */
    public function testIsReorderToggleEnabled()
    {
        /*$this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(false);*/

        $this->assertFalse($this->checkoutConfig->isReorderEnabled());
    }

    /**
     * Test isTermsAndConditionsEnabled
     *
     * @return void
     */
    public function testIsTermsAndConditionsEnabledForDefault()
    {
        /*$this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);*/
        $companyLevelConfig = ["terms_and_conditions" => true];
        $this->customizedMegamenuDataHelperMock->expects($this->any())->method(self::GET_STORE_ID)
        ->willReturn('default');
        $this->companyHelperMock->expects($this->any())->method('getCompanyLevelConfig')
        ->willReturn($companyLevelConfig);
        $this->assertTrue($this->checkoutConfig->isTermsAndConditionsEnabled());
    }

    /**
     * Test isTermsConditionToggleDisabled
     *
     * @return void
     */
    public function testIsTermsConditionToggleEnabled()
    {
        /*$this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(false);*/

        $this->assertFalse($this->checkoutConfig->isTermsAndConditionsEnabled());
    }
   
    /**
     * Test GetToggleFinalStatusEnabledForDefaultElseIf
     *
     * @return void
     */
    public function testGetToggleFinalStatusEnabledForDefaultElseIf()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->customizedMegamenuDataHelperMock
            ->expects($this->any())
            ->method(self::GET_STORE_CODE)
            ->willReturn('default');
        $this->assertFalse($this->checkoutConfig->getToggleFinalStatus());
    }

    /**
     * Test GetToggleFinalStatusEnabledForDefault
     *
     * @return void
     */
    public function testGetToggleFinalStatusEnabledForDefault()
    {
        $this->customizedMegamenuDataHelperMock
            ->expects($this->any())
            ->method(self::GET_STORE_CODE)
            ->willReturn('default');

        $this->companyHelperMock
            ->expects($this->any())
            ->method('getCompanyLevelConfig')
            ->willReturn(['your_toggle_type' => true]);

        $result = $this->checkoutConfig->getToggleFinalStatus(true, 'your_toggle_type', 'your_store_id');

        $this->assertTrue($result);
    }

    /**
     * Test GetToggleFinalStatusDisable
     *
     * @return void
     */
    public function testGetToggleFinalStatusFalse()
    {
        $this->customizedMegamenuDataHelperMock
            ->expects($this->any())
            ->method(self::GET_STORE_CODE)
            ->willReturn('non-default');

        $this->companyHelperMock
            ->expects($this->any())
            ->method('getCompanyLevelConfig')
            ->willReturn(['your_toggle_type' => false]);

        $result = $this->checkoutConfig->getToggleFinalStatus(true, 'your_toggle_type', 'your_store_id');

        $this->assertFalse($result);
    }

    /**
     * Test GetToggleFinalStatusEnable
     *
     * @return void
     */
    public function testGetToggleFinalStatusBothTrue()
    {
        $this->customizedMegamenuDataHelperMock
            ->expects($this->any())
            ->method(self::GET_STORE_CODE)
            ->willReturn('non-default');

        $this->companyHelperMock
            ->expects($this->any())
            ->method('getCompanyLevelConfig')
            ->willReturn(['your_toggle_type' => true]);

        $result = $this->checkoutConfig->getToggleFinalStatus(true, 'your_toggle_type', 'your_store_id');

        $this->assertTrue($result);
    }

    /**
     * Test case for getAccountDiscountWarningFlag
     */
    public function testGetAccountDiscountWarningFlag()
    {
        $this->checkoutSessionMock->expects($this->any())
        ->method('getAccountDiscountWarningFlag')
        ->willReturn(true);

        $this->assertEquals(true, $this->checkoutConfig->getAccountDiscountWarningFlag());
    }
}
