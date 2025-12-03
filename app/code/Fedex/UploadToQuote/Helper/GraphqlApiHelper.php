<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Helper;

use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\Product\Type;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\NegotiableQuote\Api\Data\CommentInterfaceFactory;
use Magento\NegotiableQuote\Model\CommentRepository;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Store\Model\ScopeInterface;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\Mars\Helper\PublishToQueue;
use Fedex\Mars\Model\Config as MarsConfig;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\QuoteFactory;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Exception;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Sales\Model\OrderFactory;

/**
 * GraphqlApiHelper class for api methods
 */
class GraphqlApiHelper extends AbstractHelper
{
    private const DATETIMEFORMAT = 'Y-m-d\TH:i:sP';

    public const FUSE_ADD_TO_QUOTE_PRODUCT =
    'fedex/upload_to_quote_config/fuse_add_to_quote_item';

    public const FUSE_SAVE_TO_QUOTE_BEFORE_RATE_QUOTE =
    'environment_toggle_configuration/environment_toggle/mazegeeks_d209908';

    public const FUSE_BIDDING_IN_STORE_UPDATES =
    'environment_toggle_configuration/environment_toggle/mazegeeks_B2361911';

    public const TIGER_D236536 = 'tiger_d236356';

    /** @var string */
    public const TIGER_FEATURE_B_2645989 = 'tiger_feature_b2645989';

    public const RETAIL_UPLOAD_TO_QUOTE = 'tiger_team_E_469378_u2q_pickup';

    public const BIDDING = 'bidding';

    public const COUPON = 'COUPON';

    public const TIME_FORMAT = 'g:i a';

    /** @var CommentRepositoryInterface */
    protected $commentRepository;

    /** @var CommentInterfaceFactory  */
    protected $commentFactory;

    /** @var CartDataHelper $cartDataHelper */
    protected $cartDataHelper;

    /** @var CustomerRepositoryInterface $customerRepository */
    protected $customerRepository;

    /** @var CompanyRepositoryInterface $companyRepository */
    protected $companyRepository;

    /** @var TimezoneInterface $timezoneInterface */
    private $timezoneInterface;

    /** @var AdminConfigHelper $adminConfigHelper */
    private $adminConfigHelper;

    /** @var NegotiableQuoteRepositoryInterface $negotiableQuoteRepository */
    private $negotiableQuoteRepository;

    /**  @var History $quoteHistory */
    private $quoteHistory;

    /**  @var searchCriteriaBuilder $searchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /**  @var ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

    /**  @var FXORateQuote $fxoRateQuote */
    protected FXORateQuote $fxoRateQuote;

    /**
     * @var PublishToQueue
     */
    private PublishToQueue $publish;

    /**
     * @var MarsConfig
     */
    private MarsConfig $marsConfig;

    /**
     * @var SessionManagerInterface $session
     */
    private $session;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CartIntegrationRepositoryInterface $cartIntegrationRepository
     */
    private $cartIntegrationRepository;

    /**
     * @var FuseBidViewModel $fuseBidViewModel
     */
    protected $fuseBidViewModel;

    /**
     * @var OrderFactory $order
     */
    protected $order;

    /** @var $statusEnumValues  */
    private $statusMapping = [
        NegotiableQuoteInterface::STATUS_CREATED => 'CREATED',
        NegotiableQuoteInterface::STATUS_EXPIRED => 'EXPIRED',
        NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN => 'SENT',
        NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER => 'CHANGE_REQUESTED',
        NegotiableQuoteInterface::STATUS_DECLINED => 'CANCELED',
        NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN => 'REQUESTED',
        NegotiableQuoteInterface::STATUS_ORDERED  => 'ORDERED',
        NegotiableQuoteInterface::STATUS_CLOSED  => 'CLOSED',
        NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER => 'EXPIRED',
    ];

    private $newStatusMapping = [
        NegotiableQuoteInterface::STATUS_CREATED => 'CREATED',
        NegotiableQuoteInterface::STATUS_EXPIRED => 'EXPIRED',
        NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN => 'SENT',
        NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER => 'CHANGE_REQUESTED',
        NegotiableQuoteInterface::STATUS_DECLINED => 'CANCELED',
        NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN => 'REQUESTED',
        NegotiableQuoteInterface::STATUS_ORDERED  => 'ORDERED',
        NegotiableQuoteInterface::STATUS_CLOSED  => 'CLOSED',
        NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER => 'EXPIRED',
        AdminConfigHelper::NBC_SUPPORT => 'NBC_SUPPORT',
        AdminConfigHelper::NBC_PRICED => 'NBC_PRICED'
    ];

    /**
     * @param Context $context
     * @param CommentRepository $commentRepository
     * @param CommentInterfaceFactory $commentFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param CartDataHelper $cartDataHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TimezoneInterface $timezoneInterface
     * @param \Fedex\UploadToQuote\Helper\AdminConfigHelper $adminConfigHelper
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param History $quoteHistory
     * @param ScopeConfigInterface $scopeConfig
     * @param FXORateQuote $fxoRateQuote
     * @param PublishToQueue $publish
     * @param MarsConfig $marsConfig
     * @param SessionManagerInterface $session
     * @param LoggerInterface $logger
     * @param QuoteFactory $quoteFactory
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param FuseBidViewModel $fuseBidViewModel
     * @param OrderFactory $order
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        commentRepository $commentRepository,
        CommentInterfaceFactory $commentFactory,
        CustomerRepositoryInterface $customerRepository,
        CompanyRepositoryInterface $companyRepository,
        CartDataHelper $cartDataHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TimezoneInterface $timezoneInterface,
        AdminConfigHelper $adminConfigHelper,
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        History $quoteHistory,
        ScopeConfigInterface $scopeConfig,
        FXORateQuote $fxoRateQuote,
        PublishToQueue $publish,
        MarsConfig $marsConfig,
        SessionManagerInterface $session,
        LoggerInterface $logger,
        QuoteFactory $quoteFactory,
        CartIntegrationRepositoryInterface $cartIntegrationRepository,
        FuseBidViewModel $fuseBidViewModel,
        OrderFactory $order,
        protected readonly ToggleConfig $toggleConfig,
    ) {
        parent::__construct($context);
        $this->commentRepository = $commentRepository;
        $this->commentFactory = $commentFactory;
        $this->customerRepository = $customerRepository;
        $this->companyRepository = $companyRepository;
        $this->cartDataHelper = $cartDataHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->timezoneInterface = $timezoneInterface;
        $this->adminConfigHelper = $adminConfigHelper;
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->quoteHistory = $quoteHistory;
        $this->scopeConfig = $scopeConfig;
        $this->fxoRateQuote = $fxoRateQuote;
        $this->publish = $publish;
        $this->marsConfig = $marsConfig;
        $this->session = $session;
        $this->logger = $logger;
        $this->quoteFactory = $quoteFactory;
        $this->cartIntegrationRepository = $cartIntegrationRepository;
        $this->fuseBidViewModel = $fuseBidViewModel;
        $this->order = $order;
    }

    /**
     * Get Quote Info
     *
     * @param object $quote
     * @return array
     */
    public function getQuoteInfo($quote)
    {
        $quoteId = (int) $quote->getId();
        $quoteData = $this->negotiableQuoteRepository->getById($quoteId);
        if($this->adminConfigHelper->toggleUploadToQuoteSubmitDate()) {
            $quoteCreationDate = $this->timezoneInterface->date(strtotime($this->adminConfigHelper->getSubmitDate($quoteId)), null, true, false)
            ->format(self::DATETIMEFORMAT);
        } else {
            $quoteCreationDate = $this->timezoneInterface->date(strtotime($quote->getCreatedAt()), null, true, false)
            ->format(self::DATETIMEFORMAT);
        }
        $quoteObj = $this->quoteFactory->create()->load($quoteId);
        $quoteUpdatedDate = '';
        if ($quoteObj->getUpdatedAt()){
            $quoteUpdatedDate = $this->timezoneInterface->date(strtotime($quoteObj->getUpdatedAt()), null, true, false)
            ->format(self::DATETIMEFORMAT);
        }
        $quoteSubmittedDate = "";
        if ($quoteObj->getConvertedAt()){
            $quoteSubmittedDate = $this->timezoneInterface->date(strtotime($quoteObj->getConvertedAt()), null, true, false)
                ->format(self::DATETIMEFORMAT);
        }
        $quoteExpirationDate = $this->adminConfigHelper
            ->getExpiryDate($quoteId, self::DATETIMEFORMAT);
        $quoteInfo = [];
        $quoteInfo['quote_id'] = $quoteId;
        $quoteInfo['quote_creation_date'] =  $quoteCreationDate;
        $quoteInfo['quote_updated_date'] =  $quoteUpdatedDate;
        $quoteInfo['quote_submitted_date'] =  $quoteSubmittedDate;
        $quoteInfo['quote_expiration_date'] = $quoteExpirationDate;
        $quoteInfo['gross_amount'] = $quote->getSubtotal();
        $quoteInfo['discount_amount'] = $quote->getDiscount();
        $quoteInfo['quote_total'] = $quote->getBaseGrandTotal();
        if ($this->adminConfigHelper->isToggleB2564807Enabled()){
            $quoteInfo['quote_status'] = $this->newStatusMapping[$quoteData->getStatus()] ?? "";
        }else{
            $quoteInfo['quote_status'] = $this->statusMapping[$quoteData->getStatus()] ?? "";
        }
        $quoteInfo['hub_centre_id'] = $quoteData->getData('quote_mgnt_location_code');
        $quoteInfo['location_id'] = $quoteData->getData('quote_mgnt_location_code');

        return $quoteInfo;
    }

    /**
     * Get Contact Info
     *
     * @param object $quote
     * @return array
     */
    public function getQuoteContactInfo($quote)
    {
        $contactResult = [];
        // Check if the data is dummy
        if ($this->isDummyContactInfo($quote)) {
            return $contactResult;
        }

        $contactResult['email'] = $quote->getCustomerEmail();
        $contactResult['phone_number'] = $quote->getCustomerTelephone();
        $contactResult['first_name'] = $quote->getCustomerFirstname();
        $contactResult['last_name'] = $quote->getCustomerLastname();
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled()) {
            $contactResult['retail_customer_id'] = $this->getRetailCustomerId($quote);
        }

        return $contactResult;
    }

    /**
     * Check if isDummyContactInfo
     *
     * @param object $quote
     * @return boolean
     */
    private function isDummyContactInfo($quote)
    {
        $dummyEmailPattern = '/^dummy\.customer\+.*@dummy\.com$/';
        $dummyFirstName = 'Dummy';
        $dummyLastName = 'Customer';

        $isDummyEmail = '';
        if(!empty($quote->getCustomerEmail())){
            $isDummyEmail = preg_match($dummyEmailPattern, $quote->getCustomerEmail());
        }
        $isDummyName = $quote->getCustomerFirstname() === $dummyFirstName &&
            $quote->getCustomerLastname() === $dummyLastName;

        return $isDummyEmail || $isDummyName;
    }

    /**
     * Get Retail Customer Id
     *
     * @param object $quote
     * @return string
     */
    public function getRetailCustomerId($quote)
    {
        try {
            $quoteIntegration = $this->cartIntegrationRepository->getByQuoteId($quote->getId());
            return $quoteIntegration ? $quoteIntegration->getRetailCustomerId() : "";
        } catch (Exception $e) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ .
            'Error in Fetching Quote Integration: ' . $e->getMessage());
            return "";
        }
    }

    /**
     * Get Quote Line Item
     *
     * @param object|Quote $quote
     * @param array  $rateResponse
     * @return array
     */
    public function getQuoteLineItems($quote, $rateResponse)
    {
        $lineItemResult = $quoteItemResult = [];
        if ($this->isD236356Enabled()) {
            $quoteItem = $quote->getAllItems();
        } else {
            $quoteItem = $quote->getAllVisibleItems();
        }
        $buyRequest = "";
        $originalFiles = "";
        /** @var QuoteItem $item */
        foreach ($quoteItem as $item) {
            if ($item->getProductType() === Type::TYPE_BUNDLE) {
                continue;
            }
            $editable = true;
            $buyRequest = "";
            $originalFiles = "";
            $userProductName = "";
            $infoBuyRequest = $item->getOptionByCode('info_buyRequest');
            if ($infoBuyRequest->getValue()) {
                $decodedProductData = json_decode($infoBuyRequest->getValue(), true);
                $userProductName = $decodedProductData['external_prod'][0]['userProductName'] ?? $item->getName();
                if (isset($decodedProductData['external_prod'][0])) {
                    $buyRequest = json_encode($decodedProductData['external_prod'][0]);
                }
                if (isset($decodedProductData['originalFiles'][0])) {
                    $originalFiles = json_encode($decodedProductData['originalFiles'][0]);
                }
            }

            if ($item->getMiraklOfferId()) {
                $editable = false;
            }

            $quoteItemResult['editable'] = $editable;
            $quoteItemResult['item_id'] = $item->getId();
            $quoteItemResult['name'] = $userProductName;
            $quoteItemResult['qty'] = $item->getQty();
            $quoteItemResult['price'] = $item->getCustomPrice();
            $quoteItemResult['discount_amount'] = $item->getDiscount();
            $quoteItemResult['base_price'] = $item->getCustomPrice();
            $quoteItemResult['row_total'] = $item->getRowTotal();
            $quoteItemResult['product'] = $buyRequest;
            $quoteItemResult['original_files'] = $originalFiles;
            $quoteItemResult['lineItemRateDetails'] = $this->getLineItemRateDetails($rateResponse, $item->getId());
            $lineItemResult[] = $quoteItemResult;
        }

        return $lineItemResult;
    }

        /**
         * Get Quote Line Item
         *
         * @param object $quote
         * @return array
         */
        public function getQuoteLineItemsForApprovedQuote($orderIncrementId)
        {
            $lineItemResult = [];
            $orderData = $this->order->create()->loadByIncrementId($orderIncrementId);
            if ($this->isD236356Enabled()) {
                $orderItems = $orderData->getAllItems();
            } else {
                $orderItems = $orderData->getAllVisibleItems();
            }
            $lineItemResult = $quoteItemResult = [];
            $buyRequest = "";
            $originalFiles = "";
            /** @var \Magento\Sales\Api\Data\OrderItemInterface $item */
            foreach ($orderItems as $item) {
                if ($item->getProductType() === Type::TYPE_BUNDLE) {
                    continue;
                }
                $editable = true;
                $buyRequest = "";
                $originalFiles = "";
                $userProductName = "";
                $productValues = $item->getProductOptions();
                $infoBuyRequest = $productValues['info_buyRequest'] ?? null;
                if (!empty($infoBuyRequest)) {
                    $userProductName = $infoBuyRequest['external_prod'][0]['userProductName'] ?? $item->getName();
                    if (isset($infoBuyRequest['external_prod'][0])) {
                        $buyRequest = json_encode($infoBuyRequest['external_prod'][0]);
                    }
                    if (isset($infoBuyRequest['originalFiles'][0])) {
                        $originalFiles = json_encode($infoBuyRequest['originalFiles'][0]);
                    }
                }

                if ($item->getMiraklOfferId()) {
                    $editable = false;
                }
                $quoteItemResult['editable'] = $editable;
                $quoteItemResult['item_id'] = $item->getId();
                $quoteItemResult['name'] = $userProductName;
                $quoteItemResult['qty'] = $item->getQtyOrdered();
                $quoteItemResult['price'] = $item->getPrice();
                $quoteItemResult['discount_amount'] = $item->getDiscountAmount();
                $quoteItemResult['base_price'] = $item->getPrice();
                $quoteItemResult['row_total'] = $item->getRowTotal();
                $quoteItemResult['product'] = $buyRequest;
                $quoteItemResult['original_files'] = $originalFiles;
                $quoteItemResult['lineItemRateDetails'] = $this->getLineItemRateDetailsForApprovedQuote($item);
                $lineItemResult[] = $quoteItemResult;
            }

            return $lineItemResult;
        }
    /**
     * Get FxoAccountNumber
     *
     * @param object $quote
     * @return mixed
     */
    public function getFxoAccountNumberOfQuote($quote)
    {
        $fxoAccount = $quote->getFedexAccountNumber();

        return $this->cartDataHelper->decryptData($fxoAccount);
    }

    /**
     * Get Quote Company Name
     *
     * @param object $quote
     * @return string|null
     * @throws GraphQlInputException
     */
    public function getQuoteCompanyName($quote)
    {
        $customerId = $quote->getCustomerId();
        try {
            $customer = $this->customerRepository->getById($customerId);
            $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
            if ($companyAttributes) {
                $companyId = $companyAttributes->getCompanyId();
                if ($companyId) {
                    $company = $this->companyRepository->get((int) $companyId);
                    return $company->getCompanyName();
                }
            }
        } catch (Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }

    /**
     * Set Quote Notes
     *
     * @param string $commentText
     * @param string $quoteId
     * @param string $type
     */
    public function setQuoteNotes($commentText, $quoteId, $type)
    {
        switch ($type) {
            case "quote_change_requested":
            case "quote_expired":
            case "quote_approved":
            case "quote_declined":
            case "nbc_priced":
            case "nbc_support":
                $creatorType = 3;
                break;
            default:
                $creatorType = 2;
        }
        try {
            $comment = $this->commentFactory->create();
            $comment->setParentId($quoteId);
            $comment->setComment($commentText);
            $comment->setCreatorType($creatorType);
            $comment->setCreatorId('2');
            $comment->setType($type);
            $comment->save();

            if ($this->marsConfig->isEnabled()) {
                $this->publish->publish((int)$quoteId, 'negotiableQuote');
            }

        } catch (Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }

    /**
     * Get Quote Notes
     *
     * @param string $quoteId
     * @return array
     */
    public function getQuoteNotes($quoteId): array
    {
        $notes = [];
        $this->searchCriteriaBuilder->addFilter('parent_id', $quoteId);
        $comments = $this->commentRepository->getList($this->searchCriteriaBuilder->create())->getItems();
        foreach ($comments as $comment) {
            $type = $comment->getType();
            if ($type === null) {
                $type = ($comment->getComment() === "Review my quote") ? "quote_created" : "quote_updated";
            }
            $notes[] = [
                'date' => $this->timezoneInterface->date(strtotime($comment->getCreatedAt()))
                    ->format(self::DATETIMEFORMAT),
                'comment' => $comment->getComment(),
                'created_by' => ($comment->getCreatorType() == 2) ? 'STORE' : 'ECOMM',
                'type' =>  $type
            ];
        }

        return $notes;
    }

    /**
     * Update Quote status
     *
     * @param obj $quote
     * @param array $status
     * @return void
     */
    public function changeQuoteStatus($quote, $status)
    {
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $negotiableQuote->setStatus($status);
        $negotiableQuote->save();
        $this->adminConfigHelper->updateGridQuoteStatus($quote->getId(), $status);
        $this->quoteHistory->updateStatusLog($quote->getId(), true);
        $currentDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $currentTime = $this->timezoneInterface->date()->format(self::TIME_FORMAT);
        $value['quoteStatus'] = $status;
        if ($status == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN) {
            $value['readyForReviewDate'] = $currentDate;
            $value['readyForReviewTime'] = $currentTime;
        } elseif ($status == NegotiableQuoteInterface::STATUS_CLOSED) {
            $value['closedDate'] = $currentDate;
            $value['closedTime'] = $currentTime;
        }
        $values[] = $value;
        $this->adminConfigHelper->addCustomLog($quote->getId(), $values);
    }

    /**
     * Get Fuse AddToQuote Product
     *
     * @return obj
     */
    public function getFuseAddToQuoteProduct()
    {
        return $this->scopeConfig->getValue(
            self::FUSE_ADD_TO_QUOTE_PRODUCT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get RateSummary Data
     *
     * @param array $rateResponse
     * @return array
     */
    public function getRateSummaryData($rateResponse)
    {
        $keysOfRateSummaryData = [
            'grossAmount',
            'discounts',
            'totalDiscountAmount',
            'netAmount',
            'taxableAmount',
            'taxAmount',
            'totalAmount',
            'totalFees',
            'productsTotalAmount',
            'deliveriesTotalAmount',
            'estimatedVsActual'
        ];

        $rateSummaryResultData = [];
        foreach ($keysOfRateSummaryData as $key) {
            $rateSummaryResultData[$key] = $rateResponse['output']['rateQuote']['rateQuoteDetails'][0][$key] ?? null;
        }

        return $rateSummaryResultData;
    }

    /**
     * Get RateSummaryDataForApprovedQuote Data
     *
     * @param int $quoteId
     * @return array
     */
    public function getRateSummaryDataForApprovedQuote($orderIncrementId)
    {
        $keysOfRateSummaryData = [
            'grossAmount' => 'subtotal',
            'discounts' => 'discount_amount',
            'totalDiscountAmount' => 'discount_amount',
            'netAmount' => 'subtotal',
            'taxableAmount'=> 'subtotal_incl_tax',
            'taxAmount' => 'tax_amount',
            'totalAmount' => 'grand_total',
            'totalFees' => 'total_fees',
            'productsTotalAmount'  => 'subtotal' ,
            'deliveriesTotalAmount' => 'shipping_amount',
            'estimatedVsActual' => 'estimatedVsActual'
        ];
        $orderData = $this->order->create()->loadByIncrementId($orderIncrementId);
        $rateSummaryResultData = [];
        $discountData  = [];
        foreach ($keysOfRateSummaryData as $key => $value) {

            if ($key == 'discounts') {
                $discountData = $orderData->getData($value) ?? [];
                $rateSummaryResultData[$key][] = [
                    'amount' => $discountData,
                    'type' => $this->getDiscountType()
                ];
            } elseif ($key == 'estimatedVsActual') {
                $rateSummaryResultData[$key] = "Actual";
            } elseif ($key == 'netAmount' && $orderData->getDiscountAmount() > 0 && $this->toggleConfig->getToggleConfigValue(self::TIGER_FEATURE_B_2645989)) {
                $rateSummaryResultData[$key] = $orderData->getSubtotal() - $orderData->getDiscountAmount();
            }elseif (isset($orderData[$value])) {
                $rateSummaryResultData[$key] = $orderData->getData($value) ?? null;
            }
        }

        return $rateSummaryResultData;
    }

    /**
     * @return string
     */
    private function getDiscountType(): string
    {
        return $this->toggleConfig->getToggleConfigValue(self::TIGER_FEATURE_B_2645989)
            ? self::COUPON
            : self::BIDDING;
    }


    /**
     * Get RateSummary Data
     *
     * @param array $rateResponse
     * @param int $itemId
     * @return array
     */
    public function getLineItemRateDetails($rateResponse, $itemId)
    {
        $rateSummaryResultData = [];
        if (!isset($rateResponse['output']['rateQuote']['rateQuoteDetails'][0]['productLines'])) {
            return $rateSummaryResultData;
        }
        foreach ($rateResponse['output']['rateQuote']['rateQuoteDetails'][0]['productLines'] as $lineItem) {
            if ($lineItem['instanceId'] == $itemId) {
                $keys = [
                    'instanceId',
                    'productId',
                    'type',
                    'name',
                    'userProductName',
                    'unitQuantity',
                    'unitOfMeasurement',
                    'priceable',
                    'productRetailPrice',
                    'productLinePrice',
                    'productDiscountAmount',
                    'productLineDiscounts',
                    'productLineDetails',
                    'links',
                    'specialInstructions',
                    'reorderCatalogReference',
                    'lineReorderEligibility',
                    'vendorReference',
                    'productTaxAmount'
                ];

                foreach ($keys as $key) {
                    $rateSummaryResultData[$key] = $lineItem[$key] ?? null;
                }

                break;
            }
        }
        return $rateSummaryResultData;
    }

    /**
     * Get RateSummary Data
     *
     * @param array $rateResponse
     * @param int $itemId
     * @return array
     */
    public function getLineItemRateDetailsForApprovedQuote($item)
    {
        $rateSummaryResultData = [];
        $productValues = $item->getProductOptions();
        $orderItems = $productValues['info_buyRequest']['productRateTotal'] ?? [];
        foreach ($orderItems as $lineItem) {
            $keys = [
                'instanceId',
                'productId',
                'type',
                'name',
                'userProductName',
                'unitQuantity',
                'unitOfMeasurement',
                'priceable',
                'productRetailPrice',
                'productLinePrice',
                'productDiscountAmount',
                'productLineDiscounts',
                'productLineDetails',
                'links',
                'specialInstructions',
                'reorderCatalogReference',
                'lineReorderEligibility',
                'vendorReference',
                'productTaxAmount'
            ];

            foreach ($keys as $key) {
                $rateSummaryResultData[$key] = $lineItem[$key] ?? null;
            }
            break;
        }
        return $rateSummaryResultData;
    }

    /**
     * Get Rate Response
     *
     * @param obj $quote
     * @param array|null $quoteItemsArray
     * @return array
     */
    public function getRateResponse($quote, $quoteItemsArray = null)
    {
        $uploadToQuoteRequest = [];
        $discountIntent= $this->getDiscountIntentForQuote($quote);
        $rateResponse = $this->fxoRateQuote->getFXORateQuote(
            $quote,
            null,
            false,
            $uploadToQuoteRequest,
            $quoteItemsArray,
            $discountIntent
        );

        if (isset($rateResponse['errors'][0]['message'])) {
            throw new GraphQlInputException(
                __("Rate API Error: " . $rateResponse['errors'][0]['message'])
            );
        }
        if (isset($rateResponse['errors']) && !empty($rateResponse['errors'])) {
            throw new GraphQlInputException(
                __("Rate API Error: API failure")
            );
        }

        return $rateResponse;
    }

    /**
     * Add logs for Grahql API
     *
     * @param array $logData
     */
    public function addLogsForGraphqlApi($logData)
    {
        $sessionId = $this->session->getSessionId();
        $logData['sessionId'] = $sessionId;
        $this->logger->Info(__METHOD__ . ':' . __LINE__
        . '==========='.$logData['query'].' Graphql API call===========');
        $this->logger->Info(__METHOD__ . ':' . __LINE__ . 'session Id=='. $sessionId);
        $this->logger->Info(__METHOD__ . ':' . __LINE__ . ': log Data for quotes API: ' .var_export($logData, true));
    }

    /**
     * Get discount Intent for quote
     *
     * @param obj $quote
     */
    public function getDiscountIntentForQuote($quote)
    {
        $discountIntent = null;
        $quoteItem = $quote->getAllVisibleItems();
        foreach ($quoteItem as $item) {
            $infoBuyRequest = $item->getOptionByCode('info_buyRequest');
            if ($infoBuyRequest->getValue()) {
                $decodedProductData = json_decode($infoBuyRequest->getValue(), true);
                if (isset($decodedProductData['discountIntent'])) {
                    $discountIntent = $decodedProductData['discountIntent'];
                }
            }
        }

        return $discountIntent;
    }

     /**
     * Save Quote before the rate quote call fix toggle
     *
     * @return boolean
     */
    public function quotesavebeforeratequoteFixToggle()
    {
        return $this->scopeConfig->getValue(
            self::FUSE_SAVE_TO_QUOTE_BEFORE_RATE_QUOTE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Allow in store revision requested in bidding flow
     *
     * @return boolean
     */
    public function quotebiddinginstoreupdatesFixToggle()
    {
        return $this->scopeConfig->getValue(
            self::FUSE_BIDDING_IN_STORE_UPDATES,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isD236356Enabled(): bool
    {
        return $this->toggleConfig->getToggleConfigValue(self::TIGER_D236536);
    }

    /**
     * @return bool|int|string|null
     */
    public function isTigerRetailUploadToQuoteEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue(self::RETAIL_UPLOAD_TO_QUOTE);
    }

}
