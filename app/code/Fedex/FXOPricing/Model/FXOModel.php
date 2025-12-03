<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Fedex\CartGraphQl\Model\Address\CollectRates\ShippingRate;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\ProductBundle\Api\ConfigInterface as ProductBundleConfigInterface;

class FXOModel
{
    public const DISCOUNT_CUSTOMER = 'AR_CUSTOMERS';
    public const DISCOUNT_COUPON = 'COUPON';
    public const DISCOUNT_CORPORATE = 'CORPORATE';

    /**
     * @param Cart $cart
     * @param Session $customerSession
     * @param CollectionFactory $quoteItemCollectionFactory
     * @param LoggerInterface $logger
     * @param CheckoutSession $checkoutSession
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     * @param ConfigInterface $config
     * @param ShippingRate $shippingRate
     * @param JsonSerializer $json
     * @param ToggleConfig $toggleConfig
     * @param ProductBundleConfigInterface $productBundleConfigInterface
     * @prama ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly Cart $cart,
        private readonly Session $customerSession,
        private readonly CollectionFactory $quoteItemCollectionFactory,
        private readonly LoggerInterface $logger,
        private readonly CheckoutSession $checkoutSession,
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle,
        private readonly ConfigInterface $config,
        private readonly ShippingRate $shippingRate,
        private readonly JsonSerializer $json,
        private readonly ToggleConfig $toggleConfig,
        private readonly ProductBundleConfigInterface $productBundleConfigInterface
    ) {
    }

    /**
     * Get DB Items Count
     *
     * @param object $quote
     * @return int
     */
    public function getDbItemsCount($quote)
    {
        $quoteItemCollection = $this->quoteItemCollectionFactory->create();

        if ($this->addToCartPerformanceOptimizationToggle->isActive()) {
            return $quoteItemCollection->addFieldToSelect('quote_id')
                ->addFieldToFilter('quote_id', $quote->getId())->getSize();
        } else {
            return $quoteItemCollection->addFieldToSelect('*')
                ->addFieldToFilter('quote_id', $quote->getId())
                ->getSize();
        }
    }

    /**
     * Remove item from cart
     *
     * @param object $quote
     */
    public function removeQuoteItem($quote)
    {
        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            if (!$item->getCustomPrice()) {
                $quote->deleteItem($item);
            }
        }
        $this->cart->save();
    }

    /**
     * Remove item from cart
     *
     * @param object $quote
     * @return string $errorMessage
     */
    public function removeReorderQuoteItem($quote)
    {
        $items = $quote->getAllVisibleItems();
        $errorMessage = 'System error, Please try again.';

        foreach ($items as $item) {
            if (!$item->getCustomPrice()) {
                $quote->deleteItem($item);
            }
        }
        $this->cart->save();

        return $errorMessage;
    }

    /**
     * Manage Cart Discount
     *
     * @param object $quote
     * @param array $rateApiOutputdata
     * @param object $cartDataHelper
     * @return string|null
     */
    public function resetCartDiscounts($quote, $rateApiOutputdata, $cartDataHelper)
    {
        $discountTypes = $rateApiOutputdata['output']['rateQuote']['rateQuoteDetails']
        [0]['discounts'] ?? [];
        $accountDiscount = null;
        $couponDiscount = null;
        foreach ($discountTypes as $discountType) {
            if ($discountType['type'] == static::DISCOUNT_CUSTOMER ||
                $discountType['type'] == static::DISCOUNT_CORPORATE) {
                $accountDiscount = true;
            } elseif ($discountType['type'] == static::DISCOUNT_COUPON) {
                $couponDiscount = true;
            }
        }
        $couponCode = $quote->getCouponCode();

        if ($accountDiscount && !empty($this->checkoutSession->getCouponDiscountExist()) && !$couponDiscount) {
            $this->checkoutSession->setWarningMessageFlag(true);
            $this->checkoutSession->unsCouponDiscountExist();
        }
        if ($couponDiscount && !empty($this->checkoutSession->getAccountDiscountExist()) && !$accountDiscount) {
            $this->checkoutSession->setWarningMessageFlag(true);
            $this->checkoutSession->unsAccountDiscountExist();
        }
        if ($accountDiscount && !empty($this->checkoutSession->getAccountDiscountExist()) && !$couponDiscount) {
            $this->checkoutSession->setWarningMessageFlag(true);
            $this->checkoutSession->unsAccountDiscountExist();
        }
        if (!$couponDiscount) {
            $couponCode = $this->removePromoCode($rateApiOutputdata['output'], $quote);
        }

        return $couponCode ?? null;
    }

    /**
     * Check Errors and Remove Fedex Account
     *
     * @param object $quote
     * @param array $fxoRateResponse
     * @return void
     */
    public function checkErrorsAndRemoveFedexAccount($quote, $fxoRateResponse)
    {
        $errors = ["INTERNAL.SERVER.FAILURE"];
        if (isset($fxoRateResponse["errors"])) {
            foreach ($fxoRateResponse['errors'] as $fxoErrors) {
                if (in_array($fxoErrors['code'], $errors)) {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Internal server failure.');
                    $quote->setFedexAccountNumber();
                    $this->customerSession->setFedexAccountWarning('Internal server failure. Please try again.');
                    break;
                }
            }
        }
    }

    /**
     * Check Errors and Remove Discounts
     *
     * @param object $quote
     * @param array $rateApiOutputdata
     * @param string $cartDataHelper
     * @return void
     */
    public function checkErrorsAndRemoveDiscounts($quote, $rateApiOutputdata, $cartDataHelper): void
    {
        $discountTypes = isset($rateApiOutputdata['output']['rateQuote']['rateQuoteDetails']
            [0]['discounts']) ? $rateApiOutputdata['output']['rateQuote']['rateQuoteDetails']
        [0]['discounts'] : [];
        $accountDiscount = null;
        $couponDiscount = null;
        foreach ($discountTypes as $discountType) {
            if ($discountType['type'] == static::DISCOUNT_CUSTOMER) {
                $accountDiscount = true;
            } elseif ($discountType['type'] == static::DISCOUNT_COUPON) {
                $couponDiscount = true;
            }
        }
        if ($accountDiscount && !empty($this->checkoutSession->getCouponDiscountExist()) && !$couponDiscount) {
            $this->checkoutSession->setWarningMessageFlag(true);
            $this->checkoutSession->unsCouponDiscountExist();
        }
        if ($couponDiscount && !empty($this->checkoutSession->getAccountDiscountExist()) && !$accountDiscount) {
            $this->checkoutSession->setWarningMessageFlag(true);
            $this->checkoutSession->unsAccountDiscountExist();
        }
        if (!$couponDiscount) {
            $this->removePromoCode($rateApiOutputdata['output'], $quote);
        }
    }

    /**
     * Refactor Coupon Code
     *
     * @param array $fxoRateResponse
     * @param object $quote
     * @return string|void|null
     */
    public function removePromoCode($fxoRateResponse, $quote)
    {
        $message = null;
        $couponCode = $quote->getCouponCode();
        if ($couponCode) {
            $message = $this->promoCodeWarningHandling($quote, $fxoRateResponse);
            if ($message &&
                !$quote->getIsFromShipping() &&
                !$quote->getIsFromPickup() &&
                !$quote->getIsFromAccountScreen()
                ) {
                $this->customerSession->setPromoErrorMessage($message);

                return $quote->getCouponCode() ?? null;
            }

            return $message;
        }
    }

    /**
     * Promo/Account number warning handling
     *
     * @param object $quote
     * @param array $fxoRateResponse
     * @return string
     */
    public function handlePromoAccountWarnings($quote, $fxoRateResponse)
    {
        $message =  null;
        $accountWarning = null;
        foreach ($fxoRateResponse['alerts'] as $fxoAlert) {
            $alertCode = $fxoAlert['code'];
            if ($alertCode == "COUPONS.CODE.INVALID") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Invalid coupon code.');
                $message = 'Promo code invalid. Please try again.';
                $quote->setCouponCode();
            } elseif ($alertCode == "MINIMUM.PURCHASE.REQUIRED") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Minimum purchase amount not met.');
                $message = 'Minimum purchase amount not met.';
                $quote->setCouponCode();
            } elseif ($alertCode == "INVALID.PRODUCT.CODE") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Invalid product code.');
                $message = $fxoAlert['message'];
                $quote->setCouponCode();
            } elseif ($alertCode == "COUPONS.CODE.EXPIRED") {
                //To be decided since no expired/Redeemed coupon exist as of now
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Promo code expired.');
                $message = 'Promo code has expired. Please try again.';
                $quote->setCouponCode();
            } elseif ($alertCode == "COUPONS.CODE.REDEEMED") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Promo code has already been redeemed.');
                $message = 'Promo code has already been redeemed.';
                $quote->setCouponCode();
            } elseif ($alertCode == "RATEREQUEST.FEDEXACCOUNTNUMBER.INVALID") {
                $quote->setFedexAccountNumber();
                $accountWarning = true;
            }
            if ($message) {
                break;
            }
        }
        if (!empty($message) || !empty($accountWarning)) {
            $quote->save();
        }
    }

    /**
     * Promo code warning handling
     *
     * @param obj $quote
     * @param array $fxoRateResponse
     * @return string
     */
    public function promoCodeWarningHandling($quote, $fxoRateResponse)
    {
        $message =  null;
        foreach ($fxoRateResponse['alerts'] as $fxoAlert) {
            $alertCode = $fxoAlert['code'];
            if ($alertCode == "COUPONS.CODE.INVALID") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Invalid coupon code.');
                $message = 'Promo code invalid. Please try again.';
                $quote->setCouponCode();
            } elseif ($alertCode == "MINIMUM.PURCHASE.REQUIRED") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Minimum purchase amount not met.');
                $message = 'Minimum purchase amount not met.';
                $quote->setCouponCode();
            } elseif ($alertCode == "INVALID.PRODUCT.CODE") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Invalid product code.');
                $message = $fxoAlert['message'];
                $quote->setCouponCode();
            } elseif ($alertCode == "COUPONS.CODE.EXPIRED") {
                //To be decided since no expired/Redeemed coupon exist as of now
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Promo code expired.');
                $message = 'Promo code has expired. Please try again.';
                $quote->setCouponCode();
            } elseif ($alertCode == "COUPONS.CODE.REDEEMED") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Promo code has already been redeemed.');
                $message = 'Promo code has already been redeemed.';
                $quote->setCouponCode();
            }
            if ($message) {
                break;
            }
        }

        return $message;
    }

    /**
     * Promo code warning old handling
     *
     * @param object $quote
     * @param array $fxoRateResponse
     * @return string
     */
    public function promoCodeWarningOldHandling($quote, $fxoRateResponse)
    {
        $alertCode = $fxoRateResponse['alerts'][0]['code'];
        $message = null;

        if ($alertCode == "COUPONS.CODE.INVALID") {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Invalid coupon code.');
            $message = 'Promo code invalid. Please try again.';
            $quote->setCouponCode();
        } elseif ($alertCode == "MINIMUM.PURCHASE.REQUIRED") {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Minimum purchase amount not met.');
            $message = 'Minimum purchase amount not met.';
            $quote->setCouponCode();
        } elseif ($alertCode == "INVALID.PRODUCT.CODE") {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Invalid product code.');
            $message = $fxoRateResponse['alerts'][0]['message'];
            $quote->setCouponCode();
        } elseif ($alertCode == "COUPONS.CODE.EXPIRED") {
            //To be decided since no expired/Redeemed coupon exist as of now
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Promo code expired.');
            $message = 'Promo code has expired. Please try again.';
            $quote->setCouponCode();
        } elseif ($alertCode == "COUPONS.CODE.REDEEMED") {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Promo code has already been redeemed.');
            $message = 'Promo code has already been redeemed.';
            $quote->setCouponCode();
        }

        return $message;
    }

    /**
     * Update Quote discount in DB.
     *
     * @param CartFactory $quote
     * @param productRates $productRates
     * @param couponCode $couponCode
     * @param string $cartDataHelper
     * @param bool $isGraphQlRequest
     * @param bool $isNegotiableQuoteGraphQlRequest
     * @return true
     */
    public function updateQuoteDiscount(
        $quote,
        $productRates,
        $couponCode,
        $cartDataHelper,
        $isGraphQlRequest = false,
        $isNegotiableQuoteGraphQlRequest = false
    ) {
        $totalType = $isGraphQlRequest ? 'totalAmount' : 'netAmount';

        if (isset($productRates['output']['rateQuote']['rateQuoteDetails'][0][$totalType])) {
            $netAmount = $productRates['output']['rateQuote']['rateQuoteDetails'][0][$totalType];
            $netAmount = $cartDataHelper->formatPrice($netAmount);
            $totalDiscountAmount = $productRates['output']['rateQuote']['rateQuoteDetails'][0]['totalDiscountAmount'];
            $totalDiscountAmount = $cartDataHelper->formatPrice($totalDiscountAmount);
            $grossAmount = $productRates['output']['rateQuote']['rateQuoteDetails'][0]['grossAmount'];
            $grossAmount = $cartDataHelper->formatPrice($grossAmount);
            $quote->setDiscount($totalDiscountAmount);
            $quote->setCouponCode($couponCode);
            $quote->setSubtotal($grossAmount);
            $quote->setBaseSubtotal($grossAmount);

            if ($this->toggleConfig->getToggleConfigValue('techtitans_205366_subtotal_fix')) {
                $quote->setSubtotalWithDiscount($grossAmount);
                $quote->setBaseSubtotalWithDiscount($grossAmount);
            }

            if ($isGraphQlRequest) {
                try {
                    $quoteIntegration = $this->cartIntegrationRepository->getByQuoteId($quote->getId());
                    $taxAmount = $productRates['output']['rateQuote']['rateQuoteDetails'][0]['taxAmount'];
                    $quote->setTaxAmount($taxAmount);
                    $quote->setCustomTaxAmount($taxAmount);
                    if (!$isNegotiableQuoteGraphQlRequest) {
                        $quoteIntegration->setRaqNetAmount(
                            (float) $productRates['output']['rateQuote']['rateQuoteDetails'][0]['netAmount']
                        );
                        $this->cartIntegrationRepository->save($quoteIntegration);
                    }
                    if ($this->config->isEnabledCartPricingFix()) {
                        $productRateArray = $productRates['output']['rateQuote']['rateQuoteDetails'][0];
                        $deliveryFee = (float)$productRateArray['deliveriesTotalAmount'];
                        $productsTotalAmount = isset($productRateArray['productsSubTotalAmount']) ?
                            (float)$productRateArray['productsSubTotalAmount'] :
                            (float)$productRateArray['productsTotalAmount'];

                        $deliveryData = $quoteIntegration->getDeliveryData();
                        if (!empty($deliveryData)) {
                            $deliveryArray = $this->json->unserialize($deliveryData);
                            $deliveryArray['shipping_price'] = $deliveryFee;
                            $deliveryJson = $this->json->serialize($deliveryArray);
                            $quoteIntegration->setDeliveryData($deliveryJson);
                            $this->cartIntegrationRepository->save($quoteIntegration);

                            $shippingAddress = $quote->getShippingAddress();
                            $this->shippingRate->collect($shippingAddress, $quoteIntegration);
                        }
                            $quote->setSubtotalWithDiscount($productsTotalAmount);
                            $quote->setBaseSubtotalWithDiscount($productsTotalAmount);
                    }
                } catch (\Exception $error) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Can\'t retrieve integration. ' .
                        $error->getMessage());
                }
            }
            $quote->setGrandTotal($netAmount);
            $quote->setBaseGrandTotal($netAmount);
            $quote->save();
        }

        return true;
    }

    /**
     * SaveDiscountBreakdown
     *
     * @param object $quote
     * @param array $rateApiOutputdata
     * @return boolean
     */
    public function saveDiscountBreakdown($quote, $rateApiOutputdata)
    {
        if (!$this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
            $shippingDiscount = $this->getShippingDiscount($rateApiOutputdata);
            $discounts = $this->getDiscounts($rateApiOutputdata);
            $accountDiscount = [];
            $qtyDiscount = [];
            $promoDiscountarr =[];
            if (!empty($discounts)) {
                if ($this->toggleConfig->getToggleConfigValue('mazegeek_B2352379_discount_breakdown')) {
                    foreach ($discounts as $key => $val) {
                        foreach ($val as $discount) {
                            $discount['amount'] = (string)$discount['amount'];
                            if ($discount['type'] == 'AR_CUSTOMERS' || $discount['type'] == 'CORPORATE') {
                                $accountDiscount[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                            } elseif ($discount['type'] == 'QUANTITY') {
                                $qtyDiscount[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                            } elseif ($discount['type'] == 'COUPON') {
                                $promoDiscountarr[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                            }
                        }
                    }
                } else {
                    foreach ($discounts as $discount) {
                        $discount['amount'] = (string)$discount['amount'];
                        if ($discount['type'] == 'AR_CUSTOMERS' || $discount['type'] == 'CORPORATE') {
                            $accountDiscount[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                        } elseif ($discount['type'] == 'QUANTITY') {
                            $qtyDiscount[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                        } elseif ($discount['type'] == 'COUPON') {
                            $promoDiscountarr[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                        }
                    }
                }
                $promoDiscount = array_sum($promoDiscountarr);
                if ($shippingDiscount == 0.00) {
                    $quote->setPromoDiscount($promoDiscount);
                } else {
                    $quote->setPromoDiscount($promoDiscount-$shippingDiscount);
                }
                $quote->setAccountDiscount(array_sum($accountDiscount));
                $quote->setVolumeDiscount(array_sum($qtyDiscount));
                $quote->setShippingDiscount($shippingDiscount);
                $this->resetDiscount($quote, $discounts);
            } else {
                $quote->setVolumeDiscount(0);
                $quote->setAccountDiscount(0);
                $quote->setPromoDiscount(0);
            }
            $quote->save();

            return true;
        }

        $shippingDiscount = $this->getShippingDiscount($rateApiOutputdata);
        $discounts        = $this->getDiscounts($rateApiOutputdata);
        $bundleQtyDiscount  = 0.0;
        $regularQtyDiscount = 0.0;
        $accountDiscounts   = [];
        $couponDiscounts    = [];

        $hasGranularity    = (bool) $this->toggleConfig->getToggleConfigValue('mazegeek_B2352379_discount_breakdown');

        if ($hasGranularity) {
            $bundleMembersById = $this->getBundleMemberIds($quote);
            [$bundleQtyDiscount, $regularQtyDiscount] = $this->splitQuantityDiscountsByBundle(
                $rateApiOutputdata,
                $bundleMembersById
            );
        }

        if (!empty($discounts)) {
            $this->accumulateDiscounts(
                $discounts,
                $hasGranularity,
                $accountDiscounts,
                $couponDiscounts,
                $regularQtyDiscount
            );

            $promo = array_sum($couponDiscounts);
            $quote->setPromoDiscount($shippingDiscount == 0.00 ? $promo : $promo - $shippingDiscount);
            $quote->setAccountDiscount(array_sum($accountDiscounts));
            $quote->setVolumeDiscount($regularQtyDiscount);
            $quote->setBundleDiscount($bundleQtyDiscount);
            $quote->setShippingDiscount($shippingDiscount);

            $this->resetDiscount($quote, $discounts);

            $productDiscounts =
                (float)$quote->getBundleDiscount() +
                (float)$quote->getVolumeDiscount() +
                (float)$quote->getAccountDiscount() +
                (float)$quote->getPromoDiscount();

            $subtotal = (float)$quote->getSubtotal();
            $subtotalWithDiscount = max(0.0, $subtotal - $productDiscounts);

            // quote
            $quote->setSubtotalWithDiscount($subtotalWithDiscount);
            $quote->setBaseSubtotalWithDiscount($subtotalWithDiscount);

            $addr = $quote->getShippingAddress();
            if ($addr) {
                $addr->setSubtotalWithDiscount($subtotalWithDiscount);
                $addr->setBaseSubtotalWithDiscount($subtotalWithDiscount);

                // Magento armazena desconto no address como valor NEGATIVO
                $addr->setDiscountAmount(-$productDiscounts);
                $addr->setBaseDiscountAmount(-$productDiscounts);
            }

        } else {
            $this->clearDiscounts($quote);
        }

        $quote->save();
        return true;

    }

    /**
     * @param $quote
     * @return array
     */
    public function getBundleMemberIds($quote): array
    {
        $bundleMembers = [];
        foreach ($quote->getAllItems() as $item) {
            if (
                $item->getProductType() === Type::TYPE_BUNDLE ||
                ($item->getParentItem() && $item->getParentItem()->getProductType() === Type::TYPE_BUNDLE)
            ) {
                $bundleMembers[(int)$item->getItemId()] = true;
            }
        }
        return $bundleMembers;
    }

    /**
     * @param array $data
     * @param array $bundleMembers
     * @return float[]
     */
    public function splitQuantityDiscountsByBundle(array $data, array $bundleMembers): array
    {
        $bundleQty  = 0.0;
        $regularQty = 0.0;

        $lines = $data['output']['rateQuote']['rateQuoteDetails'][0]['productLines'] ?? [];
        foreach ($lines as $line) {
            $instanceId = isset($line['instanceId']) ? (int)$line['instanceId'] : null;
            if (!$instanceId || empty($line['productLineDiscounts'])) {
                continue;
            }
            foreach ($line['productLineDiscounts'] as $d) {
                if (($d['type'] ?? '') !== 'QUANTITY') {
                    continue;
                }
                $amount = $this->parseAmount((string)($d['amount'] ?? '0'));
                if (isset($bundleMembers[$instanceId])) {
                    $bundleQty += $amount;
                } else {
                    $regularQty += $amount;
                }
            }
        }
        return [$bundleQty, $regularQty];
    }

    /**
     * @param array $discounts
     * @param bool $hasGranularity
     * @param bool $enableBundleSplit
     * @param array $accountDiscounts
     * @param array $couponDiscounts
     * @param float $regularQtyDiscount
     * @return void
     */
    public function accumulateDiscounts(
        array $discounts,
        bool $hasGranularity,
        array &$accountDiscounts,
        array &$couponDiscounts,
        float &$regularQtyDiscount
    ): void {
        $groups = $hasGranularity ? $discounts : [$discounts];

        foreach ($groups as $group) {
            foreach ($group as $disc) {
                $type   = $disc['type'] ?? '';
                $amount = $this->parseAmount((string)($disc['amount'] ?? '0'));

                match ($type) {
                    'AR_CUSTOMERS', 'CORPORATE' => $accountDiscounts[] = $amount,
                    'COUPON'                    => $couponDiscounts[]  = $amount,
                    'QUANTITY'                  => (!$hasGranularity) && $regularQtyDiscount += $amount,
                    default                     => null,
                };
            }
        }
    }

    /**
     * @param string $amount
     * @return float
     */
    public function parseAmount(string $amount): float
    {
        return (float) str_replace(',', '', rtrim(ltrim($amount, '($'), ')'));
    }

    /**
     * @param $quote
     * @return void
     */
    public function clearDiscounts($quote): void
    {
        $quote->setVolumeDiscount(0);
        $quote->setBundleDiscount(0);
        $quote->setAccountDiscount(0);
        $quote->setPromoDiscount(0);
        if ($this->config->canApplyShippingDiscount() && $quote->getShippingDiscount() > 0) {
            $quote->setShippingDiscount(0);
        }
    }

    /**
     * Get Discounts
     *
     * @param array $rateApiOutputdata
     * @return array
     */
    public function getDiscounts($rateApiOutputdata)
    {
        $discounts = [];
        $rateQuoteDetails = $rateApiOutputdata['output']['rateQuote']['rateQuoteDetails'] ?? [];
        if (!empty($rateQuoteDetails)) {
            foreach ($rateQuoteDetails as $details) {
                if ($this->toggleConfig->getToggleConfigValue('mazegeek_B2352379_discount_breakdown')) {
                    if (!empty($details['productLines'])) {
                        foreach ($details['productLines'] as $deldisc) {
                            if (!empty($deldisc['productLineDiscounts'])) {
                                $discounts[] = $deldisc['productLineDiscounts'];
                            }
                        }
                    }
                } else {
                    if (!empty($details['discounts'])) {
                        $discounts = $details['discounts'];
                    }
                }
            }
        }
        return $discounts;
    }

    /**
     * Get Discounts
     *
     * @param array $rateApiOutputdata
     * @return float
     */
    public function getShippingDiscount($rateApiOutputdata)
    {
        $discount = 0;
        $rateQuoteDetails = $rateApiOutputdata['output']['rateQuote']['rateQuoteDetails'] ?? [];
        if (!empty($rateQuoteDetails)) {
            foreach ($rateQuoteDetails as $discdetails) {
                if (!empty($discdetails['deliveryLines'][1]['deliveryLineDiscounts'])) {
                    foreach ($discdetails['deliveryLines'][1]['deliveryLineDiscounts'] as $deldisc) {
                        if ($deldisc['type'] == 'COUPON' || $deldisc['type'] == 'CORPORATE') {
                            if (is_string($deldisc['amount'])) {
                                $discount = str_replace(',', '', rtrim(ltrim($deldisc['amount'], "($"), ")"));
                            } else {
                                $discount = $deldisc['amount'];
                            }
                        }
                    }
                }
            }
        }
        return $discount;
    }

    /**
     * Is VolumeDiscount Applied on Item
     *
     * @param object $quote
     * @param array $productRates
     * @return boolean
     */
    public function isVolumeDiscountAppliedonItem($quote, $productRates)
    {
        $productDiscountAmount  = $productLineDiscounts = [];
        if (isset($productRates['output']['rateQuote']['rateQuoteDetails'][0]['productLines'])) {
            foreach ($productRates['output']['rateQuote']['rateQuoteDetails'][0]['productLines'] as $val) {
                $id = $val['instanceId'];
                if (isset($val['productLineDiscounts'])) {
                    $productLineDiscounts = $val['productLineDiscounts'];
                    foreach ($productLineDiscounts as $discount) {
                        $discount['amount']  = (string) $discount['amount'];
                        if ($discount['type'] == 'QUANTITY') {
                             $productDiscountAmount[$id][] =
                             floatval(str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")")));
                        }
                    }
                }
            }
            $this->saveVolumeDiscountQuoteItem($quote, $productDiscountAmount);

        }
        return true;
    }

    /**
     * SaveVolumeDiscountQuoteItem
     *
     * @param object $quote
     * @param array $productDiscountAmount
     * @return boolean
     */
    public function saveVolumeDiscountQuoteItem($quote, $productDiscountAmount)
    {
        if (!$this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
            $items = $quote->getAllVisibleItems();
            foreach ($items as $item) {
                $volumeDiscount = isset($productDiscountAmount[$item->getItemId()]) ?
                    array_sum($productDiscountAmount[$item->getItemId()]) : 0;
                $item->setVolumeDiscount($volumeDiscount);
                $item->save();
            }
            return true;
        }

        foreach ($quote->getAllVisibleItems() as $item) {
            $isBundleParent = ($item->getProductType() === Type::TYPE_BUNDLE) && !$item->getParentItem();

            if ($isBundleParent) {
                $sumChildren = 0.0;
                foreach ($item->getChildren() as $child) {
                    $cid = (int)$child->getItemId();
                    if (!empty($productDiscountAmount[$cid])) {
                        $sumChildren += array_sum((array)$productDiscountAmount[$cid]);
                    }
                }
                $item->setData('bundle_discount', (float)$sumChildren);
                $item->setData('volume_discount', 0.0);
                $item->save();
            } else {
                $iid      = (int)$item->getItemId();
                $discount = !empty($productDiscountAmount[$iid]) ? array_sum((array)$productDiscountAmount[$iid]) : 0.0;
                $item->setData('volume_discount', (float)$discount);
                $item->setData('bundle_discount', 0.0);
                $item->save();
            }
        }

        return true;
    }

    /**
     * ResetDiscount
     *
     * @param object $quote
     * @param array $discounts
     * @return boolean
     */
    public function resetDiscount($quote, $discounts)
    {
        if ($this->toggleConfig->getToggleConfigValue('mazegeek_B2352379_discount_breakdown')) {
            foreach ($discounts as $discountChild) {
                if (!in_array('QUANTITY', array_column($discountChild, 'type'))) {
                    $quote->setVolumeDiscount(0);
                    $quote->setBundleDiscount(0);
                }
                if (!in_array('AR_CUSTOMERS', array_column($discountChild, 'type'))
                    && !in_array('CORPORATE', array_column($discountChild, 'type'))) {
                    $quote->setAccountDiscount(0);
                }
                if (!in_array('COUPON', array_column($discountChild, 'type'))) {
                    $quote->setPromoDiscount(0);
                }
            }
        } else {
            if (!in_array('QUANTITY', array_column($discounts, 'type'))) {
                $quote->setVolumeDiscount(0);
                $quote->setBundleDiscount(0);
            }
            if (!in_array('AR_CUSTOMERS', array_column($discounts, 'type'))
                && !in_array('CORPORATE', array_column($discounts, 'type'))) {
                $quote->setAccountDiscount(0);
            }
            if (!in_array('COUPON', array_column($discounts, 'type'))) {
                $quote->setPromoDiscount(0);
            }
        }

        return true;
    }
}
