<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\ViewModel;

use Fedex\Cart\ViewModel\CartSummary;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOCMConfigurator\Helper\Batchupload;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\ViewModel\UnfinishedProjectNotification;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;

class CartSummaryTest extends TestCase
{
    /**
     * @var (\Magento\Checkout\Model\Cart & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartMock;

    /**
     * @var (\Magento\Quote\Model\Quote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteMock;

    /**
     * @var SdeHelper
     */
    protected $sdeHelperMock;

    /**
     * @var CartSummary
     */
    protected $cartSummaryMock;

    /**
     * @var SdeHelper
     */
    protected $sdeHelper;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * @var CartFactory $cartFactory
     */
    protected $cartFactory;

    /**
     * @var QuoteHelper
     */
    protected QuoteHelper $quoteHelper;

    /**
     * @var FormKey
     */
    protected FormKey $formKey;

    /**
     * @var Batchupload
     */
    protected Batchupload $batchUpload;

    /**
     * @var UnfinishedProjectNotification
     */
    protected $unfinishedProjectNotification;

    /**
     * @var MarketPlaceHelper
     */
    protected MarketPlaceHelper $marketPlaceHelper;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsSdeStore', 'getSdeCategoryUrl', 'isMarketplaceProduct', 'getBaseUrl'])
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create', 'getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->batchUpload = $this->getMockBuilder(Batchupload::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getUserWorkspaceSessionValue',
                'getApplicationType',
                'getRetailPrintUrl',
                'getCommercialPrintUrl',
            ])
            ->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->unfinishedProjectNotification = $this->getMockBuilder(UnfinishedProjectNotification::class)
            ->disableOriginalConstructor()
            ->setMethods(['isProjectAvailable'])
            ->getMock();
        $this->marketPlaceHelper = $this->getMockBuilder(MarketPlaceHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasLegacyDocumentInQuoteSession', 'checkLegacyDocApiOnCartToggle'])
            ->getMock();

        $this->cartSummaryMock = $this->getMockBuilder(CartSummary::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getUpdateContinueShoppingCtaToggle',
                'getAllPrintProductUrl'
            ])
            ->getMock();

        $this->quoteHelper = $this->createMock(QuoteHelper::class);

        $this->formKey = $this->createMock(FormKey::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->cartSummaryMock = $objectManagerHelper->getObject(
            CartSummary::class,
            [
                'sdeHelper' => $this->sdeHelperMock,
                'toggleConfig' => $this->toggleConfig,
                'quoteHelper' => $this->quoteHelper,
                'cartFactory' => $this->cartFactory,
                'cartMock' => $this->cartMock,
                'batchupload' => $this->batchUpload,
                'quoteMock' => $this->quoteMock,
                'unfinishedProjectNotification' => $this->unfinishedProjectNotification
            ]
        );
    }

    /**
     * @test
     */
    public function testCartPerfOptEnabledTrue(): void
    {
        $toggleMock = $this->getMockBuilder(AddToCartPerformanceOptimizationToggle::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isActive'])
            ->getMock();
        $toggleMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $objectManager = new ObjectManager($this);
        /** @var CartSummary $cartSummary */
        $cartSummary = $objectManager->getObject(
            CartSummary::class,
            [
                'sdeHelper' => $this->sdeHelperMock,
                'toggleConfig' => $this->toggleConfig,
                'cartFactory' => $this->cartFactory,
                'quoteHelper' => $this->quoteHelper,
                'formKey' => $this->formKey,
                'batchupload' => $this->batchUpload,
                'unfinishedProjectNotification' => $this->unfinishedProjectNotification,
                'addToCartPerformanceOptimizationToggle' => $toggleMock,
                'marketPlaceHelper' => $this->marketPlaceHelper,
            ]
        );

        // 3) Assert true
        $this->assertTrue(
            $cartSummary->isAddToCartPerformanceOptimizationEnabled()
        );
    }

    /**
     * @test
     */
    public function testCartPerfOptEnabledFalse(): void
    {
        $toggleMock = $this->getMockBuilder(AddToCartPerformanceOptimizationToggle::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isActive'])
            ->getMock();
        $toggleMock->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $objectManager = new ObjectManager($this);

        /** @var CartSummary $cartSummary */
        $cartSummary = $objectManager->getObject(
            CartSummary::class,
            [
                'sdeHelper' => $this->sdeHelperMock,
                'toggleConfig' => $this->toggleConfig,
                'cartFactory' => $this->cartFactory,
                'quoteHelper' => $this->quoteHelper,
                'formKey' => $this->formKey,
                'batchupload' => $this->batchUpload,
                'unfinishedProjectNotification' => $this->unfinishedProjectNotification,
                'addToCartPerformanceOptimizationToggle' => $toggleMock,
                'marketPlaceHelper' => $this->marketPlaceHelper,
            ]
        );

        // 3) Assert false
        $this->assertFalse(
            $cartSummary->isAddToCartPerformanceOptimizationEnabled()
        );
    }

    /**
     * @test testIsSdeStore
     *
     * @return void
     */
    public function testIsSdeStore()
    {
        $isSdeStore = true;
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn($isSdeStore);

        $this->assertEquals($isSdeStore, $this->cartSummaryMock->isSdeStore());
    }

    /**
     * @test testGetSdeCategoryUrl
     *
     * @return void
     */
    public function testGetSdeCategoryUrl()
    {
        $sdeCategoryUrl = 'test-url';
        $this->sdeHelperMock->expects($this->any())->method('getSdeCategoryUrl')->willReturn($sdeCategoryUrl);

        $this->sdeHelperMock
            ->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/ondemand/');

        $this->assertEquals($sdeCategoryUrl, $this->cartSummaryMock->getSdeCategoryUrl());
    }

    /**
     * @test testGetSdeNotCategoryUrl
     *
     * @return void
     */
    public function testGetSdeNotCategoryUrl()
    {
        $sdeCategoryUrl = 'all-print-products-commercial.html';
        $this->sdeHelperMock->expects($this->any())->method('getSdeCategoryUrl')->willReturn($sdeCategoryUrl);

        $this->assertEquals($sdeCategoryUrl, $this->cartSummaryMock->getSdeCategoryUrl());

        $this->assertEquals($sdeCategoryUrl, $this->cartSummaryMock->getSdeCategoryUrl());
    }

    public function testIsMarketplaceProduct()
    {
        $this->sdeHelperMock->method('isMarketplaceProduct')
            ->willReturn(true);

        $this->assertEquals(true, $this->cartSummaryMock->isMarketplaceProduct());
    }

    /**
     * @test
     */
    public function testSdeCatUrlUsesPrintUrlWhenEnabled(): void
    {
        $baseUrl      = 'https://example.com/';
        $redirectUrl  = 'https://example.com/print';

        $this->sdeHelperMock->expects($this->once())
            ->method('getSdeCategoryUrl')
            ->willReturn($baseUrl);
        $this->sdeHelperMock->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);

        $formKeyMock = $this->createMock(FormKey::class);
        $togglePerfOptMock = $this->getMockBuilder(AddToCartPerformanceOptimizationToggle::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isActive'])
            ->getMock();

        $togglePerfOptMock->method('isActive')->willReturn(false);

        $cartSummary = $this->getMockBuilder(CartSummary::class)
            ->setConstructorArgs([
                $this->sdeHelperMock,
                $this->toggleConfig,
                $this->cartFactory,
                $this->quoteHelper,
                $formKeyMock,
                $this->batchUpload,
                $this->unfinishedProjectNotification,
                $togglePerfOptMock,
                $this->marketPlaceHelper,
            ])
            ->onlyMethods(['getUpdateContinueShoppingCtaToggle', 'getAllPrintProductUrl'])
            ->getMock();

        $cartSummary->expects($this->once())
            ->method('getUpdateContinueShoppingCtaToggle')
            ->willReturn(true);
        $cartSummary->expects($this->once())
            ->method('getAllPrintProductUrl')
            ->willReturn($redirectUrl);

        $this->assertEquals($redirectUrl, $cartSummary->getSdeCategoryUrl());
    }

    /**
     * @test Get Form Key
     *
     * @return void
     */
    public function testGetFormKey()
    {
        $this->assertNull(
            $this->cartSummaryMock->getFormKey()
        );
    }

    /**
     * @test isViewMyProjectButtonEnable
     *
     * @return void
     */
    public function testIsViewMyProjectButtonEnableElse()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->assertFalse($this->cartSummaryMock->isViewMyProjectButtonEnable());
    }

    /**
     * @test isViewMyProjectButtonEnable
     *
     * @return void
     */
    public function testIsViewMyProjectButtonEnableIf()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->batchUpload->expects($this->any())
            ->method('getUserWorkspaceSessionValue')
            ->willReturn(true);
        $this->quoteHelper->expects($this->any())
            ->method('isFullMiraklQuote')
            ->willReturn(false);
        $this->assertNull($this->cartSummaryMock->isViewMyProjectButtonEnable());
    }

    /**
     * Test Get View Projetc Url.
     *
     * @return Null|String
     */
    public function testGetViewMyProjectUrl()
    {
        $this->sdeHelperMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/default/');
        $expectedResult = 'https://staging3.office.fedex.com/default/';
        $actualResult = $this->cartSummaryMock->getViewMyProjectUrl();
        $expectedResultModf = 'https://staging3.office.fedex.com/configurator/index/index?viewproject=true';
        $this->assertEquals($expectedResultModf, $actualResult);
    }

    /**
     * Test Get View Projetc Url Commercial.
     *
     * @return Null|String
     */
    public function testGetViewMyProjectUrlCommercial()
    {
        $this->sdeHelperMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/ondemand/');
        $expectedResult = 'https://staging3.office.fedex.com/ondemand/';
        $actualResult = $this->cartSummaryMock->getViewMyProjectUrl();
        $expectedResultModf = 'https://staging3.office.fedex.com/ondemand/configurator/index/index?viewproject=true';
        $this->assertEquals($expectedResultModf, $actualResult);
    }

    /**
     * Test toggle value for Millionaires - B-2154431: Update Continue Shopping CTA
     *
     * @return boolean
     */
    public function testGetUpdateContinueShoppingCtaToggle()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->cartSummaryMock->getUpdateContinueShoppingCtaToggle());
    }

    /**
     * Test CTA retail site url for continue shopping button
     *
     * @return string
     */
    public function testGetAllPrintRetailProductUrl()
    {
        $this->batchUpload
            ->expects($this->any())
            ->method('getApplicationType')
            ->willReturn('retail');

        $this->sdeHelperMock
            ->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/ondemand/');

        $this->batchUpload
            ->expects($this->any())
            ->method('getRetailPrintUrl')
            ->willReturn('all-print-products-retail.html');

        $this->assertNotEmpty($this->cartSummaryMock->getAllPrintProductUrl());
    }

    /**
     * Test CTA commercial site url for continue shopping button
     *
     * @return string
     */
    public function testGetAllPrintCommercialProductUrl()
    {
        $this->batchUpload
            ->expects($this->any())
            ->method('getApplicationType')
            ->willReturn('commercial');

        $this->sdeHelperMock
            ->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('https://staging3.office.fedex.com/ondemand/');

        $this->batchUpload
            ->expects($this->any())
            ->method('getCommercialPrintUrl')
            ->willReturn('all-print-products-commercial.html');

        $this->assertNotEmpty($this->cartSummaryMock->getAllPrintProductUrl());
    }

    /**
     * @test
     */
    public function testPrintUrlRemovesDefault()
    {
        $this->batchUpload
            ->expects($this->once())
            ->method('getApplicationType')
            ->willReturn('retail');

        $baseUrl = 'https://example.com/default/';
        $this->sdeHelperMock
            ->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);

        $this->batchUpload
            ->expects($this->once())
            ->method('getRetailPrintUrl')
            ->willReturn('all-print-products-retail.html');

        $expected = 'https://example.com/' . 'all-print-products-retail.html';
        $actual   = $this->cartSummaryMock->getAllPrintProductUrl();

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test method to Check if the cart summary contains a legacy document. | B-2353473
     *
     * @return void
     */
    public function testCheckLegacyDocOnCartSummary()
    {
        $this->marketPlaceHelper->expects($this->any())->method('hasLegacyDocumentInQuoteSession')->willReturn(true);
        $this->cartSummaryMock->checkLegacyDocOnCartSummary();
    }

    /**
     * Test method check if the legacy document API call should be toggled in the cart. | B-2353473
     *
     * @return void
     */
    public function testCheckLegacyDocApiOnCartToggle()
    {
        $this->marketPlaceHelper->expects($this->any())->method('checkLegacyDocApiOnCartToggle')->willReturn(true);
        $this->cartSummaryMock->checkLegacyDocApiOnCartToggle();
    }
}
