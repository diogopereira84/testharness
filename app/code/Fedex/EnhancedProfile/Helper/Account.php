<?php
declare(strict_types=1);

namespace Fedex\EnhancedProfile\Helper;

use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Model\CompanySelfRegDataFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\Base\Helper\Auth as AuthHelper;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;

class Account extends AbstractHelper
{
    public const RETAILSTORECODE = 'main_website_store';

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param CheckoutSession $checkoutSession
     * @param EnhancedProfile $enhancedProfile
     * @param EncryptorInterface $encryptor
     * @param CompanyManagementInterface $companyRepository
     * @param ToggleConfig $toggleConfig
     * @param CompanySelfRegDataFactory $companySelfRegDataFactory
     * @param Json $json
     * @param AuthHelper $authHelper
     * @param CompanyHelper $companyHelper
     * @param GetLoggedAsCustomerAdminIdInterface|null $getLoggedAsCustomerAdminId
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Context $context,
        protected StoreManagerInterface $storeManager,
        protected Session $customerSession,
        protected CheckoutSession $checkoutSession,
        protected EnhancedProfile $enhancedProfile,
        protected EncryptorInterface $encryptor,
        protected CompanyManagementInterface $companyRepository,
        protected ToggleConfig $toggleConfig,
        private CompanySelfRegDataFactory $companySelfRegDataFactory,
        private Json $json,
        protected AuthHelper $authHelper,
        private CompanyHelper $companyHelper,
        readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle,
        private LoggerInterface $logger,
        protected ?GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId = null
    ) {
        parent::__construct($context);
        $this->getLoggedAsCustomerAdminId = $getLoggedAsCustomerAdminId
        ?? ObjectManager::getInstance()->get(GetLoggedAsCustomerAdminIdInterface::class);
    }

    /**
     * @param bool|string $filterByType
     * @return array
     */
    public function getActivePersonalAccountList($filterByType = false)
    {
        $personalAccountsList = [];
        $profileSession = $this->customerSession->getProfileSession();
        if ($profileSession && isset($profileSession->output->profile->accounts)) {
            foreach ($profileSession->output->profile->accounts as &$account) {
                if ($account && !isset($account->accountType) || ($account->accountType == '' || $account->accountType == 'PRINTING')) {
                    $accountSummary = $this->enhancedProfile->getAccountSummary($account->accountNumber);

                    if($this->addToCartPerformanceOptimizationToggle->isActive()){
                        $existing = $this->customerSession->getCompanyDataPayment() ?? [];
                        $existing[$account->accountNumber] = $accountSummary;
                        $this->customerSession->setCompanyDataPayment($existing);
                    }

                    if (isset($accountSummary['account_status'])
                        && (strtolower($accountSummary['account_status']) == 'active'
                            || $accountSummary['account_status'] == null)
                    ) {
                        $account->accountValid = true;
                        $account->accountType = $accountSummary['account_type'] ?? 'NA';
                    } else {
                        $account->accountValid = false;
                        continue;
                    }
                } elseif ($account && (property_exists($account, 'accountValid') && !$account->accountValid)) {
                    continue;
                }
                $dataSelected = $account->primary ? 1 : 0;
                $fedexAccountShow = $account->accountLabel . ' - ' . ltrim($account->maskedAccountNumber, '*');
                if (!$filterByType || strtolower($filterByType) == strtolower($account->accountType)) {
                    $personalAccountsList[$account->accountNumber] = [
                        'account_number' => $account->accountNumber,
                        'selected' => $dataSelected,
                        'label' => $fedexAccountShow,
                        'valid' => true,
                        'type' => $account->accountType
                    ];
                }
            }
        }

        return $personalAccountsList;
    }

    /**
     * @param $filterByType
     * @return array
     */
    public function getActiveCompanyAccountList($filterByType = false)
    {
        if($this->addToCartPerformanceOptimizationToggle->isActive()){
            if($this->customerSession->isLoggedIn()){
                $companyCustomerId = $this->getCompanyByCustomerId($this->customerSession->getId());
                if ($companyCustomerId) {
                    $companyId = $companyCustomerId->getId();

                    $companyFedexAccountNumber = $this->companyHelper->getFedexAccountNumber($companyId);
                    $existingCompanyData = $this->customerSession->getCompanyDataPayment() ?? [];
                    $existingCompanyData[$companyFedexAccountNumber] = $this->enhancedProfile->getAccountSummary($companyFedexAccountNumber);
                    $this->customerSession->setCompanyDataPayment($existingCompanyData);
                }
            }
        }

        $companyAccountsList = [];
        $companyAccounts = $this->customerSession->getCompanyAccountsList();
        if (!empty($companyAccounts)) {
            foreach ($companyAccounts as $accountName => $accountSummary) {

                if(!$accountSummary) {
                    continue;
                }

                $accountType = !empty($accountSummary['account_type']) ? $accountSummary['account_type'] : '';

                // Get Account Type based on account name
                // This enables us to remove account types that were placed at the wrong input field
                // and are not valid for the current account type
                $accountTypeByName = strstr($accountName, '_', true);

                // Map account type by name to 'payment' if it is 'fedex'
                $accountTypeByName = ($accountTypeByName == 'fedex') ? 'payment' : $accountTypeByName;

                if (!$filterByType || (strtolower($accountType) == $filterByType && strtolower($accountTypeByName) == $filterByType)){

                    $fedexAccountShow = ucfirst(strstr($accountName, '_', true)) . ' Account - *'
                        . substr($accountSummary['account_number'], -4);
                    $companyAccountsList[$accountSummary['account_number']] =[
                        'selected'  => false,
                        'label'     => $fedexAccountShow,
                        'valid'     => true,
                        'type'      => $accountType
                    ];
                }
            }
        }

        return $companyAccountsList;
    }

    /**
     * @param $accountNumber
     * @return bool|Quote
     */
    public function applyAccountNumberToCheckoutSession($accountNumber)
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            if ($quote && $quote->getId() && $accountNumber) {
                $accountNo = $this->encryptor->encrypt($accountNumber);
                $quote->setData('fedex_account_number', $accountNo);
                $this->checkoutSession->setAppliedFedexAccNumber($accountNo);
                $this->checkoutSession->setAppliedFedexAccDiscountOnly($accountNumber);

                return $quote;
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error Message: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * @param $customerId int
     * @return \Magento\Company\Api\Data\CompanyInterface|null
     */
    public function getCompanyByCustomerId($customerId)
    {
        try {
            return $this->companyRepository->getByCustomerId($customerId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * To identify the retail store
     *
     * @return boolean
     */
    public function isRetail()
    {
        $isRetail = false;
        if ($this->getCurrentStoreCode() == self::RETAILSTORECODE) {
            $isRetail = true;
        }
        return $isRetail;
    }

    /**
     * Get current store code
     *
     * @return string
     */
    public function getCurrentStoreCode()
    {
        return $this->storeManager->getGroup()->getCode();
    }

    /**
     * @return false|string
     */
    public function getCompanyLoginType()
    {
        if ($this->authHelper->isLoggedIn()) {
            return match ($this->authHelper->getCompanyAuthenticationMethod()) {
                AuthHelper::AUTH_FCL => 'FCL',
                AuthHelper::AUTH_SSO => 'SSO',
                AuthHelper::AUTH_PUNCH_OUT => 'EPro Punchout',
                default => false
            };
        }
        return false;
    }

    /**
     * Check if current store is SDE store
     *
     * @return Boolean
     */
    public function getIsSdeStore()
    {
        $companyData = $this->customerSession->getOndemandCompanyInfo();
        if ($companyData && is_array($companyData) &&
            !empty($companyData['url_extension']) &&
            !empty($companyData['company_type']) &&
            $companyData['company_type'] == 'sde'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if current store is SelfReg store
     *
     * @return Boolean
     */
    public function getIsSelfRegStore()
    {
        if ($this->customerSession->getId()) {
            $companyData = $this->companyRepository->getByCustomerId($this->customerSession->getId());
            if (!empty($companyData)) {
                $companySelfRegData = $this->companySelfRegDataFactory->create()->getCollection()
                    ->addFieldToFilter('company_id', ['eq' => $companyData->getId()])->getFirstItem();
                if ($companySelfRegData && $companySelfRegData->getSelfRegData() !== null) {

                    $companySelfRegData = $this->json->unserialize($companySelfRegData->getSelfRegData());
                    if ($companySelfRegData && isset($companySelfRegData['enable_selfreg'])
                        && $companySelfRegData['enable_selfreg']) {

                        return (bool)$companySelfRegData['enable_selfreg'];
                    }
                }
            }
        }

        return false;
    }

    /**
     * @return string|null
     */
    public function getAppliedFedexAccDiscountOnly()
    {
        return (string)$this->checkoutSession->getAppliedFedexAccDiscountOnly();
    }

    /**
     * @return array
     */
    public function getLoggedInProfileInfo() {
        return $this->enhancedProfile->getLoggedInProfileInfo();
    }

    /**
     * @return array
     */
    public function getPreferredPaymentMethod() {
        $profileInfo = $this->enhancedProfile->getLoggedInProfileInfo();
        $preferredPaymentMethod = $profileInfo->output->profile->payment->preferredPaymentMethod ?? null;
        if ($preferredPaymentMethod === 'ACCOUNT') {
            $preferredPaymentMethod = 'fedexaccountnumber';
        } elseif ($preferredPaymentMethod === 'CREDIT_CARD') {
            $preferredPaymentMethod = 'creditcard';
        }
        return $preferredPaymentMethod;
    }

    /**
     * @param bool|string $filterByType
     * @return string
     */
    public function getAccountNumberType($accountNumber)
    {
        if ($accountNumber) {
            $accountSummary = $this->enhancedProfile->getAccountSummary($accountNumber);
            if (is_array($accountSummary) && isset($accountSummary['account_type'])) {
                return $accountSummary['account_type'];
            }
        }

        return '';
    }

    /**
     *  Get Explorers E-427430 Company Settings Customer Admin Toggle
     * @return bool
     */
    public function isCompanySettingToggleEnabled(): bool
    {
        return $this->enhancedProfile->isCompanySettingToggleEnabled();
    }

    /**
     * Check if Admin ID is greater than zero
     *
     * @return bool
     */
    public function getAdminIdByLoginAsCustomer()
    {
        $adminId = $this->getLoggedAsCustomerAdminId->execute();
        return $adminId > 0;
    }

    /**
     * get the baseUrl
     *
     * @return string
     */
    public function getCurrentBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * get the toggle enable value
     *
     * @return bool
     */
    public function isCTCAdminToggleEnable()
    {
        return $this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator');
    }
}
