<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
declare(strict_types=1);

namespace Fedex\Cart\Observer\Frontend\Checkout;

use Fedex\EnhancedProfile\Helper\Account;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use \Exception;

/**
 * Class AddFedexAccountNumber
 *
 * This class is responsible for auto populating the fedex account number
 */
class AddFedexAccountNumber implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var bool|Quote
     */
    protected $hasQuote = false;

    /**
     * Constructor function
     *
     * @param CartFactory $cartFactory
     * @param Session $checkoutSession
     * @param CustomerSession $customerSession
     * @param CompanyHelper $companyHelper
     * @param CartDataHelper $cartDataHelper
     * @param EnhancedProfile $enhancedProfileViewModel
     * @param Account $accountHelper
     * @param FXORate $fxoRateHelper
     * @param FXORateQuote $fxoRateQuote
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     * @param AuthHelper $authHelper
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     */
    public function __construct(
        protected CartFactory     $cartFactory,
        protected Session         $checkoutSession,
        protected CustomerSession $customerSession,
        protected CompanyHelper   $companyHelper,
        protected CartDataHelper  $cartDataHelper,
        protected EnhancedProfile $enhancedProfileViewModel,
        protected Account         $accountHelper,
        protected FXORate         $fxoRateHelper,
        protected FXORateQuote    $fxoRateQuote,
        protected LoggerInterface $logger,
        protected ToggleConfig    $toggleConfig,
        protected AuthHelper      $authHelper,
        protected MarketplaceHelper $marketPlaceHelper,
        readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
    )
    {
    }

    /**
     * Execute Method
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->addToCartPerformanceOptimizationToggle->isActive()){
            $fedExAccountNumber =  $this->companyHelper->getFedexAccountNumber();
        }
        $quote = $this->cartFactory->create()->getQuote();
        $explorersFixRateQuoteCall = $this->toggleConfig->getToggleConfigValue('explorers_fix_rateQuoteCall');
        try {
            if($this->authHelper->isLoggedIn()) {
                $customerId = $this->customerSession->getCustomerId();
                $company = $this->accountHelper->getCompanyByCustomerId($customerId);
                if ($company) {
                    $this->handleCompanyAccounts($company);
                    $this->applyDiscountAccountForCommercial();
                } elseif ($this->accountHelper->isRetail()) {
                    $this->applyDiscountAccountForRetail();
                }

                if ($explorersFixRateQuoteCall) {
                    if (!$this->fxoRateHelper->isEproCustomer()) {
                        $this->fxoRateQuote->getFXORateQuote($quote);
                    } else {
                        $this->fxoRateHelper->getFXORate($quote);
                    }
                } else {
                    $this->fxoRateQuote->getFXORateQuote($quote);
                }
            }
        } catch (Exception $exception) {
            $this->logger->debug(__(
                'Error while setting fedEx account number for quote %1: %2',
                $quote->getId(),
                $exception->getMessage()
            ));
        }
    }

    /**
     * @return void
     */
    protected function applyDiscountAccountForRetail(): void
    {

        $personalAccountList = $this->accountHelper->getActivePersonalAccountList();

        // Ignore any personal accounts of type SHIPPING when the vendor-specific shipping account toggle is enabled
        // This will ensure we do not set a shipping account number as a default discount account number in the checkout session
        if ($this->marketPlaceHelper->isVendorSpecificCustomerShippingAccountEnabled()) {
            $personalAccountList = array_filter($personalAccountList, function ($account) {
                return isset($account['type']) && strtoupper((string)$account['type']) !== 'SHIPPING';
            });
        }
        $accountNumber = array_search(
            1,
            array_column($personalAccountList, 'selected', 'account_number')
        );
        if ($accountNumber && !$this->checkoutSession->getRemoveFedexAccountNumber()) {
            $this->hasQuote = $this->accountHelper->applyAccountNumberToCheckoutSession((string)$accountNumber);
        }
    }

    /**
     * @return void
     */
    protected function applyDiscountAccountForCommercial(): void
    {

        $accountNumber = false;
        $personalAccountList = $this->accountHelper->getActivePersonalAccountList();
        $companyPaymentAccountList = $this->accountHelper->getActiveCompanyAccountList('payment');
        $companyDiscountAccountList = $this->accountHelper->getActiveCompanyAccountList('discount');
        if (!empty($companyPaymentAccountList)) {

            $accountNumber = key($companyPaymentAccountList);
        } elseif (!empty($companyDiscountAccountList)) {

            $accountNumber = key($companyDiscountAccountList);
        } elseif (!empty($personalAccountList)) {

            $accountNumber = array_search(
                1,
                array_column($personalAccountList, 'selected', 'account_number')
            );
        }

        if ($accountNumber && !$this->checkoutSession->getRemoveFedexAccountNumber()) {

            $this->hasQuote = $this->accountHelper->applyAccountNumberToCheckoutSession((string)$accountNumber);
        }
    }

    /**
     * @param $company
     * @return void
     */
    protected function handleCompanyAccounts($company): void
    {

        $accounts = [
            'fedex_account_number' => $company->getFedexAccountNumber(),
            'shipping_account_number' => $company->getShippingAccountNumber(),
            'discount_account_number' => $company->getDiscountAccountNumber()
        ];
        foreach ($accounts as $key => $accountNumber) {

            if ($accountNumber) {

                if($accountConfigured = $this->checkIfAccountExistsAndIsValid($key, $accountNumber)) {
                    $accounts[$key] = $accountConfigured;
                } else {
                    $accountSummary = $this->enhancedProfileViewModel->getAccountSummary($accountNumber);
                    if (!empty($accountSummary)) {

                        if (strtolower($accountSummary['account_status']) != 'active' && $accountSummary['account_status'] != null) {

                            unset($accounts[$key]);
                        } else {
                            $accountSummary['account_number'] = $accountNumber;
                            $accounts[$key] = $accountSummary;
                        }
                    }
                }
            }
        }
        $this->customerSession->setCompanyAccountsList($accounts);
    }

    protected function checkIfAccountExistsAndIsValid($key, $accountNumber) {
        $oldAccountsList = $this->customerSession->getCompanyAccountsList();
        if(isset($oldAccountsList[$key]['account_number']) && $oldAccountsList
            && $oldAccountsList[$key]['account_number'] == $accountNumber
            && $oldAccountsList[$key]['account_type'] != '') {
            return $oldAccountsList[$key];
        }

        return false;
    }
}
