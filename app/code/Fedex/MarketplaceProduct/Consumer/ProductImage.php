<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Consumer;

use Fedex\MarketplaceCheckout\Helper\Data;
use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Mirakl\Mci\Helper\Product\Image;
use PHPUnit\Util\Exception;
use Psr\Log\LoggerInterface;
use Magento\SharedCatalog\Model\Management as SharedCatalogManagement;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\Entity\TypeFactory;


/**
 * Class ProductImage
 */
class ProductImage
{
    /**
     * Marketplace Image Mapping Configuration
     */
    const XML_PATH_MARKETPLACE_IMAGE_MAPPING = 'fedex/marketplace_configuration/image_mapping_list';

    private const NON_CUSTOMIZABLE_ATTRIBUTE_SET = 'FXONonCustomizableProducts';

    private array $attributeSetNameCache = [];

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Image $imageHelper
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     * @param Data $marketplaceCheckoutHelper
     * @param SharedCatalogManagement $sharedCatalogManagement
     * @param SetFactory $setFactory
     * @param TypeFactory $typeFactory
     */
    public function __construct(
        private ProductCollectionFactory    $productCollectionFactory,
        private ProductRepositoryInterface  $productRepository,
        private Image                       $imageHelper,
        private LoggerInterface             $logger,
        private ScopeConfigInterface        $scopeConfig,
        private SerializerInterface         $serializer,
        private Data                        $marketplaceCheckoutHelper,
        private SharedCatalogManagement     $sharedCatalogManagement,
        private SetFactory                  $setFactory,
        private TypeFactory                 $typeFactory
    ) {
    }

    /**
     * @param $dataReceived
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($dataReceived)
    {
        if ($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()) {
            $dataReceived = json_decode($dataReceived);

            $collection = $this->setupProductCollection($dataReceived->product_ids);

            $this->processMiraklImportImages($collection);
        }
    }

    /**
     * @param $collection
     * @return void
     */
    public function processMiraklImportImages($collection)
    {
        if ($collection->getSize()) {

            $miraklImageAttributes = $this->getMiraklImageAttributes();
            /** @var Product $product */
            foreach ($collection as $product) {
                try {
                    if ($productMediaGalleryEntries = $product->getMediaGalleryEntries()) {

                        $imageMappingArray = $this->getImageMappingArray();
                        $miraklImagesWithValue = $this->getMiraklImagesWithValue($product, $miraklImageAttributes);
                        $productImagesFilename = $this->getProductImages($productMediaGalleryEntries);
                        $miraklAttributesWithImageFilename = $this->getMiraklProductImagesCombined(
                            $miraklImagesWithValue,
                            $productImagesFilename
                        );

                        /**
                         * @var string $miraklAttributeCode
                         * @var ProductAttributeMediaGalleryEntryInterface $productImage
                         */
                        foreach ($miraklAttributesWithImageFilename as $miraklAttributeCode => $productImage) {
                            list($attributeList, $exclude) = $this->getProductImageAttributeListForMiraklImage(
                                $imageMappingArray,
                                $miraklAttributeCode
                            );
                            $productImage->setDisabled($exclude);
                            $productImage->setTypes($attributeList);
                            $productImage->setLabel($this->getImageAltText($miraklAttributeCode, $product));
                        }

                        $product->setMediaGalleryEntries($productMediaGalleryEntries);

                        $sharedCatalogId = $this->sharedCatalogManagement->getPublicCatalog()->getId();
                        $product->setData('shared_catalogs', (string)$sharedCatalogId);

                        $attributeSetName = $this->loadAttributeSetNameById((int)$product->getAttributeSetId());

                        $product->setPublished(1);

                        if ($attributeSetName === self::NON_CUSTOMIZABLE_ATTRIBUTE_SET) {
                            $product->setData('product_attribute_sets_id', $product->getAttributeSetId());
                        }

                        $this->productRepository->save($product);
                    }
                } catch (\LogicException $e) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' PRODUCT_ID: ' . $product->getId() . ' '. $e->getMessage());
                }
                catch (\Exception $e) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Get Product Image Attribute List for Mirakl Image
     *
     * @param $imageMappingArray
     * @param $imageAttributeCode
     * @return array
     */
    public function getProductImageAttributeListForMiraklImage($imageMappingArray, $imageAttributeCode): array
    {
        if(isset($imageMappingArray[$imageAttributeCode])) {
            return [$imageMappingArray[$imageAttributeCode]['image_attribute'], $imageMappingArray[$imageAttributeCode]['exclude']];
        }

        return [[], false];
    }

    /**
     * Combine Mirakl Images with Product Images
     *
     * @param $miraklImagesWithValue
     * @param $productImagesFilename
     * @return array
     */
    public function getMiraklProductImagesCombined($miraklImagesWithValue, $productImagesFilename): array
    {
        if (count(array_keys($miraklImagesWithValue)) == count(array_values($productImagesFilename))) {
            return array_combine(array_keys($miraklImagesWithValue), array_values($productImagesFilename));
        } else {
            throw new \LogicException("The image keys and the image values count don't match");
        }
        //return array_combine(array_keys($miraklImagesWithValue), array_values($productImagesFilename));
    }

    /**
     * Get Product Images
     *
     * @param ProductAttributeMediaGalleryEntryInterface[] $productMediaGalleryEntries
     * @return array
     */
    public function getProductImages(&$productMediaGalleryEntries): array
    {
        $imagesFilename = [];
        if ($productMediaGalleryEntries) {
            foreach ($productMediaGalleryEntries as $mediaGalleryEntry) {
                $imagesFilename[] = $mediaGalleryEntry;
            }
        }

        return $imagesFilename;
    }

    /**
     * Get Mirakl Images with Value
     *
     * @param $product
     * @param $miraklImageAttributes
     * @return array
     */
    public function getMiraklImagesWithValue($product, $miraklImageAttributes): array
    {
        $miraklImageWithValue = [];
        foreach ($miraklImageAttributes as $miraklImageAttribute) {

            /** @var EavAttribute $miraklImageAttribute */
            if (!$url = $product->getData($miraklImageAttribute->getAttributeCode())) {
                continue;
            }

            if ($url == Image::DELETED_IMAGE_URL) {
                $product->setData($miraklImageAttribute->getAttributeCode(), '');
                continue;
            }

            $miraklImageWithValue[$miraklImageAttribute->getAttributeCode()] = true;
        }

        return $miraklImageWithValue;
    }

    /**
     * Get Mirakl Image Attributes
     * @return EavAttribute[]
     */
    public function getMiraklImageAttributes(): array
    {
        $miraklImageAttributes = $this->imageHelper->getImagesAttributes();
        ksort($miraklImageAttributes);

        return $miraklImageAttributes;
    }

    /**
     * Will read through the image mapping configuration and return an array of the image mapping
     * @return array
     */
    public function getImageMappingArray(): array
    {
        $imageMappingArray = null;
        $imageMappingJson = $this->scopeConfig->getValue(self::XML_PATH_MARKETPLACE_IMAGE_MAPPING);
        if ($imageMappingJson) {

            $imageMapping = $this->serializer->unserialize($imageMappingJson);
            foreach ($imageMapping as $value) {

                if (isset($value['mirakl_attribute'])) {
                    $imageMappingArray[$value['mirakl_attribute']] = $value;
                }
            }
        }

        return $imageMappingArray ?? [];
    }

    /**
     * @param $productIds
     * @return mixed
     */
    public function setupProductCollection($productIds)
    {
        $collection = $this->productCollectionFactory->create()->addIdFilter($productIds);
        $collection->setStoreId(0);
        $collection->addAttributeToSelect('*');
        $collection->addMediaGalleryData();

        return $collection;
    }

    /**
     * @param $miraklAttributeCode
     * @param $product
     * @return string
     */
    public function getImageAltText($miraklAttributeCode, $product): string
    {
        return $product->getData('alt_text_' . $miraklAttributeCode) ?? '';
    }

    /**
     * @param int $attributeSetId
     * @return string|null
     */
    public function loadAttributeSetNameById(int $attributeSetId): ?string
    {
        if (isset($this->attributeSetNameCache[$attributeSetId])) {
            return $this->attributeSetNameCache[$attributeSetId];
        }

        try {
            $attributeSet = $this->setFactory->create()->load($attributeSetId);
            $name = $attributeSet->getAttributeSetName();
            $this->attributeSetNameCache[$attributeSetId] = $name;
            return $name;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ': ' . $e->getMessage());
            return null;
        }
    }
}
