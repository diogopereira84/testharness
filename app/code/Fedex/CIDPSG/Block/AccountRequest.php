<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Block;

use Magento\Framework\View\Element\Template\Context;
use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Request\Http;

/**
 * AccountRequest Block class
 */
class AccountRequest extends Template
{
    
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param AdminConfigHelper $adminConfigHelper
     * @param Http $request
     */
    public function __construct(
        Context $context,
        protected AdminConfigHelper $adminConfigHelper,
        protected Http $request
    ) {
        parent::__construct($context);
    }

    /**
     * Get the list of regions present in the given Country
     *
     * @param string $countryCode
     * @return array
     */
    public function getAllStates($countryCode)
    {
        return $this->adminConfigHelper->getAllStates($countryCode);
    }

    /**
     * Get the list of states for US and Canada both
     *
     * @return array
     */
    public function getBothStates()
    {
        return $this->adminConfigHelper->getBothStates();
    }

    /**
     * Get the current url params
     *
     * @return array
     */
    public function getUrlParams()
    {
        return $this->request->getParams();
    }

    /**
     * To get account request form terms and condition text.
     *
     * @return string
     */
    public function getAccountTermConditionText()
    {
        return $this->adminConfigHelper->getAccountTermConditionText();
    }

    /**
     * To get account Nature Of Business Options.
     *
     * @return array
     */
    public function getNatureOfBusinessOptions()
    {
        return [
            ['label' => 'Aerospace', 'title'=>'Aerospace'],
            ['label' => 'Agriculture_Forestry', 'title'=>'Agriculture Forestry'],
            ['label' => 'Automotive', 'title'=>'Automotive'],
            ['label' => 'Business_Services_Consultant', 'title'=>'Business Services'],
            ['label' => 'Communication_Carriers_Transportation_Utilities', 'title'=>'Communication Utilities'],
            ['label' => 'Computer_Manufacturer', 'title'=>'Computer Manufacturer'],
            ['label' => 'Computer_Related_Retailer_Wholesaler_Distributor', 'title'=>'Computer Wholesaler'],
            ['label' => 'Computer_services_consulting', 'title'=>'Computer consulting'],
            ['label' => 'Computer_Technology_reseller', 'title'=>'Computer Reseller'],
            ['label' => 'Construction_Architecture_Engineering', 'title'=>'Construction Engineering'],
            ['label' => 'Data_Processing_Services', 'title'=>'Data Processing'],
            ['label' => 'Education', 'title'=>'Education'],
            ['label' => 'Electronics', 'title'=>'Electronics'],
            ['label' => 'Federal_Government', 'title'=>'Federal Government'],
            ['label' => 'Financial_Services', 'title'=>'Financial Services'],
            ['label' => 'Healthcare_Health_services', 'title'=>'Healthcare Services'],
            ['label' => 'Insurance', 'title'=>'Insurance'],
            ['label' => 'Internet_Access_Providers_ISP', 'title'=>'Internet Providers'],
            ['label' => 'Legal', 'title'=>'Legal'],
            ['label' => 'Manufacturing_consumer_goods', 'title'=>'Manufacturing Goods'],
            ['label' => 'Manufacturing_Industrial', 'title'=>'Manufacturing Industrial'],
            ['label' => 'Marketing_Advertising_Entertainment', 'title'=>'Marketing Entertainment'],
            ['label' => 'Oil_Gas_Mining_Other_natural_resources', 'title'=>'Oil Gas Mining'],
            ['label' => 'Publishing_Broadcast_Media', 'title'=>'Publishing Media'],
            ['label' => 'Real_Estate', 'title'=>'Real Estate'],
            ['label' => 'Research_Development_Lab', 'title'=>'Research Lab'],
            ['label' => 'Retail_Wholesale', 'title'=>'Retail Wholesale'],
            ['label' => 'Service_Provider', 'title'=>'Service Provider'],
            ['label' => 'Software_Technology_Developer', 'title'=>'Software Developer'],
            ['label' => 'State_Local_Government', 'title'=>'Local Government'],
            ['label' => 'Travel_Hospitality_Recreation_Entertainment', 'title'=>'Travel Hospitality'],
            ['label' => 'VAR_VAD_Systems_or_Network_Integrators', 'title'=>'Network Integrators'],
            ['label' => 'Web_Development_Production', 'title'=>'Web Development'],
            ['label' => 'Wholesale_Retail_Distribution', 'title'=>'Retail Distribution']
        ];
    }

    /**
     * To get account request form terms and condition text.
     *
     * @return string
     */
    public function getConfirmationPopupMessage()
    {
        return $this->adminConfigHelper->getConfirmationPopupMessage();
    }

    /**
     * Get Retry count for PEGA API attempts
     *
     * @return int
     */
    public function getPegaRetryCount()
    {
        return $this->adminConfigHelper->getPegaRetryCount();
    }

    /**
     * To get authorized user popup content
     *
     * @return string
     */
    public function getAuthorizedUserPopupMessage()
    {
        return $this->adminConfigHelper->getAuthorizedUserPopupMessage();
    }
}
