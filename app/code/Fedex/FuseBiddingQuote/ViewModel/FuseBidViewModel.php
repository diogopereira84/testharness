<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\ViewModel;

use Exception;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Model\NegotiableCartRepository;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Registry;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;

/**
 * FuseBidding module viewmodel class
 */
class FuseBidViewModel implements ArgumentInterface
{
    /**
     * Name of cookie
     */
    private const ERROR_POPUP_SESSION = 'error_popup_session';

    /**
     * FuseBidViewModal Constructor
     *
     * @param FuseBidHelper $fuseBidHelper
     * @param NegotiableCartRepository $negotiableCartRepository
     * @param LoggerInterface $logger
     * @param SessionManagerInterface $sessionManager
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteFactory $quoteFactory
     * @param Registry $registry
     * @param CustomerRepositoryInterface $customerRepository
     * @param NegotiableQuoteManagementInterface $negotiableQuoteManagement
     */
    public function __construct(
        protected FuseBidHelper $fuseBidHelper,
        protected NegotiableCartRepository $negotiableCartRepository,
        protected LoggerInterface $logger,
        private SessionManagerInterface $sessionManager,
        protected StoreManagerInterface $storeManager,
        protected CustomerFactory $customerFactory,
        protected CartRepositoryInterface $quoteRepository,
        protected QuoteFactory $quoteFactory,
        protected Registry $registry,
        private CustomerRepositoryInterface $customerRepository,
        private NegotiableQuoteManagementInterface $negotiableQuoteManagement
    )
    {
    }

    /**
     * Check if FuseBidding toggle is enable and is applicable only for Retail store.
     *
     * @return boolean
     */
    public function isFuseBidToggleEnabled()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $isFuseBidToggleEnabled = false;
        if ($this->fuseBidHelper->isFuseBidGloballyEnabled() &&
        $this->fuseBidHelper->getUploadToQuoteConfigValue('enable', $storeId)) {
            $isFuseBidToggleEnabled = true;
        }

        return $isFuseBidToggleEnabled;
    }

    /**
     * Validate customer email with same quote.
     *
     * @param obj $customer
     * @param string $quoteId
     * @return string
     */
    public function validateCustomerQuote($customer, $quoteId)
    {
        $myQuotesUrl = '';
        try {
            $quote = $this->negotiableCartRepository->get($quoteId);
            $customerEmail = $customer->getSecondaryEmail() ? $customer->getSecondaryEmail() : $customer->getEmail();
            $myQuotesUrl = 'uploadtoquote/index/quotehistory';
            if ($quote && ($quote->getCustomerEmail() != $customerEmail)) {
                $this->setErrorPopupSessionValue(true);
            }
            if ($quote && ($quote->getCustomerEmail() == $customerEmail)) {
                $this->associateQuoteWithCustomer($customer, $quote);
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .' quote with quote id '.$quoteId.' error => '.
                $e->getMessage()
            );
        }

        return $myQuotesUrl;
    }

    /**
     * Display login error popup
     *
     * @return string
     */
    public function displayPopupForLoginError()
    {
        return $this->getErrorPopupSessionValue() ?? false;
    }

    /**
     * Get General Configuration Value
     *
     * @param string $code
     * @return string
     */
    public function getGeneralConfig($code)
    {
        return $this->fuseBidHelper->getSsoConfigValue($code);
    }

    /**
     * Set error popup session value
     *
     * @param string $value
     * @return void
     */
    public function setErrorPopupSessionValue($value)
    {
        $this->sessionManager->start();
        $this->sessionManager->setData(self::ERROR_POPUP_SESSION, $value);
    }

    /**
     * Get error popup session value
     *
     * @return string
     */
    public function getErrorPopupSessionValue()
    {
        $this->sessionManager->start();

        return $this->sessionManager->getData(self::ERROR_POPUP_SESSION);
    }

    /**
     * Deactivate quote
     *
     * @return void
     */
    public function deactivateQuote()
    {
        $this->fuseBidHelper->deactivateQuote();
    }

    /**
     * Associate quote with user on login
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    public function associateQuoteWithCustomer($customer, $quote)
    {
        try {
            $existingUserExternalId = $customer->getData('external_id');
            $currentQuoteCustomerId = $quote->getCustomerId();
            if ($existingUserExternalId && $currentQuoteCustomerId) {
                $quoteCustomer = $this->customerFactory->create()->load($currentQuoteCustomerId);
                if (!$quoteCustomer->getData('external_id')) {
                    $this->updateQuoteWithCustomerInfo($quote->getId(), $customer);
                    $this->logger->info(
                        sprintf(
                            "Quote updated to customer ID %d and old customer ID %d deleted.",
                            $customer->getId(),
                            $currentQuoteCustomerId
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf("Error associating quote with customer: %s", $e->getMessage())
            );
        }
    }

    /**
     * Update quote with customer information
     *
     * @param int $quoteId
     * @param obj $customer
     * @return void
     */
    public function updateQuoteWithCustomerInfo($quoteId, $customer)
    {
        $quote = $this->quoteFactory->create()->load($quoteId);
        $dummyCustomerId = $quote->getCustomerId();
        $customer->getEmail();
        try {
            $id = $customer->getId();
            if ($id != $dummyCustomerId) {
                $quote->setCustomerFirstname($customer->getFirstname());
                $quote->setCustomerLastname($customer->getLastname());
                $quote->setCustomerIsGuest(0);
                $quote->setCustomerId($id);
                $quote->setCustomerGroupId($customer->getGroupId());
                $this->quoteRepository->save($quote);
                $this->negotiableQuoteManagement->recalculateQuote($quoteId);
                $quote = $this->quoteFactory->create()->load($quoteId);
                $this->registry->register('isSecureArea', true);
                $customer = $this->customerRepository->deleteById($dummyCustomerId);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Check rate quote details api toggle is enable or not
     *
     * @return boolean
     */
    public function isRateQuoteDetailApiEnabed()
    {
        return $this->fuseBidHelper->isRateQuoteDetailApiEnabed();
    }

    /**
     * Check send sourceRetailLocationId toggle is enable or not
     *
     * @return boolean
     */
    public function isSendRetailLocationIdEnabled()
    {
        return $this->fuseBidHelper->isSendRetailLocationIdEnabled();
    }

    /**
     * To check Online Checkout for Fusebidding Quote toggle is enable or not
     *
     * @return boolean
     */
    public function isBidCheckoutEnabled()
    {
        return $this->fuseBidHelper->isBidCheckoutEnabled();
    }

     /**
     * Fixed the contact information update issue
     *
     * @return boolean
     */
    public function isContactInfoFix()
    {
        return $this->fuseBidHelper->isContactInfoFix();
    }

    /**
     * @return bool|int
     */
    public function isToggleD215974Enabled()
    {
        return $this->fuseBidHelper->isToggleD215974Enabled();
    }
}
