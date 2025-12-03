<?php

/**
 * Copyright © fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Model;

use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\CatalogMvp\Api\ProductPriceSyncSubscriberInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogPriceSyncHelper;
use Magento\Company\Model\CompanyFactory;
use Magento\Framework\Serialize\Serializer\Json as serializerJson;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\CatalogMvp\Helper\EmailHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Registry;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class ProductPriceSyncSubscriber implements ProductPriceSyncSubscriberInterface
{
    private const EXPLORERS_NON_STANDARD_CATALOG = 'explorers_non_standard_catalog';


    /**
     * ProductPriceSyncSubscriber Construct
     * @param LoggerInterface $logger
     * @param serializerJson $serializerJson
     * @param CatalogPriceSyncHelper $catalogPriceSyncHelper
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param CompanyFactory $companyFactory
     * @param StoreManagerInterface $storeManager
     * @param EmailHelper $emailHelper
     * @param ToggleConfig $toggleConfig
     * @param Registry $registry
     * @return void
     */

    public function __construct(
        protected LoggerInterface $logger,
        protected serializerJson $serializerJson,
        protected CatalogPriceSyncHelper $catalogPriceSyncHelper,
        protected ProductRepositoryInterface $productRepositoryInterface,
        protected CompanyFactory $companyFactory,
        protected StoreManagerInterface $storeManager,
        private EmailHelper $emailHelper,
        protected ToggleConfig $toggleConfig,
        private Registry $registry
    )
    {
    }

    /**
     * Process queue message
     *
     * @param string $message
     */
    public function processMessage(MessageInterface $message)
    {
        try {
            $shareCatalogId = null;
            $tierPrice = [];
            $sameProductFlag = false;
            $productGlobalPrice = null;
            $rmMessage = $message->getMessage();
            $messageArray = $this->serializerJson->unserialize($rmMessage);
            foreach ($messageArray as $message) {
                try {
                    $product = $this->productRepositoryInterface->get($message['sku']);
                    $customerGroupId = $message['customer_group_id'];
                    $siteName = $this->getSiteNameByCustomerGroupId($customerGroupId);
                    $shareCatalogId = $message['shared_catalog_id'];
                    if ($product) {
                        $responsePrice = false;
                        $responseData = $this->catalogPriceSyncHelper->getProductPrice(
                            $product,
                            $siteName,
                            $customerGroupId
                        );
                        if ($responseData) {
                            $responsePrice = $responseData['output']['rate']['rateDetails'][0]['netAmount'];
                            if (!$this->catalogPriceSyncHelper->customerAdminCheck()) {
                                $priceable = $responseData['output']['rate']['rateDetails'][0]['productLines'][0]['priceable'];
                                $this->sendReadyForReviewEmail($product, $priceable, $responsePrice);
                            }
                        }

                        if ($responsePrice) {
                            $productGlobalPrice = $responsePrice;
                            if ($this->toggleConfig->getToggleConfigValue('mazegeeks_d176976') && empty($product->getProductUpdatedDate())) {
                                $product->setProductUpdatedDate($product->getUpdatedAt());
                            }
                            $webSiteId = $this->storeManager->getStore()->getWebsiteId() ?? 1;
                            $arrayKey = $customerGroupId . '-' . $webSiteId;
                            if (is_array($message) && array_key_exists('is_for_same_product', $message) && $message['is_for_same_product']) {
                                $tierPrice[$arrayKey] = [
                                    'cust_group'    => $customerGroupId,
                                    'price'         => $responsePrice,
                                    'website_price' => $responsePrice,
                                    'website_id'    => $webSiteId,
                                    'price_qty'     => 1,
                                ];
                                $sameProductFlag = true;
                            } else {
                                $tierPrices[$arrayKey] = [
                                    'cust_group'    => $customerGroupId,
                                    'price'         => $responsePrice,
                                    'website_price' => $responsePrice,
                                    'website_id'    => $webSiteId,
                                    'price_qty'     => 1,
                                ];
                                $product->setData('tier_price', $tierPrices);
                                if($this->catalogPriceSyncHelper->getCorrectPriceToggle()){
                                    $product->setPrice($responsePrice);
                                }
                                $product->save();
                            }
                        } elseif ($this->toggleConfig->getToggleConfigValue('mazegeeks_d176976') && empty($product->getProductUpdatedDate())) {
                               $product->setProductUpdatedDate($product->getUpdatedAt());
                               $product->save();
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ': Price not updated for sku :' . $message['sku'] . $e->getMessage());
                }
            }
            if ($sameProductFlag && empty(!$product)) {
                $product->setData('tier_price', $tierPrice);
                if ($this->toggleConfig->getToggleConfigValue('explorers_d_198368_fix')) {
                    $product->setData('price', $productGlobalPrice);
                }
                $product->save();
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ':Error while price sync :' . $e->getMessage());
        }
        empty($shareCatalogId) ?: $this->catalogPriceSyncHelper->quequCleanUp($shareCatalogId);
    }

    /**
     * getSiteName
     * return string
     */

    public function getSiteNameByCustomerGroupId($customerGroupID)
    {
        $siteName = null;
        $companyObjs = $this->companyFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_group_id', ['eq' => $customerGroupID]);

        if ($companyObjs) {
            foreach ($companyObjs as $companyObj) {
                $siteName = $companyObj->getData();
                $siteName = $siteName['site_name'];
            }
        } else {
            $siteName = ',';
        }
        return $siteName;
    }

    /**
     * Send Ready for review Email
     *
     * @param object $product
     * @param bool|null $priceable
     * @param float $responsePrice
     */
    public function sendReadyForReviewEmail($product, $priceable, $responsePrice)
    {
        $productData = [];
        $published = $product->getPublished();
        $productData['folder_path'] = $product->getFolderPath();
        $productData['company_id'] = $product->getAddedBy();
        $productData['product_name'] = $product->getName();
        $productData['product_price'] = $responsePrice;
        $customerDetails = $this->emailHelper->getCustomerDetails($product->getId());
        $productData['customer_name'] = !empty($customerDetails['customer_name']) ? $customerDetails['customer_name'] : '';
        $productData['customer_email'] = !empty($customerDetails['customer_email']) ? $customerDetails['customer_email'] : '';
        if (!empty($product->getFolderPath()) && !empty($product->getAddedBy())) {
            // Ready for Review email send logic
            if ($priceable && !$published && $product->getIsPendingReview() == 1 && $product->getSentToCustomer()) {
                $product->setData('is_pending_review', 2);
                $this->emailHelper->sendReadyForReviewEmailCustomerAdmin($productData);
            }

            // Ready for order email send logic
            if ($priceable && $product->getSentToCustomer() && ($product->getIsPendingReview() == 1 || $product->getIsPendingReview() == 2) && $published) {
                $product->setData('is_pending_review', 3);
                $this->emailHelper->sendReadyForOrderEmailCustomerAdmin($productData);

                $externalProd = $product->getExternalProd();
                if ($this->toggleConfig->getToggleConfigValue('explorer_nsc_replace_file') && $externalProd) {
                    $externalProductData = json_decode($externalProd, true);
                    if (!empty($externalProductData) && !empty($externalProductData['userWorkspace'])) {
                        unset($externalProductData['userWorkspace']);
                    }
                    $externalProd = json_encode($externalProductData);
                    $product->setExternalProd($externalProd);
                }
            }
        }

        return $this;
    }
}
