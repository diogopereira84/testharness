<?php
declare(strict_types=1);
namespace Fedex\ShippingEstimator\Model\Config\Region;

use Magento\Directory\Api\CountryInformationAcquirerInterface;

class RegionProvider
{
    /**
     * RegionProvider constructor.
     * @param CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param string $countryId
     */
    public function __construct(
        protected CountryInformationAcquirerInterface $countryInformationAcquirer,
        private string $countryId = 'US'
    )
    {
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $countries = $this->countryInformationAcquirer->getCountriesInfo();
        foreach ($countries as $country) {
            if ($country->getId() == $this->countryId) {
                $regions = [];
                if ($availableRegions = $country->getAvailableRegions()) {
                    foreach ($availableRegions as $region) {
                        $regions[] = [
                            'value' => $region->getId(),
                            'label' => $region->getName()
                        ];
                    }
                }
            }
        }
        return $regions;
    }
}
