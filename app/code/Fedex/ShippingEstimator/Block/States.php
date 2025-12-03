<?php
declare(strict_types=1);

namespace Fedex\ShippingEstimator\Block;

use \Magento\Framework\View\Element\Template\Context;
use \Magento\Directory\Api\CountryInformationAcquirerInterface;
use \Fedex\ShippingEstimator\Model\Config\ShippingEstimatorConfig;

/**
 * Class provides states with two letter codes
 * @package Fedex\ShippingEstimator\Block
 */
class States extends \Magento\Framework\View\Element\Template
{

    /**
     * States constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected CountryInformationAcquirerInterface $countryInformationAcquirer,
        protected ShippingEstimatorConfig $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get the list of regions present in the given Country
     * Returns empty array if no regions available for Country
     * @param $countryCode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRegionsOfCountry($countryCode) {

        $regions = [];
        $country = $this->countryInformationAcquirer->getCountryInfo($countryCode);
        $excludedStates = $this->config->getExcludedStates();
        if ($availableRegions = $country->getAvailableRegions()) {
            foreach ($availableRegions as $region) {
                if (!in_array($region->getId(), $excludedStates)) {
                    $regions[] = [
                        'id' => $region->getId(),
                        'code' => $region->getCode(),
                        'name' => $region->getName()
                    ];
                }
            }
        }

        return $regions;
    }

}
