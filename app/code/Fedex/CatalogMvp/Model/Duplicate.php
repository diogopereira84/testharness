<?php

namespace Fedex\CatalogMvp\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Option\Repository as OptionRepository;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\DuplicatedProductAttributesCopier;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\Store;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\Catalog\Model\Product\CopyConstructorInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use \Magento\Catalog\Model\Product\Copier;
use \Magento\Eav\Api\AttributeSetRepositoryInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;


/**
* @codeCoverageIgnore
*/

class Duplicate extends Copier
{
    /**
     * @var Option\Repository
     */
    protected $optionRepository;

    /**
     * @param CopyConstructorInterface $copyConstructor
     * @param ProductFactory $productFactory
     * @param ScopeOverriddenValue $scopeOverriddenValue
     * @param OptionRepository|null $optionRepository
     * @param MetadataPool|null $metadataPool
     * @param ProductRepositoryInterface $productRepository
     * @param DuplicatedProductAttributesCopier $attributeCopier
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        CopyConstructorInterface $copyConstructor,
        ProductFactory $productFactory,
        private ScopeOverriddenValue $scopeOverriddenValue,
        OptionRepository $optionRepository,
        MetadataPool $metadataPool,
        private ProductRepositoryInterface $productRepository,
        private DuplicatedProductAttributesCopier $attributeCopier,
        private CatalogMvp $helper,
        private AttributeSetRepositoryInterface $attributeSet,
        protected CatalogDocumentRefranceApi $catalogdocumentrefapi,
        private \Magento\Framework\App\State $state,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($copyConstructor, $productFactory, $scopeOverriddenValue, $optionRepository, $metadataPool, $productRepository, $attributeCopier);
        $this->optionRepository = $optionRepository;
    }

    /**
     * Create product duplicate
     *
     * @param Product $product
     * @return Product
     */

    public function copy(Product $product): Product
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $objectManager = ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(ScopeConfigInterface::class);
        $storeId = $scopeConfig->getValue('ondemand_setting/category_setting/b2b_default_store',ScopeInterface::SCOPE_STORE);

        /*  Regardless in what scope the product was provided,
            for duplicating we want to clone product in Global scope first */
        if ((int)$product->getStoreId() !== Store::DEFAULT_STORE_ID) {
            $product = $this->productRepository->getById($product->getId(), true,  $storeId);
        }
        /** @var Product $duplicate */
        $duplicate = $this->productFactory->create();
        $productData = $product->getData();
        $productData = $this->removeStockItem($productData);
        $duplicate->setData($productData);
        $duplicate->setOptions([]);
        $duplicate->setMetaTitle(null);
        $duplicate->setMetaKeyword(null);
        $duplicate->setMetaDescription(null);
        $duplicate->setIsDuplicate(true);
        $duplicate->setOriginalLinkId($product->getData($metadata->getLinkField()));
        $duplicate->setStatus(Status::STATUS_DISABLED);
        $duplicate->setCreatedAt(null);
        $duplicate->setUpdatedAt(null);
        $duplicate->setId(null);
        $duplicate->setStoreId($storeId);
        $duplicate->setWebsiteIds($product->getWebsiteIds());
        $duplicate->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE);
        // code added to genrate new 32 bit SKU start
        $attributeSetRepository = $this->attributeSet->get($duplicate->getAttributeSetId());
        $attributeSetValue = $attributeSetRepository->getAttributeSetName();

        if ($this->helper->isMvpCtcAdminEnable() && $attributeSetValue === 'PrintOnDemand') {
            $uUID = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
            $duplicate->setSKu($uUID);
        }
        if ($this->helper->isSelfRegCustomerAdmin() && $this->helper->isMvpSharedCatalogEnable()) {
            $uUID = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
            $duplicate->setSKu($uUID);
            $duplicate->setStatus(Status::STATUS_ENABLED);
        }
        // code added to genrate new 32 bit SKU start
        $this->copyConstructor->build($product, $duplicate);

        if ($this->state->getAreaCode() !== "adminhtml") {
            $duplicate->setUrlKey($duplicate->getSku());
        } else {
            $this->setDefaultUrl($product, $duplicate, $storeId);
            $this->attributeCopier->copyProductAttributes($product, $duplicate);
        }
        $this->setStoresUrl($product, $duplicate, $storeId);
        $this->optionRepository->duplicate($product, $duplicate);

        // code added to create refernce
        if ($this->helper->isProductPodEditAbleById($product->getId())) {
            $documentIds = $this->catalogdocumentrefapi->getDocumentId($product->getExternalProd());
            foreach ($documentIds as $documentId) {
                $this->catalogdocumentrefapi->addRefernce($documentId, $product->getId());
            }
            $this->catalogdocumentrefapi->updateProductDocumentEndDate($product, 'customer_admin');
        }

        return $duplicate;
    }

    private function removeStockItem(array $productData): array
    {
        if (isset($productData[ProductInterface::EXTENSION_ATTRIBUTES_KEY])) {
            $extensionAttributes = $productData[ProductInterface::EXTENSION_ATTRIBUTES_KEY];
            if (null !== $extensionAttributes->getStockItem()) {
                $extensionAttributes->setData('stock_item', null);
            }
        }
        return $productData;
    }

    private function setDefaultUrl(Product $product, Product $duplicate, $onDemandStoreId) : void
    {
        $duplicate->setStoreId($onDemandStoreId);
        $resource = $product->getResource();
        $attribute = $resource->getAttribute('url_key');
        $productId = $product->getId();
        $urlKey = $resource->getAttributeRawValue($productId, 'url_key', $onDemandStoreId);
        do {
            $urlKey = $this->modifyUrl($urlKey);
            $duplicate->setUrlKey($urlKey);
        } while (!$attribute->getEntity()->checkAttributeUniqueValue($attribute, $duplicate));
        $duplicate->setData('url_path', null);
        $resource->save($duplicate);
    }

    private function setStoresUrl(Product $product, Product $duplicate, $onDemandStoreId) : void
    {
        $storeIds = $duplicate->getStoreIds();
        $productId = $product->getId();
        $productResource = $product->getResource();
        $attribute = $productResource->getAttribute('url_key');
        $duplicate->setData('save_rewrites_history', false);
        foreach ($storeIds as $storeId) {
            $useDefault = !$this->scopeOverriddenValue->containsValue(
                ProductInterface::class,
                $product,
                'url_key',
                $storeId
            );
            if ($useDefault) {
                continue;
            }

            $duplicate->setStoreId($storeId);
            $urlKey = $productResource->getAttributeRawValue($productId, 'url_key', $storeId);
            $iteration = 0;

            do {
                if ($iteration === 10) {
                    throw new UrlAlreadyExistsException();
                }

                $urlKey = $this->modifyUrl($urlKey);
                $duplicate->setUrlKey($urlKey);
                $iteration++;
            } while (!$attribute->getEntity()->checkAttributeUniqueValue($attribute, $duplicate));
            $duplicate->setData('url_path', null);
            $productResource->saveAttribute($duplicate, 'url_path');
            $productResource->saveAttribute($duplicate, 'url_key');
        }
        $duplicate->setStoreId($onDemandStoreId);
    }

    /**
    * @codeCoverageIgnore
    */
    private function modifyUrl(string $urlKey) : string
    {
        return preg_match('/(.*)-(\d+)$/', $urlKey, $matches)
            ? $matches[1] . '-' . ($matches[2] + 1)
            : $urlKey . '-1';
    }
}
