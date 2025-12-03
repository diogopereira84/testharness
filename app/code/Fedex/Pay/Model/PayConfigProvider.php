<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Pay\Model;

use Magento\Store\Model\ScopeInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Psr\Log\LoggerInterface;

class PayConfigProvider implements ConfigProviderInterface
{
    const CHECKOUT_US_STATES = "checkout/state_filter/us_state_filter";

    /**
     * @var $methodCode
     */
    protected $methodCode = 'pay';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected CountryInformationAcquirerInterface $countryInformationAcquirer,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Getting State list to show in checkout page in Billing address for pickup
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'pay' => [
                    'usstates' => [$this->methodCode => $this->getSelectedStates()]
                ]
            ]
        ];
    }

    /**
     * Get states array with state code
     *
     * @return array
     */
    public function getSelectedStates()
    {
        try {
            $allowedStates = $this->scopeConfig->getValue(self::CHECKOUT_US_STATES, ScopeInterface::SCOPE_STORE);

            if ($allowedStates) {
                $arrStrAllState = [];
                $stateValue = [];
                $statesArray = explode(",", $allowedStates);
                $countries = $this->countryInformationAcquirer->getCountriesInfo();
                $arrStrAllState = $this->getAllState($countries);
                if (!empty($statesArray) && !empty($arrStrAllState)) {
                    foreach ($statesArray as $states) {
                        $stateValue[] = ['value' => $arrStrAllState[$states], 'label' => $arrStrAllState[$states]];
                    }
                }

                return $stateValue;
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.' Error in getting state values: '.$e->getMessage());
        }

        return [];
    }

    /**
     * Get states array with state code
     *
     * @return array
     */
    protected function getAllState($countries)
    {
        if (!empty($countries)) {
            foreach ($countries as $country) {
                foreach ($country->getAvailableRegions() as $region) {
                    $arrStrAllState[$region->getName()] = $region->getCode();
                }
            }
        }

        return $arrStrAllState;
    }
}
