<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\EnhancedProfile\ViewModel;

use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\AdditionalData;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * EnhancedProfile ViewModel class
 */
class CompanyPaymentData implements ArgumentInterface
{
    public const TAB_INDEX = ' tabindex="0"';

    /**
     * EnhancedProfile constructor.
     *
     * @param LoggerInterface $logger
     * @param EnhancedProfile $enhancedProfile
     * @param AdditionalDataFactory $additionalDataFactory
     * @param Json $json
     * @param CompanyHelper $companyHelper
     * @param Repository $assetRepo
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected EnhancedProfile $enhancedProfile,
        private AdditionalDataFactory $additionalDataFactory,
        private Json $json,
        private CompanyHelper $companyHelper,
        protected Repository $assetRepo
    )
    {
    }

    /**
     * Prepare credit card JSON for add credit cart
     *
     * @return object
     */
    public function getCompanyDataById()
    {
        $companyId = $this->getCompanyId();

        return $this->additionalDataFactory->create()
            ->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter(AdditionalData::COMPANY_ID, ['eq' => $companyId])
            ->getFirstItem();
    }

    /**
     * Get CC Data
     *
     * @return array
     */
    public function getCompanyCcData()
    {
        $creditCardData = [];
        $companyObject = $this->getCompanyDataById();

        if (!$companyObject->isEmpty() && !empty($companyObject->getCcData())) {
            $ccToken = $companyObject->getCcToken();
            $ccData = $this->json->unserialize($companyObject->getCcData());
            $ccTokenExpiryDateTime = $companyObject->getCcTokenExpiryDateTime();
            $defaultPaymentMethod = $companyObject->getDefaultPaymentMethod();

            $creditCardData['data'] = array_merge(
                $ccData,
                [
                    "token" => $ccToken,
                    "tokenExpirationDate" => $ccTokenExpiryDateTime,
                    "DefaultPayMethod" => $defaultPaymentMethod
                ]
            );
        }

        return $creditCardData;
    }

    /**
     * Get CompanyId
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->companyHelper->getCompanyId();
    }

    /**
     * Get Company Account Numbers
     *
     * @return array
     */
    public function getCompanyAccountNumbers()
    {
        $accountData = [];
        $companyId = $this->companyHelper->getCompanyId();
        $companyData = $this->companyHelper->getCustomerCompany($companyId);

        if ($companyData) {
            $printAccount = $companyData->getFedexAccountNumber() != null ? $companyData->getFedexAccountNumber() : $companyData->getDiscountAccountNumber();
            $editable = $companyData->getFedexAccountNumber() != null ? $companyData->getFxoAccountNumberEditable() : $companyData->getDiscountAccountNumberEditable();
            $fedexAccountNumber = trim((string) $printAccount);
            $shippingAccountNumber = trim((string) $companyData->getShippingAccountNumber());
            $maskedFedexAccNumber = substr($fedexAccountNumber, -4);
            $maskedShipAccNumber = substr($shippingAccountNumber, -4);
            $accountData = [
                ['label' => 'FedEx Account ' . $maskedFedexAccNumber, 'account_number' => $fedexAccountNumber, 'type' => 'Print', 'editable' => (int) $editable, 'maskednumber' => '*' . $maskedFedexAccNumber],
                ['label' => 'FedEx Account ' . $maskedShipAccNumber, 'account_number' => $shippingAccountNumber, 'type' => 'Ship', 'editable' => (int) $companyData->getShippingAccountNumberEditable(), 'maskednumber' => '*' . $maskedShipAccNumber]
            ];
            if ($fedexAccountNumber == '') {
                unset($accountData[0]);
            }
            if ($shippingAccountNumber == '') {
                unset($accountData[1]);
            }
        }

        return $accountData;
    }

    /**
     *
     * Get Is Non Editable Company Cc Payment Method
     *
     * @return boolean
     */
    public function getIsNonEditablePaymentMethod()
    {
        return $this->companyHelper->getNonEditableCompanyCcPaymentMethod();
    }

    /**
     * Make html for add and update credit card
     *
     * @return string|bool
     */
    public function makeCreditCardViewHtml()
    {
        $cardInfo = $this->getCompanyCcData();
        $companyName = '';

        if (!empty($cardInfo["data"])) {
            if (isset($cardInfo["data"]['ccCompanyName'])) {
                $companyName = $cardInfo["data"]['ccCompanyName'] . ' ';
            }
            $countryName = '';
            if ($cardInfo["data"]['country'] == 'US') {
                $countryName = "United States of America";
            } else {
                $countryName = $cardInfo["data"]['country'];
            }
            $stateTitle = '';
            foreach ($this->enhancedProfile->getRegionsOfCountry('us') as $state) {
                if ($cardInfo["data"]["state"] == $state['label']) {
                    $stateTitle = $state['title'];
                }
            }
            $cardIcon = str_replace(' ', '_', strtolower($cardInfo["data"]["ccType"])) . '.png';
            $iconUrl = $this->enhancedProfile->getMediaUrl() . 'wysiwyg/images/' . $cardIcon;
            $tokenExpDate =  $cardInfo["data"]["ccExpiryMonth"] . '/' . substr($cardInfo["data"]["ccExpiryYear"], -2);
            $isTokenExpiry = $this->enhancedProfile->getTokenIsExpired($cardInfo["data"]["tokenExpirationDate"]);
            $primary = '';
            $expires = '';
            if (!$isTokenExpiry) {
                $primary = '<div class="cart-status-default-content">
                            <div class="cart-status-default">
                                <span class="default">' . __('Default') . '</span>
                            </div>
                        </div>';
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
                                <img src="' . $iconUrl . '" alt="' . $cardInfo["data"]["ccType"] . '"/>
                            </div>
                            <div class="right">
                                <div class="card-type">
                                    <span>' . $cardInfo["data"]["ccType"] . '</span>
                                </div>
                                <div class="card-number">
                                    <span>' . __('ending in ') . '*' . substr($cardInfo["data"]["ccNumber"], -4) . '
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="head-mid">' . $expires . '</div>
                        <div class="head-right">' . $primary . '</div>
                    </div>
                    <div class="credit-card-body">
                        <div class="credit-card-name">
                            <div class="name-content">
                                <div class="name-title">
                                    <span>' . __('Name on card') . '</span>
                                </div>
                                <div class="name">
                                    <span>' . $cardInfo["data"]["nameOnCard"] . '</span>
                                </div>
                            </div>
                            <div class="action">
                                <div class="action-edit" tabindex="0">
                                    <span data-primary="true" class="edit">' .__('Edit'). '</span>
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
            $cardInfo["data"]["nameOnCard"]
            . '</div>
                                    <span>' .
            implode(" ", [$cardInfo["data"]["addressLine1"], $cardInfo["data"]["addressLine2"]]) . ', ' .
            $companyName .
            $cardInfo["data"]["city"] . ' ' .
            $stateTitle . ' ' .
            $cardInfo["data"]["zipCode"] . ' ' .
            $countryName
            . '</span>
                                </div>
                                <div class="mobile-edit" tabindex="0">
                                    <span>' . __('Edit') . '</span>
                                </div>
                            </div>
                            <div class="action">
                                <span class="remove remove-shared-credit-card" '. self::TAB_INDEX .'>' .
            __('Remove')
                . '</span>
                            </div>
                        </div>
                    </div>
                </div>';
        } else {

            return false;
        }
    }

    /**
     * siteLevel html for add and update credit card
     *
     * @return string|bool
     */
    public function siteLevelCreditCardViewHtml()
    {
        $cardInfo = $this->getCompanyCcData();
        $companyName = '';

        if (!empty($cardInfo["data"])) {
            if (isset($cardInfo["data"]['ccCompanyName'])) {
                $companyName = $cardInfo["data"]['ccCompanyName'] . ' ';
            }
            $countryName = '';
            if ($cardInfo["data"]['country'] == 'US') {
                $countryName = "United States of America";
            } else {
                $countryName = $cardInfo["data"]['country'];
            }
            $stateTitle = '';
            foreach ($this->enhancedProfile->getRegionsOfCountry('us') as $state) {
                if ($cardInfo["data"]["state"] == $state['label']) {
                    $stateTitle = $state['title'];
                }
            }
            $cardIcon = str_replace(' ', '_', strtolower($cardInfo["data"]["ccType"])) . '.png';
            $iconUrl = $this->assetRepo->getUrl('Fedex_EnhancedProfile::images/'.$cardIcon);
            $tokenExpDate =  $cardInfo["data"]["ccExpiryMonth"] . '/' . substr($cardInfo["data"]["ccExpiryYear"], -2);
            $isTokenExpiry = $this->enhancedProfile->getTokenIsExpired($cardInfo["data"]["tokenExpirationDate"]);
            $primary = '';
            $expires = '';
            $expires = '<div class="card-expires"><span>' .__('Expires ') . $tokenExpDate . '</span></div>';

            return '<div class="credit-cart-content">
                    <div class="credit-card-head">
                        <div class="head-left">
                            <div class="left">
                                <img src="' . $iconUrl . '" alt="' . $cardInfo["data"]["ccType"] . '"/>
                            </div>
                            <div class="right">
                                <div class="card-type">
                                    <span>' . $cardInfo["data"]["ccType"] . '</span>
                                </div>
                                <div class="card-number">
                                    <span>' . __('ending in ') . '*' . substr($cardInfo["data"]["ccNumber"], -4) . '
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="head-mid">' . $expires . '</div>
                    </div>
                    <div class="credit-card-body">
                        <div class="credit-card-name">
                            <div class="name-content">
                                <div class="name-title">
                                    <span>' . __('Name on card') . '</span>
                                </div>
                                <div class="name">
                                    <span>' . $cardInfo["data"]["nameOnCard"] . '</span>
                                </div>
                            </div>
                            <div class="action">
                                <div class="action-edit" tabindex="0">
                                    <span data-primary="true" class="edit credit-card-edit">' .__('Edit'). '</span>
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
                $cardInfo["data"]["nameOnCard"]
                . '</div>
                                    <span>' .
                implode(" ", [$cardInfo["data"]["addressLine1"], $cardInfo["data"]["addressLine2"]]) . ', ' .
                $companyName .
                $cardInfo["data"]["city"] . ' ' .
                $stateTitle . ' ' .
                $cardInfo["data"]["zipCode"] . ' ' .
                $countryName
                . '</span>
                                </div>
                                <div class="mobile-edit" tabindex="0">
                                    <span>' . __('Edit') . '</span>
                                </div>
                            </div>
                            <div class="action">
                                <span class="remove-site-level-click remove remove-site-level-credit-card" '. self::TAB_INDEX .'>' .
                __('Remove')
                . '</span>
                            </div>
                        </div>
                    </div>
                </div>';
        } else {

            return false;
        }
    }

    /**
     * Get Fedex Print And Ship Account Numbers
     *
     * @return array
     */
    public function getFedexPrintShipAccounts()
    {
        return $this->companyHelper->getFedexPrintShipAccounts();
    }
}
