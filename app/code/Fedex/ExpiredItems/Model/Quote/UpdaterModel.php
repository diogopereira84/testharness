<?php
/**
 * @category    Fedex
 * @package     Fedex_ExpiredItems
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ExpiredItems\Model\Quote;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\ResourceModel\Quote\Item\OptionFactory as CustomOptionResourceFactory;
use Magento\Quote\Model\ResourceModel\Quote\ItemFactory as QuoteItemResourceFactory;
use Magento\Quote\Model\ResourceModel\QuoteFactory as QuoteResourceFactory;
use Magento\Tax\Model\CalculationFactory as TaxCalculationFactory;
use Mirakl\Connector\Helper\Config;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Helper\Tax as TaxHelper;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Synchronizer;
use Mirakl\Connector\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use Mirakl\Core\Exception\ShippingZoneNotFound;
use Mirakl\MMP\Front\Domain\Collection\Shipping\OrderShippingFeeCollection;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeError;
use Psr\Log\LoggerInterface;
use Fedex\ProductUnavailabilityMessage\Model\CheckProductAvailabilityDataModel;
use Mirakl\FrontendDemo\Model\Quote\Updater as MiraklUpdater;

class UpdaterModel extends MiraklUpdater
{
    /**
     * @param QuoteResourceFactory $quoteResourceFactory
     * @param QuoteItemResourceFactory $quoteItemResourceFactory
     * @param CustomOptionResourceFactory $customOptionResourceFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param TaxCalculationFactory $taxCalculationFactory
     * @param EventManagerInterface $eventManager
     * @param OfferResourceFactory $offerResourceFactory
     * @param Config $config
     * @param OfferCollector $offerCollector
     * @param Synchronizer $quoteSynchronizer
     * @param QuoteHelper $quoteHelper
     * @param TaxHelper $taxHelper
     * @param LoggerInterface $logger
     * @param CheckProductAvailabilityDataModel $checkProductAvailabilityDataModel
     */
    public function __construct (
        QuoteResourceFactory $quoteResourceFactory,
        QuoteItemResourceFactory $quoteItemResourceFactory,
        CustomOptionResourceFactory $customOptionResourceFactory,
        PriceCurrencyInterface $priceCurrency,
        TaxCalculationFactory $taxCalculationFactory,
        EventManagerInterface $eventManager,
        OfferResourceFactory $offerResourceFactory,
        Config $config,
        OfferCollector $offerCollector,
        Synchronizer $quoteSynchronizer,
        QuoteHelper $quoteHelper,
        TaxHelper $taxHelper,
        LoggerInterface $logger,
        private CheckProductAvailabilityDataModel $checkProductAvailabilityDataModel
    ) {
        parent::__construct(
            $quoteResourceFactory,
            $quoteItemResourceFactory,
            $customOptionResourceFactory,
            $priceCurrency,
            $taxCalculationFactory,
            $eventManager,
            $offerResourceFactory,
            $config,
            $offerCollector,
            $quoteSynchronizer,
            $quoteHelper,
            $taxHelper,
            $logger
        );
    }

    public function synchronize(CartInterface $quote)
    {
        if (!$quote->getItemsCount() || !$this->quoteHelper->isMiraklQuote($quote)) {
            $this->resetQuoteShippingFields($quote);

            return;
        }

        $hasError = false;
        $defaultErrorMessage = __('An error occurred while processing your shopping cart.' .
            ' Please contact store owner if the problem persists.');

        $offerResource = $this->offerResourceFactory->create();

        $addQuoteError = function ($message) use (&$quote) {
            $quote->setHasError(true);
            $quote->addMessage($message);
        };

        try {
            $shippingFees = $this->quoteSynchronizer->getShippingFees($quote);

            $this->eventManager->dispatch('mirakl_check_quote_offers_before', [
                'quote' => $quote,
                'shipping_fees' => $shippingFees,
            ]);

            // Useful closure to mark given item as failed
            $addItemError = function (CartItemInterface $item, $message) use (&$hasError) {
                $hasError = true;
                $item->setHasError(true);
                $item->removeMessageByText($message); // Avoid duplicate messages
                $item->addMessage($message);
            };

            // Update offer data on quote item if it has changed
            $updateItemOffer = function (
                CartItemInterface $item, Offer $offer, $offerFinalPrice = null
            ) use ($offerResource) {
                $offerResource->load($offer, $offer->getId()); // reload offer data
                /** @var \Magento\Quote\Model\Quote\Item\Option $customOption */
                $customOption = $item->getProduct()->getCustomOption('mirakl_offer');
                if ($customOption && $customOption->getId()) {
                    if ($offerFinalPrice) {
                        $offer->setPrice($offerFinalPrice);
                        $offer->setPriceRanges([['qty' => $item->getQty(), 'price' => $offerFinalPrice]]);
                        $offer->setDiscountRanges([['qty' => $item->getQty(), 'price' => $offerFinalPrice]]);
                    }
                    $customOption->setValue($offer->toJson());
                    $this->customOptionResourceFactory->create()->save($customOption);
                }
            };

            /** @var CartItemInterface|QuoteItem $item */
            foreach ($this->offerCollector->getItemsWithOffer($quote) as $item) {
                /** @var Offer $offer */
                $offer = $item->getData('offer');
                $offerId = $offer->getId();

                $item->setMiraklOfferId($offerId);
                $this->setShopToItem($item, $offer);

                // Check if offer is still present in Mirakl platform
                if ($shippingFees && $shippingFees->getErrors()) {
                    /** @var ShippingFeeError $error */
                    foreach ($shippingFees->getErrors() as $error) {
                        if ($error->getOfferId() != $offerId) {
                            continue; // no error on this offer
                        }
                        if ($error->getErrorCode() == 'OFFER_NOT_FOUND') {
                            if ($this->config->isAutoRemoveOffers()) {
                                $offerResource->delete($offer);
                                $message = __(
                                    'Offer for product "%1" is not available anymore. It has been removed from your cart.',
                                    $item->getName()
                                );
                                $addQuoteError($message);
                                $quote->removeItem($item->getId());
                            } else {
                                $addItemError($item, __('This offer no longer exists.'));
                            }
                        } elseif ($error->getErrorCode() == 'SHIPPING_TYPE_NOT_ALLOWED' && $error->getShippingTypeCode()) {
                            // Message on item are displed only on cart view so modifications need to be made in cart controler
                            $item->setMiraklShippingType('');
                            $item->setMiraklShippingTypeLabel('');
                            $addItemError($item, __(
                                'The selected shipping method is not allowed for this offer, it has been reset. ' .
                                'Please refresh the page to list available shipping methods.'
                            ));
                        } else {
                            $addItemError($item, __(
                                'An error occurred with this offer: %1. Try to modify shipping address.',
                                __($error->getErrorCode())
                            ));
                        }
                        continue 2;
                    }
                }

                $orderShippingFee = $this->getItemOrderShippingFee($item);

                $shippingRateOffer = $this->getItemShippingRateOffer($item);
                if (!$shippingRateOffer->getId()) {
                    $addItemError($item, __('This offer is not available.'));
                    continue;
                }

                // Update quote item shipping method and fee
                $this->setItemShippingFee($item, $shippingRateOffer);
                $this->setItemShippingType($item, $orderShippingFee->getSelectedShippingType());
                $item->setMiraklLeadtimeToShip($orderShippingFee->getLeadtimeToShip());

                // Check if offer quantity has changed
                if ($offer->getQty() != $shippingRateOffer->getQuantity()) {
                    // Message on item is displayed only on cart view so modifications need to be made in cart controller
                    if ($this->config->isAutoUpdateOffers()) {
                        $offerResource->updateOrderConditions($offerId);
                        $updateItemOffer($item, $offer);
                    }
                }

                // Check if requested quantity is available
                if ($shippingRateOffer->getLineOriginalQuantity() != $shippingRateOffer->getLineQuantity()) {

                    $product = $item->getProduct();
                    $toggle  = $this->checkProductAvailabilityDataModel->isE441563ToggleEnabled();
                    $isProductUnavailable = $product->getData('is_unavailable');

                    /** Check the toggle and conditions applied to check if is unavailable message must be shown. */
                    if ($item->getQty() > $shippingRateOffer->getQuantity() && (!$toggle && ($toggle && !$isProductUnavailable))) {
                        $addItemError($item, __(
                            'Requested quantity of %1 is not available for product "%2". Quantity available: %3.',
                            (int) $item->getQty(),
                            $item->getName(),
                            (int) $shippingRateOffer->getQuantity()
                        ));

                        if (!$shippingRateOffer->getQuantity()) {
                            $quote->removeItem($item->getId());
                        }
                    } else {
                        // Problem with order conditions
                        /** @var \Mirakl\MMP\FrontOperator\Domain\Offer $sdkOffer */
                        $sdkOffer = $offerResource->updateOrderConditions($offerId);
                        $updateItemOffer($item, $offer);

                        if ($sdkOffer->getMinOrderQuantity()
                            && $shippingRateOffer->getLineOriginalQuantity() < $sdkOffer->getMinOrderQuantity()) {
                            $addItemError($item, __(
                                'The fewest you may purchase is %1.',
                                $sdkOffer->getMinOrderQuantity() * 1
                            ));
                        } elseif ($sdkOffer->getMaxOrderQuantity()
                            && $shippingRateOffer->getLineOriginalQuantity() > $sdkOffer->getMaxOrderQuantity()) {
                            $addItemError($item, __(
                                'The most you may purchase is %1.',
                                $sdkOffer->getMaxOrderQuantity() * 1
                            ));
                        } elseif ($sdkOffer->getPackageQuantity() > 1
                            && $shippingRateOffer->getLineOriginalQuantity() % $sdkOffer->getPackageQuantity() != 0) {
                            $addItemError($item, __(
                                'You can buy this product only in quantities of %1 at a time.',
                                $sdkOffer->getPackageQuantity()
                            ));
                        }
                    }

                    if ($shippingRateOffer->getLineQuantity() > 0) {
                        $addItemError($item, __(
                            'Quantity was recalculated from %1 to %2',
                            $item->getQty(),
                            $shippingRateOffer->getLineQuantity()
                        ));
                    }
                    $item->setQty($shippingRateOffer->getLineQuantity());
                }

                // We update price only when offer is in stock
                // When offer is out of stock discounts are not taken into account in SH02
                if ($shippingRateOffer->getLineQuantity() > 0) {
                    // Check if price has changed
                    $offerPrice = $shippingRateOffer->getPrice();
                    $itemPrice = $this->config->getOffersIncludeTax($quote->getStoreId())
                        ? $item->getPriceInclTax()
                        : $item->getPrice();
                    if ($itemPrice != $offerPrice) {
                        $item->addMessage(__(
                            'Price has changed from %1 to %2',
                            $this->priceCurrency->format($itemPrice, false),
                            $this->priceCurrency->format($offerPrice, false)
                        ));
                        $updateItemOffer($item, $offer, $offerPrice);
                    }
                }

                $this->quoteItemResourceFactory->create()->save($item);
            }

            // Mark quote as failed if an error occurred
            if ($hasError) {
                $addQuoteError(
                    __('Some errors occurred while processing your shopping cart. Please verify it.')
                );
            }

            $quote->collectTotals();
            $this->quoteResourceFactory->create()->save($quote);

            $this->eventManager->dispatch('mirakl_check_quote_offers_after', [
                'quote' => $quote,
                'shipping_fees' => $shippingFees,
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // If a request exception occurs, define empty shipping fees on quote to avoid possible multiple API calls.
            $quote->setMiraklShippingFees(OrderShippingFeeCollection::create());

            $message = $defaultErrorMessage;
            $response = $e->getResponse();

            if ($response && strpos($response->getHeaderLine('Content-Type'), 'application/json') === 0) {
                $result = \Mirakl\parse_json_response($response);
                if (!empty($result['message'])) {
                    $message = __($result['message']);
                }
            }

            $addQuoteError($message);
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error Message: ' . $e->getTraceAsString());
        } catch (ShippingZoneNotFound $e) {
            $addQuoteError($e->getMessage());
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error Message: ' . $e->getTraceAsString());
        } catch (\Exception $e) {
            $addQuoteError($defaultErrorMessage);
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error Message: ' . $e->getTraceAsString());
        }
    }
}
