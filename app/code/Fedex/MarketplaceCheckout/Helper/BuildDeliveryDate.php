<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Model\Constants\DateConstants;
use Fedex\MarketplaceCheckout\Model\Constants\ShippingConstants;
use Magento\Framework\App\Helper\Context;

class BuildDeliveryDate extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $holidaysList = [];

    private bool $isCutOffTimeElapsed = false;

    /**
     * @param Context $context
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        private ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Conditions to add more time.
     *
     * @param string $currentDateTime
     * @param int $businessDays
     * @param string $cutOffLimit
     * @param string $holidayDates
     * @param int $additionalProcessingDays
     * @param string $timezone
     * @return false|int
     */
    public function getAllowedDeliveryDate(string $currentDateTime, int $businessDays, string $cutOffLimit, string $holidayDates, int $additionalProcessingDays, string $timezone)
    {
        $this->setHolidayDates($holidayDates);

        $deliveryDateTime = $this->cutOffLimit($currentDateTime, $cutOffLimit, $additionalProcessingDays, $timezone);
        $deliveryDateTime = $this->addBusinessDays($deliveryDateTime, $businessDays);

        return strtotime($deliveryDateTime);
    }

    /**
     * Check if the date specified is a holiday
     *
     * @param string $date
     * @return bool
     */
    private function isHoliday(string $date): bool
    {
        $date = date('m/d/Y', strtotime($date));
        return in_array($date, $this->holidaysList);
    }

    /**
     * Add Cutoff limit to dates
     *
     * @param string $currentDateTime
     * @param string $cutOffLimit
     * @param int $additionalProcessingDays
     * @param string $timezone
     * @return string
     */
    public function cutOffLimit(string $currentDateTime, string $cutOffLimit, int $additionalProcessingDays, string $timezone)
    {
        if ($this->isHoliday($currentDateTime) || $this->isWeekend($currentDateTime)) {
            return date('Y-m-d G:i:s', strtotime($currentDateTime));
        }

        if ($this->getCurrentTime($currentDateTime, $timezone) > $this->getCutOffLimitTime($currentDateTime, $cutOffLimit, $timezone)) {
            $this->isCutOffTimeElapsed = true;
            $daysToAdd = 1;
            if ($additionalProcessingDays > 0) {
                $daysToAdd = $additionalProcessingDays;
            }
            $cutOffDate = date('Y-m-d G:i:s', strtotime($currentDateTime));
            for ($i = 1; $i <= $daysToAdd; $i++) {
                $cutOffDate = date('Y-m-d G:i:s', strtotime($cutOffDate . ' +1 day'));
                while ($this->isHoliday($cutOffDate) || $this->isWeekend($cutOffDate)) {
                    $cutOffDate = date('Y-m-d G:i:s', strtotime($cutOffDate . ' +1 day'));
                }
            }
            return $cutOffDate;
        }
        return $currentDateTime;
    }

    /**
     * Add business days.
     *
     * @param string $deliveryDateTime
     * @param int $businessDays
     * @return string
     */
    public function addBusinessDays(string $deliveryDateTime, int $businessDays): string
    {
        if (!$this->isCutOffTimeElapsed
            && !$this->isHoliday($deliveryDateTime) && !$this->isWeekend($deliveryDateTime)) {
            $businessDays -= 1;
        }

        $i = 0;
        while ($i < $businessDays) {
            $deliveryDateTime = date('Y-m-d G:i:s', strtotime($deliveryDateTime . ' +1 day'));
            if (!$this->isHoliday($deliveryDateTime) && !$this->isWeekend($deliveryDateTime)) {
                //Increment only if it is not a holiday or weekend, allowing the loop to continue incrementing additional days until it reaches a weekday
                $i++;
            }
        }

        return date("Y-m-d G:i:s", strtotime($deliveryDateTime));
    }

    /**
     * Check if the date specified is weekend
     *
     * @param string $date
     * @return bool
     */
    public function isWeekend(string $date): bool
    {
        try {
            $dateTime = new \DateTimeImmutable($date);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid date format: $date");
        }

        $dayOfWeek = (int) $dateTime->format('N');

        return in_array($dayOfWeek, DateConstants::WEEKEND_DAYS, true);
    }

    /**
     * Return next day in case of weekend
     *
     * @param string $date
     * @return string
     */
    public function getNextWeekday(string $date): string
    {
        $isWeekend = $this->isWeekend($date);

        while ($isWeekend) {
            $date = date('Y-m-d G:i:s', strtotime($date . ' +1 day'));
            $isWeekend = $this->isWeekend($date);
        }

        return $date;
    }

    /**
     * Get current time
     *
     * @param string $currentDateTime
     * @param string $timezone
     * @return int
     */
    public function getCurrentTime($currentDateTime, $timezone): int
    {
        $dateTime = new \DateTime($currentDateTime, new \DateTimeZone($timezone) );

        return $dateTime->getTimestamp();
    }

    /**
     * @param string $currentDateTime
     * @param string $cutOffLimit
     * @param string $timezone
     * @return int
     * @throws \Exception
     */
    public function getCutOffLimitTime(string $currentDateTime, string $cutOffLimit, string $timezone): int
    {
        $dateTime = new \DateTime($currentDateTime);
        $dateTime->setTimezone(new \DateTimeZone($timezone));
        $dateTime->modify('today ' . $cutOffLimit);

        return $dateTime->getTimestamp();
    }

    /**
     * Creates an array of holiday dates
     * @param string $holidays
     * @return void
     */
    private function setHolidayDates(string $holidays = ''): void
    {
        if (!empty($holidays)) {
            $holidayDates = explode(",", str_replace("/", "-", $holidays));
            foreach ($holidayDates as $holidayDate) {
                $this->holidaysList[] = date('m/d/Y', strtotime(date("Y") . '-'
                    . trim($holidayDate)));
            }
        }
    }

    /**
     * addProduction days in expected DeliveryTime
     *
     * @param string $deliveryDateTime
     * @param string $productionDays
     * @return string
     */
    public function addProductionDays(string $deliveryDateTime, string $productionDays): string
    {
        $deliveryDateTime = $this->getNextWeekday($deliveryDateTime);
        for ($i = 1; $i < (int)$productionDays; $i++) {
            while ($this->isHoliday($deliveryDateTime) || $this->isWeekend($deliveryDateTime)) {
                $deliveryDateTime = date('Y-m-d G:i:s', strtotime($deliveryDateTime . ' +1 day'));
            }
            $deliveryDateTime = date('Y-m-d G:i:s', strtotime($deliveryDateTime . ' +1 day'));
        }
        return date("Y-m-d G:i:s", strtotime($deliveryDateTime));
    }

    /**
     * @param string $label
     * @param string $deliveryDate
     * @return string
     */
    public function formatDeliveryDateWithEodTextIfGroundShipping(string $label, string $deliveryDate): string
    {
        if (stripos($label, ShippingConstants::FEDEX_GROUND_METHOD_LABEL_WITHOUT_COPYRIGHT) !== false) {
            $deliveryDate = date('l, F d', strtotime($deliveryDate));
            $deliveryDate = $deliveryDate . ', ' . DateConstants::EOD_TEXT;
        }
        return $deliveryDate;
    }
}

