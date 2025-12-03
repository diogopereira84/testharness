<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\Region;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Fedex\SubmitOrderSidebar\Model\BillingAddressBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * SubmitOrder Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SubmitOrder
{
    /**
     * SubmitOrder constructor
     *
     * @param CartFactory $cartFactory
     * @param Session $checkoutSession
     * @param DeliveryHelper $helper
     * @param PunchoutHelper $punchoutHelper
     * @param RegionFactory $regionFactory
     * @param SubmitOrderHelper $submitOrderHelper
     * @param StoreManagerInterface $storeManager
     * @param BillingAddressBuilder $billingAddressBuilder
     * @param CustomerSession $customerSession
     * @param DataObjectFactory $dataObjectFactory
     * @param QuoteHelper $quoteHelper
     */
    public function __construct(
        protected CartFactory $cartFactory,
        protected Session $checkoutSession,
        protected DeliveryHelper $helper,
        private PunchoutHelper $punchoutHelper,
        protected RegionFactory $regionFactory,
        private SubmitOrderHelper $submitOrderHelper,
        private StoreManagerInterface $storeManager,
        private BillingAddressBuilder $billingAddressBuilder,
        protected CustomerSession $customerSession,
        private DataObjectFactory $dataObjectFactory,
        private QuoteHelper $quoteHelper,
        private readonly ToggleConfig $toggleConfig
    ) {
    }

    /**
     * Get quote from checkout
     *
     * @return Quote
     */
    public function getQuote()
    {
        return $this->cartFactory->create()->getQuote();
    }

    /**
     * Unset order in progress
     *
     * @return mixed
     */
    public function unsetOrderInProgress()
    {
        return $this->checkoutSession->unsOrderInProgress();
    }

    /**
     * Get GTIN NUMBER
     *
     * @return string|null
     */
    public function getGTNNumber(): ?string
    {
        return $this->punchoutHelper->getGTNNumber();
    }

    /**
     * Get Rate Request shipment special services
     *
     * @return array
     */
    public function getRateRequestShipmentSpecialServices(): array
    {
        return $this->helper->getRateRequestShipmentSpecialServices();
    }

    /**
     * Get Region using regioncode
     *
     * @param int|string $regionCode
     * @return Region
     */
    public function getRegionByRegionCode(int|string $regionCode): Region
    {
        return $this->regionFactory->create()->load($regionCode);
    }

    /**
     * Get UUid
     *
     * @return string
     */
    public function getUuid(): string
    {
        return $this->submitOrderHelper->getUuid();
    }

    /**
     * Get billingAddress
     *
     * @param array $paymentData
     * @param object $quote
     * @return AddressInterface
     */
    public function getBillingAddress($paymentData, $quote): AddressInterface
    {
        return $this->billingAddressBuilder->build($paymentData, $quote);
    }

    /**
     * Get webhookUrl
     *
     * @param string $orderNumber
     * @return string
     * @throws NoSuchEntityException
     */
    public function getWebHookUrl(string $orderNumber): string
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        return "{$baseUrl}rest/V1/fedexoffice/orders/{$orderNumber}/status";
    }

    /**
     * Checks if the customer is retail or E-Pro
     *
     * @return boolean true|false
     */
    public function isFclCustomer(): bool
    {
        if ($this->customerSession->getCustomerId()
            && !$this->customerSession->getCustomerCompany()) {
            return true;
        }

        return false;
    }

    /**
     * Get Product and Product Associations
     *
     * @param $items
     * @param bool $isFullMiraklQuote
     * @return array
     */
    public function getProductAndProductAssociations($items, bool $isFullMiraklQuote = false)
    {
        $result = $product = $productAssociations = [];
        $id = 0;

        foreach ($items as $item) {
            if ($item->getProductType() == Type::TYPE_BUNDLE) {
                continue;
            }
            if ($item->getMiraklOfferId()) {
                $marketPlaceProductInfo = $this->quoteHelper->getMarketplaceRateQuoteRequest($item);
                $product[] = $marketPlaceProductInfo;
                $productAssociations[$item->getData('mirakl_shop_id')][] = ['id' => $marketPlaceProductInfo['instanceId'], 'quantity' => intval($marketPlaceProductInfo['qty']), 'is_marketplace' => true];
            } else {
                $additionalOption = $item->getOptionByCode('info_buyRequest');
                $additionalOptions = $additionalOption->getValue();
                $productJson = (array)json_decode((string)$additionalOptions)->external_prod[0];

                if (isset($productJson['catalogReference'])) {
                    $productJson['catalogReference'] = (array)$productJson['catalogReference'];
                }
                if (isset($productJson['preview_url'])) {
                    unset($productJson['preview_url']);
                }
                if (isset($productJson['fxo_product'])) {
                    unset($productJson['fxo_product']);
                }
                $productJson['instanceId'] = $item->getItemId() ?? $id;
                $productJson['qty'] = $item->getQty();
                $product[] = $productJson;
                $productAssociations[0][] = ['id' => $productJson['instanceId'], 'quantity' => intval($item->getQty()), 'is_marketplace' => false];
                $id++;
            }
        }
        $result['product'] = $product;
        $result['productAssociations'] = $productAssociations;

        return $result;
    }

    /**
     * Validate rate quote API RAQ-119 errors
     *
     * @param array $errors
     * @return bool
     */
    public function validateRateQuoteAPIErrors($errors)
    {
        foreach ($errors as $error) {
            if (isset($error['code']) && $error['code'] == "RAQ.SERVICE.119") {

                return true;
            }
        }

        return false;
    }

    /**
     * Validate rate quote API order already exit warning
     *
     * @param array $warnings
     * @return bool
     */
    public function validateRateQuoteAPIWarnings($warnings)
    {
        foreach ($warnings as $warning) {
            if (!empty($warning['code']) && !empty($warning['alertType'])
                && $warning['code'] == "QCXS.SERVICE.ORDERNUMBER" && $warning['alertType'] == "WARNING") {

                return true;
            }
        }
    }
}
