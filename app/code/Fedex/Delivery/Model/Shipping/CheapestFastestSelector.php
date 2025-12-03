<?php

declare(strict_types=1);

namespace Fedex\Delivery\Model\Shipping;

use Psr\Log\LoggerInterface;

class CheapestFastestSelector
{
    private array $control = [];

    public function __construct(private readonly LoggerInterface $logger) {}

    /**
     * @param ShippingMethod[] $shippingMethods
     * @return ShippingMethod[]
     */
    public function applyCheapestAndFastest(array $shippingMethods): array
    {
        foreach ($shippingMethods as $key => $method) {
            $this->processMethod($method, $key);
        }

        $this->markCheapestAndFastest($shippingMethods);

        return $shippingMethods;
    }

    private function processMethod(ShippingMethod $method, int|string $key): void
    {
        $group = $method->getIdentityGroup();
        $amount = $method->getAmount();
        $deliveryDate = $method->getDeliveryDate();

        if ($deliveryDate === null) {
            $this->logger->info(__('Invalid delivery date for group: %1', $group));
            return;
        }

        if (!isset($this->control[$group])) {
            $this->initializeGroup($group, $amount, $deliveryDate, $key);
        } else {
            $this->updateCheapest($group, $amount, $key, $deliveryDate);
            $this->updateFastest($group, $deliveryDate, $key);
        }
    }

    private function initializeGroup(string $group, float $amount, int $deliveryDate, int|string $key): void
    {
        $this->control[$group] = [
            'cheapest_key' => $key,
            'fastest_key' => $key,
            'amount' => $amount,
            'deliveryDate' => $deliveryDate,
        ];
    }

    private function updateCheapest(string $group, float $amount, int|string $key, int $deliveryDate): void
    {
        if ($this->control[$group]['amount'] > $amount) {
            $this->control[$group]['cheapest_key'] = $key;
            $this->control[$group]['amount'] = $amount;
        } elseif ($this->control[$group]['amount'] === $amount &&
            $this->control[$group]['deliveryDate'] > $deliveryDate) {
            $this->control[$group]['cheapest_key'] = $key;
        }
    }

    private function updateFastest(string $group, int $deliveryDate, int|string $key): void
    {
        if ($this->control[$group]['deliveryDate'] > $deliveryDate) {
            $this->control[$group]['fastest_key'] = $key;
            $this->control[$group]['deliveryDate'] = $deliveryDate;
        }
    }

    private function markCheapestAndFastest(array &$shippingMethods): void
    {
        foreach ($this->control as $group => $data) {
            $shippingMethods[$data['cheapest_key']]->setCheapest(true);
            $shippingMethods[$data['fastest_key']]->setFastest(true);
        }
    }
}
