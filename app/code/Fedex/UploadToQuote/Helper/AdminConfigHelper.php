<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Helper;

use Fedex\ProductBundle\Api\ConfigInterface as ProductBundleConfig;
use Fedex\UploadToQuote\Model\QuoteGrid;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Model\CompanyRepository;
use Magento\Quote\Model\QuoteFactory;
use Magento\NegotiableQuote\Model\NegotiableCartRepository;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteFactory;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\NegotiableQuote\Model\HistoryManagementInterface;
use Magento\NegotiableQuote\Model\History;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Fedex\UploadToQuote\Model\QuoteGridFactory;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\CartFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Api\Data\HistoryInterface;
use Magento\Framework\Api\SortOrder;
use Magento\NegotiableQuote\Model\HistoryRepositoryInterface;

/**
 * UploadtoQuote AdminConfigHelper class
 */
class AdminConfigHelper extends AbstractHelper
{
    public const CONFIG_BASE_PATH = 'fedex/upload_to_quote_config/';
    public const NON_STANDARD_CATALOG_CONFIG_BASE_PATH = 'fedex/non_standard_catalog_popup_model_config/';
    public const XML_PATH_FROM_EMAIL = 'fedex/upload_to_quote_config/from_email';
    public const XML_PATH_QUOATE_DECLINE_USER_EMAIL_ENABLE =
        'fedex/transactional_email/quote_decline_customer_email_enable';
    public const XML_PATH_QUOATE_DECLINE_EMAIL_TEMPLATE =
        'fedex/transactional_email/quote_decline_customer_email_template';
    public const XML_PATH_QUOATE_CHANGE_REQUEST_USER_EMAIL_ENABLE =
        'fedex/transactional_email/quote_change_request_email_enable';
    public const XML_PATH_QUOATE_CHANGE_REQUEST_EMAIL_TEMPLATE =
        'fedex/transactional_email/quote_change_request_email_template';
    public const XML_PATH_QUOTE_READY_FOR_REVIEW_EMAIL_ENABLE =
        'fedex/transactional_email/quote_ready_for_review_email_enable';
    public const XML_PATH_QUOTE_READY_FOR_REVIEW_EMAIL_TEMPLATE =
        'fedex/transactional_email/quote_ready_for_review_email_template';
    public const XML_PATH_QUOTE_CONFIRMATION_EMAIL_ENABLE =
        'fedex/transactional_email/quote_confirmation_email_enable';
    public const XML_PATH_QUOTE_CONFIRMATION_EMAIL_TEMPLATE =
        'fedex/transactional_email/quote_confirmation_email_template';
    public const QUOTE_WHY_CAN_NOT_EDIT_BUTTON =
        'fedex/upload_to_quote_config/quote_why_can_not_edit_button';
    public const EMAIL_CONFIG_BASE_PATH = 'fedex/transactional_email/';
    public const DECLINED = 'declined';
    public const CHANGE_REQUEST = 'change_request';
    public const READY_FOR_REVIEW = 'submitted_by_admin';
    public const NBC_SUPPORT = 'nbc_support';
    public const NBC_PRICED = 'nbc_priced';
    public const STATUS_NBC_SUPPORT = 'NBC Support';
    public const STATUS_NBC_PRICED = 'NBC Priced';
    public const CONFIRMED = 'confirmed';
    public const EXPIRED = 'expired';
    public const EXPIRATION = 'expiration';
    public const APPROVED = 'ordered';
    public const EXPIRATION_PERIOD = '+30 days';
    public const DECLINED_BY_TEAM = 'declined_by_team';
    public const DEFAULT_EXPIRATION_PERIOD_TIME = 'quote/general/default_expiration_period_time';
    public const DEFAULT_EXPIRATION_PERIOD_COUNT = 'quote/general/default_expiration_period';
    public const TIME_FORMAT = 'g:i a';
    /**
     * Toggle for D-206707 U2Q_Getting "Schedule Maintenance" after clicking on Quote History
     * @var string XML_PATH_TOGGLE_D206707
     */
    public const XML_PATH_TOGGLE_D206707 = 'tiger_d206707';
    /**
     * Toggle for D-213254 U2Q_Expiry issue fix
     * @var string QUOTE_EXPIRY_ISSUE_FIX
     */

    public const QUOTE_EXPIRY_ISSUE_FIX = 'mazegeek_u2q_quote_expiry_issue_fix';

    /**
     * @var string XML_PATH_TOGGLE_D226511
     */
    public const XML_PATH_TOGGLE_D226511 = 'mazegeeks_email_timezone_issue';

    /**
     * @var string XML_PATH_TOGGLE_B2564807
     */
    public const XML_PATH_TOGGLE_B2564807 = 'magegeeks_B_2564807_nbc';

    /**
     * @var string XML_PATH_TOGGLE_D240012
     */
    public const XML_PATH_TOGGLE_D240012 = 'sgc_D240012_upload_to_quote_expire_status';

    /**
     * @var string XML_PATH_TOGGLE_D233151
     */
    public const XML_PATH_TOGGLE_D233151 = 'magegeeks_D_233151';

    /**
     * @var string XML_PATH_TOGGLE_B2564807
     */
    public const XML_PATH_TOGGLE_D235112 = 'tiger_d235112';

    /**
     * Toggle for D-235696 No email is triggered when a bid status is changed to revision requested
     * @var string XML_PATH_TOGGLE_D235696
     */
    public const XML_PATH_TOGGLE_D235696 = 'mazegeeks_D235696';

    /**
     * @var string XML_PATH_TOGGLE_D234006
     */
    public const XML_PATH_TOGGLE_D234006 = 'mazegeeks_d234006_adminquoteissueforfusebidding';

    /**
     * @var Quote $quoteHolder
     */
    protected $quoteHolder = null;
    /**
     * @var NegotiableQuote $negotiableQuoteHolder
     */
    protected $negotiableQuoteHolder = null;

    /**
     * AdminConfigHelper Constructor
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ToggleConfig $toggleConfig
     * @param CompanyRepository $companyRepository
     * @param QuoteFactory $quoteFactory
     * @param NegotiableCartRepository $negotiableCartRepository
     * @param TimezoneInterface $timezoneInterface
     * @param CheckoutHelper $checkoutHelper
     * @param NegotiableQuoteFactory $negotiableQuoteFactory
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param SortOrderBuilder $sortOrderBuilder
     * @param HistoryManagementInterface $historyManagement
     * @param History $quoteHistory
     * @param CartItemRepositoryInterface $cartItemRepositoryInterface
     * @param QuoteGridFactory $quoteGridFactory
     * @param SdeHelper $sdeHelper
     * @param ItemFactory $itemFactory
     * @param CheckoutSession $checkoutSession
     * @param CartFactory $cartFactory
     * @param AttributeSetRepositoryInterface $attributeSetRepositoryInterface
     * @param ImageHelper $imageHelper
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param CollectionFactory $quoteItemCollectionFactory
     * @param HistoryInterface $historyInterface
     * @param SortOrder $sortOrder
     * @param HistoryRepositoryInterface $historyRepositoryInterface
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        protected ToggleConfig $toggleConfig,
        protected CompanyRepository $companyRepository,
        protected QuoteFactory $quoteFactory,
        protected NegotiableCartRepository $negotiableCartRepository,
        protected TimezoneInterface $timezoneInterface,
        protected CheckoutHelper $checkoutHelper,
        protected NegotiableQuoteFactory $negotiableQuoteFactory,
        protected LoggerInterface $logger,
        protected CustomerSession $customerSession,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        private SortOrderBuilder $sortOrderBuilder,
        protected HistoryManagementInterface $historyManagement,
        private History $quoteHistory,
        protected CartItemRepositoryInterface $cartItemRepositoryInterface,
        private QuoteGridFactory $quoteGridFactory,
        protected SdeHelper $sdeHelper,
        protected ItemFactory $itemFactory,
        protected CheckoutSession $checkoutSession,
        protected CartFactory $cartFactory,
        protected AttributeSetRepositoryInterface $attributeSetRepositoryInterface,
        protected ImageHelper $imageHelper,
        protected CartRepositoryInterface $cartRepositoryInterface,
        protected readonly CollectionFactory $quoteItemCollectionFactory,
        protected HistoryInterface $historyInterface,
        protected SortOrder $sortOrder,
        protected HistoryRepositoryInterface $historyRepositoryInterface,
        protected ProductBundleConfig $productBundleConfig
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * To get the Upload To Quote Config Value
     *
     * @param string $key
     * @param int|null $storeId
     * @return bool|string
     */
    public function getUploadToQuoteConfigValue($key, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_BASE_PATH . $key,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * To get non standard catalog config value
     *
     * @param string $key
     * @param int|null $storeId
     * @return bool|string
     */
    public function getNonStandardCatalogConfigValue($key, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::NON_STANDARD_CATALOG_CONFIG_BASE_PATH . $key,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Upload to quote toggle value
     *
     * @param int $storeId
     * @param int|null $companyId
     * @return boolean
     * @throws NoSuchEntityException
     */
    public function isUploadToQuoteEnable($storeId, $companyId): bool
    {
        if (!$storeId) {
            return false;
        }

        if ($companyId) {
            return $this->isCompanyUploadToQuoteEnabled($companyId, $storeId);
        }

        return $this->isGlobalUploadToQuoteEnabled($storeId);
    }

    /**
     * Check if Upload to Quote is enabled for a company
     *
     * @param int $companyId
     * @param int $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    private function isCompanyUploadToQuoteEnabled($companyId, $storeId): bool
    {
        $company = $this->companyRepository->get((int) $companyId);

        return $company->getAllowUploadToQuote()
            && $this->isGlobalUploadToQuoteEnabled($storeId);
    }

    /**
     * Check if Upload to Quote is enabled globally for a store
     *
     * @param int $storeId
     * @return bool
     */
    private function isGlobalUploadToQuoteEnabled($storeId): bool
    {
        return (bool) $this->getUploadToQuoteConfigValue('enable', $storeId)
            && (bool) $this->toggleConfig->getToggleConfigValue('xmen_upload_to_quote');
    }

    /**
     * Get Non Standard Catalog For User
     *
     * @param int $storeId
     * @param int $companyId
     * @return boolean
     */
    public function isAllowNonStandardCatalogForUser($storeId, $companyId)
    {
        if ($storeId && $companyId
            && $this->companyRepository->get((int) $companyId)->getAllowNonStandardCatalog()
            && $this->getUploadToQuoteConfigValue('enable', $storeId)
            && $this->toggleConfig->getToggleConfigValue('explorers_non_standard_catalog')) {
            return true;
        }

        return false;
    }

    /**
     * Get Upload to quote toggle value store level
     *
     * @param int $storeId
     * @return boolean
     */
    public function isUploadToQuoteEnableForNSCFlow($storeId)
    {
        if ($storeId
        && $this->getUploadToQuoteConfigValue('enable', $storeId)
        && $this->toggleConfig->getToggleConfigValue('explorers_non_standard_catalog')
        && !$this->sdeHelper->getIsSdeStore()) {
            return true;
        }

        return false;
    }

    /**
     * Get Upload to quote toggle value for admin
     *
     * @return boolean
     */
    public function isUploadToQuoteToggle()
    {
        return $this->toggleConfig
        ->getToggleConfig("environment_toggle_configuration/environment_toggle/xmen_upload_to_quote");
    }

    /**
     * Get negotiable quote status
     *
     * @param int $quoteId
     * @param boolean $quoteDeatilPage
     * @return string
     */
    public function getNegotiableQuoteStatus($quoteId, $quoteDeatilPage = false)
    {
        if ($this->isToggleD206707Enabled()) {
            if (is_object($quoteId)) {
                $this->quoteHolder = $quoteId;
                $quoteId = $this->quoteHolder->getId();
            }
            if ($this->quoteHolder == null || $quoteId != $this->quoteHolder->getId()) {
                $this->quoteHolder = $this->quoteFactory->create()->load($quoteId);

            }
        } else {
            $this->quoteHolder = $this->quoteFactory->create()->load($quoteId);
        }

        if ($this->quoteHolder->getData()) {
            if ($this->isToggleD206707Enabled()) {
                if ($this->negotiableQuoteHolder == null || $this->negotiableQuoteHolder->getQuoteId() != $quoteId) {
                    $this->negotiableQuoteHolder = $this->negotiableQuoteRepository->getById($quoteId);
                }
            } else {
                $this->negotiableQuoteHolder = $this->negotiableQuoteFactory->create()->load($quoteId);;
            }
            if (($this->negotiableQuoteHolder->getStatus() == "created" || $this->negotiableQuoteHolder->getStatus() == "processing_by_admin")
                && !$this->quoteHolder->getQuoteMgntLocationCode()
            ) {
                return "Ready For Review";
            }
            if ($this->negotiableQuoteHolder->getStatus() == self::NBC_PRICED) {
                return self::STATUS_NBC_PRICED;
            }

            if ($this->negotiableQuoteHolder->getStatus() == self::NBC_SUPPORT) {
                return self::STATUS_NBC_SUPPORT;
            }

            if ($this->negotiableQuoteHolder->getStatus() == "expired") {
                return "Expired";
            } else {
                $statusData = $this->getStatusDataByQuoteId($quoteId);
                $status = $this->negotiableQuoteHolder->getStatus();
                if (isset($statusData['quoteStatus'])) {
                    $status = $statusData['quoteStatus'];
                }

                if ($quoteDeatilPage) {
                    return $this->getQuoteStatusLabelForQuoteDetails($status);
                }

                if($this->toggleUploadToQuoteExpireStatus()) {
                    $quoteStatus = $this->getQuoteStatusLabel($status, $this->getSubmitDate($quoteId));
                } else {
                    $quoteStatus = $this->getQuoteStatusLabel($status, $this->quoteHolder->getCreatedAt());
                }

                if ($this->quoteHolder->getIsEproQuote() && $this->quoteHolder->getSentToErp() && $quoteStatus == "Ready for Review") {
                    return 'Sent to ERP';
                } else {
                    return $quoteStatus;
                }
            }
        }
    }

    /**
     * Get expiry date
     *
     * @param string $quoteId
     * @param string $format
     * @return string
     */
    public function getExpiryDate($quoteId, $format, $quote = null)
    {
        if ($this->isToggleD206707Enabled()) {
            if ($this->negotiableQuoteHolder == null || $this->negotiableQuoteHolder->getQuoteId() != $quoteId) {
                $this->negotiableQuoteHolder = $this->negotiableQuoteRepository->getById($quoteId);
            }
        } else {
            $negotiableQuoteRepository = $this->negotiableCartRepository->get($quoteId);
            $this->negotiableQuoteHolder = $negotiableQuoteRepository->getExtensionAttributes()->getNegotiableQuote();
        }
        $expiryDate = $this->negotiableQuoteHolder->getExpirationPeriod();
        if ($expiryDate) {
            if($this->isToggleD226511Enabled()){
                $expiryDate = $this->timezoneInterface->date(strtotime($expiryDate));
                return $this->timezoneInterface->date($expiryDate, null, true, false)->format($format);
            }else{
                $expiryDate = $this->timezoneInterface->date(strtotime($expiryDate));
                return $this->timezoneInterface->date($expiryDate, null, false, false)->format($format);
            }
        } else {
            $expirationPeriodCount = $this->scopeConfig->getValue(
                self::DEFAULT_EXPIRATION_PERIOD_COUNT,
                ScopeInterface::SCOPE_WEBSITE
            );
            $expirationPeriodTime = $this->scopeConfig->getValue(
                self::DEFAULT_EXPIRATION_PERIOD_TIME,
                ScopeInterface::SCOPE_WEBSITE
            );
            $expiryValueConfig=(int)$expirationPeriodCount . ' ' . $expirationPeriodTime;
            $createdAt = $this->isToggleD206707Enabled() && $quote
                ? $quote->getCreatedAt()
                : $this->negotiableQuoteHolder->getCreatedAt();
            $expiryDate = $this->timezoneInterface
            ->date(strtotime($createdAt.$expiryValueConfig));

            return $this->getFormattedDate($expiryDate, $format);
        }
    }

    /**
     * Get formatted date
     *
     * @param string $dateString
     * @param string $format
     * @return string
     */
    public function getFormattedDate($dateString, $format = 'm/d/Y')
    {
        return $this->timezoneInterface->date($dateString)->format($format);
    }

    /**
     * Convert price
     *
     * @param double $price
     * @return string
     */
    public function convertPrice($price)
    {
        return $this->checkoutHelper->convertPrice($price);
    }

    /**
     * Get quote status label for quote details
     *
     * @param string $key
     * @return string
     */
    public function getQuoteStatusLabelForQuoteDetails($key)
    {
        return $this->getUploadToQuoteStatusList()[$key];
    }

    /**
     * Get quote status label
     *
     * @param string $key
     * @param string $createdAt
     * @return string
     */
    public function getQuoteStatusLabel($key, $createdAt = '')
    {
        if ($createdAt
            && ($key == NegotiableQuoteInterface::STATUS_CREATED
            || $key == NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN
            || $key == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN
            || $key == AdminConfigHelper::NBC_PRICED
            || $key == AdminConfigHelper::NBC_SUPPORT)
        ) {
            $currentDate = date_create($this->timezoneInterface->date()->format('Y-m-d'));
            $createdAt = date_create($this->timezoneInterface->date(strtotime($createdAt))->format('Y-m-d'));

            if (date_diff($createdAt, $currentDate)->days >= 25) {
                return 'Set to Expire';
            }
        }

        if (isset($key)) {
             return $this->getUploadToQuoteStatusList()[$key];
        }
    }

    /**
     * Get quote status list
     *
     * @return array
     */
    public function getUploadToQuoteStatusList()
    {
        return [
            NegotiableQuoteInterface::STATUS_CREATED => __('Store Review'),
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN => __('Store Review'),
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN => __('Ready for Review'),
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER => __('Change Requested'),
            NegotiableQuoteInterface::STATUS_ORDERED => __('Approved'),
            NegotiableQuoteInterface::STATUS_EXPIRED => __('Expired'),
            NegotiableQuoteInterface::STATUS_DECLINED => __('Declined'),
            NegotiableQuoteInterface::STATUS_CLOSED => __('Canceled'),
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER => __('Set To Expire'),
            AdminConfigHelper::NBC_PRICED => __('NBC Priced'),
            AdminConfigHelper::NBC_SUPPORT => __('NBC Support'),
        ];
    }

    /**
     * Update quote status
     *
     * @param int $quoteId
     * @param string $statusKey
     * @return void
     */
    public function updateQuoteStatusByKey($quoteId, $statusKey)
    {
        try {
            $quote = $this->negotiableQuoteFactory->create()->load($quoteId);
            if ($quote->getId()) {
                $quote->setStatus($statusKey)->save();
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ . ' Quote status set as '.$statusKey.' for quote id : '.$quoteId
                );
                $this->updateGridQuoteStatus($quoteId, $statusKey);
                $this->clearQuote($quoteId);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Upload to quote status has not been changed : ' . $e->getMessage());
        }
    }

    /**
     * Update finally epro quote status by key
     *
     * @param int $quoteId
     * @param string $quoteStatus
     * @return void
     */
    public function updateFinalQuoteStatus($quoteId, $quoteStatus)
    {
        try {
            if ($this->isToggleD206707Enabled()) {
                if ($this->negotiableQuoteHolder == null || $this->negotiableQuoteHolder->getQuoteId() != $quoteId) {
                    $this->negotiableQuoteHolder = $this->negotiableQuoteRepository->getById($quoteId);
                }
            } else {
                $negotiableQuoteRepository = $this->negotiableCartRepository->get($quoteId);
                $this->negotiableQuoteHolder = $negotiableQuoteRepository->getExtensionAttributes()->getNegotiableQuote();
            }

            if ($this->negotiableQuoteHolder->getId()) {
                $this->negotiableQuoteHolder->setStatus($quoteStatus);
                $this->negotiableQuoteHolder->save();
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ . ' Quote status set as '.$quoteStatus.' for quote id : '.$quoteId
                );
                $this->updateGridQuoteStatus($quoteId, $quoteStatus);
                $values = [];
                $value['quoteStatus'] = $quoteStatus;
                $value['readyForReviewDate'] = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
                $value['readyForReviewTime'] = $this->timezoneInterface->date()->format(self::TIME_FORMAT);
                $value['sentToErp'] = "After decline finally quote submitted to ERP System";
                $values[] = $value;
                $this->updateStatusLog($quoteId);
                $this->addCustomLog($quoteId, $values);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .' Upload to quote status could not changed for the quote id : ' . $quoteId .' Error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Clear quote item and deactivate quote.
     *
     * @param int $quoteId
     * @return void
     */
    public function clearQuote($quoteId)
    {
        $currentQuote = $this->cartFactory->create()->getQuote();
        $currentQuoteId = $currentQuote->getId();
        if ($quoteId == $currentQuoteId) {
            $currentQuote->setIsActive(0);
            if ($this->toggleConfig->getToggleConfigValue('mazegeek_u2q_quote_decline_admin_fix')) {
                $negotiableQuote = $this->negotiableQuoteFactory->create()->load($currentQuote->getId());
                $negotiableQuote->setIsRegularQuote(1);
                $negotiableQuote->save();
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ . ' Setting isRegularQuote as 1 for negotiable quote id '.
                    $currentQuote->getId()
                );
            }
            $currentQuote->save();
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . ' Quote with quote id '.$currentQuote->getId().' deactivated.'
            );
            $newQuote = $this->quoteFactory->create();
            $newQuote->save();
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . ' New quote with quote id '.$newQuote->getId().' activated.'
            );
            $this->checkoutSession->replaceQuote($newQuote);
        }
    }

    /**
     * Update quote status with history update
     *
     * @param int $quoteId
     * @param string $statusKey
     * @return void
     */
    public function updateQuoteStatusWithHisotyUpdate($quoteId, $statusKey)
    {
        try {
            $quote = $this->negotiableQuoteFactory->create()->load($quoteId);
            if ($quote->getId()) {
                $quote->setStatus($statusKey);
                $quote->setIsRegularQuote(1);
                $quote->save();
                $this->updateGridQuoteStatus($quoteId, $statusKey);
                $this->updateStatusLog($quoteId);
                $values = [];
                $value['quoteStatus'] = NegotiableQuoteInterface::STATUS_ORDERED;
                $value['approvedDate'] = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
                $value['approvedTime'] = $this->timezoneInterface->date()->format(self::TIME_FORMAT);
                $values[] = $value;
                $this->addCustomLog($quoteId, $values);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Upload to quote status has not been changed : ' . $e->getMessage());
        }
    }

    /**
     * Update quote status with history update
     *
     * @param int $quoteId
     * @param string $statusKey
     * @return boolean
     */
    public function updateQuoteStatusWithDeclined($quoteId, $statusKey)
    {
        $isQuoteStatusUpdated = false;
        try {
            $quote = $this->negotiableQuoteFactory->create()->load($quoteId);
            if ($quote->getId() && $quote->getStatus() != NegotiableQuoteInterface::STATUS_ORDERED) {
                $this->updateQuoteStatusByKey($quoteId, $statusKey);
                $this->updateStatusLog($quoteId);
                $this->addDeclineLogHistory($quoteId, 'Declined on delete all items');
                $isQuoteStatusUpdated = true;
            }
        } catch (\Exception $e) {
            if ($this->toggleConfig->getToggleConfigValue('mazegeek_u2q_quote_decline_admin_fix')) {
                $this->deactivateQuote($quoteId);
            }
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Upload to quote status has not been changed : ' . $e->getMessage());
        }

        return $isQuoteStatusUpdated;
    }

    /**
     * Add decline log history
     *
     * @return void
     */
    public function addDeclineLogHistory($quoteId, $declinedReason, $additionalComments = '')
    {
        $values = [];
        $value['quoteStatus'] =  NegotiableQuoteInterface::STATUS_DECLINED;
        $value['declinedDate'] = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $value['declinedTime'] = $this->timezoneInterface->date()->format(self::TIME_FORMAT);
        $value['reasonForDeclining'] = $declinedReason;
        $value['additionalComments'] = $additionalComments;
        $values[] = $value;
        $this->addCustomLog($quoteId, $values);
    }

    /**
     * Update Epro Negotiable Quote when finally submit quote
     *
     * @param object $quote
     * @return void
     */
    public function updateEproNegotiableQuote($quote)
    {
        try {
            $quoteId = $quote->getId();
            if ($quoteId) {
                $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
                $eproNegotiableQuoteTotalPrice = $quote->getGrandTotal();
                $negotiableQuote->setIsRegularQuote(1);
                $negotiableQuote->setNegotiatedPriceType(3);
                $negotiableQuote->setNegotiatedPriceValue($eproNegotiableQuoteTotalPrice);
                $negotiableQuote->setOriginalTotalPrice($eproNegotiableQuoteTotalPrice);
                $negotiableQuote->setBaseOriginalTotalPrice($eproNegotiableQuoteTotalPrice);
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ . 'Updated negotiable quote price for quote id : '.$quoteId
                );

                foreach ($quote->getAllItems() as $item) {
                    $negotiableQuoteItem = $item->getExtensionAttributes()->getNegotiableQuoteItem();
                    if (empty($negotiableQuoteItem->getOriginalPrice())) {
                        $negotiableQuoteItem->setOriginalPrice($item->getPrice());
                    }
                }

                $negotiableQuoteItem->save();

                $quoteGrid = $this->quoteGridFactory->create()->load($quoteId);
                if ($quoteGrid->getId()) {
                    $quoteGrid->setGrandTotal($eproNegotiableQuoteTotalPrice);
                    $quoteGrid->setBaseGrandTotal($eproNegotiableQuoteTotalPrice);
                    $quoteGrid->save();
                    $this->logger->info(
                        __METHOD__ . ':' . __LINE__ . 'updated grand total in negotiable quote grid table for quote id :'. $quoteId
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . 'Error when updating epro negotiable quote price for quote id :'.$quoteId.' error' . $e->getMessage()
            );
        }
    }

    /**
     * Update staus of quote in negotiable table grid
     *
     * @param int $quoteId
     * @param string $statusKey
     * @return void
     */
    public function updateGridQuoteStatus($quoteId, $statusKey)
    {
        $quoteGrid = $this->quoteGridFactory->create()->load($quoteId);
        if ($quoteGrid->getId()) {
            $quoteGrid->setStatus($statusKey)->save();
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . ' updated grid status as '.$statusKey.' for quote id : '. $quoteId
            );
        }
    }

    /**
     * Get quote status key
     *
     * @param int $quoteId
     * @return string
     */
    public function getQuoteStatusKeyByQuoteId($quoteId, $quote = null)
    {
        if ($this->isToggleD206707Enabled() && !is_null($quote)) {
            $this->quoteHolder = $quote;
            $quoteId = $this->quoteHolder->getId();
        } else {
            $this->quoteHolder = $this->quoteFactory->create()->load($quoteId);
        }
        if ($this->quoteHolder->getData()) {
            return $this->getQuoteStatus($quoteId);
        } else {
            $quoteId = $this->getNegotiableQuoteId();
            if ($quoteId) {
                $this->customerSession->setUploadToQuoteId($quoteId);

                return $this->getQuoteStatus($quoteId);
            } else {
                return NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN;
            }
        }
    }

    /**
     * Get status of the quote
     *
     * @param int $quoteId
     * @return string
     */
    public function getQuoteStatus($quoteId)
    {
        if ($this->isToggleD206707Enabled()) {
            if ($this->negotiableQuoteHolder == null || $this->negotiableQuoteHolder->getQuoteId() != $quoteId) {
                $this->negotiableQuoteHolder = $this->negotiableQuoteRepository->getById($quoteId);
            }
        } else {
            $negotiableQuoteRepository = $this->negotiableCartRepository->get($quoteId);
            $this->negotiableQuoteHolder = $negotiableQuoteRepository->getExtensionAttributes()->getNegotiableQuote();
        }

        return $this->negotiableQuoteHolder->getStatus();
    }

    /**
     * Check If a Quote is Punchout Quotes
     * @param  int $quoteId
     * @return boolean
     */
    public function checkIsPunchoutQuote($quoteId)
    {
        if ($this->isToggleD206707Enabled()) {
            if ($this->negotiableQuoteHolder == null || $this->negotiableQuoteHolder->getQuoteId() != $quoteId) {
                $this->negotiableQuoteHolder = $this->negotiableQuoteRepository->getById($quoteId);
            }
        } else {
            $negotiableQuoteRepository = $this->negotiableCartRepository->get($quoteId);
            $this->negotiableQuoteHolder = $negotiableQuoteRepository->getExtensionAttributes()->getNegotiableQuote();
        }
        if ($this->negotiableQuoteHolder) {
            $quoteName = $this->negotiableQuoteHolder->getQuoteName();
            if ($quoteName && str_contains($quoteName, 'Punchout')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get quote id from the negotiable quotes
     *
     * @return int|null $quoteId
     */
    public function getNegotiableQuoteId()
    {
        $clientId = $this->customerSession->getId();
        $sortOrder = $this->sortOrderBuilder->setField('quote_id')->setDirection('DESC')->create();
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('creator_id', $clientId)
            ->setPageSize(1)->setSortOrders([$sortOrder])->create();
        $quotesList = $this->negotiableQuoteRepository->getList($searchCriteria);
        $quotes = $quotesList->getItems();
        $quotesResult = [];
        foreach ($quotes as $quote) {
            $quotesResult[] = (int) $quote->getId();
        }

        return $quotesResult[0] ?? null;
    }

    /**
     * Get From Email address for sending mails
     *
     * @return string
     */
    public function getFromEmail()
    {
        return (string) $this->scopeConfig->getValue(
            static::XML_PATH_FROM_EMAIL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get upload to quote email config value
     *
     * @param string $key
     * @param int|null $storeId
     * @return mixed
     */
    public function getUploadToQuoteEmailConfigValue($key, $storeId = null)
    {
        return (string) $this->scopeConfig->getValue(
            static::EMAIL_CONFIG_BASE_PATH . $key,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * GetQuoteEditButton Msg for cart summary
     *
     * @return string
     */
    public function getQuoteEditButtonMsg()
    {
        return (string) $this->scopeConfig->getValue(
            self::QUOTE_WHY_CAN_NOT_EDIT_BUTTON,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Function to check priceable to quote level
     *
     * @param object $quote
     * @return boolean
     */
    public function checkoutQuotePriceisDashable($quote)
    {
        $priceable = 0;
        if ($this->isToggleD206707Enabled() && $quote instanceof QuoteGrid) {
            $allVisibleItems = $this->getQuoteAllVisibleItems($quote);
        } else {
            $allVisibleItems = $quote->getAllVisibleItems();
        }
        if ($allVisibleItems
        && $this->getQuoteStatusKeyByQuoteId($quote->getId(), $quote) != NegotiableQuoteInterface::STATUS_ORDERED) {
            foreach ($allVisibleItems as $quoteitems) {
                $infoRequest =  $quoteitems->getOptionByCode('info_buyRequest');
                $productJson = $infoRequest->getValue() ?? '';
                if ($productJson) {
                    $itemOption = json_decode($productJson, true);
                    $priceableValue =  isset($itemOption['external_prod'][0]['priceable'])
                    && !$itemOption['external_prod'][0]['priceable'] ? false : true;
                    if (!$priceableValue) {
                        $priceable = 1;
                        break;
                    }
                }
            }
        }
        return $priceable;
    }

    /**
     * Function to check pricable at quote item level
     *
     * @param array $result
     * @param object $quote
     * @return array
     */
    public function checkoutQuoteItemPriceableValue($result, $quote)
    {
        if ($result['quoteItemData'] && $quote->getAllVisibleItems()) {
            foreach ($quote->getAllVisibleItems() as $pair) {
                $result = $this->quoteItemIsPriceable($result, $pair);
            }
        }

        return $result;
    }

    /**
     * Check quote item is priceable or not
     *
     * @param array $result
     * @param object $pair
     * @return array
     */
    public function quoteItemIsPriceable($result, $pair)
    {
        foreach ($result['quoteItemData'] as $item => $value) {
            if ($pair->getItemId() === $value['item_id']) {
                $infoRequest =  $pair->getOptionByCode('info_buyRequest');
                $itemOptions = json_decode($infoRequest->getValue(), true);
                $priceableStatus =  isset($itemOptions['external_prod'][0]['priceable'])
                && !$itemOptions['external_prod'][0]['priceable'] ? false : true;
                if (!$priceableStatus) {
                    $result['quoteItemData'][$item]['price_dash'] = 1;
                } else {
                    $result['quoteItemData'][$item]['price_dash'] = 0;
                }
            }
        }

        return $result;
    }

    /**
     * If product has special instruction or not
     *
     * @param array $productJson
     * @param bool $specialInstruction
     * @return bool
     */
    public function isProductLineItems($productJson, $specialInstruction = false)
    {
        if ($this->toggleConfig->getToggleConfigValue(self::XML_PATH_TOGGLE_D235112)) {
            if ($specialInstruction) {
                return isset($productJson['properties'])
                    ? $this->extractSpecialInstructionValue($productJson['properties'])
                    : '';
            }
            return $productJson['priceable'] ?? false;
        } else {
            if ($specialInstruction) {
                if (isset($productJson['properties'])) {
                    $allowedKeys = ['CUSTOMER_SI', 'USER_SPECIAL_INSTRUCTIONS'];
                    foreach ($productJson['properties'] as $property) {
                        foreach ($allowedKeys as $key) {
                            if ((isset($property->name) && $property->name == $key && !empty($property->value)) ||
                                (is_array($property) && isset($property['name']) && $property['name'] == $key && !empty($property['value']))) {
                                return $property->value ?? $property['value'] ?? '';
                            }
                        }
                    }
                } else {
                    return '';
                }
            }

            return $productJson['priceable'] ?? false;
        }
    }

    private function extractSpecialInstructionValue(array $properties): string
    {
        $allowedKeys = ['CUSTOMER_SI', 'USER_SPECIAL_INSTRUCTIONS'];
        foreach ($properties as $property) {
            $name = is_array($property) ? ($property['name'] ?? null) : ($property->name ?? null);
            $value = is_array($property) ? ($property['value'] ?? null) : ($property->value ?? null);
            if (in_array($name, $allowedKeys, true) && !empty($value)) {
                return $value;
            }
        }
        return '';
    }

    /**
     * To check if SI product is editable or not
     *
     * @param array $productJson
     * @return bool
     */
    public function isSiItemNonEditable($productJson)
    {
        if (isset($productJson['properties']) && $productJson['priceable']) {
            foreach ($productJson['properties'] as $property) {
                if (isset($property->name) && $property->name == 'CUSTOMER_SI') {
                    return $property->value ?? '';
                } elseif (is_array($property)
                && isset($property['name']) && $property['name'] == 'CUSTOMER_SI') {
                    return $property['value'] ?? '';
                }
            }
        }

        return false;
    }

    /**
     * To check SI item edit button disable after approving the quote
     *
     * @param json $productJson
     * @return boolean
     */
    public function isSiItemEditBtnDisable($productJson)
    {
        $isMarketplaceProduct = isset($productJson['is_marketplace']) && $productJson['is_marketplace'] === true;

        $externalSkus = $productJson['externalSkus'] ?? [];

        if (!$isMarketplaceProduct && !empty($externalSkus)) {
            return true;
        }

        return false;
    }

    /**
     * Checks is marketplace product
     *
     * @return boolean true|false
     */
    public function isMarketplaceProduct(): bool
    {
        return $this->sdeHelper->isMarketplaceProduct();
    }

   /**
    * Check item is pricable or not
    *
    * @param json $productJson
    * @return boolean
    */
    public function isItemPriceable($productJson)
    {
        $productJson = json_decode($productJson, true);
        if (isset($productJson['external_prod'][0]['priceable'])
        && !$productJson['external_prod'][0]['priceable']) {
            return false;
        }

        return true;
    }

    /**
     * Check file is stardard or not
     *
     * @param json $productJson
     * @return boolean
     */
    public function isNonStandardFile($productJson)
    {
        $productJson = json_decode($productJson, true);
        if (isset($productJson['external_prod'][0]['isEditable'])
        && !$productJson['external_prod'][0]['isEditable']
        && isset($productJson['external_prod'][0]['priceable'])
        && !$productJson['external_prod'][0]['priceable']) {
            return true;
        }

        return false;
    }

    /**
     * Get Upload to quote toggle value globally
     *
     * @return boolean
     */
    public function isUploadToQuoteGloballyEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue('xmen_upload_to_quote');
    }

    /**
     * Update status of quote in negotiable_quote_history table
     *
     * @param int $quoteId
     * @return void
     */
    public function updateStatusLog($quoteId)
    {
        $this->historyManagement->updateStatusLog($quoteId);
    }

    /**
     * Add Custom Log
     *
     * @param int $quoteId
     * @param array $values
     * @return void
     */
    public function addCustomLog($quoteId, $values)
    {
        $this->historyManagement->addCustomLog($quoteId, $values);
    }

    /**
     * Get quote history by quote id
     *
     * @param int $quoteId
     * @return array
     */
    public function getQuoteHistory($quoteId)
    {
        $quoteHistory = $this->historyManagement->getQuoteHistory($quoteId);
        $lastHistoryId =  array_key_last($quoteHistory);
        $history =  $this->quoteHistory->load($lastHistoryId);

        return $history->getLogData();
    }

    /**
     * Get status data by quote id
     *
     * @param int $quoteId
     * @return array
     */
    public function getStatusDataByQuoteId($quoteId)
    {
        $logData = [];
        $quoteHistory = $this->getQuoteHistory($quoteId);
        if ($quoteHistory) {
            $logData = $this->filterLogData($this->getQuoteHistory($quoteId), $quoteId);
        }

        return $logData;
    }

    /**
     * Filter log data from queue
     *
     * @param array $lastHistoryEntry
     * @param int $quoteId
     * @return array
     */
    public function filterLogData($lastHistoryEntry, $quoteId)
    {
        $logData = [];
        $lastHistoryData = json_decode($lastHistoryEntry, true);
        if (isset($lastHistoryData['custom_log'])) {
            $logData = $this->filterDataByStatus($lastHistoryData['custom_log']);
        } else {
            $arrQueues = $this->customerSession->getUploadToQuoteActionQueue() ?? [];
            foreach ($arrQueues as $arrQueue) {
                if ($arrQueue['action'] == 'declined' && $arrQueue['quoteId'] == $quoteId) {
                    $logData = $arrQueue;
                    $logData['quoteStatus'] = $arrQueue['action'];
                    $logData['declinedDate'] = $arrQueue['declinedDate'];
                    $logData['declinedProgessBarMsg'] = 'You declined this quote on
                '.$this->getFormattedDate($arrQueue['declinedDate'], 'd/m/y').'
                at '.$arrQueue['declinedTime'].'.';
                }
                if ($arrQueue['action'] == 'changeRequested' && $arrQueue['quoteId'] == $quoteId) {
                    $logData = $arrQueue;
                    $logData['quoteStatus'] = $arrQueue['action'];
                    $logData['changeRequestedDate'] = $arrQueue['changeRequestedDate'];
                    $logData['requestChangeProgessBarMsg'] = 'You requested a change on '
                    .$this->getFormattedDate($arrQueue['changeRequestedDate'], 'd/m/y').' at '
                    .$arrQueue['changeRequestedTime'].'.';
                }
            }
        }

        return $logData;
    }

    /**
     * Filter log data by status
     *
     * @param array $lastHistoryData
     * @return array
     */
    public function filterDataByStatus($lastHistoryData)
    {
        $logData = [];
        foreach ($lastHistoryData as $lastHistory) {
            if (isset($lastHistory['quoteStatus'])
            && $lastHistory['quoteStatus'] == NegotiableQuoteInterface::STATUS_DECLINED) {
                $declinedTime = $lastHistory['declinedTime']
                ?? $this->getFormattedDate($lastHistory['declinedDate'], self::TIME_FORMAT);
                $logData = $lastHistory;
                $logData['declinedProgessBarMsg'] = 'You declined this quote on
                '.$this->getFormattedDate($lastHistory['declinedDate'], 'd/m/y').'
                at '.$declinedTime.'.';
            } elseif (isset($lastHistory['quoteStatus'])
            && $lastHistory['quoteStatus'] == NegotiableQuoteInterface::STATUS_ORDERED) {
                $approvedTime = $lastHistory['approvedTime']
                ?? $this->getFormattedDate($lastHistory['approvedDate'], self::TIME_FORMAT);
                $logData = $lastHistory;
                $logData['approveProgessBarMsg'] = 'You approved this quote on '
                .date('d/m/y', strtotime($lastHistory['approvedDate'])).' at '.$approvedTime.'.';
            } elseif (isset($lastHistory['quoteStatus'])
            && $lastHistory['quoteStatus'] == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER) {
                $declinedTime = isset($lastHistory['changeRequestedTime']) ? $this->getFormattedDate($lastHistory['changeRequestedTime'], self::TIME_FORMAT) : '';
                $logData = $lastHistory;
                $logData['requestChangeProgessBarMsg'] = isset($lastHistory['changeRequestedDate']) ? 'You requested a change on '
                .$this->getFormattedDate($lastHistory['changeRequestedDate'], 'd/m/y').' at '.$declinedTime.'.' : '';
            } elseif (isset($lastHistory['quoteStatus'])
            && $lastHistory['quoteStatus'] == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN) {
                $readyForReviewTime = $lastHistory['readyForReviewTime']
                ?? $this->getFormattedDate($lastHistory['readyForReviewDate'], self::TIME_FORMAT);
                $logData = $lastHistory;
                $logData['readyForReviewProgessBarMsg'] = 'You requested a change on '
                .$this->getFormattedDate($lastHistory['readyForReviewDate'], 'd/m/y').' at '.$readyForReviewTime.'.';
            } elseif (isset($lastHistory['quoteStatus'])
            && $lastHistory['quoteStatus'] == NegotiableQuoteInterface::STATUS_CLOSED) {
                $closedTime = $lastHistory['closedTime']
                ?? $this->getFormattedDate($lastHistory['closedDate'], self::TIME_FORMAT);
                $logData = $lastHistory;
                $logData['closedProgessBarMsg'] = 'You closed it on '
                .$this->getFormattedDate($lastHistory['closedDate'], 'd/m/y').' at '.$closedTime.'.';
            }
        }

        return $logData;
    }

    /**
     * Remove item from quote
     *
     * @param int $quoteId
     * @param int $itemId
     * @return void
     */
    public function removeQuoteItem($quoteId, $itemId)
    {
        $this->cartItemRepositoryInterface->deleteById($quoteId, $itemId);
    }

    /**
     * Get deleted items
     *
     * @return array
     */
    public function getDeletedItems()
    {
        $arrQueues = $this->customerSession->getUploadToQuoteActionQueue() ?? [];
        $deletedItem = [];
        foreach ($arrQueues as $arrQueue) {
            if ($arrQueue['action'] == 'deleteItem') {
                $deletedItem[] = $arrQueue['itemId'];
            }
        }

        return $deletedItem;
    }

    /**
     * Get price info for remaining item
     *
     * @return array
     */
    public function getTotalPriceInfForRemainingItem()
    {
        $arrQueues = $this->customerSession->getUploadToQuoteActionQueue() ?? [];
        $lastQuoteRequest = end($arrQueues);
        $zeroDollarSku = false;
        if (isset($lastQuoteRequest['rateQuoteResponse']['alerts'])) {
            $alerts = $lastQuoteRequest['rateQuoteResponse']['alerts'];
            foreach ($alerts as $alert) {
                if ($alert['code'] == 'QCXS.SERVICE.ZERODOLLARSKU') {
                    $zeroDollarSku = true;
                }
            }
        }
        $totalPriceForRemainingItem = [];
        if (!$zeroDollarSku && $lastQuoteRequest
        && isset($lastQuoteRequest['rateQuoteResponse']['rateQuote']['rateQuoteDetails'][0])) {
            $rateResponse = $lastQuoteRequest['rateQuoteResponse']['rateQuote']['rateQuoteDetails'][0];
            $totalPriceForRemainingItem = [
                'grossAmount' => $rateResponse['grossAmount'],
                'totalDiscountAmount' => $rateResponse['totalDiscountAmount'],
                'netAmount' => $rateResponse['netAmount'],
                'taxableAmount' => $rateResponse['taxableAmount'],
                'taxAmount' => $rateResponse['taxAmount'],
                'totalAmount' => $rateResponse['totalAmount'],
                'productsTotalAmount' => $rateResponse['productsTotalAmount'],
                'deliveriesTotalAmount' => $rateResponse['deliveriesTotalAmount'],
                'totalRemainingItems' => count($rateResponse['productLines'])
            ];
        }
        $productLines = $lastQuoteRequest['rateQuoteResponse']['rateQuote']['rateQuoteDetails'][0]['productLines']
        ?? [];
        if ($productLines) {
            $totalPriceForRemainingItem['totalRemainingItems'] =  count($productLines);
        }

        return $totalPriceForRemainingItem;
    }

    /**
     * Check quote is negotiated or not
     *
     * @param int $quoteId
     * @return boolean
     */
    public function isQuoteNegotiated($quoteId)
    {
        $isNegotiated = false;
        if ($this->negotiableQuoteFactory->create()->load($quoteId)->getId()) {
            $isNegotiated = true;
        }

        return $isNegotiated;
    }

    /**
     * Get item SI type
     *
     * @param json $productJson
     * @return string
     */
    public function getSiType($productJson)
    {
        $productJson = json_decode($productJson, true);
        $siType = '';
        if (isset($productJson['external_prod'][0]['priceable'])
        && !$productJson['external_prod'][0]['priceable']) {
            $isEditable = $productJson['external_prod'][0]['isEditable'] ?? false;
            $siType = 'NON_STANDARD_FILE';
            if ($isEditable) {
                $siType = 'ADDITIONAL_PRINT_INSTRUCTIONS';
            }
        }

        return $siType;
    }

     /**
      * Get product value
      *
      * @param object $item
      * @param int $quoteId
      * @return array
      */
    public function getProductValue($item, $quoteId, $quote = null)
    {
        if ($this->getQuoteStatusKeyByQuoteId($quoteId, $quote) != NegotiableQuoteInterface::STATUS_ORDERED) {
            $infoBuyRequest = $item->getOptionByCode('info_buyRequest');
            $productJson = $infoBuyRequest->getValue() ?? '';
            $decodeProductValue = [];
            if ($productJson) {
                $infoBuyRequestValue = $infoBuyRequest->getValue();
                $decodeProductValue = json_decode($infoBuyRequestValue, true);
            }
        } else {
            $salesOrderItem = $this->itemFactory->create()->load($item->getId(), 'quote_item_id');
            $productOptions = $salesOrderItem->getProductOptions();
            $decodeProductValue = $productOptions['info_buyRequest'] ?? '';
        }

        return $decodeProductValue;
    }

    /**
     * Get product json
     *
     * @param object $item
     * @param int $quoteId
     * @return array
     */
    public function getProductJson($item, $quoteId, $quote = null)
    {
        return $this->getProductValue($item, $quoteId, $quote)['external_prod'][0] ?? [];
    }

    /**
     * Update quote log in negotiable quote hisory
     *
     * @param int $quoteId
     * @param string $quoteStatus
     * @return void
     */
    public function updateLogHistory($quoteId, $quoteStatus)
    {
        $values = [];
        $value['quoteStatus'] = $quoteStatus;
        $value['changeRequestedDate'] = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $value['changeRequestedTime'] = $this->timezoneInterface->date()->format(self::TIME_FORMAT);
        $values[] = $value;
        $this->updateStatusLog($quoteId);
        $this->addCustomLog($quoteId, $values);
    }

    /**
     * Get X-MEN U2Q Quote Detail CUSTOMER_SI validate
     *
     * @return boolean
     */
    public function isU2QCustomerSIEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue('xmen_u2q_customer_si_validate');
    }

    /**
     * Get toggle value for decline quote when all items are deleted
     *
     * @return boolean
     */
    public function isMarkAsDeclinedEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue('xmen_decline_quote_on_truncate');
    }

    /**
     * Get product attributeset name
     *
     * @return string
     */
    public function getProductAttributeName($attributeSetId)
    {
        return $this->attributeSetRepositoryInterface->get($attributeSetId)->getAttributeSetName();
    }

    /**
     * Get product image url
     *
     * @param object $product
     * @param int $width
     * @param int $height
     * @return string
     */
    public function productImageUrl($product, $width, $height)
    {
        return $this->imageHelper->init($product, 'product_page_image_small')
        ->setImageFile($product->getFile())->resize($width, $height)->getUrl();
    }

    /**
     * Check if negotiable quote existing for quote
     *
     * @param int $quoteId
     * @return boolean
     */
    public function isNegotiableQuoteExistingForQuote($quoteId)
    {
        $isNegotiableQuoteExist = false;
        if ($this->isToggleD206707Enabled()) {
            if ($this->negotiableQuoteHolder == null || $this->negotiableQuoteHolder->getQuoteId() != $quoteId) {
                $this->negotiableQuoteHolder = $this->negotiableQuoteRepository->getById($quoteId);
            }
        } else {
            $negotiableQuoteRepository = $this->negotiableCartRepository->get($quoteId);
            $this->negotiableQuoteHolder = $negotiableQuoteRepository->getExtensionAttributes()->getNegotiableQuote();
        }

        if ($this->negotiableQuoteHolder->getId() === $quoteId) {
            $isNegotiableQuoteExist = true;
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . ' Negotiable Quote already exist for this quote id : '.$quoteId
            );
        }

        return $isNegotiableQuoteExist;
    }

    /**
     * Get toggle value for My Quotes Maintenace page fix
     *
     * @return boolean
     */
    public function getMyQuoteMaitenanceFixToggle()
    {
        return $this->toggleConfig->getToggleConfigValue('mazegeek_team_d193943_my_quotes_maitenace_fix');
    }

    /**
     * Deactivate quote on exception
     *
     * @param int $quoteId
     * @return void
     */
    public function deactivateQuote($quoteId)
    {
        try {
            if ($this->toggleConfig->getToggleConfigValue('mazegeek_team_utoq_quote_deactive_fix')) {
                $negotiableQuote = $this->negotiableQuoteFactory->create()->load($quoteId);
                $negotiableQuote->setIsRegularQuote(1);
                $negotiableQuote->save();
                $this->logger->info(__METHOD__ . ':' . __LINE__ .
                ' is_regular_quote is set to 1 for quote id : '.$quoteId);
                $curentQuote = $this->cartRepositoryInterface->get($quoteId);
                $curentQuote->setIsActive(false);
                $this->cartRepositoryInterface->save($curentQuote);
                $this->logger->info(__METHOD__ . ':' . __LINE__ .
                ' quote is deactivated with quote id : '.$quoteId);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Quote is not deactivated with quote id : '.$quoteId.'
            and with error message : ' . $e->getMessage());
        }
    }

    /**
     * Check approval fix toggle are enabled or not
     *
     * @return boolean
     */
    public function utoqApprovaFixToggle()
    {
        return $this->toggleConfig->getToggleConfigValue('mazegeek_utoq_approval_fix');
    }

    /**
     * Get Is Magento Quote Detail Enhancement Toggle Enabled
     *
     * @return boolean
     */
    public function isMagentoQuoteDetailEnhancementToggleEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue('mazegeeks_E_455768_magento_quote_details_enhancements');
    }

    /**
     * @param $quote
     * @return array
     */
    protected function getQuoteAllVisibleItems($quote) {
        $items = [];
        foreach ($this->getItemsCollection($quote) as $item) {
            if (!$item->isDeleted() && !$item->getParentItemId() && !$item->getParentItem()) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * @param $quote
     * @return \Magento\Quote\Model\ResourceModel\Quote\Item\Collection
     */
    protected function getItemsCollection($quote) {
        if ($this->productBundleConfig->isTigerE468338ToggleEnabled()) {
            $quote = $this->quoteFactory->create();
            $quote->load($quote->getId());

            return $quote->getAllItems();
        }

        $item = $this->quoteItemCollectionFactory->create();
        $item->addFieldToFilter('quote_id', $quote->getId());

        return $item;
    }

    /**
    * Toggle for upload to quote expire status
    *
    * @return boolean
    */
    public function toggleUploadToQuoteExpireStatus()
    {
       return $this->toggleConfig->getToggleConfigValue('sgc_D240012_upload_to_quote_expire_status');
    }

    /**
     * Check if D-206707 toggle is enabled
     * @return bool
     */
    public function isToggleD206707Enabled()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XML_PATH_TOGGLE_D206707);
    }

     /**
     * Quote expiry fix toggle
     *
     * @return boolean
     */
    public function quoteexpiryIssueFixToggle()
    {
        return $this->toggleConfig->getToggleConfigValue('mazegeek_u2q_quote_expiry_issue_fix');
    }

    /**
     * Check if D-226511 toggle is enabled
     * @return bool
     */
    public function isToggleD226511Enabled()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XML_PATH_TOGGLE_D226511);
    }

    /**
    * Toggle for upload to quote submit date
    *
    * @return boolean
    */
    public function toggleUploadToQuoteSubmitDate()
    {
       return $this->toggleConfig->getToggleConfigValue('sgc_E467881_upload_to_quote_submit_date');
    }

    /**
    * Toggle for NBC SUPPORT AND NBC PRICED
    *
    * @return boolean
    */
    public function isToggleB2564807Enabled()
    {
       return $this->toggleConfig->getToggleConfigValue(self::XML_PATH_TOGGLE_B2564807);
    }

    /**
    * Toggle for FUSE Min Price filter
    *
    * @return boolean
    */
    public function isToggleD233151Enabled()
    {
       return $this->toggleConfig->getToggleConfigValue(self::XML_PATH_TOGGLE_D233151);
    }
    
    /** Toggle for Revision Requested Email
    *
    * @return boolean
    */
    public function isToggleD235696Enabled()
    {
       return $this->toggleConfig->getToggleConfigValue(self::XML_PATH_TOGGLE_D235696);
    }

    /**
     * Get submit date
     *
     * @param int $quoteId
     * @return string
     */
    public function getSubmitDate($quoteId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
        ->addFilter('quote_id', $quoteId)
        ->addFilter('status', 'created')
        ->addSortOrder(
            $this->sortOrderBuilder
                ->setField('created_at')
                ->setDirection(SortOrder::SORT_ASC)
                ->create()
        )
        ->setPageSize(1)
        ->create();

        $items = $this->historyRepositoryInterface->getList($searchCriteria)->getItems();

        return !empty($items) ? reset($items)->getCreatedAt() : null;
    }

    /**
     * Get submit dates for the quote table
     *
     * @param array $quoteIds
     * @return array
     */
    public function getSubmitDates(array $quoteIds): array
    {
        if (empty($quoteIds)) {
            return [];
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('quote_id', $quoteIds, 'in')
            ->addFilter('status', 'created')
            ->addSortOrder(
                $this->sortOrderBuilder
                    ->setField('created_at')
                    ->setDirection(SortOrder::SORT_ASC)
                    ->create()
            )
            ->create();

        $items = $this->historyRepositoryInterface->getList($searchCriteria)->getItems();

        $submitDates = [];

        foreach ($items as $item) {
            $quoteId = $item->getQuoteId();
            if (!isset($submitDates[$quoteId])) {
                $submitDates[$quoteId] = $item->getCreatedAt();
            }
        }

        return $submitDates;
    }

    /**
    * Toggle for mazegeeks_d234006_adminquoteissueforfusebidding
    *
    * @return boolean
    */
    public function isMazegeeksD234006Adminquoteissueforfusebidding()
    {
       return $this->toggleConfig->getToggleConfigValue(self::XML_PATH_TOGGLE_D234006);
    }
}
