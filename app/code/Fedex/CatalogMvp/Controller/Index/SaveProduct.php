<?php

/**
 * Copyright © fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Controller\Index;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\SelfReg\Model\Config as SelfRegConfig;
use Fedex\CustomerCanvas\Model\ConfigProvider;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Customer\Model\SessionFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Api\WebhookInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Helper\EmailHelper;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use \Magento\Framework\Stdlib\DateTime\DateTime;

class SaveProduct implements ActionInterface
{
    public const PRINT_ON_DEMAND = 'PrintOnDemand';
    public const PENDING_REVIEW_FLAG = 'is_pending_review';
    public const EXPLORERS_NON_STANDARD_CATALOG = 'explorers_non_standard_catalog';
    public $isFromEdit = false;
    protected FileDriver $fileDriver;
    public const TECH_TITANS_B2559087_CONFIGURABLE_TOAST_MESSAGE = 'tech_titans_B_2559087';

    /**
     * Execute SaveProduct Controller
     *
     * @param SetFactory $attributeSetFactory
     * @param ProductFactory $productFactory
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param Processor $mediaGalleryProcessor
     * @param Filesystem $filesystem
     * @param CatalogMvp $catalogMvpHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param SessionFactory $sessionFactory
     * @param ProductManagementInterface $productSharedCatalogManagement
     * @param CategoryLinkManagementInterface $categoryLink
     * @param WebhookInterface $webhookInterface
     * @param ProductRepository $productRepository
     * @param ProductAction $productAction
     * @param CatalogDocumentRefranceApi $catalogdocumentrefapi
     * @param ToggleConfig $toggleConfig
     * @param EmailHelper $catalogMvpEmailHelper
     * @param CategoryRepository $categoryRepository
     * @param RequestInterface $requestInterface
     * @param FileDriver $fileDriver
     * @param SelfRegConfig $selfRegConfig
     */
    public function __construct(
        protected SetFactory $attributeSetFactory,
        protected ProductFactory $productFactory,
        protected LoggerInterface $logger,
        protected JsonFactory $resultJsonFactory,
        protected Processor $mediaGalleryProcessor,
        protected Filesystem $filesystem,
        protected CatalogMvp $catalogMvpHelper,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected SharedCatalogRepositoryInterface $sharedCatalogRepository,
        protected SessionFactory $sessionFactory,
        protected ProductManagementInterface $productSharedCatalogManagement,
        protected CategoryLinkManagementInterface $categoryLink,
        protected WebhookInterface $webhookInterface,
        protected ProductRepository $productRepository,
        protected ProductAction $productAction,
        protected CatalogDocumentRefranceApi $catalogdocumentrefapi,
        protected ToggleConfig $toggleConfig,
        protected EmailHelper $catalogMvpEmailHelper,
        protected CategoryRepository $categoryRepository,
        protected RequestInterface $requestInterface,
        protected SelfRegConfig $selfRegConfig,
        protected ConfigProvider $configProvider,
        protected DateTime $date,
        FileDriver $fileDriver
    ) {
        $this->fileDriver                     = $fileDriver;
    }

    /**
     * Execute method
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $isToggleEnable = $this->catalogMvpHelper->isMvpSharedCatalogEnable();
        $isAdminUser = $this->catalogMvpHelper->isSharedCatalogPermissionEnabled();
        $productPostData = $this->requestInterface->getParams();
        $json = $this->resultJsonFactory->create();
        $isNonStandardCatalogToggle = $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_NON_STANDARD_CATALOG);
        $customerSessionId = null;
        $currentCategoryIds = [];

        if ($isToggleEnable && $isAdminUser && !empty($productPostData)) {

                $customerSessionId = $this->catalogMvpHelper->getCustomerSessionId();
                $this->logger->info(
                    __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                    . ' Catalog Mvp Save Product Data Start Processing: '. json_encode($productPostData)
                );

            $currentDateTime = date("Y-m-d H:i:s");
            $currentTime = $this->catalogMvpHelper->convertTimeIntoPST($currentDateTime);
            $productStartDate = $productEndDate = $categoryUrl = $categoryUrl = null;
            $productAttributeSetId = $this->getProductAttributeSetId();
            $productSku = $this->generate32BitSKU();
            $customerSession = $this->sessionFactory->create();
            $customerSession->setFromMvpProductCreate(true);
            $customerTimezone = $productPostData["customerTimeZone"] ?? "";
            $productName = $productPostData["productName"] ?? "";
            $productStartDate = $productPostData["productStartDate"] ?? null;
            $productNoEndDate = ($productPostData["noStartAnEndDate"] == "true") ? true : false;
            $productDescription = $productPostData["productDescription"] ?? "";
            $productTag = $productPostData["productTag"] ?? "";
            $customizationfields = $productPostData["customizationfields"] ?? "";
            $customizable = false;
            if (isset($productPostData["customizable"]) && $productPostData["customizable"] == "true") {
                $customizable = true;
            }
            $externalProd = $productPostData["externalProd"] ?? "";
            if ($externalProd) {
                $prodata = json_decode($externalProd, true);
                $prodata['userProductName'] = $productName;
                if($this->configProvider->isDyeSubEnabled()) {
                    $vendorOptions = $productPostData["vendorOptions"] ?? "";
                    if (!empty($vendorOptions)) {
                        $vendorOptions = json_decode($vendorOptions,true);
                        $vendorOptions['designCreationTime'] = $this->date->date('Y-m-d H:i:s');
                        $prodata['vendorOptions'] = [$vendorOptions];
                    }
                }
                $externalProd = json_encode($prodata);
            }
            $productPrice = $productPostData["productPrice"] ?? "0.0";
            $productCategory = $productPostData["productCategory"] ?? "[]";
            $fxoMenuId = $productPostData["fxoMenuId"] ?? "";
            $isCustomerCanvas = false;
            if(!empty($fxoMenuId) && $this->configProvider->isDyeSubEnabled()){
                $originalProduct = $this->productRepository->get($fxoMenuId);
                $isCustomerCanvas = $originalProduct->getIsCustomerCanvas();
            }
            $productId = $productPostData["productId"] ?? null;
            if ($productId) {
                $this->isFromEdit = true;
            }

            if (!$productNoEndDate) {
                $productEndDate = $productPostData["productEndDate"] ?? null;
            }

            if ($productCategory && !is_array($productCategory)) {
                $currentCategoryIds = json_decode((string) $productCategory, true);
            } elseif (!empty($productCategory) && is_array($productCategory)) {
                $currentCategoryIds = $productCategory;
            }

            if (is_array($currentCategoryIds) && !empty($currentCategoryIds)) {
                $categoryId = end($currentCategoryIds);
                $categoryObj = $this->categoryRepository->get($categoryId);
                $categoryUrl = $categoryObj->getUrl();
            }

            $publish = true;
            if ($productStartDate) {
                $productStartDate =
                $this->catalogMvpHelper->convertTimeIntoPSTWithCustomerTimezone($productStartDate, $customerTimezone);
                if ($currentTime < $productStartDate) {
                    $publish = false;
                }


                    $this->logger->info(
                        __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                        . ' Catalog Mvp check publish from start date ' . $publish
                    );

            }

            if ($productEndDate) {
                $productEndDate =
                $this->catalogMvpHelper->convertTimeIntoPSTWithCustomerTimezone($productEndDate, $customerTimezone);
                if ($productEndDate < $currentTime) {
                    $publish = false;
                }


                    $this->logger->info(
                        __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                        . ' Catalog Mvp check publish from end date ' . $publish
                    );

            }

            $url = preg_replace('#[^0-9a-z]+#i', '-', $productName);
            $urlKey = strtolower($url);
            $urlKey = $urlKey . "-" . rand(11111, 99999);


                $this->logger->info(
                    __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                    . ' Catalog Mvp url key generated: ' . $urlKey
                );


            $product = empty($this->isFromEdit) ? $this->productFactory->create()
             : $this->productRepository->getById($productId);

            $product->setName($productName);
            if ($this->isFromEdit) {

                    $this->logger->info(
                        __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId . ' Catalog Mvp with edit flow'
                    );

                $this->productAction->updateAttributes([$productId], ['name' => $productName], 0);
            } else {
                $product->setStoreId(0);
                $product->setSku($productSku);

                    $this->logger->info(
                        __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                        . ' Catalog Mvp set product sku in new product creation flow: ' . $productSku
                    );

            }

            $product->setCatalogDescription($productDescription);
            $product->setRelatedKeywords($productTag);
            $product->setAttributeSetId($productAttributeSetId);
            $product->setStatus(1);
            $product->setVisibility(1);
            $product->setUrlKey($urlKey);
            $product->setTaxClassId(0);
            $product->setTypeId('simple');
            $product->setPrice($productPrice);
            $product->setData('pod2_0_editable', 1);
            $product->setData('start_date_pod', $productStartDate);
            $product->setData('end_date_pod', $productEndDate);
            if($isCustomerCanvas){
                $product->setData('is_customer_canvas', $isCustomerCanvas);
            }


            if ($this->toggleConfig->getToggleConfigValue('tiger_d215859')) {
                $product->setData(self::PENDING_REVIEW_FLAG, 0);
            }

            if ($customizationfields) {
                $product->setData('customization_fields', $customizationfields);
                $customizable = $this->isCustomizableProduct($customizationfields);

                    $this->logger->info(
                        __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                        . ' Catalog Mvp check if product is customizable ' . $customizable
                    );

            }

            /**
             * D-167762 value of customize_search_action should be "true"
             * for all products with attribute set PrintOnDemand
             */
            $product->setData('customize_search_action', true);
            $product->setData('customizable', $customizable);
            $product->setExternalProd($externalProd);
            $product->setPublished($publish);
            $product->setWebsiteIds([1]);
            $product->setStockData(
                [
                    'use_config_manage_stock' => 0,
                    'is_in_stock' => 1,
                    'qty' => 9999,
                    'manage_stock' => 0,
                    'use_config_notify_stock_qty' => 0,
                ]
            );
            $product = $this->setProductImage($externalProd, $product, $productSku);

                $this->logger->info(
                    __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                    . ' Catalog Mvp after product image set'
                );


            try {
                if ($this->toggleConfig->getToggleConfigValue('explorers_d186292_fix')) {
                    // B-1899728 Catalog Item Ready for Review Email
                    if ($isNonStandardCatalogToggle && !$prodata['priceable']) {
                        $product->setPublished(false);
                        $product->setData(self::PENDING_REVIEW_FLAG, 1);
                        $product->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
                        $this->logger->info(
                            __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                            . ' Catalog Mvp set new item pending review flag 1 for non standard catalog flow'
                        );
                    }
                    // End Send Ready for Review Email

                    if ($this->isFromEdit) {
                        $updated_at = $currentDateTime;
                        $attributeset_Id = $product->getAttributeSetId();
                        $this->logger->info(
                            __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                            . ' Catalog Mvp edit item set for live search product attribute data'
                        );

                        if (!$this->toggleConfig->getToggleConfigValue('explorers_d_190199_fix')) {
                            $product->setStoreId(0);
                        }

                        $product->setName($productName);
                        $product->setPrice($productPrice);
                        $product->setData('pod2_0_editable', 1);
                        $product->setData('start_date_pod', $productStartDate);
                        $product->setData('end_date_pod', $productEndDate);
                        $product->setExternalProd($externalProd);
                        $product->setPublished($publish);
                        $product->setProductUpdatedDate($updated_at);
                        $product->setProductAttributeSetsId($attributeset_Id);
                    } else {
                        $created_at = $currentDateTime;
                        $updated_at = $currentDateTime;
                        $attributeset_Id = $productAttributeSetId;
                        $this->logger->info(
                            __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                            . ' Catalog Mvp new item set for live search product attribute data'
                        );
                        $product->setProductCreatedDate($created_at);
                        $product->setProductUpdatedDate($updated_at);
                        $product->setProductAttributeSetsId($attributeset_Id);
                    }

                    $customerGroupId = $customerSession->getCustomer()->getGroupId();
                    $sharedCatalogsIds = [];
                    if (!$this->isFromEdit) {
                        $this->logger->info(
                            __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                            . ' Catalog Mvp set product into shared catalogs start'
                        );
                        $this->searchCriteriaBuilder->addFilter("customer_group_id", $customerGroupId);
                        $searchCriteria = $this->searchCriteriaBuilder->create();
                        $sharedCatalogs = $this->sharedCatalogRepository->getList($searchCriteria)->getItems();
                        foreach ($sharedCatalogs as $sharedCatalog) {
                            if (!in_array($sharedCatalog->getId(), $sharedCatalogsIds)) {
                                $sharedCatalogsIds[] = $sharedCatalog->getId();
                            }
                        }

                        // Save Shared Catalog in custom attribute for Live Search
                        if($this->catalogMvpHelper->isD216406Enabled()){
                            $sharedCatalogIds = $sharedCatalogsIds ? implode(',', array_unique($sharedCatalogsIds)) : null;
                        }else{
                            $sharedCatalogIds = $sharedCatalogsIds ? implode(',', $sharedCatalogsIds) : null;
                        }
                        $product->setData('shared_catalogs', $sharedCatalogIds);

                        $this->logger->info(
                            __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                            . ' Catalog Mvp set product into shared catalogs end'
                        );
                    }
                    if ($isNonStandardCatalogToggle && !$prodata['priceable']) {
                        $product->setData('folder_path', $categoryUrl);
                        $product->setData('added_by', $customerSession->getCustomerCompany());
                    }

                    $product->getResource()->save($product);

                    $this->logger->info(
                        __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                        . ' Catalog Mvp Product Saved Successfully.'
                    );

                    if ($isNonStandardCatalogToggle && !$prodata['priceable']) {
                        $customerEmail = !empty($customerSession->getCustomer()->getSecondaryEmail())
                            ? $customerSession->getCustomer()->getSecondaryEmail()
                            : $customerSession->getCustomer()->getEmail();

                        $productData['product_name'] = $product->getName();
                        $productData['product_id'] = $product->getId();
                        $productData['folder_path'] = $categoryUrl;
                        $productData['item_name'] = $productName;
                        $productData['added_by'] = $customerSession->getCustomer()->getName();
                        $productData['special_instruction'] = $this->catalogMvpEmailHelper->getSpecialInstruction(
                            $prodata
                        );
                        $productData['company_id'] = $customerSession->getCustomerCompany();
                        $productData['customer_email'] = $customerEmail;
                        $this->catalogMvpEmailHelper->sendReadyForReviewEmail($productData);
                    }

                    $this->catalogMvpHelper->setProductVisibilityValue($product, $product->getAttributeSetId());

                    // code added to create reference id
                    $documentIds = $this->catalogdocumentrefapi->getDocumentId($product->getExternalProd());
                    foreach ($documentIds as $documentId) {
                        $this->catalogdocumentrefapi->addRefernce($documentId, $product->getId());
                    }

                    $this->logger->info(
                        __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                        . ' Catalog Mvp extended document life before'
                    );

                    // added asynchronous extended document life
                    $this->catalogdocumentrefapi->extendDocLifeQueueProccess($product);

                    $this->logger->info(
                        __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                        . ' Catalog Mvp extended document life after'
                    );

                    if (!$this->isFromEdit) {
                        if (!empty($sharedCatalogsIds)) {
                            foreach ($sharedCatalogsIds as $sharedCatalogId) {
                                $this->productSharedCatalogManagement->assignProducts($sharedCatalogId, [$product]);
                            }
                        }
                        $this->categoryLink->assignProductToCategories($productSku, $currentCategoryIds);
                        $this->logger->info(
                            __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                            . ' Catalog Mvp product assigned into categories: '
                            . json_encode($currentCategoryIds, JSON_PRETTY_PRINT)
                        );
                        $customerSession->unsFromMvpProductCreate();
                    }
                } else {


                        $product->getResource()->save($product);


                    if ($this->toggleConfig->getToggleConfigValue('techtitans_D190199_fix')) {
                        $cacheDisabledProductsId = $customerSession->getCacheDisableProductsID() ?? [];
                        $cacheDisabledProductsId[
                            $product->getId()
                        ] = time();
                        $customerSession->setCacheDisableProductsID(
                            $cacheDisabledProductsId
                        );
                    }

                    // B-1899728 Catalog Item Ready for Review Email
                    if ($isNonStandardCatalogToggle && !$prodata['priceable']) {
                        $customerEmail = !empty($customerSession->getCustomer()->getSecondaryEmail())
                         ? $customerSession->getCustomer()->getSecondaryEmail()
                          : $customerSession->getCustomer()->getEmail();


                            $this->logger->info(
                                __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                                . ' Catalog Mvp set product value for non standard catalog start'
                            );


                        $product->setPublished(false);
                        $product->setData(self::PENDING_REVIEW_FLAG, 1);
                        $product->setData('folder_path', $categoryUrl);
                        $product->setData('added_by', $customerSession->getCustomerCompany());
                        $product->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
                        $productData['product_name'] = $product->getName();
                        $productData['folder_path'] = $categoryUrl;
                        $productData['item_name'] = $productName;
                        $productData['added_by'] = $customerSession->getCustomer()->getName();
                        $productData['special_instruction'] = $this->catalogMvpEmailHelper->getSpecialInstruction($prodata);
                        $productData['product_id'] = $product->getId();
                        $productData['company_id'] = $customerSession->getCustomerCompany();
                        $productData['customer_email'] = $customerEmail;
                        $product->setData('folder_path', $categoryUrl);
                        $product->setData('added_by', $customerSession->getCustomerCompany());
                        $this->catalogMvpEmailHelper->sendReadyForReviewEmail($productData);

                            $this->logger->info(
                                __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                                . ' Catalog Mvp set product value for non standard catalog end'
                            );

                    }
                    // End Send Ready for Review Email

                    $created_at = $product->getCreatedAt();
                    $updated_at = $product->getUpdatedAt();
                    $attributeset_Id = $product->getAttributeSetId();
                    $this->catalogMvpHelper->setProductVisibilityValue($product, $product->getAttributeSetId());

                    if ($this->isFromEdit) {

                            $this->logger->info(
                                __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                                . ' Catalog Mvp edit set live search product attribute data'
                            );

                        $product->setStoreId(0);
                        $product->setName($productName);
                        $product->setPrice($productPrice);
                        $product->setData('pod2_0_editable', 1);
                        $product->setData('start_date_pod', $productStartDate);
                        $product->setData('end_date_pod', $productEndDate);
                        $product->setExternalProd($externalProd);
                        $product->setPublished($publish);
                        $product->setProductUpdatedDate($updated_at);
                        $product->setProductAttributeSetsId($attributeset_Id);

                            $product->getResource()->save($product);

                    } else {

                            $this->logger->info(
                                __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                                . ' Catalog Mvp new item set live search product attribute data'
                            );

                        $product->setProductCreatedDate($created_at);
                        $product->setProductUpdatedDate($updated_at);
                        $product->setProductAttributeSetsId($attributeset_Id);

                            $product->getResource()->save($product);

                    }

                    // code added to create reference id
                    $documentIds = $this->catalogdocumentrefapi->getDocumentId($product->getExternalProd());
                    foreach ($documentIds as $documentId) {
                        $this->catalogdocumentrefapi->addRefernce($documentId, $product->getId());
                    }
                    //added asynchronous extended document life
                    $this->catalogdocumentrefapi->extendDocLifeApiSyncCall($product);


                        $this->logger->info(
                            __METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId
                            . ' Catalog Mvp document life extend after'
                        );


                    $customerGroupId = $customerSession->getCustomer()->getGroupId();
                    if (!$this->isFromEdit) {
                        $this->searchCriteriaBuilder->addFilter("customer_group_id", $customerGroupId);
                        $searchCriteria = $this->searchCriteriaBuilder->create();
                        $sharedCatalogs = $this->sharedCatalogRepository->getList($searchCriteria)->getItems();
                        $sharedCatalogsIds = [];

                        foreach ($sharedCatalogs as $sharedCatalog) {
                            if (!in_array($sharedCatalog->getId(), $sharedCatalogsIds)) {
                                $sharedCatalogsIds[] = $sharedCatalog->getId();
                            }
                        }

                        foreach ($sharedCatalogsIds as $sharedCatalogId) {
                            $this->productSharedCatalogManagement->assignProducts($sharedCatalogId, [$product]);
                        }

                        // Save Shared Catalog in custom attribute for Live Search
                        if($this->catalogMvpHelper->isD216406Enabled()){
                            $sharedCatalogIds = $sharedCatalogsIds ? implode(',', array_unique($sharedCatalogsIds)) : null;
                        }else{
                            $sharedCatalogIds = $sharedCatalogsIds ? implode(',', $sharedCatalogsIds) : null;
                        }

                        $product->setData('shared_catalogs', $sharedCatalogIds);


                        $product->getResource()->save($product);

                        $this->categoryLink->assignProductToCategories($productSku, $currentCategoryIds);
                        $customerSession->unsFromMvpProductCreate();
                    }
                }

                $productActivityDescription = "CREATE";
                if ($this->isFromEdit) {
                    $productActivityDescription = "UPDATE";
                }

                $this->catalogMvpHelper->insertProductActivity($product->getId(), $productActivityDescription);

                $message = __("Item has been added to your shared catalog. The item will be available shortly.");

                $toastToggleEnabled = $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_B2559087_CONFIGURABLE_TOAST_MESSAGE);
                if ($toastToggleEnabled) {
                    // Get dynamic message from configuration or use default
                    $configMessage = $this->selfRegConfig->getAddCatalogItemMessage();
                    $message = (!empty($configMessage) && trim($configMessage) !== '')
                        ? __($configMessage)
                        : __("Item has been added to your shared catalog. The item will be available shortly.");
                }

                if ($isNonStandardCatalogToggle && empty($prodata['priceable'])) {
                    $message = __("Catalog item has been submitted for review by a FedEx Office team member.");
                }

                if ($this->isFromEdit) {
                    $message = __("Item has been edited.");
                }

                $response = [
                    "success" => true,
                    "msg" => $message,
                    "product_sku" => $productSku,
                    "categoryUrl" => $categoryUrl,
                ];

                $requestData[] = [
                    "sku" => $productSku,
                    "customer_group_id" => $customerGroupId,
                    "shared_catalog_id" => null
                ];
                $this->webhookInterface->addProductToRM($requestData);

                if ($fxoMenuId != "") {
                    $this->catalogMvpHelper->updateFxoMenuId($product->getId(), $fxoMenuId);
                }
            } catch (\Exception $error) {
                $this->logger->critical(
                    __METHOD__ . ':' . __LINE__ . ' Error encounter while product save : ' . $error->getMessage()
                );
                $response = [
                    "success" => false,
                    "error" => __("Something went wrong while saving product. Please try again later."),
                    "product_sku" => $productSku,
                    "categoryUrl" => $categoryUrl,
                ];
            }
        } else {
            $response = [
                "success" => false,
                "error" => __("Something went wrong while saving product. Please try again later."),
                "product_sku" => "",
                "categoryUrl" => "",
            ];
        }

        $json->setData($response);
        return $json;
    }

    /**
     * Generate32BitSKU Method
     *
     * @return string
     */
    public function generate32BitSKU()
    {
        $productSku = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        return $productSku;
    }

    /**
     * GetProductAttributeSetId Method
     *
     * @return int
     */
    public function getProductAttributeSetId()
    {
        $attributeSetModel = $this->attributeSetFactory->create();
        $attributeSet =  $attributeSetModel->getCollection()
            ->addFieldToFilter('attribute_set_name', self::PRINT_ON_DEMAND)
            ->setPageSize(1)
            ->getFirstItem();

        if ($attributeSet && $attributeSet->getId()) {
            return $attributeSet->getId();
        }

        return false;
    }

    /**
     * Set product image method
     *
     * @return boolean
     */
    public function setProductImage($postData, $product, $productSku)
    {
        if ($postData) {
            $postData = json_decode($postData, true);
            $productImage = false;
            try {
                if (isset($postData['contentAssociations'])) {
                    $documentId = isset($postData['contentAssociations'][0]['contentReference'])
                     ? $postData['contentAssociations'][0]['contentReference'] : null;
                    if ($documentId) {
                        // B-2421984 remove preview API calls from Catalog and any other flows

                        if ($this->catalogMvpHelper->isB2421984Enabled()) {
                            $previewImageUrl = $this->catalogdocumentrefapi->getPreviewImageUrl($documentId);
                            $extension = 'jpeg';
                            $imageName = trim($documentId) . $productSku . '.' . $extension;
                            $productImage = $this->saveImageToMediaFolder($previewImageUrl, $imageName);
                        } else {
                            $previewResponse = $this->catalogdocumentrefapi->curlCallForPreviewApi($documentId);
                            $base64ImageSrc = base64_encode($previewResponse);
                            $extension = 'jpeg';
                            $imageName = trim($documentId) . $productSku . '.' . $extension;
                            $productImage = $this->saveImageToMediaFolder($base64ImageSrc, $imageName);
                        }
                    }
                    if ($productImage && !is_null($product)) {
                        $this->mediaGalleryProcessor->addImage(
                            $product,
                            $productImage,
                            ['image', 'small_image', 'thumbnail'],
                            false,
                            false
                        );
                    }
                }
            } catch (\Exception $error) {
                $this->logger->critical(
                    __METHOD__ . ':' . __LINE__ .' Error saving the image: ' . $error->getMessage()
                );
            }
        }

        return $product;
    }

    /**
     * Save image to media folder method
     *
     * @return boolean|string
     */
    public function saveImageToMediaFolder($imageData, $imageName)
    {
        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $fileName = 'catalog/product/' . str_replace(' ', '', $imageName);
            $filePath = $mediaDirectory->getAbsolutePath($fileName);
            if (!file_exists($filePath)) {
                // B-2421984 remove preview API calls from Catalog and any other flows
                if ($this->catalogMvpHelper->isB2421984Enabled()) {
                    $imageContent = $this->fileDriver->fileGetContents($imageData);
                    if ($imageContent === false) {
                        $this->logger->critical(
                            __METHOD__ . ':' . __LINE__ .' Failed to download image from: ' . $imageData
                        );
                        return false;
                    }
                    $mediaDirectory->writeFile($filePath, $imageContent);
                    return $fileName;
                } else {
                    $mediaDirectory->writeFile($filePath, base64_decode($imageData));
                    return $fileName;
                }
            }
        } catch (\Exception $error) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ' Error saving the image: ' . $error->getMessage()
            );
        }

        return false;
    }

    /**
     * Check Custom Data Value
     *
     * @param string $customizeFields
     *
     * @return boolean
     */
    public function isCustomizableProduct($customizeFields)
    {
        $customizeFields = json_decode((string) $customizeFields, true);
        if (!empty($customizeFields)) {
            foreach ($customizeFields as $customizeField) {
                if (isset($customizeField['formFields'])) {
                    foreach ($customizeField['formFields'] as $field) {
                        if (isset($field['label'])) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
