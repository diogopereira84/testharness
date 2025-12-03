<?php

namespace Fedex\CustomerAddresses\Model\Config\Region;

class RegionInformationProvider
{
    /**
     * @var $addressRepository
     */
    protected $addressRepository;

    /**
     * Region construct
     *
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirer
     */
    public function __construct(
        protected \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirer
    )
    {
    }

    /**
     * Get region for country
     *
     * @return array
     */
    public function toOptionArray()
    {
        $countries = $this->countryInformationAcquirer->getCountriesInfo();
        foreach ($countries as $country) {
            // Get regions for this country:
            $regions = [];
            if ($availableRegions = $country->getAvailableRegions()) {
                foreach ($availableRegions as $region) {
                    $regions[] = [
                        'value' => $region->getName(),
                        'label' => $region->getName()
                    ];
                }
            }
        }
        return $regions;
    }
}
