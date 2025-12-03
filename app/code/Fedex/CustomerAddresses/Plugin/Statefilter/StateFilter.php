<?php

namespace Fedex\CustomerAddresses\Plugin\Statefilter;

class StateFilter
{
    /**
     * @var $allowedUsStates
     */
    protected $allowedUsStates;

    /**
     * StateFilter constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirer
     */
    public function __construct(
        protected \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        private \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirer
    )
    {
    }

    /**
     * Get allowed state
     *
     * @param object $subject
     * @param array $options
     *
     * @return array|boolean
     */
    public function afterToOptionArray($subject, $options)
    {
        $allowedStates = $this->scopeConfig
        ->getValue('checkout/state_filter/us_state_filter', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->allowedUsStates = explode(",", $allowedStates);

        $countries = $this->countryInformationAcquirer->getCountriesInfo();
        foreach ($countries as $country) {
            // Get regions for this country:
            $arrStrAllState = [];
            if ($availableRegions = $country->getAvailableRegions()) {
                foreach ($availableRegions as $region) {
                    $arrStrAllState[$region->getName()] = [
                        'code' => $region->getCode()
                    ];
                }
            }
        }
        $arrResultStates = array_filter($options, function ($option) {
            if (isset($option['label'])) {
                return in_array($option['label'], $this->allowedUsStates);
            }
        });

        $arrStatesOptions = $arrResultStates ?? $options;
        foreach ($arrStatesOptions as $resultState) {
            $stateCode = $arrStrAllState[$resultState['label']]['code'];
            $arrStateOptions[] =[
                                'value'=> $resultState['value'],
                                'title'=> $resultState['title'],
                                'country_id'=> $resultState['country_id'],
                                'label'=> $stateCode,
                        ];
        }
        return $arrStateOptions;
    }
}
