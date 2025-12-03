<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Service\Address;

use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Model\RegionFactory;

class MiraklShippingAddressFormatter
{
    /**
     * @param RegionFactory $regionFactory
     * @param CountryInformationAcquirerInterface $countryInfo
     */
    public function __construct(
        private RegionFactory $regionFactory,
        private CountryInformationAcquirerInterface $countryInfo
    ) {}

    /**
     * @param array $data
     * @return string
     */
    public function format(array $data): string
    {
        $lines = [];

        $fullName = trim(($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? ''));
        if ($fullName) {
            $lines[] = $fullName;
        }

        if (!empty($data['company'])) {
            $lines[] = $data['company'];
        }

        if (!empty($data['street']) && is_array($data['street'])) {
            foreach ($data['street'] as $line) {
                if (trim($line)) {
                    $lines[] = $line;
                }
            }
        }

        $lines[] = $this->getRegionLine($data);

        $countryName = $this->getCountryName($data['countryId'] ?? '');
        if ($countryName) {
            $lines[] = $countryName;
        }

        if (!empty($data['telephone'])) {
            $lines[] = 'T: <a href="tel:' . $data['telephone'] . '">' . $data['telephone'] . '</a>';
        }

        return implode('<br />', $lines);
    }

    /**
     * @param array $data
     * @return string
     */
    private function getRegionLine(array $data): string
    {
        $city = $data['city'] ?? '';
        $regionName = $data['regionCode'] ?? '';

        if (!empty($data['regionId'])) {
            $region = $this->regionFactory->create()->load((int)$data['regionId']);
            if ($region->getId()) {
                $regionName = $region->getName();
            }
        }

        $postcode = $data['postcode'] ?? '';
        return implode(', ', array_filter([$city, $regionName, $postcode]));
    }

    /**
     * @param string $countryCode
     * @return string
     */
    private function getCountryName(string $countryCode): string
    {
        try {
            $country = $this->countryInfo->getCountryInfo($countryCode);
            return $country->getFullNameLocale();
        } catch (\Exception) {
            return $countryCode;
        }
    }
}
