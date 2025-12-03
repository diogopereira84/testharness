<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Test\Unit\Helper;

use Fedex\MarketplacePunchout\Model\Config\Marketplace;
use Fedex\MarketplacePunchout\Model\ExpiredProducts;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\App\Http\Context;
use Magento\Customer\Model\Context as AuthContext;
use DateTime;
use Psr\Log\LoggerInterface;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Base\Helper\Auth;
use Fedex\ExpiredItems\Model\Config;

/**
 * Test class ExpiredItemTest
 */
class ExpiredItemTest extends TestCase
{
    /**
     * @var (\Fedex\FXOPricing\Model\FXORateQuote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fxoRateQuoteHelperMock;
    protected $publicCookieMetadataMock;
    protected $quote;
    protected $item;
    protected $dateTimeMock;
    protected $expiredProducts;
    protected $expiredItemMock;
    private const CURR_DATE = '2023-02-21';

    private const EXP_DATE = '2023-02-17';
    /**
     * @var CookieManagerInterface $cookieManagerMock
     */
    private $cookieManagerMock;

    /**
     * @var CookieMetadataFactory $cookieMetadataFactoryMock
     */
    private $cookieMetadataFactoryMock;

    /**
     * @var CheckoutSession $checkoutSessionMock
     */
    private $checkoutSessionMock;

    /**
     * @var TimezoneInterface $timezoneMock
     */
    private $timezoneMock;

    /**
     * @var FXORate $fxoHelperMock
     */
    private $fxoHelperMock;

    /**
     * @var ConfigProvider $configProviderMock
     */
    private $configProviderMock;

    /**
     * @var Context $httpContextMock
     */
    private $httpContextMock;

    /**
     * @var LoggerInterface $loggerMock
     */
    private $loggerMock;

    /**
     * @var FXORateQuote $fxoRateQuoteMock
     */
    private $fxoRateQuoteMock;

    /**
     * @var ToggleConfig $toggleConfigMock
     */
    private $toggleConfigMock;

    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var Marketplace $config
     */
    private Marketplace $config;

    protected Auth|MockObject $baseAuthMock;

    protected Config $expiredConfigMock;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteCookie'])
            ->getMockForAbstractClass();

        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();

        $this->timezoneMock = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'getConfigTimezone', 'convertConfigTimeToUtc'])
            ->getMockForAbstractClass();

        $this->fxoHelperMock = $this->getMockBuilder(FXORate::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFXORate', 'isEproCustomer'])
            ->getMock();
        $this->fxoRateQuoteHelperMock = $this->getMockBuilder(FXORateQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProviderMock = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isEnabled',
                'getExpiryTime',
                'getExpiryThresholdTime',
                'getMiniCartExpiredMessage',
                'getMiniCartExpiryMessage'])
            ->getMock();

        $this->publicCookieMetadataMock = $this->getMockBuilder(PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItemById', 'getId'])
            ->getMock();

        $this->item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCreatedAt', 'getMiraklOfferId', 'getAdditionalData'])
            ->getMock();

        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->setMethods(['modify', 'setTimezone'])
            ->getMock();

        $this->httpContextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->fxoRateQuoteMock = $this->getMockBuilder(FXORateQuote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFXORateQuote'])
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getExpiredItemIds',
                'setExpiredItemIds',
                'setExpiredMessage',
                'setExpiryMessage',
                'isLoggedIn',
                'unsExpiredItemIds',
                'setExpiredItemTransactionId',
                'getExpiredItemTransactionId'
            ])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->expiredProducts = $this->createMock(ExpiredProducts::class);
        $this->config = $this->createMock(Marketplace::class);

        $this->expiredConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->expiredItemMock = $objectManagerHelper->getObject(
            ExpiredItem::class,
            [
                'cookieManager' => $this->cookieManagerMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'timezone' => $this->timezoneMock,
                'fxoHelper' => $this->fxoHelperMock,
                'configProvider' => $this->configProviderMock,
                'httpContext' => $this->httpContextMock,
                'logger' => $this->loggerMock,
                'fxoRateQuote' => $this->fxoRateQuoteMock,
                'toggleConfig' => $this->toggleConfigMock,
                'customerSession' => $this->customerSession,
                'expiredProducts' => $this->expiredProducts,
                'config' => $this->config,
                'authHelper' => $this->baseAuthMock,
                'fxoRateQuoteMock' => $this->fxoRateQuoteHelperMock,
                'expiredConfig' => $this->expiredConfigMock
            ]
        );
    }

    /**
     * Test method for clear expired modal cookie
     *
     * @return void
     */
    public function testClearExpiredModalCookie()
    {
        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setDomain')
            ->willReturnSelf();

        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();

        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();

        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setSecure')
            ->willReturnSelf();

        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();

        $this->cookieMetadataFactoryMock->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadataMock);

        $this->cookieManagerMock->expects($this->once())
            ->method('deleteCookie')
            ->willReturnSelf();

        $this->assertNull($this->expiredItemMock->clearExpiredModalCookie());
    }

    /**
     * Test method for is item expiring soon
     *
     * @return void
     */
    public function testIsItemExpiringSoon()
    {
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getItemById')->with(54274)->willReturn($this->item);
        $this->item->expects($this->any())->method('getId')->willReturn(54274);
        $this->item->expects($this->any())->method('getCreatedAt')->willReturn(self::EXP_DATE);

        $this->timezoneMock->expects($this->any())->method('date')->withConsecutive(
            [self::EXP_DATE],
            [null],
        )->willReturnOnConsecutiveCalls($this->dateTimeMock, $this->dateTimeMock);
        $this->configProviderMock->expects($this->any())->method('getExpiryThresholdTime')->willReturn(3);
        $this->configProviderMock->expects($this->any())->method('getExpiryTime')->willReturn(2);
        $this->dateTimeMock->expects($this->any())->method('modify')->willReturnSelf();

        $this->timezoneMock->expects($this->any())->method('convertConfigTimeToUtc')->withConsecutive(
            [$this->dateTimeMock],
            [$this->dateTimeMock],
        )->willReturnOnConsecutiveCalls(self::EXP_DATE, self::CURR_DATE);

        $this->assertEquals(true, $this->expiredItemMock->isItemExpiringSoon(54274));
    }

    /**
     * Test method for is item expiring soon
     *
     * @return void
     */
    public function testIsItemExpiringSoonMarketplace()
    {
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getItemById')->with(54274)->willReturn($this->item);
        $this->item->expects($this->any())->method('getId')->willReturn(54274);
        $this->item->expects($this->any())->method('getMiraklOfferId')->willReturn('offer-id');
        $this->item->expects($this->any())->method('getCreatedAt')->willReturn(self::EXP_DATE);
        $this->item->expects($this->any())->method('getAdditionalData')
            ->willReturn('{"expire_soon": "5"}');

        $this->timezoneMock->expects($this->any())->method('date')->withConsecutive(
            [self::EXP_DATE],
            [null],
        )->willReturnOnConsecutiveCalls($this->dateTimeMock, $this->dateTimeMock);
        $this->configProviderMock->expects($this->any())->method('getExpiryThresholdTime')->willReturn(3);
        $this->configProviderMock->expects($this->any())->method('getExpiryTime')->willReturn(2);
        $this->dateTimeMock->expects($this->any())->method('modify')->willReturnSelf();

        $this->timezoneMock->expects($this->any())->method('convertConfigTimeToUtc')->withConsecutive(
            [$this->dateTimeMock],
            [$this->dateTimeMock],
        )->willReturnOnConsecutiveCalls(self::EXP_DATE, self::CURR_DATE);

        $this->assertEquals(true, $this->expiredItemMock->isItemExpiringSoon(54274));
    }

    /**
     * Test method for is item expiring soon with false
     *
     * @return void
     */
    public function testIsItemExpiringSoonWithFalse()
    {
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getItemById')->with(54274)->willReturn($this->item);
        $this->item->expects($this->any())->method('getId')->willReturn(54274);
        $this->item->expects($this->any())->method('getCreatedAt')->willReturn(self::CURR_DATE);

        $this->timezoneMock->expects($this->any())->method('date')->withConsecutive(
            [self::CURR_DATE],
            [null],
        )->willReturnOnConsecutiveCalls($this->dateTimeMock, $this->dateTimeMock);
        $this->configProviderMock->expects($this->any())->method('getExpiryThresholdTime')->willReturn(1);
        $this->configProviderMock->expects($this->any())->method('getExpiryTime')->willReturn(2);
        $this->dateTimeMock->expects($this->any())->method('modify')->willReturnSelf();

        $this->timezoneMock->expects($this->any())->method('convertConfigTimeToUtc')->withConsecutive(
            [$this->dateTimeMock],
            [$this->dateTimeMock],
        )->willReturnOnConsecutiveCalls(self::CURR_DATE, self::EXP_DATE);

        $this->assertEquals(false, $this->expiredItemMock->isItemExpiringSoon(54274));
    }

    /**
     * Test method for is item expiring soon
     *
     * @return void
     */
    public function testIsItemExpired()
    {
        $this->item->expects($this->any())->method('getCreatedAt')->willReturn(self::EXP_DATE);
        $this->item->expects($this->any())->method('getAdditionalData')
            ->willReturn('{"expire": "5"}');

        $this->timezoneMock->expects($this->any())->method('date')->withConsecutive(
            [self::EXP_DATE],
            [null],
        )->willReturnOnConsecutiveCalls($this->dateTimeMock, $this->dateTimeMock);
        $this->dateTimeMock->expects($this->any())->method('modify')->willReturnSelf();

        $this->timezoneMock->expects($this->any())->method('convertConfigTimeToUtc')->withConsecutive(
            [$this->dateTimeMock],
            [$this->dateTimeMock],
        )->willReturnOnConsecutiveCalls(self::EXP_DATE, self::CURR_DATE);

        $this->assertTrue($this->expiredItemMock->isItemExpired($this->item));
    }

    /**
     * Test method for is item expiring soon
     *
     * @return void
     */
    public function testIsItemExpiredWithFalse()
    {
        $this->item->expects($this->any())->method('getCreatedAt')->willReturn(self::EXP_DATE);
        $this->item->expects($this->any())->method('getAdditionalData')
            ->willReturn('{"expire_soon": "5"}');

        $this->assertFalse($this->expiredItemMock->isItemExpired($this->item));
    }

    /**
     * Test method for get expired instance ids
     *
     * @return void
     */
    public function testGetExpiredInstanceIds()
    {
        $this->httpContextMock->expects($this->any())->method('getValue')->willReturn(AuthContext::CONTEXT_AUTH);
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(54274);

        $this->assertNotEquals([], $this->expiredItemMock->getExpiredInstanceIds());
    }

    /**
     * Test method for get expired instance ids with data
     *
     * @return void
     */
    public function testGetExpiredInstanceIdsWithData()
    {
        $response = json_decode(
            '{"errors":[{"code":"RATEREQUEST.PRODUCTS.INVALID",
            "message":"one or more products are invalid or expired with instance ids : 0,1234"}
            ]}',
            true
        );
        $this->configProviderMock->expects($this->any())->method('isEnabled')->willReturn(true);
        $this->httpContextMock->expects($this->any())->method('getValue')->willReturn(AuthContext::CONTEXT_AUTH);
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(127669);
        $this->fxoRateQuoteMock->expects($this->any())->method('getFXORateQuote')
            ->with($this->quote)->willReturn($response);

        $this->assertNotEquals([0, 1234], $this->expiredItemMock->getExpiredInstanceIds());
    }

    /**
     * Test method for get expired instance ids with data using helper method
     *
     * @return void
     */
    public function testGetExpiredInstanceIdsWithDataFXORateHelper()
    {
        $response = json_decode(
            '{"errors":[{"code":"RATEREQUEST.PRODUCTS.INVALID",
            "message":"one or more products are invalid or expired with instance ids : 0,1234"}]}',
            true
        );
        $this->configProviderMock->expects($this->any())->method('isEnabled')->willReturn(true);
        $this->httpContextMock->expects($this->any())->method('getValue')->willReturn(AuthContext::CONTEXT_AUTH);
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(127669);
        $this->fxoHelperMock->expects($this->any())->method('getFXORate')->with($this->quote)->willReturn($response);

        $this->assertNotEquals([0, 1234], $this->expiredItemMock->getExpiredInstanceIds());
    }

    /**
     * Test callRateApiGetExpiredInstanceIds
     *
     * @return void
     */
    public function testCallRateApiGetExpiredInstanceIds()
    {
        $response = json_decode(
            '{"errors":[{"code":"RATEREQUEST.PRODUCTS.INVALID",
            "message":"one or more products are invalid or expired with instance ids : 0,1234"}]}',
            true
        );
        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn([]);
        $this->fxoHelperMock->expects($this->any())->method('getFXORate')->willReturn($response);
        $this->expiredProducts->expects($this->once())
            ->method('execute')->willReturn(['1234']);

        $this->assertNotEquals([0, 1234], $this->expiredItemMock->callRateApiGetExpiredInstanceIds('quote'));
    }

    /**
     * Test callRateApiGetExpiredInstanceIds with catelog expired
     *
     * @return void
     */
    public function testCallRateApiGetExpiredInstanceIdsWithCateLogExpired()
    {
        $response = json_decode(
            '{"errors":[{"code":"PRODUCTS.CATALOGREFERENCE.INVALID",
            "message":"one or more products are invalid or expired with instance ids : 0,1234"}]}',
            true
        );
        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn([]);
        $this->fxoHelperMock->expects($this->any())->method('getFXORate')->willReturn($response);

        $this->assertNotEquals([0, 1234], $this->expiredItemMock->callRateApiGetExpiredInstanceIds('quote'));
    }

    /**
     * Test callRateApiGetExpiredInstanceIds with unset expired instance id
     *
     * @return void
     */
    public function testCallRateApiGetExpiredInstanceIdsWithUnsetIntanceId()
    {
        $response = json_decode(
            '{"errors":[{"code":"CodeData",
            "message":"one or more products are invalid or expired with instance ids : 0,1234"}]}',
            true
        );
        $this->customerSession->expects($this->any())->method('unsExpiredItemIds')->willReturn(true);

        $this->assertNotEquals([0, 1234], $this->expiredItemMock->callRateApiGetExpiredInstanceIds('quote'));
    }

    /**
     * Test setExpiredItemMessageCustomerSession
     *
     * @return void
     */
    public function testSetExpiredItemMessageCustomerSession()
    {
        $this->configProviderMock->expects($this->any())
            ->method('getMiniCartExpiredMessage')
            ->willReturn('Expired Message');

        $this->configProviderMock->expects($this->any())
            ->method('getMiniCartExpiryMessage')
            ->willReturn('Expiry Message');

        $this->customerSession->expects($this->any())
            ->method('setExpiredMessage')
            ->willReturn('Expired Message');

        $this->customerSession->expects($this->any())
            ->method('setExpiryMessage')
            ->willReturn('Expiry Message');

        $this->assertNotEquals([0, 1234], $this->expiredItemMock->setExpiredItemMessageCustomerSession());
    }

    /**
     * Test isItemExpiringSoon for non customizable product
     *
     * @return void
     */
    public function testIsItemExpiringSoonForNonCustomizableProduct()
    {
        $this->checkoutSessionMock->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getItemById')->willReturn($this->item);
        $this->item->method('getId')->willReturn(1);
        $this->baseAuthMock->method('isLoggedIn')->willReturn(true);
        $this->expiredConfigMock->method('isIncorrectCartExpiryMassageToggleEnabled')->willReturn(true);
        $this->item->method('getMiraklOfferId')->willReturn('test_offer_id');
        $this->item->method('getAdditionalData')->willReturn('{"punchout_enabled": false}');

        $this->assertFalse($this->expiredItemMock->isItemExpiringSoon(1));
    }

    /**
     * Test isItemExpired for non customizable product
     *
     * @return void
     */
    public function testIsItemExpiredForNonCustomizableProduct()
    {
        $this->expiredConfigMock->method('isIncorrectCartExpiryMassageToggleEnabled')->willReturn(true);
        $this->item->method('getMiraklOfferId')->willReturn('test_offer_id');
        $this->item->method('getAdditionalData')->willReturn('{"punchout_enabled": false}');

        $this->assertFalse($this->expiredItemMock->isItemExpired($this->item));
    }

    /**
     * Test isItemExpired returns false when no mirakl id
     *
     * @return void
     */
    public function testIsItemExpiredReturnsFalseWhenNoMiraklId()
    {
        $this->expiredConfigMock->method('isIncorrectCartExpiryMassageToggleEnabled')->willReturn(true);
        $this->item->method('getMiraklOfferId')->willReturn(null);
        $this->item->method('getAdditionalData')->willReturn('{"expire_soon": "5"}');

        $this->assertFalse($this->expiredItemMock->isItemExpired($this->item));
    }

    /**
     * Test isItemExpired returns false when additional data is null
     *
     * @return void
     */
    public function testIsItemExpiredReturnsFalseWhenAdditionalDataIsNull()
    {
        $this->expiredConfigMock->method('isIncorrectCartExpiryMassageToggleEnabled')->willReturn(true);
        $this->item->method('getMiraklOfferId')->willReturn('test_offer_id');
        $this->item->method('getAdditionalData')->willReturn(null);

        $this->assertFalse($this->expiredItemMock->isItemExpired($this->item));
    }

    /**
     * Test isItemExpired returns false when punchout is not set
     *
     * @return void
     */
    public function testIsItemExpiredReturnsFalseWhenPunchoutIsNotSet()
    {
        $this->expiredConfigMock->method('isIncorrectCartExpiryMassageToggleEnabled')->willReturn(true);
        $this->item->method('getMiraklOfferId')->willReturn('test_offer_id');
        $this->item->method('getAdditionalData')->willReturn('{"some_other_key": "some_value"}');

        $this->assertFalse($this->expiredItemMock->isItemExpired($this->item));
    }

    /**
     * Test callRateApiGetExpiredInstanceIds with toggle enabled
     *
     * @return void
     */
    public function testCallRateApiGetExpiredInstanceIdsWithToggleEnabled()
    {
        $response = [
            'errors' => [[
                'code' => ExpiredItem::EXPIRED_CODE,
                'message' => 'one or more products are invalid or expired with instance ids : 0,1234'
            ]]
        ];
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(ExpiredItem::TOGGLE_D178760_PRODUCT_ID_MISSING)
            ->willReturn(true);
        $this->fxoHelperMock->expects($this->once())->method('isEproCustomer')->willReturn(false);
        $this->fxoRateQuoteMock->expects($this->once())->method('getFXORateQuote')->willReturn($response);
        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn([]);
        $this->expiredProducts->expects($this->once())->method('execute')->willReturn([]);

        $this->expiredItemMock->callRateApiGetExpiredInstanceIds('quote');
    }

    /**
     * Test callRateApiGetExpiredInstanceIds with catalog and expired codes
     *
     * @return void
     */
    public function testCallRateApiGetExpiredInstanceIdsWithCatalogAndExpiredCodes()
    {
        $catalogResponse = [
            'errors' => [[
                'code' => ExpiredItem::CATALOG_ITEM_EXPIRED_CODE,
                'message' => 'one or more products are invalid or expired with instance ids : 0,1234'
            ]]
        ];
        $expiredResponse = [
            'errors' => [[
                'code' => ExpiredItem::EXPIRED_CODE,
                'message' => 'one or more products are invalid or expired with instance ids : 5,678'
            ]]
        ];

        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn([]);
        $this->fxoHelperMock->expects($this->once())->method('getFXORate')->willReturn($expiredResponse);

        $this->expiredItemMock->setExpiredItemIdsCustomerSession('quote', $catalogResponse);
    }

    /**
     * Test setExpiredItemIdsInSession with transaction id
     *
     * @return void
     */
    public function testSetExpiredItemIdsInSessionWithTransactionId()
    {
        $response = [
            'errors' => [[
                'message' => 'one or more products are invalid or expired with instance ids : 0,1234'
            ]],
            'transactionId' => 'ABC-123'
        ];
        $this->customerSession->expects($this->once())->method('getExpiredItemIds')->willReturn([]);
        $this->customerSession->expects($this->once())
            ->method('setExpiredItemTransactionId')
            ->with('Transaction ID:ABC-123');

        $this->expiredItemMock->setExpiredItemIdsInSession($response);
    }

    /**
     * Test getExpiredInstanceIdsTransactionID
     *
     * @return void
     */
    public function testGetExpiredInstanceIdsTransactionID()
    {
        $this->customerSession->expects($this->once())
            ->method('getExpiredItemTransactionId')
            ->willReturn('Transaction ID:12345');

        $this->assertEquals(
            'Transaction ID:12345',
            $this->expiredItemMock->getExpiredInstanceIdsTransactionID()
        );
    }
}
