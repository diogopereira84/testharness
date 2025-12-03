<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Directory\Model\Country;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * CIDPSG AdminConfigHelper class
 */
class AdminConfigHelper extends AbstractHelper
{

    public const XML_PATH_ACCOUNT_TERM_CONDITION = 'fedex/cid_psg_configuration_group/account_request_terms_condition';
    public const XML_PATH_CONFIRMATION_POPUP = 'fedex/cid_psg_configuration_group/confirmation_popup';
    public const XML_PATH_PEGA_ACCOUNT_CREATE_API = 'fedex/cid_psg_configuration_group/pega_account_create_api_url';
    public const XML_PATH_ENABLE_LOG = 'fedex/cid_psg_configuration_group/enable_log';
    public const XML_PATH_PEGA_RETRY_COUNT = 'fedex/cid_psg_configuration_group/pega_retry_count';
    public const XML_PATH_SUPPORT_TEAM_EMAIL = 'fedex/cid_psg_configuration_group/support_team_email';
    public const XML_PATH_FROM_EMAIL = 'fedex/cid_psg_configuration_group/from_email';
    public const XML_PATH_AUTHORIZED_USER_EMAIL = 'fedex/cid_psg_configuration_group/authorized_user_email';
    public const XML_PATH_PA_AGREEMENT_USER_EMAIL = 'fedex/cid_psg_configuration_group/pa_agreement_email';
    public const XML_PATH_AUTHORIZED_EMAIL_TEMPLATE =
        'fedex/cid_psg_configuration_group/authorized_user_email_template';
    public const XML_PATH_PA_AGREEMENT_EMAIL_TEMPLATE =
        'fedex/cid_psg_configuration_group/pa_acceptance_email_template';
    public const XML_PATH_AUTHORIZED_USER_POPUP = 'fedex/cid_psg_configuration_group/authorized_user_popup';

    public $canadaStates;
    public $usStates;
    public $combinedStates;
    public $formData;

    /**
     * AdminConfigHelper Constructor
     *
     * @param Context $context
     * @param Country $country
     * @param ScopeConfigInterface $scopeConfig
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        protected Country $country,
        protected DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    /**
     * Get states by country code
     *
     * @param string $countryCode
     * @return array
     */
    public function getAllStates($countryCode)
    {
        if ($countryCode == "US") {
            $states = $this->getRegionsOfCountry($countryCode);
        } elseif ($countryCode == "CA") {
            $states = $this->getCanadaStates();
        } elseif ($countryCode == "BOTH") {
            $states = $this->getBothStates();
        } else {
            $states = [];
        }

        return $states;
    }

    /**
     * Get the list of regions present in the given Country
     *
     * @param string $countryCode
     * @return array|void
     */
    public function getRegionsOfCountry($countryCode)
    {
        $regionCollection = $this->country->loadByCode($countryCode)->getRegions();
        return $regionCollection->loadData()->toOptionArray(false);
    }

    /**
     * Get the list of states for Canada
     *
     * @return array
     */
    public function getCanadaStates()
    {
        return [
            ['label' => 'AB', 'title' => 'Alberta'],
            ['label' => 'BC', 'title' => 'British Columbia'],
            ['label' => 'MB', 'title' => 'Manitoba'],
            ['label' => 'NB', 'title' => 'New Brunswick'],
            ['label' => 'NL', 'title' => 'Newfoundland'],
            ['label' => 'NU', 'title' => 'Northwest Territories / Nunavut'],
            ['label' => 'NS', 'title' => 'Nova Scotia'],
            ['label' => 'ON', 'title' => 'Ontario'],
            ['label' => 'PE', 'title' => 'Prince Edward Island'],
            ['label' => 'QC', 'title' => 'Quebec'],
            ['label' => 'SK', 'title' => 'Saskatchewan'],
            ['label' => 'YT', 'title' => 'Yukon Territories']
        ];
    }

    /**
     * Get the list of states for Canada
     *
     * @return array
     */
    public function getBothStates()
    {
        return array_merge($this->getRegionsOfCountry('US'), $this->getCanadaStates());
    }

    /**
     * To get account request form terms and condition text.
     *
     * @return string
     */
    public function getAccountTermConditionText()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_ACCOUNT_TERM_CONDITION,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get Confirmation Popup success message.
     *
     * @return string
     */
    public function getConfirmationPopupMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CONFIRMATION_POPUP,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To check if logging enable for PEGA API request response.
     *
     * @return bool
     */
    public function isLogEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_LOG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Rate API URL
     *
     * @return string
     */
    public function getPegaAccountCreateApiUrl()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PEGA_ACCOUNT_CREATE_API,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Retry count for PEGA API attempts
     *
     * @return int
     */
    public function getPegaRetryCount()
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_PEGA_RETRY_COUNT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Support Email for PEGA API failure notification
     *
     * @return string
     */
    public function getPegaApiSupportEmail()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_SUPPORT_TEAM_EMAIL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get From Email address for sending mails
     *
     * @return string
     */
    public function getFromEmail()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_FROM_EMAIL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Authorized User Email
     *
     * @return string
     */
    public function getAuthorizedUserEmail()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_AUTHORIZED_USER_EMAIL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Authorized Email Template
     *
     * @return string
     */
    public function getAuthorizedEmailTemplate()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_AUTHORIZED_EMAIL_TEMPLATE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Participate Agreement User Email
     *
     * @return string
     */
    public function getPaAgreementUserEmail()
    {
        return (string) $this->scopeConfig->getValue(
            static::XML_PATH_PA_AGREEMENT_USER_EMAIL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get PA Agreement Email Template
     *
     * @return string
     */
    public function getPaAgreementEmailTemplate()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PA_AGREEMENT_EMAIL_TEMPLATE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To Persist data in key value format
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setValue($key, $value)
    {
        $this->dataPersistor->set($key, $value);
    }

    /**
     * To get value by key
     *
     * @param string $key
     * @return mixed
     */
    public function getValue($key)
    {
        return $this->dataPersistor->get($key);
    }

    /**
     * To clear value by key
     *
     * @param string $key
     * @return void
     */
    public function clearValue($key)
    {
        $this->dataPersistor->clear($key);
    }

    /**
     * To get Authorized Popup message.
     *
     * @return string
     */
    public function getAuthorizedUserPopupMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_AUTHORIZED_USER_POPUP,
            ScopeInterface::SCOPE_STORE
        );
    }
}
