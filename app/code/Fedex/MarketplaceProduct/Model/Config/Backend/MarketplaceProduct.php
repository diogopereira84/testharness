<?php

declare (strict_types = 1);

namespace Fedex\MarketplaceProduct\Model\Config\Backend;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Collection;
use Fedex\MarketplaceToggle\Helper\Config as StoreConfig;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * MarketplaceProduct Class
 */
class MarketplaceProduct extends Value
{
    /**
     * Thrid party gallery settings
     */
    private const XML_CATALOG_THIRD_PARTY_GALLERY_SETTINGS = 'fedex/three_p_product/gallery';

    /**
     * @var ValueFactory
     */
    protected ValueFactory $_configValueFactory;

    /**
     * @var Collection variable
     */
    private $collection;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param TypeListInterface $cacheTypeList
     * @param ValueFactory $configValueFactory
     * @param Config $resourceConfig
     * @param ModuleManager $moduleManager
     * @param Collection $collectionFactory
     * @param ToggleConfig $toggleConfig
     * @param ProductResourceModel $productResourceModel
     * @param RequestInterface $request
     * @param CollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context              $context,
        Registry             $registry,
        private ScopeConfigInterface $scopeConfigInterface,
        TypeListInterface    $cacheTypeList,
        ValueFactory         $configValueFactory,
        protected Config               $resourceConfig,
        protected ModuleManager        $moduleManager,
        protected Collection           $collectionFactory,
        private ToggleConfig         $toggleConfig,
        private ProductResourceModel $productResourceModel,
        private RequestInterface     $request,
        private CollectionFactory    $categoryCollectionFactory,
        private StoreManagerInterface $storeManager,
        AbstractResource     $resource = null,
        AbstractDb           $resourceCollection = null,
        array                $data = []
    ) {
        $this->_configValueFactory = $configValueFactory;

        parent::__construct($context, $registry, $this->scopeConfigInterface, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return MarketplaceProduct
     */
    public function afterSave(): MarketplaceProduct
    {
        $is3pLayoutEnabled = (bool) $this->getData('value');
        $this->setMiraklProductCollection();
        $this->setLayoutToMiraklProducts($is3pLayoutEnabled);
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);

        return parent::afterSave();

    }

    /**
     * Get the new essendant attributes from category.
     *
     * @param ProductInterface|null $product
     * @param int|null $categoryId
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCategoryAttributes(?ProductInterface $product = null, ?int $categoryId = null): array
    {
        $categoryIds = $categoryId ?: $product->getCategoryIds();

        if (empty($categoryIds)) {
            return [];
        }

        $storeId = $this->storeManager->getStore()->getId();

        $categoryCollection = $this->categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToSelect([
                'product_listing_heading',
                'product_listing_sub_heading',
                'product_listing_banner_text',
                'product_listing_banner_icon',
                'product_detail_page_additional_information'
            ])
            ->addFieldToFilter('entity_id', ['in' => $categoryIds]);

        $categoriesData = [];
        foreach ($categoryCollection as $category) {
            $categoriesData = [
                'id' => $category->getId(),
                'product_listing_heading' => $category->getData('product_listing_heading'),
                'product_listing_sub_heading' => $category->getData('product_listing_sub_heading'),
                'product_listing_banner_text' => $category->getData('product_listing_banner_text'),
                'product_listing_banner_icon' => $category->getData('product_listing_banner_icon'),
                'product_detail_page_additional_information' => $category->getData('product_detail_page_additional_information'),
            ];
            break;
        }

        return $categoriesData;
    }

    /**
     * Set default or mirakl layout for mirakl products.
     *
     * @param $is3pLayout
     * @return void
     */
    private function setLayoutToMiraklProducts($is3pLayout)
    {
        foreach ($this->collection->getItems() as $singleProduct) {
            $singleProduct->setData('store_id', 0);
            if ($is3pLayout) {
                $this->setMiraklLayout($singleProduct);
                continue;
            }
            $this->setDefaultLayout($singleProduct);
        }
    }

    /**
     * @param $singleProduct
     * @return void
     * @throws \Exception
     */
    private function setMiraklLayout($singleProduct)
    {
        $singleProduct->setData('page_layout', StoreConfig::MIRAKL_LAYOUT_IDENTIFIER);
        $this->productResourceModel->saveAttribute($singleProduct, 'page_layout');
    }

    /**
     * @param $singleProduct
     * @return void
     * @throws \Exception
     */
    private function setDefaultLayout($singleProduct)
    {
        $singleProduct->setData('page_layout', StoreConfig::STANDARD_LAYOUT_IDENTIFIER);
        $this->productResourceModel->saveAttribute($singleProduct, 'page_layout');
    }

    /**
     * Set product collection based on mirakl products.
     *
     * @return void
     */
    protected function setMiraklProductCollection()
    {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('mirakl_mcm_product_id', array('notnull' => true));
        $this->collection = $collection;
    }

    public function get3pPdpGallerySettings(?int $store = null): string
    {
        return $this->scopeConfigInterface->getValue(
            self::XML_CATALOG_THIRD_PARTY_GALLERY_SETTINGS,
            ScopeInterface::SCOPE_STORE,
            $store
        ) ?? '';
    }
}
