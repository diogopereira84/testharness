<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplaceCheckout\Model\Config\MarketplaceConfigProvider;
use Fedex\MarketplaceCheckout\Model\Config\ToastDeliveryMessage;
use Fedex\MarketplaceCheckout\Helper\Data as HelperData;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Construct
     *
     * @param HandleMktCheckout $handleMktCheckout
     * @param MarketplaceConfigProvider $marketplaceConfigProvider
     * @param ToastDeliveryMessage $toastDeliveryMessage
     * @param HelperData $helperData
     * @param NonCustomizableProduct $nonCustomizableProductModel
     */
    public function __construct(
        protected HandleMktCheckout $handleMktCheckout,
        protected MarketplaceConfigProvider $marketplaceConfigProvider,
        protected ToastDeliveryMessage $toastDeliveryMessage,
        private HelperData $helperData,
        private NonCustomizableProduct $nonCustomizableProductModel
    )
    {
    }

    /**
     * Get checkout marketplace toggle.
     *
     * @return array
     */
    public function getConfig(): array
    {
        $promoCodeMessage = $this->marketplaceConfigProvider->getPromoCodeMessage();
        $toastTitle = $this->toastDeliveryMessage->getMarketplaceToastTitle();
        $toastShippingContent = $this->toastDeliveryMessage->getMarketplaceToastShippingContent();
        $toastPickupContent = $this->toastDeliveryMessage->getMarketplaceToastPickupContent();
        $promoCodeMessageEnabledToggle = $this->marketplaceConfigProvider->getPromoCodeMessageEnabledToggle();
        $onlyNonCustomizable = $this->helperData->checkIfItemsAreAllNonCustomizableProduct($this->helperData->getQuote());

        return [
            'checkoutDeliveryMethodsTooltip' => $this->handleMktCheckout->getCheckoutDeliveryMethodsTooltip(),
            'promoCodeMessage' => $promoCodeMessage,
            'toastTitle' => $toastTitle,
            'toastShippingContent' => $toastShippingContent,
            'toastPickupContent' => $toastPickupContent,
            'isCustomerShippingAccount3PEnabled' => $this->helperData->isCustomerShippingAccount3PEnabled(),
            'shippingAccountMessage' => $this->handleMktCheckout->getCheckoutShippingAccountMessage(),
            'promoCodeMessageEnabledToggle' => $promoCodeMessageEnabledToggle,
            'toggle_D180031_fix'=> $this->handleMktCheckout->getTigerTeamD180031Fix(),
            'isExpectedDeliveryDateEnabled' => $this->helperData->isExpectedDeliveryDateEnabled(),
            'reviewSubmitCancellationMessage' =>
                $this->helperData->getReviewSubmitAndOrderConfirmationCancellationMessage(),
            'isMktCbbEnabled' => $this->nonCustomizableProductModel->isMktCbbEnabled(),
            'isEssendantEnabled' => $this->helperData->isEssendantToggleEnabled(),
            'onlyNonCustomizableCart' => $onlyNonCustomizable,
        ];
    }
}
