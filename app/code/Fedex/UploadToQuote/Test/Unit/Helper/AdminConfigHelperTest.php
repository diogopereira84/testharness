<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Company\Model\CompanyRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\NegotiableCartRepository;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\NegotiableQuoteFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchResults;
use Magento\NegotiableQuote\Model\HistoryManagementInterface;
use Magento\NegotiableQuote\Model\History;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Fedex\UploadToQuote\Model\QuoteGridFactory;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Quote\Api\CartRepositoryInterface;

class AdminConfigHelperTest extends TestCase
{
    protected $negotiableQuote;
    protected $customerSession;
    protected $sortOrderBuilderMock;
    protected $searchCriteriaBuilderMock;
    protected $negotiableQuoteRepository;
    protected $sortOrderMock;
    protected $searchCriteriaMock;
    protected $quoteList;
    protected $quote;
    protected $quoteItem;
    protected $quoteGridFactory;
    protected $adminConfigHelperMock;
    public const CONFIG_BASE_PATH = 'fedex/upload_to_quote_config/';
    public const XML_PATH_FROM_EMAIL = 'fedex/upload_to_quote_config/from_email';
    public const XML_PATH_QUOATE_DECLINE_USER_EMAIL =
        'fedex/upload_to_quote_config/quote_decline_customer_email';
    public const XML_PATH_QUOATE_DECLINE_EMAIL_TEMPLATE =
        'fedex/upload_to_quote_config/quote_decline_customer_email_template';
    public const XML_PATH_QUOATE_CHANGE_REQUEST_USER_EMAIL =
        'fedex/upload_to_quote_config/quote_change_request_email';
    public const XML_PATH_QUOATE_CHANGE_REQUEST_EMAIL_TEMPLATE =
        'fedex/upload_to_quote_config/quote_change_request_email_template';
    public const EMAIL_CONFIG_BASE_PATH = 'fedex/transactional_email/';

    public const QUOTE_EXPIRY_ISSUE_FIX =
    'environment_toggle_configuration/environment_toggle/mazegeek_u2q_quote_expiry_issue_fix';

    /**
     * @var ScopeConfigInterface $scopeConfigMock
     */
    protected $scopeConfigMock;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * @var CompanyRepository $companyRepository
     */
    protected $companyRepository;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var TimezoneInterface $timezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var NegotiableCartRepository $negotiableCartRepository
     */
    protected $negotiableCartRepository;

    /**
     * @var CheckoutHelper $checkoutHelper
     */
    protected CheckoutHelper $checkoutHelper;

    /**
     * @var NegotiableQuoteFactory $negotiableQuoteFactory
     */
    protected NegotiableQuoteFactory $negotiableQuoteFactory;

    /**
     * @var HistoryManagementInterface $historyManagement
     */
    protected $historyManagement;

    /**
     * @var History $quoteHistory
     */
    private $quoteHistory;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var CartItemRepositoryInterface $cartItemRepositoryInterface
     */
    protected $cartItemRepositoryInterface;

    /**
     * @var SdeHelper
     */
    protected $sdeHelper;

    /**
     * @var ItemFactory $itemFactory
     */
    protected $itemFactory;

    /**
     * @var CartFactory
     */
    protected $cartFactoryMock;

    /**
     * @var Cart
     */
    protected $cartMock;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSessionMock;

    /**
     * @var AttributeSetRepositoryInterface $attributeSetRepositoryInterface
     */
    protected $attributeSetRepositoryInterface;

    /**
     * @var ImageHelper $imageHelper
     */
    protected $imageHelper;

    /**
     * @var CartRepositoryInterface $cartRepositoryInterface
     */
    protected $cartRepositoryInterface;

    public function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue', 'getQuoteEditButtonMsg'])
            ->getMockForAbstractClass();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue', 'getToggleConfig'])
            ->getMock();

        $this->companyRepository = $this->getMockBuilder(CompanyRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getAllowUploadToQuote','getAllowNonStandardCatalog'])
            ->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'getData', 'getCreatedAt', 'getQuote', 'getIsEproQuote', 'getSentToErp'])
            ->getMock();

        $this->negotiableCartRepository = $this->getMockBuilder(NegotiableCartRepository::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'get',
                'getExtensionAttributes',
                'getNegotiableQuote',
                'getStatus',
                'getQuoteMgntLocationCode',
                'getExpirationPeriod',
                'getCreatedAt',
                'getId',
                'getQuoteName'
            ])
            ->getMock();

        $this->timezoneInterface = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format'])
            ->getMockForAbstractClass();

        $this->checkoutHelper = $this->getMockBuilder(CheckoutHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['convertPrice'])
            ->getMock();

        $this->negotiableQuoteFactory = $this->getMockBuilder(NegotiableQuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->negotiableQuote = $this->getMockBuilder(NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'load', 'getStatus', 'setStatus', 'save', 'setIsRegularQuote', 'getExpirationPeriod'])
            ->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'setUploadToQuoteId', 'getUploadToQuoteActionQueue'])
            ->getMock();

        $this->sortOrderBuilderMock = $this->getMockBuilder(SortOrderBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['setField', 'setDirection', 'create'])
            ->getMock();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFilter', 'setSortOrders', 'setPageSize', 'create'])
            ->getMock();

        $this->negotiableQuoteRepository = $this->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList'])
            ->getMockForAbstractClass();

        $this->sortOrderMock = $this->getMockBuilder(SortOrder::class)
            ->getMock();

        $this->searchCriteriaMock = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->getMock();

        $this->quoteList = $this->getMockBuilder(SearchResults::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'load',
                'getAllVisibleItems',
                'getId',
                'getQuote',
                'setIsActive',
                'save',
                'setIsRegularQuote',
                'getData',
                'getIsEproQuote',
                'getSentToErp',
                'getCreatedAt',
            ])
            ->addMethods(['getQuoteMgntLocationCode'])
            ->getMock();

        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->setMethods(['getOptionByCode', 'getValue', 'getItemId', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->historyManagement = $this->getMockBuilder(HistoryManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateStatusLog', 'addCustomLog', 'getQuoteHistory'])
            ->getMockForAbstractClass();

        $this->quoteHistory = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getLogData'])
            ->getMockForAbstractClass();

        $this->cartItemRepositoryInterface = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteById'])
            ->getMockForAbstractClass();

        $this->quoteGridFactory = $this->getMockBuilder(QuoteGridFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'getId', 'setStatus', 'save'])
            ->getMock();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMarketplaceProduct', 'getIsSdeStore'])
            ->getMock();

        $this->itemFactory = $this->getMockBuilder(ItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'getProductOptions'])
            ->getMock();

        $this->cartFactoryMock = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods(['replaceQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeSetRepositoryInterface = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->setMethods(['get', 'getAttributeSetName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->imageHelper = $this->getMockBuilder(ImageHelper::class)
            ->setMethods(['init', 'setImageFile', 'resize', 'getUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartRepositoryInterface = $this->getMockBuilder(CartRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->adminConfigHelperMock = $objectManagerHelper->getObject(
            AdminConfigHelper::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'toggleConfig' => $this->toggleConfig,
                'companyRepository' => $this->companyRepository,
                'quoteFactory' => $this->quoteFactory,
                'negotiableCartRepository' => $this->negotiableCartRepository,
                'timezoneInterface' => $this->timezoneInterface,
                'checkoutHelper' => $this->checkoutHelper,
                'negotiableQuoteFactory' => $this->negotiableQuoteFactory,
                'negotiableQuote' => $this->negotiableQuote,
                'customerSession' => $this->customerSession,
                'sortOrderBuilder' => $this->sortOrderBuilderMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'quote' => $this->quote,
                'historyManagement' => $this->historyManagement,
                'quoteHistory' => $this->quoteHistory,
                'cartItemRepositoryInterface' => $this->cartItemRepositoryInterface,
                'quoteGridFactory' => $this->quoteGridFactory,
                'sdeHelper' => $this->sdeHelper,
                'itemFactory' => $this->itemFactory,
                'checkoutSession' => $this->checkoutSessionMock,
                'cartFactory' => $this->cartFactoryMock,
                'attributeSetRepositoryInterface' => $this->attributeSetRepositoryInterface,
                'imageHelper' => $this->imageHelper,
                'cartRepositoryInterface' => $this->cartRepositoryInterface
            ]
        );
    }

    /**
     * Test method for Upload To Quote Config value
     *
     * @return void
     */
    public function testGetUploadToQuoteConfigValue()
    {
        $storeId = 1;
        $key = 'abc';
        $expectedResult = self::CONFIG_BASE_PATH . $key;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::CONFIG_BASE_PATH . $key);
        $this->assertEquals($expectedResult, $this->adminConfigHelperMock->getUploadToQuoteConfigValue($key, $storeId));
    }

    /**
     * Test method for Non Standard Catalog Config Value
     *
     * @return void
     */
    public function testGetNonStandardCatalogConfigValue()
    {
        $storeId = 1;
        $key = 'abc';
        $expectedResult = AdminConfigHelper::NON_STANDARD_CATALOG_CONFIG_BASE_PATH . $key;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(AdminConfigHelper::NON_STANDARD_CATALOG_CONFIG_BASE_PATH . $key);
        $this->assertEquals(
            $expectedResult,
            $this->adminConfigHelperMock->getNonStandardCatalogConfigValue($key, $storeId)
        );
    }

    /**
     * Test getUploadToQuoteToggle
     *
     * @return void
     */
    public function testIsUploadToQuoteEnable()
    {
        $this->companyRepository->expects($this->once())->method('get')->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('getAllowUploadToQuote')->willReturn(1);
        $this->scopeConfigMock->expects($this->once())->method('getValue')->willReturn('test');
        $this->toggleConfig->expects($this->once())->method('getToggleConfigValue')->willReturn(1);

        $this->assertEquals(true, $this->adminConfigHelperMock->isUploadToQuoteEnable(1, 72));
    }

    /**
     * Test isUploadToQuoteEnableForNSCFlowCompanySetting
     *
     * @return void
     */
    public function testIsAllowNonStandardCatalogForUser()
    {
        $this->companyRepository->expects($this->once())->method('get')->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('getAllowNonStandardCatalog')->willReturn(1);
        $this->scopeConfigMock->expects($this->once())->method('getValue')->willReturn('test');
        $this->toggleConfig->expects($this->once())->method('getToggleConfigValue')->willReturn(1);

        $this->assertEquals(true, $this->adminConfigHelperMock->isAllowNonStandardCatalogForUser(1, 72));
    }

    /**
     * Test isUploadToQuoteEnableForNSCFlowCompanySetting
     *
     * @return void
     */
    public function testIsAllowNonStandardCatalogForUserFalse()
    {
        $this->companyRepository->expects($this->once())->method('get')->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('getAllowNonStandardCatalog')->willReturn(0);

        $this->assertEquals(false, $this->adminConfigHelperMock->isAllowNonStandardCatalogForUser(1, 72));
    }

    /**
     * Test getUploadToQuoteToggle
     *
     * @return void
     */
    public function testIsUploadToQuoteEnableForNSCFlow()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')->willReturn('test');
        $this->toggleConfig->expects($this->once())->method('getToggleConfigValue')->willReturn(1);
        $this->sdeHelper->expects($this->once())->method('getIsSdeStore')->willReturn(false);
        $this->assertEquals(true, $this->adminConfigHelperMock->isUploadToQuoteEnableForNSCFlow(1));
    }

    /**
     * Test getUploadToQuoteToggle with false
     *
     * @return void
     */
    public function testIsUploadToQuoteEnableWithFalse()
    {
        $this->assertEquals(false, $this->adminConfigHelperMock->isUploadToQuoteEnable(0, 72));
    }

    /**
     * Test isUploadToQuoteToggle
     *
     * @return void
     */
    public function testIsUploadToQuoteToggle()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfig')
            ->willReturn(1);

        $this->assertEquals(true, $this->adminConfigHelperMock->isUploadToQuoteToggle());
    }

    /**
     * Test getNegotiableQuoteStatus
     *
     * @return void
     */
    public function testGetNegotiableQuoteStatus()
    {
        $historyData = '{
            "custom_log": [
              {
                "quoteStatus": "declined",
                "declinedDate": "2024-01-10 05:40:36",
                "reasonForDeclining": "Price too high",
                "additionalComments": ""
              }
            ]
          }';

        $this->quoteFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('getData')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('getIsEproQuote')
            ->willReturn(true);
        $this->quoteFactory->expects($this->once())
            ->method('getSentToErp')
            ->willReturn(false);
        $this->quoteFactory->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2023-10-16');
        $this->negotiableCartRepository->expects($this->any())
            ->method('getStatus')
            ->willReturn("created");
        $this->negotiableCartRepository->expects($this->any())
            ->method('getQuoteMgntLocationCode')
            ->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')
            ->will($this->onConsecutiveCalls('2023-03-01', '2023-03-28'));
        $this->historyManagement->expects($this->any())->method('getQuoteHistory')->willReturn(['1245', '3452']);
        $this->quoteHistory->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteHistory->expects($this->any())->method('getLogData')->willReturn($historyData);
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())->method('load')->willReturnSelf();

        $this->assertNotNull($this->adminConfigHelperMock->getNegotiableQuoteStatus("4"));
    }

    /**
     * Test getNegotiableQuoteStatusForQuoteDeatils
     *
     * @return void
     */
    public function testGetNegotiableQuoteStatusForQuoteDeatilsFlag()
    {
        $historyData = '{
            "custom_log": [
              {
                "quoteStatus": "declined",
                "declinedDate": "2024-01-10 05:40:36",
                "reasonForDeclining": "Price too high",
                "additionalComments": ""
              }
            ]
          }';

        $this->quoteFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('getData')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2023-10-16');
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')
            ->will($this->onConsecutiveCalls('2023-03-01', '2023-03-28'));
        $this->historyManagement->expects($this->any())->method('getQuoteHistory')->willReturn(['1245', '3452']);
        $this->quoteHistory->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteHistory->expects($this->any())->method('getLogData')->willReturn($historyData);
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())->method('load')->willReturnSelf();

        $this->assertNotNull($this->adminConfigHelperMock->getNegotiableQuoteStatus("4", true));
    }
    /**
     * Test getNegotiableQuoteStatus
     *
     * @return void
     */
    public function testGetNegotiableQuoteStatusD206707Toggle()
    {
        $historyData = '{
            "custom_log": [
              {
                "quoteStatus": "declined",
                "declinedDate": "2024-01-10 05:40:36",
                "reasonForDeclining": "Price too high",
                "additionalComments": ""
              }
            ]
          }';

        $this->toggleConfig->method('getToggleConfigValue')->willReturnMap([
            [AdminConfigHelper::XML_PATH_TOGGLE_D206707, true],
            [AdminConfigHelper::XML_PATH_TOGGLE_D240012, false],
        ]);

        $this->quote->expects($this->atMost(2))
            ->method('getId')
            ->willReturn('4');
        $this->quote->expects($this->once())
            ->method('getData')
            ->willReturnSelf();
        $this->quote->expects($this->once())
            ->method('getIsEproQuote')
            ->willReturn(true);
        $this->quote->expects($this->once())
            ->method('getSentToErp')
            ->willReturn(false);
        $this->quote->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2023-10-16');
        $this->quote->expects($this->any())
            ->method('getQuoteMgntLocationCode')
            ->willReturnSelf();

        $this->negotiableQuoteRepository->expects($this->any())
            ->method('getById')
            ->with('4')
            ->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())
            ->method('getStatus')
            ->willReturn("created");

        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')
            ->will($this->onConsecutiveCalls('2023-03-01', '2023-03-28'));
        $this->historyManagement->expects($this->any())->method('getQuoteHistory')->willReturn(['1245', '3452']);
        $this->quoteHistory->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteHistory->expects($this->any())->method('getLogData')->willReturn($historyData);

        $this->assertNotNull($this->adminConfigHelperMock->getNegotiableQuoteStatus($this->quote));
    }

    /**
     * Test filterDataByStatus with ordered status
     *
     * @return void
     */
    public function testFilterDataByStatusWithOrdered()
    {
        $historyData = [
            [
                "quoteStatus" => "ordered",
                "approvedDate" => "05:40:08"
            ]
        ];
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn('2024-01-10 05:40:09');

        $this->assertIsArray($this->adminConfigHelperMock->filterDataByStatus($historyData));
    }

    /**
     * Test filterDataByStatus with submitted_by_customer status
     *
     * @return void
     */
    public function testFilterDataByStatusWithSubmittedByCustomer()
    {
        $historyData = [
            [
                "quoteStatus" => "submitted_by_customer",
                "changeRequestedDate" => "05:40:08"
            ]
        ];
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn('2024-01-10 05:40:09');

        $this->assertIsArray($this->adminConfigHelperMock->filterDataByStatus($historyData));
    }

    /**
     * Test filterDataByStatus with submitted_by_admin status
     *
     * @return void
     */
    public function testFilterDataByStatusWithSubmittedByAdmin()
    {
        $historyData = [
            [
                "quoteStatus" => "submitted_by_admin",
                "readyForReviewDate" => "05:40:08"
            ]
        ];
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn('2024-01-10 05:40:09');

        $this->assertIsArray($this->adminConfigHelperMock->filterDataByStatus($historyData));
    }

    /**
     * Test filterDataByStatus with closed status
     *
     * @return void
     */
    public function testFilterDataByStatusWithClosed()
    {
        $historyData = [
            [
                "quoteStatus" => "closed",
                "closedDate" => "05:40:08"
            ]
        ];
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn('2024-01-10 05:40:09');

        $this->assertIsArray($this->adminConfigHelperMock->filterDataByStatus($historyData));
    }

    /**
     * Test filterLogData with else
     *
     * @return void
     */
    public function testFilterLogDataWithElse()
    {
        $historyData = '{"test": "test" }';
        $queueData = [
            [
                'action' => 'declined',
                'quoteId' => 1234,
                'requestedDateTime' => '2024-01-10 05:40:08',
                'declinedDate' => '2024-01-10',
                'declinedTime' => '05:40:08'
            ],
            [
                'action' => 'changeRequested',
                'quoteId' => 1234,
                'changeRequestedDate' => '2024-01-10 05:40:08',
                'changeRequestedTime' => '2024-01-10'
            ]
        ];

        $this->customerSession->expects($this->once())->method('getUploadToQuoteActionQueue')->willReturn($queueData);

        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')
            ->willReturn('2024-01-10 05:40:09');

        $this->assertIsArray($this->adminConfigHelperMock->filterLogData($historyData, 1234));
    }

    /**
     * Test getNegotiableQuoteStatus
     *
     * @return void
     */
    public function testGetNegotiableQuoteStatusNBCPriced()
    {
        $historyData = '{
            "custom_log": [
              {
                "quoteStatus": "expired",
                "declinedDate": "2024-01-10 05:40:36",
                "reasonForDeclining": "Price too high",
                "additionalComments": ""
              }
            ]
          }';

        $this->quoteFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('getData')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2023-10-16');
        $this->negotiableCartRepository->expects($this->any())
            ->method('getQuoteMgntLocationCode')
            ->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')
            ->will($this->onConsecutiveCalls('2023-03-01', '2023-03-28'));
        $this->historyManagement->expects($this->any())->method('getQuoteHistory')->willReturn(['1245', '3452']);
        $this->quoteHistory->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteHistory->expects($this->any())->method('getLogData')->willReturn($historyData);
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())->method('load')->willReturnSelf();
        $this->negotiableQuote->expects($this->any())->method('getStatus')->willReturn("nbc_priced");

        $this->assertEquals('NBC Priced', $this->adminConfigHelperMock->getNegotiableQuoteStatus("4"));
    }

    /**
     * Test getNegotiableQuoteStatus
     *
     * @return void
     */
    public function testGetNegotiableQuoteStatusNbcSupport()
    {
        $historyData = '{
            "custom_log": [
              {
                "quoteStatus": "expired",
                "declinedDate": "2024-01-10 05:40:36",
                "reasonForDeclining": "Price too high",
                "additionalComments": ""
              }
            ]
          }';

        $this->quoteFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('getData')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2023-10-16');
        $this->negotiableCartRepository->expects($this->any())
            ->method('getQuoteMgntLocationCode')
            ->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')
            ->will($this->onConsecutiveCalls('2023-03-01', '2023-03-28'));
        $this->historyManagement->expects($this->any())->method('getQuoteHistory')->willReturn(['1245', '3452']);
        $this->quoteHistory->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteHistory->expects($this->any())->method('getLogData')->willReturn($historyData);
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())->method('load')->willReturnSelf();
        $this->negotiableQuote->expects($this->any())->method('getStatus')->willReturn("nbc_support");

        $this->assertEquals('NBC Support', $this->adminConfigHelperMock->getNegotiableQuoteStatus("4"));
    }

    /**
     * Test getNegotiableQuoteStatus
     *
     * @return void
     */
    public function testGetNegotiableQuoteStatusExpired()
    {
        $historyData = '{
            "custom_log": [
              {
                "quoteStatus": "expired",
                "declinedDate": "2024-01-10 05:40:36",
                "reasonForDeclining": "Price too high",
                "additionalComments": ""
              }
            ]
          }';

        $this->quoteFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('getData')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2023-10-16');
        $this->negotiableCartRepository->expects($this->any())
            ->method('getQuoteMgntLocationCode')
            ->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')
            ->will($this->onConsecutiveCalls('2023-03-01', '2023-03-28'));
        $this->historyManagement->expects($this->any())->method('getQuoteHistory')->willReturn(['1245', '3452']);
        $this->quoteHistory->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteHistory->expects($this->any())->method('getLogData')->willReturn($historyData);
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())->method('load')->willReturnSelf();
        $this->negotiableQuote->expects($this->any())->method('getStatus')->willReturn("expired");

        $this->assertNotNull($this->adminConfigHelperMock->getNegotiableQuoteStatus("4"));
    }

    /**
     * Test getNegotiableQuoteStatus for commercial
     *
     * @return void
     */
    public function testGetNegotiableQuoteStatusWithCommercial()
    {
        $this->quoteFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->quote);
        $this->quote->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->quote->expects($this->once())
            ->method('getData')
            ->willReturnSelf();
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())->method('load')->willReturnSelf();
        $this->negotiableQuote->expects($this->any())->method('getStatus')->willReturn('created');

        $this->assertNotNull($this->adminConfigHelperMock->getNegotiableQuoteStatus("4"));
    }

    /**
     * Test getExpiryDate
     *
     * @return void
     */
    public function testGetExpiryDate()
    {
        $quoteId = 123;
        $returnValue = 'November 17, 2022';
        $this->negotiableCartRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())
            ->method('getExpirationPeriod')
            ->willReturn(2023-03-01);

        $this->timezoneInterface->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->timezoneInterface->expects($this->any())->method('format')->willReturnSelf();

        $this->assertNotEquals($returnValue, $this->adminConfigHelperMock->getExpiryDate($quoteId, 'F j, Y'));
    }

    /**
     * Test getExpiryDate with null
     *
     * @return void
     */
    public function testGetExpiryDateWithNull()
    {
        $quoteId = 123;
        $returnValue = 'November 17, 2022';
        $this->negotiableCartRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())
            ->method('getExpirationPeriod')
            ->willReturn(null);

        $this->timezoneInterface->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->timezoneInterface->expects($this->any())->method('format')->willReturnSelf();

        $this->assertNotEquals($returnValue, $this->adminConfigHelperMock->getExpiryDate($quoteId, 'F j, Y'));
    }

    /**
     * Test getExpiryDate
     *
     * @return void
     */
    public function testGetExpiryDateD206707Toggle()
    {
        $quoteId = 123;
        $returnValue = 'November 17, 2022';

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->negotiableQuoteRepository->expects($this->any())
            ->method('getById')
            ->with($quoteId)
            ->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())
            ->method('getStatus')
            ->willReturn("created");
        $this->negotiableQuote->expects($this->any())
            ->method('getExpirationPeriod')
            ->willReturn(2023-03-01);

        $this->timezoneInterface->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->timezoneInterface->expects($this->any())->method('format')->willReturnSelf();

        $this->assertNotEquals($returnValue, $this->adminConfigHelperMock->getExpiryDate($quoteId, 'F j, Y', $this->quote));
    }

    /**
     * Test getExpiryDate
     *
     * @return void
     */
    public function testGetExpiryDateD206707ToggleElse()
    {
        $quoteId = 123;
        $returnValue = 'November 17, 2022';

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with(AdminConfigHelper::XML_PATH_TOGGLE_D206707)
            ->willReturn(true);

        $this->negotiableQuoteRepository->expects($this->any())
            ->method('getById')
            ->with($quoteId)
            ->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())
            ->method('getStatus')
            ->willReturn("created");
        $this->negotiableQuote->expects($this->any())
            ->method('getExpirationPeriod')
            ->willReturn(false);

        $this->quote->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn('2023-10-16');

        $this->timezoneInterface->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->timezoneInterface->expects($this->any())->method('format')->willReturnSelf();

        $this->assertNotEquals($returnValue, $this->adminConfigHelperMock->getExpiryDate($quoteId, 'F j, Y', $this->quote));
    }

    /**
     * Test convertPrice
     *
     * @return void
     */
    public function testConvertPrice()
    {
        $price = '0.6100';
        $returnValue = '$0.61';

        $this->checkoutHelper->expects($this->once())->method('convertPrice')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->adminConfigHelperMock->convertPrice($price));
    }

    /**
     * Test getQuoteStatusLabel
     *
     * @return void
     */
    public function testGetQuoteStatusLabel()
    {
        $returnValue = 'created';

        $this->assertNotEquals($returnValue, $this->adminConfigHelperMock->getQuoteStatusLabel($returnValue));
    }

     /**
      * Test getQuoteStatusLabelForQuoteDetails
      *
      * @return void
      */
    public function testGetQuoteStatusLabelForQuoteDetails()
    {
        $returnValue = 'created';

        $this->assertNotEquals(
            $returnValue,
            $this->adminConfigHelperMock->getQuoteStatusLabelForQuoteDetails($returnValue)
        );
    }

    /**
     * Test updateQuoteStatusByKey
     *
     * @return void
     */
    public function testUpdateQuoteStatusByKey()
    {
        $this->updateStatus();
        $this->cartFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quote);
        $this->quote->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        $this->quote->expects($this->any())
            ->method('setIsActive')
            ->willReturnSelf();
        $this->quote->expects($this->any())
            ->method('setIsRegularQuote')
            ->willReturnSelf();
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->quote->expects($this->any())
            ->method('save')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->quote);
        $this->quote->expects($this->any())
            ->method('save')
            ->willReturnSelf();
        $this->checkoutSessionMock->expects($this->any())
            ->method('replaceQuote')
            ->willReturn($this->quote);

        $this->assertNull($this->adminConfigHelperMock->updateQuoteStatusByKey(123, 'created'));
    }

    /**
     * Test updateQuoteStatusByKey with exception
     *
     * @return void
     */
    public function testUpdateQuoteStatusByKeyWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willThrowException($exception);

        $this->assertNull($this->adminConfigHelperMock->updateQuoteStatusByKey(1235245, 'created'));
    }

    /**
     * Test updateQuoteStatusWithHisotyUpdate
     *
     * @return void
     */
    public function testUpdateQuoteStatusWithHisotyUpdate()
    {
        $this->updateStatus();
        $this->negotiableQuote->expects($this->any())->method('setIsRegularQuote')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn('2024-01-10 05:40:06');

        $this->assertNull($this->adminConfigHelperMock->updateQuoteStatusWithHisotyUpdate(1235245, 'created'));
    }

    /**
     * Test updateQuoteStatusWithHisotyUpdate with Exception
     *
     * @return void
     */
    public function testUpdateQuoteStatusWithHisotyUpdateWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willThrowException($exception);

        $this->assertNull($this->adminConfigHelperMock->updateQuoteStatusWithHisotyUpdate(1235245, 'created'));
    }

    /**
     * common test funtion for update status
     *
     * @return void
     */
    public function updateStatus()
    {
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())->method('load')->willReturnSelf();
        $this->negotiableQuote->expects($this->any())->method('getId')->willReturn(1234);
        $this->negotiableQuote->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->negotiableQuote->expects($this->any())->method('save')->willReturnSelf();
        $this->quoteGridFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteGridFactory->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteGridFactory->expects($this->any())->method('getId')->willReturn(3452);
        $this->quoteGridFactory->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->quoteGridFactory->expects($this->any())->method('save')->willReturnSelf();
    }

    /**
     * Test checkIsPunchoutQuote with quoteId
     *
     * @return void
     */
    public function testCheckIsPunchoutQuote()
    {
        $this->negotiableCartRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getQuoteName')
            ->willReturn('Punchout Quote Creation');

        $this->assertNotNull($this->adminConfigHelperMock->checkIsPunchoutQuote(1));
    }

    /**
     * Test checkIsPunchoutQuote with quoteId with false
     *
     * @return void
     */
    public function testCheckIsPunchoutQuotewithFalse()
    {
        $this->negotiableCartRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getQuoteName')
            ->willReturn('Upload To Quote Creation');

        $this->assertNotNull($this->adminConfigHelperMock->checkIsPunchoutQuote(1));
    }

    /**
     * Test getQuoteStatusKeyByQuoteId with session quoteId
     *
     * @return void
     */
    public function testGetQuoteStatusKeyByQuoteId()
    {
        $status = "created";
        $this->quoteFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('getData')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $this->assertNotNull($this->adminConfigHelperMock->getQuoteStatusKeyByQuoteId($status));
    }

    /**
     * Test getQuoteStatusKeyByQuoteId with with quote id
     *
     * @return void
     */
    public function testGetQuoteStatusKeyByQuoteIdWithQuoteId()
    {
        $status = "created";
        $clientId = 3422;
        $this->quoteFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('getData')
            ->willReturn(false);
        $this->customerSession->expects($this->once())
            ->method('getId')
            ->willReturn($clientId);
        $this->negotiableCartRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);
        $this->sortOrderBuilderMock->expects($this->once())
           ->method('setField')
           ->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())
           ->method('setDirection')
           ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
           ->method('setPageSize')
           ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
           ->method('setSortOrders')
           ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
           ->method('create')
           ->willReturn($this->searchCriteriaMock);
        $this->negotiableQuoteRepository->expects($this->once())
           ->method('getList')
           ->willReturn($this->quoteList);
        $this->quoteList->expects($this->once())
           ->method('getItems')
           ->willReturn([0 => $this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getId')->willReturn(355037);

        $this->assertNotNull($this->adminConfigHelperMock->getQuoteStatusKeyByQuoteId($status));
    }

    /**
     * Test getQuoteStatusKeyByQuoteId with without quote id
     *
     * @return void
     */
    public function testGetQuoteStatusKeyByQuoteIdWithoutQuoteId()
    {
        $status = "created";
        $this->quoteFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $this->quoteFactory->expects($this->once())
            ->method('getData')
            ->willReturn(false);
        $this->negotiableCartRepository->expects($this->any())
            ->method('get')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())
            ->method('getStatus')
            ->willReturn($status);
        $this->sortOrderBuilderMock->expects($this->once())
           ->method('setField')
           ->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())
           ->method('setDirection')
           ->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())
           ->method('create')
           ->willReturn($this->sortOrderMock);
        $this->searchCriteriaBuilderMock->expects($this->once())
           ->method('addFilter')
           ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
           ->method('setPageSize')
           ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
           ->method('setSortOrders')
           ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
           ->method('create')
           ->willReturn($this->searchCriteriaMock);
        $this->quoteList->expects($this->once())
           ->method('getItems')
           ->willReturn([$this->quote]);
        $this->negotiableQuoteRepository->expects($this->once())
           ->method('getList')
           ->willReturn($this->quoteList);

        $this->assertNotNull($this->adminConfigHelperMock->getQuoteStatusKeyByQuoteId($status));
    }

    /**
     * Test method for getFromEmail.
     *
     * @return void
     */
    public function testGetFromEmail()
    {
        $expectedResult = self::XML_PATH_FROM_EMAIL;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_FROM_EMAIL);

        $this->assertEquals($expectedResult, $this->adminConfigHelperMock->getFromEmail());
    }

    /**
     * Test getQuoteEditButtonMsg
     *
     * @return void
     */
    public function testGetQuoteEditButtonMsg()
    {
        $uploadToQuoteEditMsg = 'test';
        $this->scopeConfigMock->expects($this->any())
            ->method('getQuoteEditButtonMsg')
            ->willReturn($uploadToQuoteEditMsg);

        $this->assertNotEquals($uploadToQuoteEditMsg, $this->adminConfigHelperMock->getQuoteEditButtonMsg());
    }

     /**
      * Test isProductLineItemsSetToTrue
      *
      * @return void
      */
    public function testIsProductLineItemsSetToTrue()
    {
        $productJson = [
            'priceable' => true,
            'properties' => [
                (object)['name' => 'USER_SPECIAL_INSTRUCTIONS', 'value' => 'some instructions'],
            ],
        ];
        $result = $this->adminConfigHelperMock->isProductLineItems($productJson, true);

        $this->assertNotTrue($result);
    }

    /**
     * Test isProductLineItemsSetToFalse
     *
     * @return void
     */
    public function testIsProductLineItemsSetToFalse()
    {
        $productJson = [
            'priceable' => false,
            'properties' => [
            ],
        ];
        $result = $this->adminConfigHelperMock->isProductLineItems($productJson, true);

        $this->assertFalse($result);
    }

    /**
     * Test isProductLineItemsWithoutSendingValue
     *
     * @return void
     */
    public function testIsProductLineItemsWithoutSendingValue()
    {
        $productJson = [
            'priceable' => true,
            'properties' => [
                (object)['name' => 'USER_SPECIAL_INSTRUCTIONS', 'value' => 'some instructions'],
            ],
        ];
        $result = $this->adminConfigHelperMock->isProductLineItems($productJson);

        $this->assertTrue($result);
    }

    /**
     * Test isProductLineItems with array
     *
     * @return void
     */
    public function testIsProductLineItemsWithArray()
    {
        $productJson = [
            'priceable' => true,
            'properties' => [
                ['name' => 'USER_SPECIAL_INSTRUCTIONS', 'value' => 'some instructions'],
            ],
        ];
        $result = $this->adminConfigHelperMock->isProductLineItems($productJson, true);

        $this->assertEquals('some instructions', $result);
    }

    /**
     * Test isProductLineItemsSetToTrue
     *
     * @return void
     */
    public function testIsSiItemNonEditableSetToTrue()
    {
        $productJson = [
            'priceable' => true,
            'properties' => [
                (object)['name' => 'CUSTOMER_SI', 'value' => 'some instructions'],
            ],
        ];
        $result = $this->adminConfigHelperMock->isSiItemNonEditable($productJson);

        $this->assertNotTrue($result);
    }

    /**
     * Test isProductLineItems with array
     *
     * @return void
     */
    public function testIsSiItemNonEditableWithArray()
    {
        $productJson = [
            'priceable' => true,
            'properties' => [
                ['name' => 'CUSTOMER_SI', 'value' => 'some instructions'],
            ],
        ];
        $result = $this->adminConfigHelperMock->isSiItemNonEditable($productJson);

        $this->assertNotTrue($result);
    }

    /**
     * Test isProductLineItemsSetToFalse
     *
     * @return void
     */
    public function testIsSiItemNonEditableSetToFalse()
    {
        $productJson = [
            'priceable' => false,
            'properties' => [
                (object)['name' => 'CUSTOMER_SI', 'value' => 'some instructions'],
            ],
        ];
        $result = $this->adminConfigHelperMock->isSiItemNonEditable($productJson);

        $this->assertFalse($result);
    }

    /**
     * Test isSiItemEditBtnDisable for true
     *
     * @return void
     */
    public function testIsSiItemEditBtnDisableSetToTrue()
    {
        $productJson = [
            'externalSkus' => ['1233', '1234'],
        ];

        $result = $this->adminConfigHelperMock->isSiItemEditBtnDisable($productJson);

        $this->assertTrue($result);
    }

    /**
     * Test isSiItemEditBtnDisable for false
     *
     * @return void
     */
    public function testIsSiItemEditBtnDisableSetToFalse()
    {
        $productJson = [
            'externalSkus' => [],
        ];

        $result = $this->adminConfigHelperMock->isSiItemEditBtnDisable($productJson);

        $this->assertFalse($result);
    }

    /**
     * Test isUploadToQuoteGloballyEnabled
     *
     * @return void
     */
    public function testIsUploadToQuoteGloballyEnabled()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);

        $this->assertEquals(1, $this->adminConfigHelperMock->isUploadToQuoteGloballyEnabled());
    }

    /**
     * Test isItemPriceable
     *
     * @return void
     */
    public function testIsItemPriceable()
    {
        $productJson = '{
            "external_prod": [
              {
                "priceable": false
              }
            ]
          }';

        $this->assertEquals(false, $this->adminConfigHelperMock->isItemPriceable($productJson));
    }

    /**
     * Test isItemPriceable with true
     *
     * @return void
     */
    public function testIsItemPriceableWithTrue()
    {
        $productJson = '{
            "external_prod": [
              {
                "priceable": true
              }
            ]
          }';

        $this->assertEquals(true, $this->adminConfigHelperMock->isItemPriceable($productJson));
    }

    /**
     * Test isNonStandardFile
     *
     * @return void
     */
    public function testIsNonStandardFile()
    {
        $productJson = '{
            "external_prod": [
              {
                "isEditable": false,
                "priceable": false
              }
            ]
          }';

        $this->getQouteStatuskye();

        $this->assertEquals(
            true,
            $this->adminConfigHelperMock->isNonStandardFile($productJson)
        );
    }

    /**
     * Common assert to getQouteStatuskye
     *
     * @return void
     */
    public function getQouteStatuskye()
    {
        $this->quoteFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteFactory->expects($this->any())->method('getData')->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())->method('get')->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())->method('getExtensionAttributes')
        ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())->method('getNegotiableQuote')
        ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())
        ->method('getStatus')->willReturn("created");
        $this->quote->expects($this->any())->method('getId')->willReturn(123);
    }

    /**
     * Test isNonStandardFile with true
     *
     * @return void
     */
    public function testIsNonStandardFileWithTrue()
    {
        $productJson = '{
            "external_prod": [
              {
                "isEditable": true
              }
            ]
          }';

        $this->assertEquals(false, $this->adminConfigHelperMock->isNonStandardFile($productJson));
    }

    /**
     * Test checkoutQuotePriceisDashable
     *
     * @return void
     */
    public function testCheckoutQuotePriceisDashable()
    {
        $productJson = '{
            "external_prod": [
              {
                "priceable": ""
              }
            ]
          }';

        $this->getQouteStatuskye();
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->quoteItem]);
        $this->quoteItem->method('getOptionByCode')->willReturnSelf();
        $this->quoteItem->method('getValue')->willReturn($productJson);

        $this->assertEquals(1, $this->adminConfigHelperMock->checkoutQuotePriceisDashable($this->quote));
    }

    /**
     * Test checkoutQuoteItemPriceableValue
     *
     * @return void
     */
    public function testCheckoutQuoteItemPriceableValue()
    {
        $result = [
            'quoteItemData' => [
                ['item_id' => 12345]
            ]
        ];
        $productJson = '{
            "external_prod": [
              {
                "priceable": false
              }
            ]
          }';

        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->quoteItem]);
        $this->quoteItem->method('getOptionByCode')->willReturnSelf();
        $this->quoteItem->method('getValue')->willReturn($productJson);
        $this->quoteItem->method('getItemId')->willReturn(12345);

        $this->assertIsArray($this->adminConfigHelperMock->checkoutQuoteItemPriceableValue($result, $this->quote));
    }

    /**
     * Test checkoutQuoteItemPriceableValue with true
     *
     * @return void
     */
    public function testCheckoutQuoteItemPriceableValueWithTrue()
    {
        $result = [
            'quoteItemData' => [
                ['item_id' => 12345]
            ]
        ];
        $productJson = '{
            "external_prod": [
              {
                "priceable": true
              }
            ]
          }';

        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->quoteItem]);
        $this->quoteItem->method('getOptionByCode')->willReturnSelf();
        $this->quoteItem->method('getValue')->willReturn($productJson);
        $this->quoteItem->method('getItemId')->willReturn(12345);

        $this->assertIsArray($this->adminConfigHelperMock->checkoutQuoteItemPriceableValue($result, $this->quote));
    }

    /**
     * Test updateStatusLog
     *
     * @return void
     */
    public function testUpdateStatusLog()
    {

        $this->historyManagement->expects($this->once())->method('updateStatusLog')->willReturn(true);

        $this->assertNull($this->adminConfigHelperMock->updateStatusLog(1234));
    }

    /**
     * Test addCustomLog
     *
     * @return void
     */
    public function testAddCustomLog()
    {

        $this->historyManagement->expects($this->once())->method('addCustomLog')->willReturn(true);

        $this->assertNull($this->adminConfigHelperMock->addCustomLog(1234, ['test']));
    }

    /**
     * Test getQuoteStatusLabel with set to expire
     *
     * @return void
     */
    public function testGetQuoteStatusLabelWithSetToExpire()
    {
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')
            ->will($this->onConsecutiveCalls('2023-04-01', '2023-04-28'));

        $this->assertEquals(
            'Set to Expire',
            $this->adminConfigHelperMock->getQuoteStatusLabel('created', '2023-04-01')
        );
    }

    /**
     * Test getUploadToQuoteEmailConfigValue
     *
     * @return void
     */
    public function testGetUploadToQuoteEmailConfigValue()
    {
        $storeId = 1;
        $key = 'abc';
        $expectedResult = self::EMAIL_CONFIG_BASE_PATH . $key;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::EMAIL_CONFIG_BASE_PATH . $key);

        $this->assertEquals(
            $expectedResult,
            $this->adminConfigHelperMock->getUploadToQuoteEmailConfigValue($key, $storeId)
        );
    }

    /**
     * Test removeQuoteItem
     *
     * @return void
     */
    public function testRemoveQuoteItem()
    {
        $quoteId = 1345;
        $itemId = 2453;
        $this->cartItemRepositoryInterface
            ->expects($this->once())
            ->method('deleteById')
            ->willReturn(true);

        $this->assertNull($this->adminConfigHelperMock->removeQuoteItem($quoteId, $itemId));
    }

    /**
     * Test getDeletedItems
     *
     * @return void
     */
    public function testGetDeletedItems()
    {
        $deletedData = [
            [
                'action' => 'deleteItem',
                'itemId' => 'test'
            ]
        ];
        $this->customerSession
            ->expects($this->once())
            ->method('getUploadToQuoteActionQueue')
            ->willReturn($deletedData);

        $this->assertIsArray($this->adminConfigHelperMock->getDeletedItems());
    }

    /**
     * Test getTotalPriceInfForRemainingItem
     *
     * @return void
     */
    public function testGetTotalPriceInfForRemainingItem()
    {
        $sessionData = [
            [
                'rateQuoteResponse' => [
                        'alerts' => [
                            [
                                'code' => 'QCXS.SERVICE.ZERODOLLARSKU'
                            ]
                        ]
                    ]
            ]
        ];
        $this->customerSession
            ->expects($this->once())
            ->method('getUploadToQuoteActionQueue')
            ->willReturn($sessionData);

        $this->assertIsArray($this->adminConfigHelperMock->getTotalPriceInfForRemainingItem());
    }

    /**
     * Test getTotalPriceInfForRemainingItem without alerts
     *
     * @return void
     */
    public function testGetTotalPriceInfForRemainingItemWithoutAlerts()
    {
        $sessionData = [
            [
                'rateQuoteResponse' => [
                        'rateQuote' => [
                            'rateQuoteDetails' => [
                                [
                                    'grossAmount' => 23,
                                    'totalDiscountAmount' => 34,
                                    'netAmount' => 13,
                                    'taxableAmount' => 0,
                                    'taxAmount' => 0,
                                    'totalAmount' => 24,
                                    'productsTotalAmount' => 26,
                                    'deliveriesTotalAmount' => 15,
                                    'productLines' => [
                                        [
                                            'instanceId' => 1234
                                        ]
                                    ]
                                ]
                            ]

                        ]
                    ]
            ]
        ];
        $this->customerSession
            ->expects($this->once())
            ->method('getUploadToQuoteActionQueue')
            ->willReturn($sessionData);

        $this->assertIsArray($this->adminConfigHelperMock->getTotalPriceInfForRemainingItem());
    }

    /**
     * Test isQuoteNegotiated
     *
     * @return void
     */
    public function testIsQuoteNegotiated()
    {
        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuote ->expects($this->once())->method('load')->willReturnSelf();
        $this->negotiableQuote->expects($this->once())->method('getId')->willReturn(1);

        $this->assertTrue($this->adminConfigHelperMock->isQuoteNegotiated(123));
    }

    /**
     * Test getSiType
     *
     * @return void
     */
    public function testGetSiType()
    {
        $productJson = '{
            "external_prod": [
              {
                "priceable": false,
                "isEditable": true
              }
            ]
          }';

        $this->assertEquals("ADDITIONAL_PRINT_INSTRUCTIONS", $this->adminConfigHelperMock->getSiType($productJson));
    }

    /**
     * Test getProductJson
     *
     * @return void
     */
    public function testGetProductJson()
    {
        $productData = [
            'info_buyRequest' => [
                'external_prod' => [
                    [
                        'test' => 123
                    ]
                ]
            ]
        ];
        $this->quoteFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('load')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('getData')->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())->method('get')->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())->method('getExtensionAttributes')
        ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())->method('getNegotiableQuote')
        ->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())
        ->method('getStatus')->willReturn("ordered");
        $this->quoteItem->expects($this->once())->method('getId')->willReturn(123);
        $this->itemFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->itemFactory->expects($this->once())->method('load')->willReturnSelf();
        $this->itemFactory->expects($this->once())->method('getProductOptions')->willReturn($productData);

        $this->assertIsArray($this->adminConfigHelperMock->getProductJson($this->quoteItem, 123));
    }

    /**
     * Test getProductJson with status ordered
     *
     * @return void
     */
    public function testGetProductJsonWithStatusOrdered()
    {
        $productJson= '{
            "external_prod": {
                "0": {
                    "test": "123"
                }
            }
        }';
        $this->getQouteStatuskye();
        $this->quoteItem->expects($this->once())->method('getOptionByCode')->willReturnSelf();
        $this->quoteItem->expects($this->any())->method('getValue')->willReturn($productJson);

        $this->assertIsArray($this->adminConfigHelperMock->getProductJson($this->quoteItem, 123));
    }

    /**
     * Test updateLogHistory
     *
     * @return void
     */
    public function testUpdateLogHistory()
    {
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn('2024-01-10 05:40:06');

        $this->assertNull($this->adminConfigHelperMock->updateLogHistory('1234', 'processing_by_admin'));
    }

    /**
     * Test isU2QCustomerSIEnabled
     *
     * @return void
     */
    public function testisU2QCustomerSIEnabled()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);

        $this->assertEquals(1, $this->adminConfigHelperMock->isU2QCustomerSIEnabled());
    }

    /**
     * Test updateQuoteStatusWithDeclined
     *
     * @return void
     */
    public function testUpdateQuoteStatusWithDeclined()
    {
        $this->negotiableQuoteFactory->expects($this->any())->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())->method('load')->willReturnSelf();
        $this->negotiableQuote->expects($this->any())->method('getId')->willReturn(1234);
        $this->negotiableQuote->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->negotiableQuote->expects($this->any())->method('save')->willReturnSelf();

        $this->quoteGridFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteGridFactory->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteGridFactory->expects($this->any())->method('getId')->willReturn(3452);
        $this->quoteGridFactory->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->quoteGridFactory->expects($this->any())->method('save')->willReturnSelf();
        $this->updateStatus();
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn('2024-01-10 05:40:09');

        $this->assertEquals(1, $this->adminConfigHelperMock->updateQuoteStatusWithDeclined(1234, 'declined'));
    }

    /**
     * Test updateQuoteStatusWithDeclined with exception
     *
     * @return void
     */
    public function testUpdateQuoteStatusWithDeclinedWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willThrowException($exception);

        $this->assertFalse($this->adminConfigHelperMock->updateQuoteStatusWithDeclined(1234, 'declined'));
    }

    /**
     * Test isMarkAsDeclinedEnabled
     *
     * @return void
     */
    public function testIsMarkAsDeclinedEnabled()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->assertTrue($this->adminConfigHelperMock->isMarkAsDeclinedEnabled());
    }

    /**
     * Test getProductAttributeName
     *
     * @return void
     */
    public function testGetProductAttributeName()
    {
        $returnValue = 'test';
        $this->attributeSetRepositoryInterface->expects($this->once())->method('get')->willReturnSelf();
        $this->attributeSetRepositoryInterface->expects($this->once())
        ->method('getAttributeSetName')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->adminConfigHelperMock->getProductAttributeName(123));
    }

    /**
     * Test productImageUrl
     *
     * @return void
     */
    public function testProductImageUrl()
    {
        $returnValue = 'images.png';
        $arrData = [
            'file' => $returnValue
        ];
        $product = new \Magento\Framework\DataObject($arrData);
        $this->imageHelper->expects($this->once())->method('init')->willReturnSelf();
        $this->imageHelper->expects($this->once())->method('setImageFile')->willReturnSelf();
        $this->imageHelper->expects($this->once())->method('resize')->willReturnSelf();
        $this->imageHelper->expects($this->once())->method('getUrl')->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->adminConfigHelperMock->productImageUrl($product, 70, 65));
    }

    /**
     * Test check if negotiable quote existing for quote
     *
     * @return void
     */
    public function testIsNegotiableQuoteExistingForQuote()
    {
        $quoteId = 123;
        $this->negotiableCartRepository->expects($this->once())->method('get')->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())->method('getExtensionAttributes')->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())->method('getNegotiableQuote')->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())->method('getId')->willReturn($quoteId);

        $this->assertTrue($this->adminConfigHelperMock->isNegotiableQuoteExistingForQuote($quoteId));
    }

    /**
     * Test check if negotiable quote existing for quote
     *
     * @return void
     */
    public function testIsNegotiableQuoteExistingForQuoteWithFalse()
    {
        $quoteId = 123;
        $this->negotiableCartRepository->expects($this->once())->method('get')->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())->method('getExtensionAttributes')->willReturnSelf();
        $this->negotiableCartRepository->expects($this->once())->method('getNegotiableQuote')->willReturnSelf();
        $this->negotiableCartRepository->expects($this->any())->method('getId')->willReturn(1234);

        $this->assertFalse($this->adminConfigHelperMock->isNegotiableQuoteExistingForQuote($quoteId));
    }

    /**
     * Test getMyQuoteMaitenanceFixToggle
     *
     * @return void
     */
    public function testGetMyQuoteMaitenanceFixToggle()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->assertTrue($this->adminConfigHelperMock->getMyQuoteMaitenanceFixToggle());
    }

    /**
     * Test deactivateQuote
     *
     * @return void
     */
    public function testDeactivateQuote()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->once())->method('load')->willReturnSelf();
        $this->negotiableQuote->expects($this->once())->method('setIsRegularQuote')->willReturnSelf();
        $this->negotiableQuote->expects($this->once())->method('save')->willReturnSelf();
        $this->cartRepositoryInterface->expects($this->once())->method('get')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('setIsActive')->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();

        $this->assertNULL($this->adminConfigHelperMock->deactivateQuote(755642));
    }

    /**
     * Test deactivateQuote with exception
     *
     * @return void
     */
    public function testDeactivateQuoteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willThrowException($exception);

        $this->assertNull($this->adminConfigHelperMock->deactivateQuote(755642));
    }

    /**
     * Test isMagentoQuoteDetailEnhancementToggleEnabled
     *
     * @return void
     */
    public function testIsMagentoQuoteDetailEnhancementToggleEnabled()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->assertEquals(true, $this->adminConfigHelperMock->isMagentoQuoteDetailEnhancementToggleEnabled());
    }

     /**
     * Test quoteexpiryIssueFixToggle
     *
     * @return void
     */
    public function testquoteexpiryIssueFixToggle()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->assertEquals(true, $this->adminConfigHelperMock->quoteexpiryIssueFixToggle());
    }

}
