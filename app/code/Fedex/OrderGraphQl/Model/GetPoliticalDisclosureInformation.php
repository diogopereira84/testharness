<?php

/**
 * @category    Fedex
 * @package     Fedex_OrderGraphQl
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Nitin Pawar <npawar.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Model;

use Magento\Sales\Model\Order;
use Fedex\PoliticalDisclosure\Api\OrderDisclosureRepositoryInterface;
use Fedex\OrderGraphQl\Api\GetPoliticalDisclosureInformationInterface;
use Fedex\CartGraphQl\Model\Region\RegionData;

class GetPoliticalDisclosureInformation implements GetPoliticalDisclosureInformationInterface
{

    /**
     * constant for address classification home
     */
    private const HOME = "HOME";

    /**
     * constant for address classification business
     */
    private const BUSINESS = "BUSINESS";

    /**
     * Constructor
     *
     * @param OrderDisclosureRepositoryInterface $orderDisclosureRepository
     * @param RegionData $regionData
     */
    public function __construct(
        private readonly OrderDisclosureRepositoryInterface $orderDisclosureRepository,
        private readonly RegionData $regionData
    ) {
    }

    /**
     * Get political disclosure information for an order search request query.
     *
     * @param Order $order
     * @return array|null
     */
    public function getPoliticalDisclosureInfo(Order $order): ?array
    {
        $politicalDisclosure = $this->orderDisclosureRepository->getByOrderId((int)$order->getId());
        if (!$politicalDisclosure) {
            return null;
        }
        $shippingAddress = $order->getShippingAddress();
        $addressClassification = self::HOME;
        $company = $shippingAddress->getData('company');
        if ($company != null && $company != "") {
            $addressClassification = self::BUSINESS;
        }
        $stateCode = $this->regionData->getRegionById($politicalDisclosure->getRegionId());
        return [
            'applicable' => (bool)$politicalDisclosure->getDisclosureStatus(),
            'description' => $politicalDisclosure->getDescription(),
            'eventDate' => $politicalDisclosure->getElectionDate(),
            'sponsor' => $politicalDisclosure->getSponsor(),
            'customer' => [
                'emailAddress' => $politicalDisclosure->getEmail(),
                'address' => [
                    'streetLines' => explode("\n", $politicalDisclosure->getAddressStreetLines()),
                    'city' => $politicalDisclosure->getCity(),
                    'stateOrProvinceCode' => $stateCode,
                    'postalCode' => $politicalDisclosure->getZipCode(),
                    'countryCode' => $shippingAddress->getCountryId(),
                    'addressClassification' => $addressClassification
                ]
            ]
        ];
    }
}
