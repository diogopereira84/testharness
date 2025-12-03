<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\EnhancedProfile\ViewModel;

use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Shipto\Helper\Data;
use Fedex\SSO\Helper\Data as SsoHelper;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\Country;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\Base\Helper\Auth as AuthHelper;
use Magento\Company\Api\CompanyManagementInterface;
use Fedex\SelfReg\ViewModel\CompanyUser;
use Fedex\Delivery\Helper\Data as CompanyHelper;

/**
 * EnhancedProfile ViewModel class
 */
class EnhancedProfile implements ArgumentInterface
{
    const TIGER_E486666 = 'tiger_e486666';
    const ID_TOKEN = 'id_token';
    public const RETAILSTORECODE = 'main_website_store';

    /**
     * FCL My Profile Id
     */
    public const FCL_MY_PROFILE_URL = 'sso/general/fcl_my_profile_url';
    public const PROFILE_API_URL = "sso/general/profile_api_url";
    public const ACCOUNT_SUMMARY = 'fedex/enhanced_profile_group/account_summary';
    public const CREDIT_CARD_TOKENS = 'fedex/enhanced_profile_group/creditcardtokens';
    public const IMAGE_BASE_URL = 'images/enhanced-profile';
    public const TAB_INDEX = ' tabindex="0"';
    public const SHARED_CC_TOOLTIP ='web/shared_cc_Tooltip/Message_cc';
    public const TOGGLE_KEY ='explorers_company_settings_customer_admin';

    /**
     * EnhancedProfile constructor.
     * @param Country $country
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $customerSession
     * @param Data $shipToHelper
     * @param SsoHelper $ssoHelper
     * @param CookieManagerInterface $cookieManager
     * @param Repository $assetRepo
     * @param Curl $curl
     * @param PunchoutHelper $punchoutHelper
     * @param LoggerInterface $logger
     * @param DateTimeFactory $dateTimeFactory
     * @param ToggleConfig $toggleConfig
     * @param StoreManagerInterface $storeManager
     * @param HeaderData $headerData
     * @param AdminConfigHelper $adminConfigHelper
     * @param AuthHelper $authHelper
     * @param CompanyManagementInterface $companyRepository
     * @param CompanyUser $companyUser
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     * @param CompanyHelper $companyHelper
     */
    public function __construct(
        protected Country $country,
        protected ScopeConfigInterface                  $scopeConfig,
        protected Session                               $customerSession,
        protected Data                                  $shipToHelper,
        protected SsoHelper                             $ssoHelper,
        protected CookieManagerInterface                $cookieManager,
        protected Repository                            $assetRepo,
        protected Curl                                  $curl,
        private PunchoutHelper                          $punchoutHelper,
        protected LoggerInterface                       $logger,
        protected DateTimeFactory                       $dateTimeFactory,
        protected ToggleConfig                          $toggleConfig,
        protected StoreManagerInterface                 $storeManager,
        protected HeaderData                            $headerData,
        private AdminConfigHelper                       $adminConfigHelper,
        protected AuthHelper                            $authHelper,
        protected CompanyManagementInterface            $companyRepository,
        private CompanyUser                             $companyUser,
        readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle,
        protected CompanyHelper                         $companyHelper,
    ) {
    }

    /**
     * Get Preferred Delivery
     *
     * @param string $locationId
     * @param string $hoursOfOperation
     * @return boolean|string false
     */
    public function getPreferredDelivery($locationId, $hoursOfOperation = false)
    {
        return $this->shipToHelper->getAddressByLocationId($locationId, $hoursOfOperation);
    }

    /**
     * Get the list of regions present in the given Country
     *
     * @param string $countryCode
     * @return array/void
     */
    public function getRegionsOfCountry($countryCode)
    {
        $regionCollection = $this->country->loadByCode($countryCode)->getRegions();
        return $regionCollection->loadData()->toOptionArray(false);
    }

    /**
     * Get config value
     *
     * @param string $code
     * @return string
     */
    public function getConfigValue($code)
    {
        return $this->scopeConfig->getValue($code, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get WLGN profile URL
     *
     * @return string
     */
    public function getFclMyProfileUrl()
    {
        return $this->scopeConfig->getValue(self::FCL_MY_PROFILE_URL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Credit Card Data
     *
     * @return array
     */
    public function getCreditCardList()
    {
        if ($this->customerSession->getCreditCardList()) {
            return $this->customerSession->getCreditCardList();
        } else {
            return [];
        }
    }

    /**
     * Get Credit Card Data
     *
     * @return array
     */
    public function getFedexAccountsList()
    {
        if ($this->customerSession->getFedexAccountsList()) {
            return $this->customerSession->getFedexAccountsList();
        } else {
            return [];
        }
    }

    /**
     * Get Loggedin profile Info
     *
     * @return array
     */
    public function getLoggedInProfileInfo()
    {
        if ($this->customerSession->getProfileSession()) {
            return $this->customerSession->getProfileSession();
        } else {
            return [];
        }
    }

    /**
     * Set Profile session
     *
     * @return void
     */
    public function setProfileSession()
    {
        $endUrl = $this->getConfigValue(self::PROFILE_API_URL);
        if ($this->ssoHelper->getFCLCookieNameToggle()) {
            $cookieName = $this->ssoHelper->getFCLCookieConfigValue();
            $fdxLogin = $this->cookieManager->getCookie($cookieName);
            if (
                $this->toggleConfig->getToggleConfigValue('magegeeks_E_464167_ExposeEnhancedProfileTabsforSSOCustomers') &&
                $this->ssoHelper->isSSOlogin()) {
                $fdxLogin = $this->cookieManager->getCookie(self::ID_TOKEN);
            }
        } else {
            $fdxLogin = $this->cookieManager->getCookie('fdx_login');
        }
        $this->ssoHelper->getProfileByProfileApi($endUrl, $fdxLogin);
    }

    /**
     * Get opening hours
     *
     * @param object $workingHours
     * @return array
     */
    public function getOpeningHours($workingHours)
    {
        $openingHours = [];
        $key = 0;

        for ($i = 0; $i < 7; $i++) {
            $hoursOfOperation = $workingHours[$i];

            if (isset($hoursOfOperation->day)) {
                if ($hoursOfOperation->day == 'MONDAY') {
                    $key = 0;
                } elseif ($hoursOfOperation->day == 'TUESDAY') {
                    $key = 1;
                } elseif ($hoursOfOperation->day == 'WEDNESDAY') {
                    $key = 2;
                } elseif ($hoursOfOperation->day == 'THURSDAY') {
                    $key = 3;
                } elseif ($hoursOfOperation->day == 'FRIDAY') {
                    $key = 4;
                } elseif ($hoursOfOperation->day == 'SATURDAY') {
                    $key = 5;
                } elseif ($hoursOfOperation->day == 'SUNDAY') {
                    $key = 6;
                }
            }

            $openTime = null;
            $closeTime = null;

            if (isset($hoursOfOperation->schedule)) {
                if ($hoursOfOperation->schedule == 'Closed') {
                    $openTime = '';
                    $closeTime = '';
                } else {
                    $openTime = isset($hoursOfOperation->openTime) ? $hoursOfOperation->openTime : null;
                    $closeTime = isset($hoursOfOperation->closeTime) ? $hoursOfOperation->closeTime : null;
                }
            }

            $openingHours[$key] = [
                'date' => isset($hoursOfOperation->date) ? $hoursOfOperation->date : null,
                'day' => isset($hoursOfOperation->day) ? $hoursOfOperation->day : null,
                'schedule' => isset($hoursOfOperation->schedule) ? $hoursOfOperation->schedule : null,
                'openTime' => $openTime,
                'closeTime' => $closeTime,
            ];
        }

        ksort($openingHours);

        return $openingHours;
    }

    /**
     * Make Array for credit card JSON for remove & make as default
     *
     * @param object $ccSessionData
     * @return array
     */
    public function makeArrayForCerditCardJson($ccSessionData)
    {
        $response = [];
        $billingAddress = $ccSessionData->billingAddress;
        $companyName = '';
        if (isset($billingAddress->company->name)) {
            $companyName = $billingAddress->company->name;
        }
        $response['creditCardToken'] = $ccSessionData->creditCardToken;
        $response['tokenExpirationDate'] = $ccSessionData->tokenExpirationDate;
        $response['cardHolderName'] = $ccSessionData->cardHolderName;
        $response['creditCardLabel'] = $ccSessionData->creditCardLabel;
        $response['maskedCreditCardNumber'] = $ccSessionData->maskedCreditCardNumber;
        $response['creditCardType'] = $ccSessionData->creditCardType;
        $response['expirationMonth'] = $ccSessionData->expirationMonth;
        $response['expirationYear'] = $ccSessionData->expirationYear;
        $response['company'] = $companyName;
        $response['streetLines'] = $billingAddress->streetLines[0];
        $response['postalCode'] = $billingAddress->postalCode;
        $response['city'] = $billingAddress->city;
        $response['stateOrProvinceCode'] = $billingAddress->stateOrProvinceCode;
        $response['countryCode'] = $billingAddress->countryCode;
        $response['primary'] = $ccSessionData->primary;

        return $response;
    }

    /**
     * Prepare credit card JSON for add credit cart
     *
     * @param array $ccFormData
     * @return json
     */
    public function prepareAddCerditCardJson($ccFormData)
    {
        return '{
            "creditCards": [
                {
                    "cardHolderName": "' . $ccFormData['cardHolderName'] . '",
                    "maskedCreditCardNumber": "' . $ccFormData['maskedCreditCardNumber'] . '",
                    "creditCardType": "' . $ccFormData['creditCardType'] . '",
                    "creditCardLabel":"' . $ccFormData['creditCardLabel'] . '",
                    "creditCardToken": "' . $ccFormData['creditCardToken'] . '",
                    "tokenExpirationDate": "' . $ccFormData['tokenExpirationDate'] . '",
                    "expirationMonth": "' . $ccFormData['expirationMonth'] . '",
                    "expirationYear": "' . $ccFormData['expirationYear'] . '",
                    "primary" : "' . $ccFormData['primary'] . '",
                    "billingAddress": {
                        "streetLines": [
                            "' . $ccFormData['streetLines'] . '"
                        ],
                       "city": "' . $ccFormData['city'] . '",
                        "stateOrProvinceCode": "' . $ccFormData['stateOrProvinceCode'] . '",
                        "postalCode": "' . $ccFormData['postalCode'] . '",
                        "countryCode": "' . $ccFormData['countryCode'] . '",
                        "company": {
                            "name": "' . $ccFormData['company'] . '"
                        }
                    }
                }
            ]
        }';
    }

    /**
     * Prepare credit card JSON for update
     *
     * @param array $ccFormData
     * @return json
     */
    public function prepareUpdateCerditCardJson($ccFormData)
    {
        if ($ccFormData['primary'] == 1) {
            $ccFormData['primary'] = 'true';
        }
        return '{
            "creditCard": {
                "cardHolderName": "' . $ccFormData['cardHolderName'] . '",
                "maskedCreditCardNumber": "' . $ccFormData['maskedCreditCardNumber'] . '",
                "creditCardType": "' . $ccFormData['creditCardType'] . '",
                "creditCardLabel":"' . $ccFormData['creditCardLabel'] . '",
                "creditCardToken": "' . $ccFormData['creditCardToken'] . '",
                "tokenExpirationDate": "' . $ccFormData['tokenExpirationDate'] . '",
                "expirationMonth": "' . $ccFormData['expirationMonth'] . '",
                "expirationYear": "' . $ccFormData['expirationYear'] . '",
                "primary" : "' . $ccFormData['primary'] . '",
                "billingAddress": {
                    "streetLines": [
                        "' . $ccFormData['streetLines'] . '"
                    ],
                    "city": "' . $ccFormData['city'] . '",
                    "stateOrProvinceCode": "' . $ccFormData['stateOrProvinceCode'] . '",
                    "postalCode": "' . $ccFormData['postalCode'] . '",
                    "countryCode": "' . $ccFormData['countryCode'] . '",
                    "company": {
                        "name": "' . $ccFormData['company'] . '"
                    }
                }
            }
        }';
    }

    /**
     * Make html for add and update credit card
     *
     * @param object $cardInfo
     * @param boolean $saveStatus
     * @return html
     */
    public function makeCreditCardHtml($cardInfo, $saveStatus)
    {
        if ($saveStatus) {
            $cardInfo = $cardInfo->creditCard;
        } else {
            $cardInfo = $cardInfo->creditCardList[0];
        }
        $companyName = '';
        if (isset($cardInfo->billingAddress->company->name)) {
            $companyName = $cardInfo->billingAddress->company->name . ' ';
        }
        $countryName = '';
        if ($cardInfo->billingAddress->countryCode == 'US') {
            $countryName = "United States of America";
        } else {
            $countryName = $cardInfo->billingAddress->countryCode;
        }
        $stateTitle = '';
        foreach ($this->getRegionsOfCountry('us') as $state) {
            if ($cardInfo->billingAddress->stateOrProvinceCode == $state['label']) {
                $stateTitle = $state['title'];
            }
        }
        $cardIcon = str_replace(' ', '_', strtolower($cardInfo->creditCardType)) . '.png';
        $iconUrl = $this->getMediaUrl() . 'wysiwyg/images/' . $cardIcon;
        $cardId = $cardInfo->profileCreditCardId;
        $isPrimary = $cardInfo->primary;
        $tokenExpDate =  $cardInfo->expirationMonth . '/' . substr($cardInfo->expirationYear, -2);
        $isTokenExpiry = $this->getTokenIsExpired($cardInfo->tokenExpirationDate);
        $primary = '';
        $expires = '';
        if (!$isTokenExpiry) {
            if ($cardInfo->primary) {
                $primary = '<div class="cart-status-default-content">
                        <div class="cart-status-default">
                            <span class="default">' . __('Default') . '</span>
                        </div>
                    </div>';
            } else {
                $primary = '<div class="cart-status-make-content">
                        <div class="cart-status-make" tabindex="0">
                            <span class="default">' .
                __('Make Default')
                    . '</span>
                        </div>
                    </div>';
            }
        }

        if ($isTokenExpiry) {
            $expires = '<div class="card-expired"><span>' . __('Expired') .'</span></div>';
        } else {
            $expires = '<div class="card-expires"><span>' .__('Expires ') . $tokenExpDate . '</span></div>';
        }

        return '<div class="credit-cart-content">
            <div class="credit-card-head">
                <div class="head-left">
                    <div class="left">
                        <img src="' . $iconUrl . '" alt="' . $cardInfo->creditCardType . '"/>
                    </div>
                    <div class="right">
                        <div class="card-type">
                            <span>' . $cardInfo->creditCardType . '</span>
                        </div>
                        <div class="card-number">
                            <span>' . __('ending in ') . '*' . substr($cardInfo->maskedCreditCardNumber, -4) . '
                            </span>
                        </div>
                    </div>
                </div>
                <div class="head-mid">' . $expires . '</div>
                <div class="head-right" data-cardId="' . $cardId . '">' . $primary . '</div>
            </div>
            <div class="credit-card-body">
                <div class="credit-card-name">
                    <div class="name-content">
                        <div class="name-title">
                            <span>' . __('Name on card') . '</span>
                        </div>
                        <div class="name">
                            <span>' . $cardInfo->cardHolderName . '</span>
                        </div>
                    </div>
                    <div class="action">
                        <div class="action-edit" data-cardId="' . $cardId . '
                        "data-profilecreditid="' . $cardId . '" tabindex="0">
                            <span data-primary="' . $isPrimary . '"class="edit">' .
        __('Edit')
        . '</span>
                        </div>
                    </div>
                </div>
                <div class="credit-card-address">
                    <div class="address-content">
                        <div class="address-title">
                            <span>' . __('Billing Address') . '</span>
                        </div>
                        <div class="content">
                            <div class="name">' .
        $cardInfo->cardHolderName
        . '</div>
                            <span>' .
        implode(" ", $cardInfo->billingAddress->streetLines) . ', ' .
        $companyName .
        $cardInfo->billingAddress->city . ' ' .
        $stateTitle . ' ' .
        $cardInfo->billingAddress->postalCode . ' ' .
        $countryName
        . '</span>
                        </div>
                        <div class="mobile-edit" tabindex="0">
                            <span>' . __('Edit') . '</span>
                        </div>
                    </div>
                    <div class="action">
                        <span class="remove" data-cardId="' . $cardId . '" '. self::TAB_INDEX .'>' .
        __('Remove')
            . '</span>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Make html for Add New Account
     *
     * @param object $accountInfo
     * @return html
     */
    public function makeNewAccountHtml($accountInfo)
    {
        $account = $accountInfo->accounts[0];
        $accountSummary = $this->getAccountSummary($account->accountNumber);
        $accountType = '';
        $tax = '';
        if ($accountSummary['account_type'] == "PAYMENT") {
            $dollarIcon = $this->assetRepo->getUrl("Fedex_EnhancedProfile::images/dollar.png");
            $accountType = '<p>
                <img src="' . $dollarIcon . '" class="dollar-icon" alt="dollor ' . $account->accountLabel . '">
                ' . __('Payment') .
            '</p>';
        } elseif ($accountSummary['account_type'] == "DISCOUNT") {
            $tagIcon = $this->assetRepo->getUrl("Fedex_EnhancedProfile::images/tag.png");
            $accountType = '<p>
                <img src="' . $tagIcon . '" class="dollar-icon" alt="tag ' . $account->accountLabel . '">
                ' . __('Discount') . '
            </p>';
        } else {
            $accountType = '<span>'.__('NA').'</span>';
        }
        $defaultAccountType = '';
        if (strtolower($accountSummary['account_status']) != 'inactive') {
            if ($account->primary == 'true') {
                $defaultAccountType = '<span class="shipping-make-default default payment-default"
                data-profile-account-id="' . $account->profileAccountId . '"
                data-account-number="' . $account->accountNumber . '"
                data-masked-account-number="' . $account->maskedAccountNumber . '"
                data-account-label="' . $account->accountLabel . '"
                data-account-type="' . $account->accountType . '"
                data-billing-reference="' . $account->billingReference .
                '" '. self::TAB_INDEX .'>' . __('Default') . '</span>';
            } else {
                $defaultAccountType = '<span class="make-default-link shipping-make-default"
                data-profile-account-id="' . $account->profileAccountId . '"
                data-account-number="' . $account->accountNumber . '"
                data-masked-account-number="' . $account->maskedAccountNumber . '"
                data-account-label="' . $account->accountLabel . '"
                data-account-type="' . $account->accountType . '"
                data-billing-reference="' . $account->billingReference
                . '" '. self::TAB_INDEX .'>' . __('Make Default') . '</span>';
            }
        }
        if ($accountSummary['account_tax_certificates'] == "TAX") {
            $taxIcon = $this->assetRepo->getUrl("Fedex_EnhancedProfile::images/tax.png");
            $tax = '<p class="tax-exempt-desc">
                <img src="' . $taxIcon . '"
                class="tax-exempt-icon" alt="tax exempt ' . $account->accountLabel . '">' . __('Tax exempt') . '
            </p>';
        }
        return '<div class="payment-account-list" id="payment_account_list_' . rand(100, 500) . '">
                <div class="payment-account-top">
                    <div class="shipping-account-container">
                        <h3>' . $account->accountLabel . '</h3>
                        <div class="mask-account">' . __('Ending in ') .
                        $account->maskedAccountNumber . '</div>
                    </div>
                    <div class="default-container">'. $defaultAccountType .'</div>
                </div>
                <div class="payment-account-info-container">
                    <div class="status-container">
                        <h4>' . __('Status') . '</h4>
                        <p><i class="fa fa-circle"></i> ' . $accountSummary['account_status'] . '</p>
                    </div>
                    <div class="eligible-for-container">
                        <h4>' . __('Eligible For') . '</h4>
                        ' . $accountType . '
                        ' . $tax . '
                    </div>
                    <div class="action-container">
                        <p class="action-edit">
                            <span class="btn-edit"
                            data-profile-account-id="' . $account->profileAccountId . '"
                            data-account-number="' . $account->accountNumber . '"
                            data-masked-account-number="' . $account->maskedAccountNumber . '"
                            data-account-label="' . $account->accountLabel . '"
                            data-account-type="' . $account->accountType . '"
                            data-billing-reference="' . $account->billingReference
                            . '" '. self::TAB_INDEX .'>' . __('Edit') . '</span>
                        </p>
                        <p class="action-remove">
                            <span class="remove-link"
                            data-profile-account-id="' . $account->profileAccountId . '" '. self::TAB_INDEX .'>'
                            . __('Remove') . '</span>
                        </p>
                    </div>
                </div>
            </div>';
    }

    /**
     * Save credit card via API
     *
     * @param string $customerRequest
     * @param json $postFields
     * @return object
     */
    public function saveCreditCard($customerRequest, $postFields)
    {
        $profileInfo = $this->getLoggedInProfileInfo();
        $userProfileId = $profileInfo->output->profile->userProfileId;
        $endPointUrl = $this->getConfigValue(self::PROFILE_API_URL).'/'.$userProfileId.'/creditcards';
        return $this->apiCall($customerRequest, $endPointUrl, $postFields);
    }

    /**
     * Update credit card via API
     *
     * @param json $postFields
     * @param string $profileCreditCardId
     * @param string $customerRequest
     * @return object
     */
    public function updateCreditCard($postFields, $profileCreditCardId, $customerRequest)
    {
        $profileInfo = $this->getLoggedInProfileInfo();
        $userProfileId = $profileInfo->output->profile->userProfileId;
        $endPointUrl = $this->getConfigValue(self::PROFILE_API_URL).'/'.$userProfileId.
        '/creditcards/'.$profileCreditCardId;

        return $this->apiCall($customerRequest, $endPointUrl, $postFields);
    }

    /**
     * Prepare credit card Token JSON
     *
     * @param string $ccFormData
     * @return json
     */
    public function prepareCreditCardTokensJson($ccFormData)
    {
        return '{
            "creditCardTokenRequest": {
                "requestId": "' . $ccFormData['requestId'] . '",
                "creditCard": {
                    "encryptedData": "' . $ccFormData['encryptedData'] . '",
                    "nameOnCard": "' . $ccFormData['nameOnCard'] . '",
                    "billingAddress": {
                        "streetLines": [
                            "' . $ccFormData['streetLines'] . '"
                        ],
                        "city": "' . $ccFormData['city'] . '",
                        "stateOrProvinceCode": "' . $ccFormData['stateOrProvinceCode'] . '",
                        "postalCode": "' . $ccFormData['postalCode'] . '",
                        "countryCode": "' . $ccFormData['countryCode'] . '",
                        "addressClassification": "HOME"
                    }
                }
            }
        }';
    }

    /**
     * Call Api to add, update & remove
     *
     * @param string $customerRequest
     * @param string $endPointUrl
     * @param json $postFields
     * @return object
     */
    public function apiCall($customerRequest, $endPointUrl, $postFields)
    {
        $tazToken = $this->punchoutHelper->getTazToken();
        $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "X-clientid: ISHP",
            $authHeaderVal . $gatewayToken,
            "Cookie: Bearer=" . $tazToken,
        ];

        if (!empty($postFields)) {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => $customerRequest,
                    CURLOPT_POSTFIELDS => $postFields,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]
            );
        } else {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => $customerRequest,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]
            );
        }

        $this->curl->post($endPointUrl, $postFields);

        return json_decode((string)$this->curl->getBody());
    }

    /**
     * Call Api to check the account status
     *
     * @param string $accountNumber
     * @return object
     */
    public function getAccountSummary($accountNumber)
    {

        if($this->addToCartPerformanceOptimizationToggle->isActive()){
            static $accountInfoCache = [];

            if (isset($accountInfoCache[$accountNumber])) {
                return $accountInfoCache[$accountNumber];
            }

            $companyDataPayment = $this->customerSession->getCompanyDataPayment();
            if (is_array($companyDataPayment) && isset($companyDataPayment[$accountNumber])) {
                return $companyDataPayment[$accountNumber];
            }
        }

        $search = ['{accountNumber}'];
        $replace = [$accountNumber];
        $endPointUrl = str_replace($search, $replace, (string)$this->getConfigValue(self::ACCOUNT_SUMMARY));
        $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
        $tazToken = $this->punchoutHelper->getTazToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "X-clientid: ISHP",
            $authHeaderVal . $gatewayToken,
            "Cookie: Bearer=" . $tazToken,
            "Cookie: fxo_ecam_userid=user;",
        ];
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
            ]
        );

        $accountInfo = [];
        try {
            $this->curl->post($endPointUrl, '');
            $response = json_decode($this->curl->getBody());
            if(($response && (
                    isset($response->status) && $response->status === 500 ||
                    isset($response->message) && $response->message == 'Account Not Found')
                )) {
                $accountInfo['account_status'] = 'inactive';
                $accountInfo['account_type'] = '';
                $accountInfo['account_tax_certificates'] =  '';

                return $accountInfo;
            }
            $accountStatus = '';
            $accountType = '';
            $accountTaxCertificates = '';
            if (isset($response->output->accounts[0]->accountUsage)) {
                $accountUsage = $response->output->accounts[0]->accountUsage;
                if (isset($accountUsage->print->status)) {
                    $accountStatus = $accountUsage->print->status;
                }
                if (isset($accountUsage->originatingOpco) && $accountUsage->originatingOpco == "FXK") {
                    if (isset($accountUsage->print->payment->allowed)
                        && $accountUsage->print->payment->allowed == "Y") {
                        $accountType = "PAYMENT";
                    } elseif (isset($accountUsage->print->payment->allowed)
                        && $accountUsage->print->payment->allowed == "N") {
                        $accountType = "DISCOUNT";
                    }
                } elseif (isset($accountUsage->originatingOpco) && $accountUsage->originatingOpco == "FX") {
                    if (isset($accountUsage->ship->status)
                        && $accountUsage->ship->status == "true") {
                        $accountType = "SHIPPING";
                    }
                }
                if (isset($accountUsage->print->taxCertificates)
                    && $accountUsage->print->taxCertificates != "null") {
                    $accountTaxCertificates = "TAX";
                }
            }
            $accountInfo['account_status'] = $accountStatus;
            $accountInfo['account_type'] = $accountType;
            $accountInfo['account_tax_certificates'] = $accountTaxCertificates;

            if($this->addToCartPerformanceOptimizationToggle->isActive()){
                $accountInfoCache[$accountNumber] = $accountInfo;
            }

        } catch (\Exception $e) {
            $this->logger->critical("Payment Account API is not working: " . $e->getMessage());
        }

        return $accountInfo;
    }

    /**
     * Validate credit card token expairation
     *
     * @param string $expirationDateTime
     * @return object
     */
    public function getTokenIsExpired($expirationDateTime)
    {
        $status = false;
        $tokenExpDate = strtotime($expirationDateTime);
        $dateModel = $this->dateTimeFactory->create();
        $today =  strtotime($dateModel->gmtDate());
        if ($today > $tokenExpDate) {
            $status = true;
        }
        return $status;
    }

    /**
     * Set Default Shipping Method
     *
     * @return void
     */
    public function setDefaultShippingMethod()
    {
        try {
            $profileInfo = $this->getLoggedInProfileInfo();
            $isShippingAccount = false;
            $isShippingAccountDefault = false;
            $counter = 0;
            if (isset($profileInfo->output->profile->accounts)) {
                $accountsList = $profileInfo->output->profile->accounts;
                foreach ($accountsList as $account) {
                    if (!$counter) {
                        $shippingAccountNumber = $account->accountNumber;
                        $counter++;
                    }
                    if (isset($account->accountType) && (strtolower($account->accountType) == 'shipping')) {
                        $isShippingAccount = true;
                        if ($account->primary) {
                            $isShippingAccountDefault = true;
                        }
                    }
                }
            }

            if ($isShippingAccount && !$isShippingAccountDefault) {
                $postFields = '{
                    "profile": {
                        "primaryShippingAccount": "'.$shippingAccountNumber.'"
                    }
                }';
                $profileApiUrl = $this->getConfigValue(self::PROFILE_API_URL).'/';
                $userProfileId = $profileInfo->output->profile->userProfileId;
                $endUrl = $profileApiUrl.$userProfileId;
                $this->apiCall('PUT', $endUrl, $postFields);
                $this->setProfileSession();
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ' Make a Default Shipping Account API is not working: ' . $e->getMessage());
        }
    }

    /**
     * Set Preferred Payment Method
     *
     * @return void
     */
    public function setPreferredPaymentMethod()
    {
        try {
            $this->setProfileSession();
            $profileInfo = $this->getLoggedInProfileInfo();
            $isFedExAccount = false;
            $isCreditCard = false;
            if (isset($profileInfo->output->profile->accounts)) {
                $accountsList = $profileInfo->output->profile->accounts;
                foreach ($accountsList as $account) {
                    if (isset($account->accountType) && (strtolower($account->accountType) == 'printing')) {
                        $accountSummary = $this->getAccountSummary($account->accountNumber);
                        if (!empty($accountSummary) && strtolower($accountSummary['account_status']) == 'active') {
                            $isFedExAccount = true;
                        }
                    }
                }
            }
            if (isset($profileInfo->output->profile->creditCards)) {
                $isCreditCard = true;
            }
            if (!$isFedExAccount && $isCreditCard) {
                $userProfileId = $profileInfo->output->profile->userProfileId;
                $postFields = '{
                    "profile": {
                        "userProfileId": "'.$userProfileId.'",
                        "payment": {
                            "preferredPaymentMethod": "CREDIT_CARD"
                        }
                    }
                }';
                $profileApiUrl = $this->getConfigValue(self::PROFILE_API_URL).'/';
                $endUrl = $profileApiUrl.$userProfileId;
                $response = $this->apiCall('PUT', $endUrl, $postFields);
                if (isset($response->output->profile->payment)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ' Make a Default Shipping Account API is not working: ' . $e->getMessage());
        }
    }

    /**
     * Validate Fdx Login
     *
     * @return boolean
     */
    public function validateFdxLogin()
    {
        if (
            $this->toggleConfig->getToggleConfigValue('techtitans_d_196604') &&
            $this->customerSession->getFdxLogin()
        ) {
            return true;
        }

        $fdxLoginStatus = true;
        $loginMethod = null;
        if ($this->authHelper->isLoggedIn()) {
            $email = $this->customerSession->getCustomer()->getEmail();
            if ($this->ssoHelper->getFCLCookieNameToggle()) {
                $cookieName = $this->ssoHelper->getFCLCookieConfigValue();
                $fdxLogin = $this->cookieManager->getCookie($cookieName);
            } else {
                $fdxLogin = $this->cookieManager->getCookie('fdx_login');
            }
            $customerId = $this->customerSession->getId();
            $companyData = $this->companyRepository->getByCustomerId($customerId);
            if ($companyData) {
                $loginMethod = $companyData->getStorefrontLoginMethodOption();
            }
            if (!isset($fdxLogin)) {
                if($loginMethod == 'commercial_store_epro') {
                    $fdxLoginStatus = true;
                } else {
                    $this->customerSession->setProfileSession([]);
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                    "User Fdx login not available for user: " . $email);
                    $fdxLoginStatus = false;
                }

            } else {
                if ($this->toggleConfig->getToggleConfigValue('techtitans_d_196604')) {
                    $this->customerSession->setFdxLogin(
                        $fdxLogin
                    );
                }
            }
        }

        return $fdxLoginStatus;
    }

    /**
     * isEproLogin
     * @return boolean
     */
    public function isEproLogin()
    {
        $companyData = $this->customerSession->getOndemandCompanyInfo();
        if ($companyData && is_array($companyData) && !empty($companyData['company_data']['storefront_login_method_option'])) {
            $loginMethod = $companyData['company_data']['storefront_login_method_option'];
            if($this->getProfileAPIfixEpro() && $loginMethod == 'commercial_store_epro') {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * D-194433 Profile API fix for Epro admin toggle
     *
     * @return boolean
     */
    public function getProfileAPIfixEpro(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue('explorers_d_194433_profileapi_fix');
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get config value
     *
     * @param string $code
     * @return string
     */
    public function getTooltipMessage()
    {
        return $this->scopeConfig->getValue(self::SHARED_CC_TOOLTIP, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if customer is Logged In
     * @deprecated use \Fedex\Base\Helper\Auth::isLoggedIn() instead
     */
    public function isLoggedIn()
    {
        return $this->authHelper->isLoggedIn();
    }

    /**
     * Get Login Validation Key
     * @return null|string
     */
    public function getLoginValidationKey()
    {
        return $this->customerSession->getLoginValidationKey();
    }

    /**
     *  Get Explorers E-427430 Company Settings Customer Admin Toggle
     * @return bool
     */
    public function isCompanySettingToggleEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TOGGLE_KEY);
    }

    /**
     * Check if quote priceable disable
     *
     * @param object $quote
     * @return boolean|bool
     */
    public function isQuotePriceableDisable($quote)
    {
        return $this->adminConfigHelper->checkoutQuotePriceisDashable($quote);
    }

    /**
     * Site Level Make html for Add New Account
     *
     * @param string|int $accountNumber
     * @param string $siteAccountType
     * @param bool $editableFlag
     * @return html
     */
    public function siteLevelMakeNewAccountHtml($accountNumber, $siteAccountType, $editableFlag)
    {
        $lastFourNumber = substr($accountNumber, -4);
        $accountLabel = 'Payment';
        $accountSummary = $this->getAccountSummary($accountNumber);
        $dollarIcon = $this->assetRepo->getUrl("images/enhanced-profile/site-amount.png");
        $information = $this->assetRepo->getUrl("Fedex_EnhancedProfile::images/information-icon.png");
        if($accountSummary['account_type'] == 'DISCOUNT') {
            $accountLabel = 'Discount';
        }
        $accountType = '<p><img src="' . $dollarIcon . '" class="dollar-icon" alt="dollor ' . "Payment" . '">
               ' . $accountLabel . '</p>';
        $defaultAccountType = '';
        $removeLink = '';
        $accountTypeNameForEditable = $siteAccountType."_account_number_editable";
        $checkedAdd = $editableFlag ? "checked" : "";
        $paymentAccountList = 'payment_account_list_'.$siteAccountType;
        if (strtolower($siteAccountType) === 'print') {
            $defaultAccountType = '<span class="print-text">' . __('Print') . '</span>';
            $defaultAccountType .= '<input type="hidden" name="print_account_number" id="print_account_number" value="'.$accountNumber.'" />';
            $removeLink =  '<span class="remove-link remove-account-link"
                            data-remove-id="'.$paymentAccountList.'"
                            data-account-type="Print" '.
                            self::TAB_INDEX .'>'
                            . __('Remove') . '</span>';
        } else {
            $defaultAccountType = '<span class="ship-text">' . __('Ship') . '</span>';
            $defaultAccountType .= '<input type="hidden" name="ship_account_number" id="ship_account_number" value="'.$accountNumber.'" />';
            $removeLink =  '<span class="remove-link remove-account-link"
                            data-remove-id="'.$paymentAccountList.'"
                            data-account-type="Ship" '.
                self::TAB_INDEX .'>'
                . __('Remove') . '</span>';
        }

        return '<div class="payment-account-list '.$accountSummary['account_type'].'" id='.$paymentAccountList.'>
                <div class="payment-account-top">
                    <div class="shipping-account-container">
                        <h3>' . __('FedEx Account ') . $lastFourNumber . '</h3>
                        <div class="mask-account">' . __('ending in *') .
                        $lastFourNumber . '</div>
                    </div>
                    <div class="default-container">'. $defaultAccountType .'</div>
                </div>
                <div class="payment-account-info-container">
                 <div class="payment-account-info-container-top">
                    <div class="status-container">
                        <h4>' . __('Status') . '</h4>
                        <p><i class="fa fa-circle"></i> ' . $accountSummary['account_status'] . '</p>
                    </div>
                    <div class="eligible-for-container">
                        <h4>' . __('Eligible For') . '</h4>
                        ' . $accountType . '
                    </div>
                    <div class="action-container">
                        <p class="action-remove">
                           ' . $removeLink . '
                        </p>
                    </div>
                   </div>
                   <div class="payment-account-info-container-bottom credit-card-container">
                           <div class="account-credit-cards-toggle cc-toggle-enable" tabindex="0">
                                <label class="toggle-title">
                                '. __('Account Number Editable'). '</label>
                               <div class="info-icon">
                                 <span class="my-tooltip">
                                    <img aria-describedby="credit-card-payment-info"
                                         src="'.$information.'"
                                         class="tooltip-toggle tooltip-icon"
                                         alt="Enable Credit Card as payment Method" />
                                         <span id="credit-card-payment-info-3"
                                         role="tooltip"
                                         class="tooltip-content alternate-tooltip-text
                                        credit-card-payment-info">
                                        '. __("When toggled on, users will be
                                        able to remove and add their own account number"). '
                                          </span>
                                 </span>
                               </div>
                               <div class="enhanced-profile-toggle switch-credit-cards-toggle">
                                  <label class="switch">
                                     <input type="checkbox" id="'.$accountTypeNameForEditable.'" name="'.$accountTypeNameForEditable.'" value="'.$editableFlag.'"
                                      '.$checkedAdd.' />
                                      <span class="custom-slider round"></span>
                                      <span class="labels" data-on="ON" data-off="OFF"></span>
                                  </label>
                               </div>
                            </div>
                   </div>
                 </div>
            </div>';
    }

    /**
     * check if B2Bapproval order flow is enabled
     */
    public function getB2BApprovalToggle() {
        return $this->companyUser->isB2BOrderAprovalEnable();
    }

    public function isTigerE486666Enabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_E486666);
    }

    public function isE464167SSOCustomerEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue('magegeeks_E_464167_ExposeEnhancedProfileTabsforSSOCustomers');
    }

    public function isSSOLogin(): bool
    {
        return (bool)$this->ssoHelper->isSSOlogin();
    }

    /**
     * Check if SSO Group is empty or not
     *
     * @return bool
     */
    public function isSSOGroup()
    {
        $companyHelper = $this->companyHelper->getAssignedCompany();
        if(is_object($companyHelper) && !empty($companyHelper->getSsoGroup())){
            return true;
        }
        return false;
    }

    /**
     * Get is Fcl Customer
     *
     * @return boolean|string false
     */
    public function isFCLCustomer()
    {
        return $this->customerSession->getCustomerId() && $this->isRetail();
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
}