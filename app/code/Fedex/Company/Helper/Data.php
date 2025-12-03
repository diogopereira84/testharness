<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Company\Helper;

use Exception;
use Fedex\Company\Model\Config\Source\CredtiCardOptions;
use Fedex\Company\Model\Config\Source\FedExAccountOptions;
use Fedex\Company\Model\Config\Source\PaymentAcceptance;
use Fedex\Company\Model\Config\Source\PaymentOptions;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyExtensionInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Company\Model\CompanyFactory;

class Data extends AbstractHelper
{
    public const HAWKS_B1999377_CODE_OPTIMIZATION = "Hawks_B1999377_code_optimization";
    public const EXPLORERS_SITE_LEVEL_QUOTE = "explorers_site_level_quoting_stores";
    protected $companyPaymentMethodCache = [];

    /**
     * Data constructor
     *
     * @param Context $context
     * @param DeliveryHelper $deliveryHelper
     * @param ToggleConfig $toggleConfig
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyManagementInterface $companyManagement
     * @param Json $json
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     * @param ResourceConnection $resourceConnection
     * @param CompanyFactory $companyFactory
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     */
    public function __construct(
        protected Context $context,
        protected DeliveryHelper $deliveryHelper,
        protected ToggleConfig $toggleConfig,
        protected CompanyRepositoryInterface $companyRepository,
        protected CompanyManagementInterface $companyManagement,
        protected Json $json,
        protected LoggerInterface $logger,
        protected DateTime $dateTime,
        protected ResourceConnection $resourceConnection,
        protected CompanyFactory $companyFactory,
        readonly  AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
    ) {
        parent::__construct($context);
    }

    /**
     * Get company payment method
     * B-1250149 : Magento Admin UI changes to group all the Customer account details
     *
     * @param int|null $companyId
     * @param bool $defaultMethodOnly
     * @return array|string
     */
    public function getCompanyPaymentMethod($companyId = null, $defaultMethodOnly = false)
    {
        try {
            if($this->addToCartPerformanceOptimizationToggle->isActive()){
                $cacheKey = $companyId . '_' . ($defaultMethodOnly ? 'default' : 'all');

                if (isset($this->companyPaymentMethodCache[$cacheKey])) {
                    return $this->companyPaymentMethodCache[$cacheKey];
                }
            }

            $company = $this->getCustomerCompany($companyId);
            if ($company) {
                $companyExtensionAttribute = $company->getExtensionAttributes();
                // Check if toggle for new payment configuration is enabled
                if ($companyExtensionAttribute) {
                    // If only default payment method is needed
                    if ($defaultMethodOnly) {
                        $paymentMethod =  $this->getPreferredPaymentMethod($companyExtensionAttribute);
                    } else {
                        // Get all payment options
                        $paymentMethod = $this->getAllPaymentMethods($companyExtensionAttribute);
                    }

                    if($this->addToCartPerformanceOptimizationToggle->isActive()) {
                        $this->companyPaymentMethodCache[$cacheKey] = $paymentMethod;
                    }
                    return $paymentMethod;
                }
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get default payment method for the company
     * B-1250149 : Magento Admin UI changes to group all the Customer account details
     *
     * @param CompanyExtensionInterface $companyExtensionAttribute
     * @return string|null
     */
    public function getPreferredPaymentMethod($companyExtensionAttribute)
    {
        $paymentOptions = $this->getCompanyPaymentOptions($companyExtensionAttribute);
        if (!empty($paymentOptions)) {
            // If multiple payment method is selected return the default payment option
            // If only one payment option is selected use it as the default one
            if (count($paymentOptions) > 1) {
                $defaultPaymentMethod = $companyExtensionAttribute->getDefaultPaymentMethod();
            } elseif (count($paymentOptions) == 1) {
                $defaultPaymentMethod = reset($paymentOptions);
            }

            if (PaymentOptions::FEDEX_ACCOUNT_NUMBER == $defaultPaymentMethod) {
                return $companyExtensionAttribute->getFedexAccountOptions();
            } elseif (PaymentOptions::CREDIT_CARD == $defaultPaymentMethod) {
                return $companyExtensionAttribute->getCreditcardOptions();
            }
        }

        return null;
    }

    /**
     * Get default payment method for the company
     * B-1250149 : Magento Admin UI changes to group all the Customer account details
     *
     * @param CompanyExtensionInterface $companyExtensionAttribute
     * @return string|null
     */
    public function getPreferredPaymentMethodName($companyId = null)
    {
        try {
            $company = $this->getCustomerCompany($companyId);
            if ($company) {
                $companyExtensionAttribute = $company->getExtensionAttributes();
                $paymentOptions = $this->getCompanyPaymentOptions($companyExtensionAttribute);
                if ($companyExtensionAttribute && $companyExtensionAttribute->getDefaultPaymentMethod()
                    && count($paymentOptions) > 1) {
                    return $companyExtensionAttribute->getDefaultPaymentMethod();
                } elseif (count($paymentOptions) == 1) {
                    return reset($paymentOptions) ?? null;
                }
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get all payment methods of the company
     * B-1250149 : Magento Admin UI changes to group all the Customer account details
     *
     * @param CompanyExtensionInterface $companyExtensionAttribute
     * @return array
     */
    public function getAllPaymentMethods($companyExtensionAttr)
    {
        $pMethod = [];
        try {
            if ($paymentOptions = $this->getCompanyPaymentOptions($companyExtensionAttr)) {
                if (in_array(PaymentOptions::FEDEX_ACCOUNT_NUMBER, $paymentOptions)) {
                    $pMethod[PaymentOptions::FEDEX_ACCOUNT_NUMBER] = $companyExtensionAttr->getFedexAccountOptions();
                }
                if (in_array(PaymentOptions::CREDIT_CARD, $paymentOptions)) {
                    $pMethod[PaymentOptions::CREDIT_CARD] = $companyExtensionAttr->getCreditcardOptions();
                }
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return $pMethod;
    }

    /**
     * Get company payment options
     *
     * @param CompanyExtensionInterface $companyExtensionAttribute
     * @return array
     */
    public function getCompanyPaymentOptions($companyExtensionAttribute)
    {
        if($this->addToCartPerformanceOptimizationToggle->isActive()){
            static $cachedPaymentOptions = null;
            if ($cachedPaymentOptions !== null) {
                return $cachedPaymentOptions;
            }

            $paymentOptions = $companyExtensionAttribute->getCompanyPaymentOptions();
            if (!empty($paymentOptions)) {
                $cachedPaymentOptions = $this->json->unserialize($paymentOptions);
                return $cachedPaymentOptions;
            }
            return [];
        }

        $paymentOptions = $companyExtensionAttribute->getCompanyPaymentOptions();
        if (!empty($paymentOptions)) {
            return $this->json->unserialize($paymentOptions);
        }

        return [];
    }

    /**
     * B-1013340 | Anuj | RT-ECVS-Resolve PHPUnit Console Errors for module 'Company'
     * B-1250149 : Magento Admin UI changes to group all the Customer account details
     * Get company configured FedEx account number
     *
     * @param int|null $companyId
     * @return string|null
     */
    public function getFedexAccountNumber($companyId = null)
    {
        $accountNumber = '';
        $isCCOnly = false;
        $isLegacy = false;

        if($this->addToCartPerformanceOptimizationToggle->isActive()){
            static $fedexAccountNumbers = [];
            if (isset($fedexAccountNumbers[$companyId])) {
                return $fedexAccountNumbers[$companyId];
            }
        }
        try {
            $company = $this->getCustomerCompany($companyId);
            $payMethods = $this->getCompanyPaymentMethod($companyId);
            if (is_array($payMethods)) {
                if (isset($payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER])
                    && $payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER] == FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT) {
                    $accountNumber = trim((string) $company->getFedexAccountNumber());
                } elseif($this->isCompanyPaymentConfigurationEnabledForCC() && isset($payMethods[PaymentOptions::CREDIT_CARD])) {
                    $isCCOnly = true;
                }
                if (isset($payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER])
                && $payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER] == FedExAccountOptions::LEGACY_FEDEX_ACCOUNT) {
                    $accountNumber = '';
                    $isLegacy = true;
                    $isCCOnly = true;
                }
            } elseif ($payMethods == PaymentAcceptance::FEDEX_ACCOUNT_NUMBERS) {
                $accountNumber = trim((string) $company->getFedexAccountNumber());
            }
            if ((empty($accountNumber)) && ($this->getDiscountAccountNumber()) && $isCCOnly == false){
                $accountNumber = trim((string) $this->getDiscountAccountNumber());
            } elseif($this->getDiscountAccountNumber() && $isCCOnly == true && $isLegacy == false) {
                $accountNumber = trim((string) $this->getDiscountAccountNumber());
            }
            if (isset($payMethods[PaymentOptions::CREDIT_CARD]) &&
                $payMethods[PaymentOptions::CREDIT_CARD] == PaymentAcceptance::LEGECY_SITE_CREDIT_CARD
                && !isset($payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER])
            ) {
                $accountNumber = trim((string) $company->getDiscountAccountNumber());
                if (empty($accountNumber)) {
                    $accountNumber = trim((string) $company->getFedexAccountNumber());
                }
            }

        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
        if($this->addToCartPerformanceOptimizationToggle->isActive()){
            $fedexAccountNumbers[$companyId] = !empty($accountNumber) ? $accountNumber : null;
        }
        return !empty($accountNumber) ? $accountNumber : null;
    }

    /**
     * @param int|null $companyId
     * @return string|null
     */
    public function getFxoAccountNumber($companyId = null)
    {
        $accountNumber = '';
        try {
            $company = $this->getCustomerCompany($companyId);
            $payMethods = $this->getCompanyPaymentMethod($companyId);
            if (is_array($payMethods)) {
                if (isset($payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER])
                    && $payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER] == FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT) {
                    $accountNumber = trim((string) $company->getFedexAccountNumber());
                }
            } elseif ($payMethods == PaymentAcceptance::FEDEX_ACCOUNT_NUMBERS) {
                $accountNumber = trim((string) $company->getFedexAccountNumber());
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return !empty($accountNumber) ? $accountNumber : null;
    }

    /**
     * B-1145880 | Get Company Home page settings
     * getCompanyHomePageSetting
     *
     * @return array
     */
    public function getCompanyHomePageSetting()
    {
        $response = ['show_upload_section' => 0, 'show_catalog_section' => 0];

        if ($company = $this->getCustomerCompany()) {
            $enableUploadOnly = $company->getEnableUploadSection();
            if (!empty($enableUploadOnly)) {
                $response = ['show_upload_section' => 1, 'show_catalog_section' => 0];
            }
        }

        return $response;
    }

    /**
     * Get company assigned to the customer
     * B-1250149 : Magento Admin UI changes to group all the Customer account details
     *
     * @param int|null $companyId
     * @return CompanyInterface|null
     */
    public function getCustomerCompany($companyId = null)
    {
        static $cachedCompanydata = null;
        if($this->addToCartPerformanceOptimizationToggle->isActive()){
            if($cachedCompanydata){
                return $cachedCompanydata;
            }
        }

        try {
            if ($companyId) {
                if ($this->addToCartPerformanceOptimizationToggle->isActive()) {
                    return $cachedCompanydata = $this->companyRepository->get($companyId);
                }
                return $this->companyRepository->get($companyId);
            } else {
                if ($this->toggleConfig->getToggleConfigValue(self::HAWKS_B1999377_CODE_OPTIMIZATION)) {
                    if ($this->addToCartPerformanceOptimizationToggle->isActive()) {
                        return $cachedCompanydata = $this->deliveryHelper->getAssignedCompany();
                    }
                    return $this->deliveryHelper->getAssignedCompany();

                } else {
                    if ($customer = $this->deliveryHelper->getCustomer()) {
                        if ($this->addToCartPerformanceOptimizationToggle->isActive()) {
                            return $cachedCompanydata = $this->deliveryHelper->getAssignedCompany($customer);
                        }
                        return $this->deliveryHelper->getAssignedCompany($customer);
                    }
                }
            }

        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' No current customer.');
        return null;
    }


    /**
     * @param int|string $customerId
     * @return CompanyInterface|null
     */
    public function getCompanyFromCustomerId(int|string $customerId)
    {
        return $this->companyManagement->getByCustomerId($customerId);
    }

    /**
     * Get fedex shipping account number for company
     * B-1250149 : Magento Admin UI changes to group all the Customer account details
     *
     * @return string|null
     */
    public function getFedexShippingAccountNumber($companyId = null)
    {
        try {
            $accountNumber = '';
            $company = $this->getCustomerCompany($companyId);
            $payMethods = $this->getCompanyPaymentMethod($companyId);

            if($this->addToCartPerformanceOptimizationToggle->isActive()){
                if (!is_array($payMethods)) {
                    return $payMethods == PaymentAcceptance::FEDEX_ACCOUNT_NUMBERS
                        ? $this->trimAccountNumber($company->getShippingAccountNumber())
                        : null;
                }

                if ($this->shouldUseFedexShippingAccountNumber($payMethods)) {
                    return $this->trimAccountNumber($company->getShippingAccountNumber());
                }

                return null;
            }


            if (is_array($payMethods)) {
                if($this->isCompanyPaymentConfigurationEnabledForCC() && isset($payMethods[PaymentOptions::CREDIT_CARD])) {
                    $accountNumber = trim((string)$company->getShippingAccountNumber());
                } elseif ((isset($payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER])
                    && $payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER] == FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT)) {
                        $accountNumber = trim((string)$company->getShippingAccountNumber());
                }
                if (isset($payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER])
                && $payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER] == FedExAccountOptions::LEGACY_FEDEX_ACCOUNT) {
                    $accountNumber = '';
                }
            } elseif ($payMethods == PaymentAcceptance::FEDEX_ACCOUNT_NUMBERS) {
                $accountNumber = trim($company->getShippingAccountNumber());
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return !empty($accountNumber) ? $accountNumber : null;
    }

    /**
     * Get fedex discount account number for company
     *  D-146468  : Adobe Commerce Admin - Hard Coded Discount Code Not Displaying Discount on User Interface
     *
     * @return string|null
     */
    public function getDiscountAccountNumber($companyId = null)
    {
        try {
            $accountNumber = '';
            $company = $this->getCustomerCompany($companyId);
            $payMethods = $this->getCompanyPaymentMethod($companyId);

            if($this->addToCartPerformanceOptimizationToggle->isActive()){
                if (!is_array($payMethods)) {
                    return $payMethods == PaymentAcceptance::FEDEX_ACCOUNT_NUMBERS
                        ? $this->trimAccountNumber($company->getDiscountAccountNumber())
                        : null;
                }

                if ($this->shouldUseDiscountAccountNumber($payMethods)) {
                    return $this->trimAccountNumber($company->getDiscountAccountNumber());
                }

                return null;
            }

            if (is_array($payMethods)) {
                if($this->isCompanyPaymentConfigurationEnabledForCC() && isset($payMethods[PaymentOptions::CREDIT_CARD])) {
                    $accountNumber = trim((string)$company->getDiscountAccountNumber());
                } elseif (isset($payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER])
                    && $payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER] == FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT) {
                    $accountNumber = trim((string)$company->getDiscountAccountNumber());
                }
                if (isset($payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER])
                && $payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER] == FedExAccountOptions::LEGACY_FEDEX_ACCOUNT) {
                    $accountNumber = '';
                }
                if (isset($payMethods[PaymentOptions::CREDIT_CARD]) && $payMethods[PaymentOptions::CREDIT_CARD] == PaymentAcceptance::LEGECY_SITE_CREDIT_CARD) {
                    $accountNumber = trim((string) $company->getDiscountAccountNumber());
                }
            } elseif ($payMethods == PaymentAcceptance::FEDEX_ACCOUNT_NUMBERS) {
                $accountNumber = trim($company->getDiscountAccountNumber());
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
        return !empty($accountNumber) ? $accountNumber : null;
    }


    /**
     * @param array $payMethods
     * @return bool
     */
    private function shouldUseFedexShippingAccountNumber(array $payMethods): bool
    {
        if ($this->isCompanyPaymentConfigurationEnabledForCC() && isset($payMethods[PaymentOptions::CREDIT_CARD])) {
            return true;
        }

        if (isset($payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER])) {
            $fedExAccountOption = $payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER];
            if ($fedExAccountOption == FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT) {
                return true;
            }
            if ($fedExAccountOption == FedExAccountOptions::LEGACY_FEDEX_ACCOUNT) {
                return false;
            }
        }

        return false;
    }


    /**
     * @param array $payMethods
     * @return bool
     */
    private function shouldUseDiscountAccountNumber(array $payMethods): bool
    {
        if ($this->isCompanyPaymentConfigurationEnabledForCC() && isset($payMethods[PaymentOptions::CREDIT_CARD])) {
            return true;
        }

        if (isset($payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER])) {
            $fedExAccountOption = $payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER];
            if ($fedExAccountOption == FedExAccountOptions::CUSTOM_FEDEX_ACCOUNT) {
                return true;
            }
            if ($fedExAccountOption == FedExAccountOptions::LEGACY_FEDEX_ACCOUNT) {
                return false;
            }
        }

        if (isset($payMethods[PaymentOptions::CREDIT_CARD])
            && $payMethods[PaymentOptions::CREDIT_CARD] == PaymentAcceptance::LEGECY_SITE_CREDIT_CARD) {
            return true;
        }

        return false;
    }

    /**
     * @param string|null $accountNumber
     * @return string|null
     */
    private function trimAccountNumber(?string $accountNumber): ?string
    {
        $trimmed = trim((string) $accountNumber);
        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Get company configured credit card data
     * B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
     *
     * @return array
     */
    public function getCompanyCreditCardData($companyId = null)
    {
        $creditCardData = [];
        try {
            $company = $this->getCustomerCompany($companyId);
            if ($company && $company->getExtensionAttributes()) {
                $companyExtensionAttribute = $company->getExtensionAttributes();
                $paymentMethods = $this->getCompanyPaymentMethod($companyId);
                if (is_array($paymentMethods) && isset($paymentMethods[PaymentOptions::CREDIT_CARD])
                    && $paymentMethods[PaymentOptions::CREDIT_CARD] == CredtiCardOptions::NEW_CREDIT_CARD) {
                    $ccToken = $companyExtensionAttribute->getCcToken();
                    $ccData = $companyExtensionAttribute->getCcData();
                    $ccTokenExpiryDateTime = $companyExtensionAttribute->getCcTokenExpiryDateTime();

                    if (!empty($ccToken) &&
                        !empty($ccData) && $this->isValidCreditCardTokenExpiryDate($ccTokenExpiryDateTime)) {
                        $ccData = $this->json->unserialize($ccData);
                        $creditCardData['token'] = $ccToken;
                        $creditCardData['data'] = array_merge($ccData, ['token' => $ccToken]);
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return $creditCardData;
    }

    /**
     * Get Default company payment method
     *
     * If both fedex account number and credit card are configured for company then,
     * default payment method will be null but both configured data will be returned.
     * B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
     *
     * @return array
     */
    public function getDefaultPaymentMethod()
    {
        $paymentMethod = ['defaultMethod' => '', 'paymentMethodInfo' => ''];
        $creditCardData = $this->getCompanyCreditCardData();
        $fedexAccountNumber = $this->getFedexAccountNumber();

        if (!empty($creditCardData) && $fedexAccountNumber) {
            $paymentMethod['paymentMethodInfo'] = [
                PaymentOptions::FEDEX_ACCOUNT_NUMBER => $fedexAccountNumber,
                PaymentOptions::CREDIT_CARD => $creditCardData['data'] ?? [],
            ];
        } elseif ($fedexAccountNumber) {
            $paymentMethod['defaultMethod'] = PaymentOptions::FEDEX_ACCOUNT_NUMBER;
            $paymentMethod['paymentMethodInfo'] = $fedexAccountNumber;
        } elseif (!empty($creditCardData)) {
            $paymentMethod['defaultMethod'] = PaymentOptions::CREDIT_CARD;
            $paymentMethod['paymentMethodInfo'] = $creditCardData['data'] ?? [];
        }
        return $paymentMethod;
    }

    /**
     * Check if new payment configuration toggle is enabled or not
     * B-1250149 : Magento Admin UI changes to group all the Customer account details
     *
     * @return bool
     */
    public function isNewCompanyPaymentConfigurationEnabled()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue('company_payment_options');
    }

    /**
     * Get credit card token expiry date time when the CC is configured in Admin.
     * B-1359540 : For Credit card configured in Magento Admin , expiration date should be validated
     *
     * @param int|null $companyId
     * @return void
     */
    public function getCreditCardTokenExpiryDateTime($companyId = null)
    {
        try {
            if ($company = $this->getCustomerCompany($companyId)) {
                $companyExtensionAttribute = $company->getExtensionAttributes();
                if ($companyExtensionAttribute && !empty($companyExtensionAttribute->getCcToken())) {
                    return $companyExtensionAttribute->getCcTokenExpiryDateTime();
                }
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return  '';
    }

    /**
     * Check if credit card token expiry date is valid or not when the CC is configured in Admin.
     * B-1359540 : For Credit card configured in Magento Admin , expiration date should be validated
     *
     * @param string $creditCardExpiryDate
     * @return bool
     */
    public function isValidCreditCardTokenExpiryDate($creditCardExpiryDate)
    {
        try {
            if (!empty($creditCardExpiryDate)) {
                $ccExpiryDatetime = $this->dateTime->gmtTimeStamp($creditCardExpiryDate);
                $currentTime = $this->dateTime->gmtTimeStamp();
                if ($currentTime < $ccExpiryDatetime) {
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return false;
    }

    /**
     * if company URL extension duplicate we returning true.
     * isCompanyUrlExtentionDuplicate
     * @param int $$id
     *
     * @return boolean
     */
    public function isCompanyUrlExtentionDuplicate($urlExt, $id)
    {
        $isExtDuplicate = false;

        if (isset($urlExt)) {
            $companyUrlExt = strtolower(trim($urlExt));
            $companyObj = $this->companyFactory->create()
                ->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('company_url_extention', ['eq' => $companyUrlExt]);

            if ($companyObj && is_array($companyObj->getData())) {
                foreach ($companyObj->getData() as $companyData) {
                    if (
                        isset($companyData['company_url_extention']) &&
                        $companyData['company_url_extention'] == $companyUrlExt &&
                        isset($companyData['entity_id']) && ($companyData['entity_id'] != $id  || $id == null)
                    ) {
                        $isExtDuplicate = true;
                    }
                }
            }
        }

        return $isExtDuplicate;
    }

        /**
     * @Athor Pratibha
     * Validate for unique domain id
     *
     * @param int $id
     * @param int $company_id
     * @return boolean
     */
    public function validateNewtworkId($networkId, $companyId)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('company');
        $query = $connection->select()->from($table)->where('network_id = ?', $networkId);
        if ($companyId !== null) {
            $query->where('entity_id <> ?', $companyId);
        }
        $result = $connection->fetchAll($query);
        if (count($result) == 0) {
            return 1;
        }

        return 0;
    }

    /**
     * @Athor Pratibha
     * Validate for unique domain name
     *
     * @param string $name
     * @param int $company_id
     * @return boolean
     */
    public function validateCompanyName($name, $companyId)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('company');
        $query = $connection->select()->from($table)->where('company_name = ?', $name);
        if ($companyId !== null) {
            $query->where('entity_id <> ?', $companyId);
        }
        $result = $connection->fetchAll($query);
        if (count($result) == 0) {
            return 1;
        }

        return 0;
    }

    /**
     *
     * Get company level configurations
     *
     * @return array
     */
    public function getCompanyLevelConfig()
    {
        $companyConfigData = [
            "promo_discount" => false,
            "account_discount" => false,
            "terms_and_conditions" => false,
            "reorder" => false,
            "epro_new_platform_order_creation" => false
        ];

        $company = $this->getCustomerCompany();
        if (is_object($company)) {
            $getAdditionalDataObject = $company->getExtensionAttributes()->getCompanyAdditionalData();
            $companyConfigData["promo_discount"] = $getAdditionalDataObject->getIsPromoDiscountEnabled();
            $companyConfigData["account_discount"] = $getAdditionalDataObject->getIsAccountDiscountEnabled();
            $companyConfigData["epro_new_platform_order_creation"] = $getAdditionalDataObject->getEproNewPlatformOrderCreation();
            // Notes used for Self Reg and SDE orders
            if (!$this->deliveryHelper->isEproCustomer()) {
                $companyConfigData["order_notes"] = $getAdditionalDataObject->getOrderNotes();
            }

            $companyConfigData["terms_and_conditions"] = $getAdditionalDataObject->getTermsAndConditions();
            $companyConfigData["reorder"] = $getAdditionalDataObject->getIsReorderEnabled()
            && !$this->deliveryHelper->isSdeCustomer();
        }

        return $companyConfigData;
    }

    /**
     * @return string|null
     */
    public function getCompanyName()
    {
        $company = $this->getCustomerCompany();
        return $company && $company->getCompanyName() ? $company->getCompanyName() :  'Site';
    }

    /**
     * @return int|string
     */
    public function getCompanyId()
    {
        $company = $this->getCustomerCompany();

        return $company && $company->getId() ? $company->getId() : '';
    }

    /**
     * getRecipientAddressFromPo
     * @return boolean
     */
    public function getRecipientAddressFromPo()
    {
        $company = $this->getCustomerCompany();
        $isRecipientAddressEnabled = false;
        if ($company) {
            $companyLoginType = $company->getStorefrontLoginMethodOption();
            if ($companyLoginType == 'commercial_store_epro') {
                $isRecipientAddressEnabled = ($company->getRecipientAddressFromPo() == 0) ? false: true;
            }
        }
        return $isRecipientAddressEnabled;
    }

    /**
     *
     * Get Is Non Editable Company Cc Payment Method
     *
     * @return boolean
     */
    public function getNonEditableCompanyCcPaymentMethod()
    {
        $nonEditableCompanyCcPaymentMethod = false;
        $company = $this->getCustomerCompany();
        if (is_object($company)) {
            $getAdditionalDataObject = $company->getExtensionAttributes()->getCompanyAdditionalData();
            $nonEditableCompanyCcPaymentMethod = $getAdditionalDataObject->getIsNonEditableCcPaymentMethod();
        }

        return $nonEditableCompanyCcPaymentMethod;
    }
    /**
     * Check if new payment configuration toggle is enabled or not
     * E-390888 : Add FedEx Accounts for CC Commercial sites
     *
     * @return bool
     */
    public function isCompanyPaymentConfigurationEnabledForCC()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue('explorers_enable_disable_fedex_account_cc_commercial');
    }
    /**
     * E-390888 - Add FedEx Accounts for CC Commercial sites
     *
     * @param int|null $companyId
     * @return bool
     */
    public function isApplicablePaymentMethodCCOnly($companyId = null)
    {
        $isCCOnly = false;
        try {
                $company = $this->getCustomerCompany($companyId);
                $payMethods = $this->getCompanyPaymentMethod($companyId);
                if (is_array($payMethods)) {
                    if (!isset($payMethods[PaymentOptions::FEDEX_ACCOUNT_NUMBER]) && isset($payMethods[PaymentOptions::CREDIT_CARD])) {
                        $isCCOnly = true;
                    }
                }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return $isCCOnly;
    }

    /**
     * Check toggle of site level quoating score feature
     */
    public function isSiteLevelQuoteToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_SITE_LEVEL_QUOTE);
    }

    /**
     * Get Fedex Print And Ship Account Numbers
     *
     * @return array
     */
    public function getFedexPrintShipAccounts()
    {
        $fedexPrintShipAccounts = [];
        $printAccount = '';
        try {
            $company = $this->getCustomerCompany();
            if (isset($company)) {
                $printAccount = $company->getFedexAccountNumber() != null ? $company->getFedexAccountNumber() : $company->getDiscountAccountNumber();
                $fedexPrintShipAccounts = [
                    "print_account" => trim((string)$printAccount),
                    "ship_account" => trim((string) $company->getShippingAccountNumber())
                ];
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return $fedexPrintShipAccounts;
    }
}
