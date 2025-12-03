<?php
declare(strict_types=1);

namespace Fedex\Cart\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\EnhancedProfile\Helper\Account;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const STRING_TYPE = 'string';
    public const REMOVE_BASE64_TOGGLE = 'is_remove_base64_image';


    public const TIGER_D_208155_PROMO_MIXED_CART = 'tiger_d_208155_promo_mixed_cart';

    /**
     * @param Context $context
     * @param ScopeConfigInterface $configInterface
     * @param DeliveryDataHelper $deliveryHelper
     * @param EncryptorInterface $encryptor
     * @param SsoConfiguration $ssoConfiguration
     * @param CustomerSession $customerSession
     * @param EnhancedProfile $enhancedProfile
     * @param CheckoutSession $checkoutSession
     * @param ToggleConfig $toggleConfig
     * @param Account $accountHelper
     */
    public function __construct(
        Context $context,
        protected ScopeConfigInterface $configInterface,
        protected DeliveryDataHelper $deliveryHelper,
        protected EncryptorInterface $encryptor,
        protected SsoConfiguration $ssoConfiguration,
        protected CustomerSession $customerSession,
        protected EnhancedProfile $enhancedProfile,
        protected CheckoutSession $checkoutSession,
        protected ToggleConfig $toggleConfig,
        protected Account $accountHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Get Max Cart Limit Value Customer
     *
     * @return $cartLimit
     */
    public function getMaxCartLimitValue()
    {
        $maxCartItemLimit = $this->configInterface->getValue("checkout/cart/max_cart_item_limit");
        $minCartItemThreshold = $this->configInterface->getValue("checkout/cart/min_cart_item_threshold");

        return ['maxCartItemLimit' => $maxCartItemLimit, 'minCartItemThreshold' => $minCartItemThreshold];
    }

    /**
     * Add Epro Class in Head
     *
     * D-77776 - Minicart , Search Allingment issue
     **/
    public function setEproClass()
    {
        $isCommercialCustomer = $this->deliveryHelper->isCommercialCustomer();
        if ($isCommercialCustomer) {
            return true;
        }

        return false;
    }

    /**
     * Encrypt data
     *
     * @param   string $value
     * @return  string
     */
    public function encryptData($data)
    {
        return $this->encryptor->encrypt($data);
    }

    /**
     * Decrypt data
     *
     * @param   string $data
     * @return  string
     */
    public function decryptData($data)
    {
        return $this->encryptor->decrypt($data);
    }

    /**
     * Get default fedex account number
     *
     * @return  string
     */
    public function getDefaultFedexAccountNumber()
    {
        $defaultFedexAccountNumber = '';

        if ($this->ssoConfiguration->isFclCustomer()) {
            $defaultFedexAccountNumber = $this->getDefaultFxoNumberForFCLUser();
        } else {
            $personalAccountList = $this->accountHelper->getActivePersonalAccountList();
            $companyAccountList = $this->accountHelper->getActiveCompanyAccountList('discount');
            if (!empty($companyAccountList)) {

                $defaultFedexAccountNumber = key($companyAccountList);
            } elseif (!empty($personalAccountList)) {

                $defaultFedexAccountNumber = array_search(
                    1,
                    array_column($personalAccountList, 'selected', 'account_number')
                );
            }
        }

        return $defaultFedexAccountNumber ? $this->encryptData((string)$defaultFedexAccountNumber) : '';
    }

    /**
     * Get default FXO account number for fcl user
     *
     * @return  string
     */
    public function getDefaultFxoNumberForFCLUser()
    {
        $fxoAccountNumber = '';
        $profileInfo = $this->customerSession->getProfileSession();
        $accountList = [];
        if (isset($profileInfo->output->profile->accounts)) {
            $accountList = $profileInfo->output->profile->accounts;
        }
        foreach ($accountList as $accountInfo) {
            $accountConditionWithPayment = $accountInfo->accountType == 'PAYMENT';
            if (($accountInfo->accountType == 'PRINTING' || ($accountConditionWithPayment))
            && $accountInfo->primary) {
                $accountSummary = $this->enhancedProfile->getAccountSummary($accountInfo->accountNumber);
                if (isset($accountSummary['account_status'])
                && strtolower($accountSummary['account_status']) == 'active') {
                    $fxoAccountNumber = $accountInfo->accountNumber;
                    break;
                }
            }
        }

        return $fxoAccountNumber;
    }

    /**
     * Format Price
     * @param $price
     * @return String|Float|Int
     */
    public function formatPrice($price)
    {
        if (gettype($price) === static::STRING_TYPE) {
            return str_replace(["$", ",", "(", ")"], "", $price);
        }

        return $price;
    }

    /**
     * Get Rate API URL
     */
    public function getRateQuoteApiUrl()
    {
        return $this->configInterface->getValue("fedex/general/rate_post_api_url");
    }

    /**
     * Check if quote priceable disable
     *
     * @param object $quote
     * @return boolean|bool
     */
    public function checkQuotePriceableDisable($quote)
    {
        return $this->enhancedProfile->isQuotePriceableDisable($quote);
    }

    /**
     * Get rate quote id from rate quote response
     *
     * @param array $rateQuoteResponse
     * @return string|null
     */
    public function getRateQuoteId($rateQuoteResponse)
    {
        if (isset($rateQuoteResponse['output']['rateQuote'])
            && isset($rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'])
        ) {
            foreach ($rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'] as $data) {
                if (isset($data['rateQuoteId'])) {
                    return $data['rateQuoteId'];
                }
            }
        }

        return null;
    }

    /**
     * Get product Association
     */
    public function getProductAssociation($item, $index, $quoteObjectItemsCount, $dbQuoteItemCount)
    {
        // TODO - Update with quote_item->qty
        if ($item->getMiraklOfferId()) {
            $additionalData = json_decode($item->getAdditionalData());
            return ['id' => $item->getItemId(), 'quantity' => $additionalData->quantity, 'is_marketplace' => true];
        }

        if (!empty($item->getItemId())) {
            return ['id' => $item->getItemId(), 'quantity' => $item->getQty(), 'is_marketplace' => false];
        }

        if ($quoteObjectItemsCount == $dbQuoteItemCount) {
            return ['id' => $item->getItemId(), 'quantity' => $item->getQty(), 'is_marketplace' => true];
        }

        return ['id' => $index, 'quantity' => $item->getQty(), 'is_marketplace' => true];
    }

     /**
      * Set FXO Product to Null
      */
    public function setFxoProductNull($decodedData, $externalProdData)
    {
        if (isset($decodedData['external_prod'][0]['fxo_product'])) {
            $externalProdData['external_prod'][0]['fxo_product'] = null;
        }

        return $externalProdData;
    }

    /**
     * Apply FedEx Account in checkout from Mini Cart in Retail
     *
     * @param object $quote
     *
     * @return boolean
     */
    public function applyFedxExAccountInCheckout($quote, $customAccountNumber = null)
    {
        $quoteConditionWithToggle = (!$quote->getData("fedex_account_number")
        && !$this->checkoutSession->getRemoveFedexAccountNumber());

        if ((!$this->checkoutSession->getRemoveFedexAccountNumber()
        && !$this->checkoutSession->getAppliedFedexAccNumber())
        || $quoteConditionWithToggle) {
            $defaultFedexAccountNumber = $customAccountNumber ?? $this->getDefaultFedexAccountNumber();
            $quote->setData('fedex_account_number', $defaultFedexAccountNumber);
            $quote->save();
        }

        return true;
    }

    /**
     * Set Address Classification
     *
     * @param string|null $addressClassification
     */
    public function setAddressClassification($addressClassification)
    {
        $this->checkoutSession->setAddressClassification($addressClassification);
    }

    /**
     * Get Address Classification
     *
     * @return string|null
     */
    public function getAddressClassification()
    {
        $addressClassification = null;
        if ($this->checkoutSession->getAddressClassification() != null
            && $this->checkoutSession->getAddressClassification() != ''
        ) {
            $addressClassification = $this->checkoutSession->getAddressClassification();
        }

        return $addressClassification;
    }

    /**
     * Check if Address Classification Toggle Enabled
     *
     * @return bool
     */
    public function isAddressClassificationFixToggleEnabled()
    {
        if ($this->toggleConfig->getToggleConfigValue('explorers_address_classification_fix')) {
            return true;
        }

        return false;
    }

    /**
     * Getting Dlt_threshold Attribute value
     * @param object $quote
     * @param object $qty
     * @return int
     */
    public function getDltThresholdHours($product, $qty)
    {
        $dltHours = 0;
        $dltThreshold = $product->getData('dlt_thresholds');
        if (!empty($dltThreshold)) {
            $dltThresholdData = json_decode($dltThreshold, true)['dlt_threshold_field'];
            foreach ($dltThresholdData as $dltData) {
                if (isset($dltData['dlt_start'], $dltData['dlt_end'], $dltData['dlt_hours']) &&
                    $qty >= $dltData['dlt_start'] && $qty <= $dltData['dlt_end']) {
                    $dltHours = $dltData['dlt_hours'];
                    break;
                }
            }
        }

        return $dltHours;
    }

    /**
     * Setting DltThreshold hour in RateQuoteApi
     * @param object $decodedData
     * @param object $dltHours
     */
    public function setDltThresholdHours($decodedData, $dltHours)
    {
        if (isset($decodedData['external_prod'][0]['externalProductionDetails'])) {
            // Update the existing productTime key
            $decodedData['external_prod'][0]['externalProductionDetails']['productionTime'] = [
                'value' => $dltHours,
                'units' => 'HOUR',
            ];
        } else {
            // Create the externalProductionDetail key with the productTime
            $decodedData['external_prod'][0]['externalProductionDetails'] = [
                'productionTime' => [
                    'value' => $dltHours,
                    'units' => 'HOUR',
                ],
            ];
        }
        return $decodedData;
    }

     /**
     * Checks if the customer is reatil or commercial customer
     */
    public function isCommercialCustomer() {
        return $this->deliveryHelper->isCommercialCustomer();
    }

    /**
     * Check if Base64 Image Toggle is Enabled
     *
     * @return bool
     */
    public function isRemoveBase64ImageToggleEnabled()
    {
        if ($this->toggleConfig->getToggleConfigValue(self::REMOVE_BASE64_TOGGLE)) {
            return true;
        }

        return false;
    }
    
    /**     
     *
     * @return bool
     */
    public function isMixedCartPromoErrorToggleEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_D_208155_PROMO_MIXED_CART);
    }
}
