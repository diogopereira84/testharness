<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\ExpiredItems\Helper;

use Fedex\ExpiredItems\Model\ConfigProvider;
use Fedex\MarketplacePunchout\Model\Config\Marketplace;
use Fedex\MarketplacePunchout\Model\ExpiredProducts;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Base\Helper\Auth as AuthHelper;

/**
 * ExpiredData Helper
 */
class ExpiredData extends AbstractHelper
{
    public const EXPIRED_CODE = "RATEREQUEST.PRODUCTS.INVALID";

    public const CATALOG_ITEM_EXPIRED_CODE = "PRODUCTS.CATALOGREFERENCE.INVALID";

    /**
     * Initilizing constructor
     *
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param ExpiredProducts $expiredProducts
     * @param Marketplace $config
     * @param AuthHelper $authHelper
     * @param ConfigProvider $expiredConfig
     */
    public function __construct(
        Context $context,
        protected CustomerSession $customerSession,
        protected ExpiredProducts $expiredProducts,
        protected Marketplace $config,
        protected AuthHelper $authHelper,
        protected ConfigProvider $expiredConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Unset expired Item ids
     *
     * @param array $rateApiOutputdata
     * @return void
     */
    public function unSetExpiredItemids($rateApiOutputdata)
    {
        $rateApiErrResponse = isset($rateApiOutputdata['errors'])
        && isset($rateApiOutputdata['errors'][0]['code'])
        && ($rateApiOutputdata['errors'][0]['code'] == self::EXPIRED_CODE
        || $rateApiOutputdata['errors'][0]['code'] == self::CATALOG_ITEM_EXPIRED_CODE);

        $rateApiZeroPrices = $this->checkZeroPriceForCurrentRateApiResponse($rateApiOutputdata);

        $customerExpiredIdsSession = !empty($this->customerSession->getExpiredItemIds())
        && $this->authHelper->isLoggedIn();

        if ($rateApiErrResponse && $this->authHelper->isLoggedIn()) {
            $this->setExpiredIdInCustomerSession($rateApiOutputdata);
        } elseif ($rateApiZeroPrices) {
            $this->setExpiredIdInCustomerSessionFromZeroPrice($rateApiZeroPrices);
        } elseif (!isset($rateApiOutputdata['errors'])
        && $customerExpiredIdsSession && !$this->customerSession->getValidateContentApiExpired()) {
            $this->customerSession->unsExpiredItemIds();
            $this->customerSession->unsExpiredItemTransactionId();
        }
    }

    /**
     * Set expired ids in session
     *
     * @param array $rateApiOutputdata
     * @return void
     */
    public function setExpiredIdInCustomerSession($rateApiOutputdata)
    {
        $message = explode(":", $rateApiOutputdata['errors'][0]['message']);
        $expiredIds = array_map('trim', explode(",", end($message)));
        $previoustExpiredIds = $this->customerSession->getExpiredItemIds()
        ? $this->customerSession->getExpiredItemIds() : [];
        $remainigExpiredIds = [];
        foreach ($expiredIds as $expiredId) {
            if (!in_array($expiredId, $previoustExpiredIds)) {
                $remainigExpiredIds[] = $expiredId;
            }
        }

        $this->customerSession->setExpiredItemIds(array_merge($previoustExpiredIds, $remainigExpiredIds));
    }

    /**
     * Exclude expired product from rate request
     *
     * @param array $rateApiData
     * @return array
     */
    public function exludeExpiredProductFromRateRequest($rateApiData)
    {
        $arrProducts = [];
        $arrExpiredInstansIds = [];
        $sessionExpiredIds = $this->customerSession->getExpiredItemIds()
        ? $this->customerSession->getExpiredItemIds() : [];
        foreach ($rateApiData['rateRequest']['products'] as $product) {
            if (!in_array($product['instanceId'], $sessionExpiredIds)
            || (isset($sessionExpiredIds[0]) && empty($sessionExpiredIds[0]))) {
                $arrProducts[] = $product;
            } else {
                $arrExpiredInstansIds[] = $product['instanceId'];
            }
        }
        $this->rebuildOrUnsetExpiredIntanceId($sessionExpiredIds, $arrExpiredInstansIds);
        $rateApiData['rateRequest']['products'] = $arrProducts;

        return $rateApiData;
    }

    /**
     * Exclude expired product from rate qoute request
     *
     * @param array $products
     * @return array
     */
    public function exludeExpiredProductFromRateQuoteRequest($products)
    {
        $arrProducts = [];
        $arrExpiredInstansIds = [];
        $sessionExpiredIds = $this->customerSession->getExpiredItemIds()
        ? $this->customerSession->getExpiredItemIds() : [];
        foreach ($products as $product) {
            if (!in_array($product['instanceId'], $sessionExpiredIds)
            || (isset($sessionExpiredIds[0]) && empty($sessionExpiredIds[0]))) {
                $arrProducts[] = $product;
            } else {
                $arrExpiredInstansIds[] = $product['instanceId'];
            }
        }
        $this->rebuildOrUnsetExpiredIntanceId($sessionExpiredIds, $arrExpiredInstansIds);

        return $arrProducts;
    }

    /**
     * Rebuild or unset expired instance id in customer session
     *
     * @param array $sessionExpiredIds
     * @param array $arrExpiredInstansIds
     * @return void
     * @throws \Exception
     */
    public function rebuildOrUnsetExpiredIntanceId($sessionExpiredIds, $arrExpiredInstansIds)
    {
        $remainingExpiredIds = array_intersect($sessionExpiredIds, $arrExpiredInstansIds);
        $marketplaceExpiredProductsIds = $this->expiredProducts->execute();
        $remainingExpiredIds = array_merge($remainingExpiredIds, $marketplaceExpiredProductsIds);

        if (!empty($remainingExpiredIds) && (isset($remainingExpiredIds[0]) && !empty($remainingExpiredIds[0]))) {
            $this->customerSession->setExpiredItemIds(array_unique($remainingExpiredIds));
        } else {
            $this->customerSession->unsExpiredItemIds();
        }
    }

    /**
     * Check for Zero Price Product in Rate API to build array
     *
     * @param array|null $rateApiOutput
     * @return array|bool
     */
    public function checkZeroPriceForCurrentRateApiResponse(array|null $rateApiOutput): bool|array
    {
        $productZeroPriceList = [];
        if (!empty($rateApiOutput) && isset($rateApiOutput['output']['rateQuote']['rateQuoteDetails'])) {
            $rateQuoteDetails = $rateApiOutput['output']['rateQuote']['rateQuoteDetails'];
            foreach ($rateQuoteDetails as $rateQuoteDetail) {

                if (isset($rateQuoteDetail['productLines'])) {

                    $productLines = $rateQuoteDetail['productLines'];
                    foreach ($productLines as $productLine) {

                        $priceable = $productLine['priceable'] ?? false;
                        if($priceable && $productLine['productRetailPrice'] == '0.0') {

                            $productZeroPriceList[] = $productLine['instanceId'];
                        }
                    }
                }
            }
        }

        return !empty($productZeroPriceList) ? $productZeroPriceList :  false;
    }

    /**
     * Set expired ids in session from Zero Priced Products
     *
     * @param array $productZeroPriceList
     * @return void
     */
    public function setExpiredIdInCustomerSessionFromZeroPrice($productZeroPriceList)
    {
        if (!empty($productZeroPriceList)) {
            $previoustExpiredIds = $this->customerSession->getExpiredItemIds()
                ? $this->customerSession->getExpiredItemIds() : [];
            $remainigExpiredIds = [];
            foreach ($productZeroPriceList as $expiredId) {
                if (!in_array($expiredId, $previoustExpiredIds)) {
                    $remainigExpiredIds[] = $expiredId;
                }
            }

            $this->customerSession->setExpiredItemIds(array_merge($previoustExpiredIds, $remainigExpiredIds));
        }
    }
}
