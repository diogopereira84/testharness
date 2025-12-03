<?php

/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 * @copyright 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\Delivery\Model;

use Fedex\Delivery\Model\Shipping\CheapestFastestSelector;
use Fedex\Delivery\Model\Shipping\ShippingMethodFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Api\Data\ShippingMethodExtensionFactory;
use Psr\Log\LoggerInterface;

class GetCheapestFastestShippingMethod
{
    const TOGGLE_D219322 = 'tiger_d219322';

    /** @var array */
    private array $cheapestFastestControl = [];

    /** @var array  */
    private array $shippingMethods;

    /**
     * Constructor
     *
     * @param ShippingMethodExtensionFactory $extensionFactory
     * @param LoggerInterface $logger
     * @param CheapestFastestSelector $selector
     * @param ShippingMethodFactory $shippingMethodFactory
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly ShippingMethodExtensionFactory $extensionFactory,
        private readonly LoggerInterface $logger,
        private readonly CheapestFastestSelector $selector,
        private readonly ShippingMethodFactory $shippingMethodFactory,
        private readonly ToggleConfig $toggleConfig
    ) {
    }

    /**
     * @param array $shippingMethods
     * @return array
     * @throws LocalizedException
     */
    public function execute(array $shippingMethods): array
    {
        if ($this->toggleConfig->getToggleConfigValue(self::TOGGLE_D219322)) {

            $normalizedMethods = $this->shippingMethodFactory->createFromArray($shippingMethods);
            $updatedMethods = $this->selector->applyCheapestAndFastest($normalizedMethods);

            return $this->shippingMethodFactory->convertToArray($shippingMethods, $updatedMethods);
        } else {

            $this->shippingMethods = $shippingMethods;
            $this->setCheapestFastestShippingInArrayControl();
            $this->setAllCheapestAndFastestValues();

            return $this->shippingMethods;
        }
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    private function setCheapestFastestShippingInArrayControl(): void
    {
        foreach ($this->shippingMethods as $key => &$shippingMethod) {
            if (empty($shippingMethod) || $shippingMethod === null) {
                continue;
            }

            /** Check if the shipping is Magento default, else is Marketplace */
            if ($shippingMethod instanceof ShippingMethodInterface) {
                $extensionAttributes = $shippingMethod->getExtensionAttributes() ?? $this->extensionFactory->create();
                $identityGroup = (string) $shippingMethod->getCarrierCode();
                $amount = (float) $shippingMethod->getAmount();
                $deliveryDate = strtotime($this->replaceEndOfDayString($shippingMethod->getMethodTitle()));

                /** set default values to extended attributes */
                $extensionAttributes->setCheapest(false);
                $extensionAttributes->setFastest(false);
                $shippingMethod->setExtensionAttributes($extensionAttributes);
            } elseif (is_array($shippingMethod)) {
                /** marketplace shipping methods that came as Array */
                $identityGroup = (string) ($shippingMethod['seller_id']
                    ?? $shippingMethod['carrier_code']
                    ?? 'marketplace');
                $amount = (float) ($shippingMethod['amount'] ?? 0);
                $deliveryDate = strtotime($this->replaceEndOfDayString($shippingMethod['deliveryDate'] ?? ''));

                /** set default values and mimic extended attributes */
                $shippingMethod['extension_attributes'] = [
                    'fastest' => false,
                    'cheapest' => false
                ];
            } else {
                continue;
            }

            if (!is_int($deliveryDate)) {
                $this->logger->info(__('DeliveryDate provided is not valid. (Key Code: %1)', $identityGroup));
                continue;
            }

            if (!isset($this->cheapestFastestControl[$identityGroup])) {
                $this->initializeShippingMethodInArray($identityGroup, $amount, $deliveryDate, $key);
                continue;
            }

            $this->checkAndSetCheapestInArray($identityGroup, $amount, $key);
            $this->checkAndSetFastestInArray($identityGroup, $deliveryDate, $key);
        }
    }

    /**
     * @return void
     */
    private function setAllCheapestAndFastestValues(): void
    {
        foreach ($this->cheapestFastestControl as $controlArray) {
            $cheapestKey = $controlArray['cheapest_key'];
            $fastestKey = $controlArray['fastest_key'];

            if ($this->shippingMethods[$cheapestKey] instanceof ShippingMethodInterface) {
                $extensionAttributes = $this->shippingMethods[$cheapestKey]->getExtensionAttributes()
                    ?? $this->extensionFactory->create();
                $extensionAttributes->setCheapest(true);
            } else {
                $this->shippingMethods[$cheapestKey]['extension_attributes']['cheapest'] = true;
            }

            if ($this->shippingMethods[$fastestKey] instanceof ShippingMethodInterface) {
                $extensionAttributes = $this->shippingMethods[$fastestKey]->getExtensionAttributes()
                    ?? $this->extensionFactory->create();
                $extensionAttributes->setFastest(true);
            } else {
                $this->shippingMethods[$fastestKey]['extension_attributes']['fastest'] = true;
            }
        }
    }

    /**
     * @param string $identityGroup
     * @param float $amount
     * @param int $deliveryDate
     * @param int|string $key
     * @return void
     */
    private function initializeShippingMethodInArray(
        string $identityGroup,
        float $amount,
        int $deliveryDate,
        int|string $key
    ): void {
        $this->cheapestFastestControl[$identityGroup]['cheapest_key'] = $key;
        $this->cheapestFastestControl[$identityGroup]['fastest_key'] = $key;
        $this->cheapestFastestControl[$identityGroup]['amount'] = $amount;
        $this->cheapestFastestControl[$identityGroup]['deliveryDate'] = $deliveryDate;
    }

    /**
     * @param string $identityGroup
     * @param float $amount
     * @param int|string $key
     * @return void
     */
    private function checkAndSetCheapestInArray(string $identityGroup, float $amount, int|string $key): void
    {
        /** check if is cheapest */
        if ($this->cheapestFastestControl[$identityGroup]['amount'] > $amount) {
            $this->cheapestFastestControl[$identityGroup]['cheapest_key'] = $key;
            $this->cheapestFastestControl[$identityGroup]['amount'] = $amount;
        }
    }

    /**
     * @param string $identityGroup
     * @param int $deliveryDate
     * @param int|string $key
     * @return void
     */
    private function checkAndSetFastestInArray(string $identityGroup, int $deliveryDate, int|string $key): void
    {
        /** check if is fastest */
        if ($this->cheapestFastestControl[$identityGroup]['deliveryDate'] > $deliveryDate) {
            $this->cheapestFastestControl[$identityGroup]['fastest_key'] = $key;
            $this->cheapestFastestControl[$identityGroup]['deliveryDate'] = $deliveryDate;
        }
    }

    /**
     * @param string $dateTime
     * @return array|string
     */
    private function replaceEndOfDayString(string $dateTime): array|string
    {
        if(str_contains($dateTime, 'End of Day')) {
            $dateTime = str_replace('End of Day', '11:59pm', $dateTime);
        }

        return $dateTime;
    }
}
