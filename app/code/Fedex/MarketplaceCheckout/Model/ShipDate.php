<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Api\ShipDateInterface;
use Fedex\MarketplaceCheckout\Helper\BuildDeliveryDate;
use Fedex\MarketplaceCheckout\Model\DTO\ShippingDateDTO;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ShipDate implements ShipDateInterface
{
    /**
     * @param BuildDeliveryDate $buildDeliveryDate
     */
    public function __construct(
        private BuildDeliveryDate       $buildDeliveryDate,
        private TimezoneInterface              $timezone
    ) {
    }

    /**
     * Calculates the shipping date based on the provided parameters.
     *
     * @param ShippingDateDTO $data
     * @return int|false
     */
    public function getShipDate(ShippingDateDTO $data): int|false
    {
        return $this->buildDeliveryDate->getAllowedDeliveryDate(
            $data->currentDateTime,
            $data->businessDays,
            $data->shippingCutOffTime,
            $data->shippingSellerHolidays,
            $data->additionalProcessingDays,
            $data->timeZone
        );
    }

    /**
     * @param string $timeZone
     * @return string
     */
    public function getCurrentDateTime(string $timeZone): string
    {
        return $this->timezone->formatDateTime(
            $this->timezone->date(),
            null,
            null,
            null,
            $timeZone,
            'yyyy-MM-dd\'T\'HH:mm:ss'
        );
    }
}