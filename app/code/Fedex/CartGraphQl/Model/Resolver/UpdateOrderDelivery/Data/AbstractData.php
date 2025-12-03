<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Api\Data\DeliveryDataHandlerInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Directory\Model\Region;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

abstract class AbstractData implements DeliveryDataHandlerInterface
{
    /**
     * @param Region $region
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param DateTime $dateTime
     * @param CartRepositoryInterface $cartRepository
     * @param InstoreConfig $instoreConfig
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        protected Region $region,
        protected CartIntegrationRepositoryInterface $cartIntegrationRepository,
        protected DateTime $dateTime,
        protected CartRepositoryInterface $cartRepository,
        protected InstoreConfig $instoreConfig,
        protected JsonSerializer $jsonSerializer
    ) {
    }

    /**
     * @param Quote $cart
     * @param array $data
     * @return void
     */
    public function setData(
        Quote $cart,
        array $data,
    ): void {
        if (isset($data[$this->getDataKey()])) {
            $this->proceed($cart, $data);
        }
    }

    /**
     * @return string
     */
    abstract public function getDataKey(): string;

    /**
     * Proceed setting delivery data if current data applies to the current delivery method
     *
     * @param Quote $cart
     * @param array $data
     * @return void
     */
    abstract public function proceed(Quote $cart, array $data): void;

    /**
     * @param $data
     * @param $type
     * @return bool|string
     */
    protected function getDeliveryDataFormatted($data, $type): bool|string
    {
        $data[$type] = true;
        return $this->jsonSerializer->serialize($data);
    }
}
