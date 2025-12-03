<?php

namespace Fedex\CatalogMvp\Observer;

use Magento\Framework\Event\ObserverInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Cart\Controller\Dunc\Index as Dunc;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\CustomerGroup\Model\FolderPermission;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ProductSaveBeforeObserver implements ObserverInterface
{
    private const HAWKS_PUBLISHED_FLAG_INDEXING = 'hawks_published_flag_indexing';
    private const TECHTITANS_D_238087_FIX_FOR_PUBLISH_TOGGLE_START_DATE = 'TechTitans_D_238087_fix_for_Publish_Toggle_Start_Date';

    public function __construct(
        protected CatalogMvp $catalogMvpHelper,
        protected RequestInterface $request,
        protected Dunc $duncApi,
        protected Processor $mediaGalleryProcessor,
        protected Filesystem $filesystem,
        protected LoggerInterface $logger,
        protected CatalogDocumentRefranceApi $catalogdocumentrefapi,
        protected FolderPermission $folderPermission,
        protected ToggleConfig $toggleConfig,
        protected FileDriver $fileDriver
    ) {
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            if (!$this->catalogMvpHelper->isMvpCtcAdminEnable()) {
                return $this;
            }

            $product = $observer->getProduct();
            $postData = $this->request->getPostValue();
            $productImage = null;

            $categoryIds = $product->getCategoryIds();
            $groupIds = $this->folderPermission->getCustomerGroupIds($categoryIds);
            if (!empty($groupIds)) {
                if($this->catalogMvpHelper->isD216406Enabled()){
                    $customerGroupIds = implode(',', array_unique($groupIds));
                }else{
                    $customerGroupIds = implode(',', $groupIds);
                }
                $product->setData('shared_catalogs', $customerGroupIds);
            }

            if (isset($postData['product']['external_prod'])) {
                $extProductArray = [];
                    $extProductJson = $postData['product']['external_prod'];
                    $extProductArray = $extProductJson ? json_decode($extProductJson, true) : null;
                    $documentId = $extProductArray['contentAssociations'][0]['contentReference'] ?? "";
                    if ($documentId) {
                           // B-2421984 remove preview API calls from Catalog and any other flows
                        if ($this->catalogMvpHelper->isB2421984Enabled()) {
                            $previewImageUrl = $this->catalogdocumentrefapi->getPreviewImageUrl($documentId);
                            $extension = 'jpeg';
                            $productSku = $postData['product']['sku'];
                            $imageName = trim($documentId) . $productSku . '.' . $extension;
                            if (!isset($postData['back']) || (isset($postData['back']) && $postData['back'] != "duplicate")) {
                                $productImage = $this->saveImageToMediaFolder($previewImageUrl, $imageName);
                            }
                        } else {
                            $previewResponse = $this->catalogdocumentrefapi->curlCallForPreviewApi($documentId);
                            $base64ImageSrc = base64_encode($previewResponse);
                            $extension = 'jpeg';
                            $productSku = $postData['product']['sku'];
                            $imageName = trim($documentId) . $productSku . '.' . $extension;
                            if (!isset($postData['back']) || (isset($postData['back']) && $postData['back'] != "duplicate")) {
                                $productImage = $this->saveImageToMediaFolder($base64ImageSrc, $imageName);
                            }
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
                if(isset($extProductArray['fxoMenuId']) && isset($extProductArray['fxoProductInstance']['productConfig']['product'])) {
                    $extProductArray['fxoProductInstance']['productConfig']['product']['userProductName'] = $postData['product']['name'];
                    $externalProd = json_encode(($extProductArray['fxoProductInstance']['productConfig']['product']));
                    $product->setData('external_prod',$externalProd);
                } else if(isset($extProductArray['userProductName'])) {
                    $extProductArray['userProductName'] = $postData['product']['name'];
                    $externalProd = json_encode($extProductArray);
                    $product->setData('external_prod',$externalProd);
                }
            }

            if (isset($postData["product"]["attribute_set_id"])) {
                $attributeSetId = $postData["product"]["attribute_set_id"];
                $isAttributeSetPrintOnDemand = $this->catalogMvpHelper->isAttributeSetPrintOnDemand($attributeSetId);

                if ($isAttributeSetPrintOnDemand) {
                    $product->setVisibility(1);
                }
            }

	    if (isset($postData['extraconfiguratorvalue']['custom_start_date'])
                && isset($postData['extraconfiguratorvalue']['customertimezone'])) {
                $customerTimezone = $postData['extraconfiguratorvalue']['customertimezone'];
                $productStartDate = $postData['extraconfiguratorvalue']['custom_start_date'];
                $productEndDate = $postData['extraconfiguratorvalue']['custom_end_date'];
                if (isset($productStartDate) && $productStartDate) {
                    $productStartDate = $this->catalogMvpHelper
                    ->convertTimeIntoPSTWithCustomerTimezone($productStartDate, $customerTimezone);
                    $product->setStartDatePod($productStartDate);
                }
                if (isset($productEndDate) && $productEndDate) {
                    $productEndDate = $this->catalogMvpHelper
                    ->convertTimeIntoPSTWithCustomerTimezone($productEndDate, $customerTimezone);
                    $product->setEndDatePod($productEndDate);
                }
            }
            if (isset($postData['product'])) {
                $productId = $postData['product']['stock_data']['product_id'] ?? "";
                if ($productId == "") {
                    $createdAt = date("Y-m-d H:i:s");
                    $product->setProductCreatedDate($createdAt);
                } else {
                    $product->setProductCreatedDate($product->getCreatedAt());
                }
                $updatedAt = date("Y-m-d H:i:s");
                $attributesetId = $postData['product']['attribute_set_id'] ?? "";
                $sharedCatalogIds = $postData['product']['shared_catalog'];
                $sharedCatalogIds = is_array($sharedCatalogIds)
                    ? implode(',', $sharedCatalogIds)
                    : ($sharedCatalogIds ?? '');

                $product->setProductUpdatedDate($updatedAt);
                $product->setProductAttributeSetsId($attributesetId);
                $product->setData('shared_catalogs', $sharedCatalogIds);

            }

            if (!empty($this->catalogMvpHelper->toggleD202288()) && isset($postData['product']['end_date_pod'])) {
                $productEndDate = $postData['product']['end_date_pod'];
                if (empty($productEndDate)) {
                    $product->setEndDatePod(null);
                } else {
                    $prodEndDate = $this->catalogMvpHelper->convertTimeIntoPST($productEndDate);
                    $product->setEndDatePod($prodEndDate);
                }
            }

            $publishedFlagToggle = $this->toggleConfig->getToggleConfigValue(self::HAWKS_PUBLISHED_FLAG_INDEXING);
            $startDateFixToggle = $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_D_238087_FIX_FOR_PUBLISH_TOGGLE_START_DATE);
            
            if ($publishedFlagToggle && $startDateFixToggle && isset($postData['product'])) {
                $currentTime = $this->catalogMvpHelper->getCurrentPSTDateAndTime();
                $startDatePod = $product->getStartDatePod();
                
                if (empty($startDatePod) || $startDatePod <= $currentTime) {
                    $product->setPublished(1);
                }
            }

        } catch (\Exception $error) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' Error saving the image: ' . $error->getMessage());
        }

        return $this;
    }

    /*save image to destination directory*/
    public function saveImageToMediaFolder($imageData, $imageName)
    {
        try {
           $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $fileName = 'catalog/product/' . $imageName;
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
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' Error saving the image: ' . $error->getMessage());
        }
        return false;
    }
}
