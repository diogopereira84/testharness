<?php
namespace Fedex\LateOrdersGraphQl\Model;

use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Fedex\Cart\ViewModel\CheckoutConfig;
use Fedex\LateOrdersGraphQl\Model\Data\DownloadLinkDTO;
use Fedex\LateOrdersGraphQl\Model\Data\OrderDetailsItemDTO;
use Fedex\LateOrdersGraphQl\Model\Data\ProductConfigEntryDTO;
use Fedex\LateOrdersGraphQl\Model\Data\CustomerDTO;
use Fedex\LateOrdersGraphQl\Model\Data\StoreRefDTO;
use Fedex\LateOrdersGraphQl\Model\Data\FulfillmentDTO;
use Fedex\LateOrdersGraphQl\Model\Data\AddressDTO;
use Fedex\LateOrdersGraphQl\Model\Data\OrderDetailsDTO;
use Fedex\LateOrdersGraphQl\Api\Data\OrderDetailsDTOInterface;
use Fedex\Shipment\Api\ProducingAddressServiceInterface;
use Magento\Sales\Api\Data\OrderInterface;

class OrderDetailsAssembler
{
    const ALLOWED_INSTRUCTION_KEYS = ['CUSTOMER_SI', 'USER_SPECIAL_INSTRUCTIONS', 'SYSTEM_SI'];
    const DOWNLOAD_LINK_TEMPLATE = 'v2/documents/%s/previewpages/1?zoomFactor=2&ClientName=POD2.0';

    public function __construct(
        protected readonly CheckoutConfig $checkoutConfig,
        protected readonly CartIntegrationNoteRepositoryInterface $cartIntegrationNoteRepository,
        protected readonly ProducingAddressServiceInterface $producingAddressService,
    ) {}

    public function assemble(OrderInterface $order): OrderDetailsDTOInterface
    {
        $store = $this->buildStoreRef($order);
        $customer = new CustomerDTO(
            $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
            $order->getCustomerEmail(),
            $order->getShippingAddress()->getTelephone(),
        );
        $orderItems = $this->buildOrderItems($order);
        $fulfillment = $this->buildFulfillment($order);
        $orderNotes = $this->buildOrderNotes($order);
        return new OrderDetailsDTO(
            $order->getIncrementId(),
            $order->getStatus(),
            $order->getCreatedAt(),
            $customer,
            $fulfillment,
            $store,
            $orderItems,
            $orderNotes,
            true
        );
    }

    protected function buildStoreRef(OrderInterface $order): StoreRefDTO
    {
        $storeInformation = $this->producingAddressService->getByOrderId($order->getId());
        if (is_null($storeInformation)) {
            return new StoreRefDTO('', '', '');
        }

        $additionalInformation = [];
        $decoded = json_decode($storeInformation->getAdditionalData(), true);
        if (is_array($decoded)) {
            $additionalInformation = $decoded;
        }
        return new StoreRefDTO(
            (string)$additionalInformation['responsible_location_id'] ?? null,
            (string)$storeInformation->getPhoneNumber(),
            (string)$storeInformation->getEmailAddress()
        );
    }

    protected function buildOrderNotes(OrderInterface $order): ?string
    {
        try {
            $orderNotes = $this->cartIntegrationNoteRepository->getByParentId((int)$order->getQuoteId());
            if ($orderNotes && $orderNotes->getId()) {
                return (string)$orderNotes->getNote();
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    protected function buildOrderItems(OrderInterface $order): array
    {
        $rawOrderItems = $order->getItems();
        $items = [];
        foreach ($rawOrderItems as $orderItem) {
            $infoBuyRequest = $orderItem->getProductOptionByCode('info_buyRequest');
            $externalProd = $infoBuyRequest['external_prod'] ?? null;
            if ($this->isValidExternalProduct($externalProd)) {
                $productInstance = $externalProd[0];
                $contentReferences = $this->extractContentReferences($productInstance);
                $features = $this->extractFeatures($productInstance);
                $productInstructions = $this->extractProductInstructions($productInstance);
                $downloadLinks = $this->extractDownloadLinks($contentReferences);
                $items[] = new OrderDetailsItemDTO(
                    (string)$externalProd[0]['id'],
                    $contentReferences,
                    $features,
                    $productInstructions,
                    $downloadLinks,
                );
            }
        }
        return $items;
    }

    private function isValidExternalProduct($externalProd): bool
    {
        return is_array($externalProd)
            && !empty($externalProd)
            && !empty($externalProd[0])
            && !empty($externalProd[0]['instanceId']);
    }

    protected function extractContentReferences(array $productInstance): array
    {
        $contentReferences = [];
        if (isset($productInstance['contentAssociations'])) {
            foreach ($productInstance['contentAssociations'] as $contentAssociation) {
                if (isset($contentAssociation['contentReference'])) {
                    $contentReferences[] = $contentAssociation['contentReference'];
                }
            }
        }
        return $contentReferences;
    }

    protected function extractFeatures(array $productInstance): array
    {
        $features = [];
        if (isset($productInstance['features'])) {
            foreach ($productInstance['features'] as $feature) {
                if (isset($feature['name']) && isset($feature['choice'])) {
                    $features[] = new ProductConfigEntryDTO($feature['name'], $feature['choice']['name'] ?? '');
                }
            }
        }
        return $features;
    }

    protected function extractProductInstructions(array $productInstance): array
    {
        $productInstructions = [];
        if (isset($productInstance['properties'])) {
            foreach ($productInstance['properties'] as $property) {
                if (isset($property['name']) && in_array($property['name'], self::ALLOWED_INSTRUCTION_KEYS, true)) {
                    $productInstructions[$property['name']] = $property['value'] ?? '';
                }
            }
        }
        return $productInstructions;
    }

    protected function extractDownloadLinks(array $contentReferences): array
    {
        $downloadLinks = [];
        $downloadLinksBaseUrl = $this->checkoutConfig->getDocumentImagePreviewUrl();
        foreach ($contentReferences as $contentReference) {
            $downloadLinks[] = new DownloadLinkDTO(
                $downloadLinksBaseUrl . sprintf(self::DOWNLOAD_LINK_TEMPLATE, $contentReference)
            );
        }
        return $downloadLinks;
    }

    protected function buildFulfillment(OrderInterface $order): ?FulfillmentDTO
    {
        $shippingMethod = $order->getShippingMethod();
        $type = 'DELIVERY';
        if (str_contains($shippingMethod, 'PICKUP')) {
            $type = 'PICKUP';
        }
        $pickupTime = $order->getData('estimated_pickup_time') ?: null;
        $deliveryTime = null;
        $shippingDescription = $order->getShippingDescription();
        if ($type === 'DELIVERY' && !empty($shippingDescription)) {
            $shippingDescription = explode('-', $shippingDescription);
            $deliveryTime = $shippingDescription[0] ?? null;
        }
        $shippingAddressData = $order->getShippingAddress();
        $shippingAddress = null;
        if ($shippingAddressData) {
            $shippingAddress = new AddressDTO(
                $shippingAddressData->getStreetLine(1),
                $shippingAddressData->getStreetLine(2),
                $shippingAddressData->getCity(),
                $shippingAddressData->getRegion(),
                $shippingAddressData->getPostcode(),
                $shippingAddressData->getCountryId()
            );
        }
        $shippingAccountNumber = $this->getShippingAccountNumberFromShipment($order);
        if ($type || $pickupTime || $deliveryTime || $shippingAccountNumber || $shippingAddress) {
            return new FulfillmentDTO(
                $type,
                $pickupTime,
                $deliveryTime,
                $shippingAccountNumber,
                $shippingAddress
            );
        }
        return null;
    }

    protected function getShippingAccountNumberFromShipment(OrderInterface $order): ?string
    {
        $shipments = $order->getShipmentsCollection();
        foreach ($shipments as $shipment) {
            $shippingAccountNumber = $shipment->getData('shipping_account_number');
            if ($shippingAccountNumber) {
                return $shippingAccountNumber;
            }
        }
        return null;
    }
}
