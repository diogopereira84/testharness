<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Nitin Pawar <npawar.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data;

use Fedex\CartGraphQl\Api\Data\DeliveryDataHandlerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Magento\Directory\Model\Region;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CartGraphQl\Helper\LoggerHelper;

class ContactData extends AbstractData implements DeliveryDataHandlerInterface
{
    public const DATA_KEY = 'alternate_contact';
    private const IS_ALTERNATE_CONTACT_ENABLED = 'tiger_b_2740163';
    private const KEY_SHIPPING_METHOD = 'shipping_method';
    private const KEY_IS_ALTERNATE = 'is_alternate';
    private const IS_ALTERNATE_VALUE = 1;

    /**
     * @param LoggerHelper $loggerHelper
     * @param Region $region
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param DateTime $dateTime
     * @param CartRepositoryInterface $cartRepository
     * @param InstoreConfig $instoreConfig
     * @param JsonSerializer $jsonSerializer
     * @param Cart $cartModel
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly LoggerHelper $loggerHelper,
        protected Region $region,
        protected CartIntegrationRepositoryInterface $cartIntegrationRepository,
        protected DateTime $dateTime,
        protected CartRepositoryInterface $cartRepository,
        protected InstoreConfig $instoreConfig,
        protected JsonSerializer $jsonSerializer,
        protected Cart $cartModel,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct(
            $region,
            $cartIntegrationRepository,
            $dateTime,
            $cartRepository,
            $instoreConfig,
            $jsonSerializer
        );
    }

    /**
     * Set alternate contact data to the cart addresses
     *
     * @param Quote $cart
     * @param array $data
     * @return void
     * @throws NoSuchEntityException
     */
    public function proceed(Quote $cart, array $data): void
    {
        $alternateContact = $data[self::DATA_KEY] ?? null;
        if (!$this->shouldApplyAlternateContact($alternateContact)) {
            return;
        }

        $integration = $this->getCartIntegration($cart);
        $deliveryData = $this->getDeliveryData($integration);
        $addresses = $this->resolveTargetAddresses($cart, $deliveryData, $integration);

        $this->applyAlternateContact($addresses, $alternateContact);
        $this->markCartAsAlternate($cart);
    }

    /**
     * Determine if alternate contact should be applied
     *
     * @param mixed $alternateContact
     * @return bool
     */
    private function shouldApplyAlternateContact(mixed $alternateContact): bool
    {
        return !empty($alternateContact)
            && $this->toggleConfig->getToggleConfigValue(self::IS_ALTERNATE_CONTACT_ENABLED);
    }

    /**
     * @param $integration
     * @return array
     */
    private function getDeliveryData($integration): array
    {
        return $this->jsonSerializer->unserialize($integration->getDeliveryData() ?? '{}');
    }

    /**
     * @param Quote $cart
     * @param array $deliveryData
     * @param $integration
     * @return array
     */
    private function resolveTargetAddresses(Quote $cart, array $deliveryData, $integration): array
    {
        $hasPickupWithoutShipping = $integration->getPickupLocationId()
            && empty($deliveryData[self::KEY_SHIPPING_METHOD]);
        $isDeliveryDataEmpty = empty($deliveryData);

        if ($hasPickupWithoutShipping || $isDeliveryDataEmpty) {
            return array_filter([
                $cart->getShippingAddress(),
                $cart->getBillingAddress()
            ], 'is_object');
        }
        return array_filter([$cart->getBillingAddress()], 'is_object');
    }

    /**
     * Apply alternate contact details to given addresses
     *
     * @param array $addresses
     * @param array $alternateContact
     * @return void
     */
    private function applyAlternateContact(array $addresses, array $alternateContact): void
    {
        foreach ($addresses as $address) {
            $this->setDataInAddress($address, $alternateContact);
        }
    }

    /**
     * Mark cart as having alternate contact
     *
     * @param Quote $cart
     * @return void
     */
    private function markCartAsAlternate(Quote $cart): void
    {
        $cart->setData(self::KEY_IS_ALTERNATE, self::IS_ALTERNATE_VALUE);
    }

    /**
     * Set additional contact details in address object
     *
     * @param \Magento\Quote\Model\Quote\Address $item
     * @param array $shippingContact
     * @return void
     * @throws \Exception
     */
    private function setDataInAddress($item, $shippingContact): void
    {
        try {
            if ($item->getId()) {
                $item->setFirstName($shippingContact['firstname'] ?? null);
                $item->setLastname($shippingContact['lastname'] ?? null);
                $item->setEmail($shippingContact['email'] ?? null);
                $item->setTelephone($shippingContact['telephone'] ?? null);
                $item->setExtNo($shippingContact['ext'] ?? null);
                $item->setContactNumber($shippingContact['telephone'] ?? null);
                $item->save();
            }
        } catch (\Exception $exception) {
            $this->loggerHelper->error(
                __METHOD__ . ':' . __LINE__ . ' Error while saving address ' . $exception->getMessage()
            );
        }
    }

    /**
     * @return string
     */
    public function getDataKey(): string
    {
        return self::DATA_KEY;
    }

    /**
     * @param Quote $cart
     * @return mixed
     * @throws NoSuchEntityException
     */
    private function getCartIntegration(Quote $cart)
    {
        return  $this->cartIntegrationRepository->getByQuoteId($cart->getId());
    }
}
