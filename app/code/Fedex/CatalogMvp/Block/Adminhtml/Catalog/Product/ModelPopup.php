<?php

namespace Fedex\CatalogMvp\Block\Adminhtml\Catalog\Product;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Catalog\Helper\Image;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CustomerCanvas\Model\ConfigProvider;
use Magento\Framework\AuthorizationInterface;

class ModelPopup extends \Magento\Backend\Block\Template
{

    public const PRINT_PRODUCTS = 'Print Products';
    public const TIGER_D_233890 = 'tiger_d233890';


    /**
     * Constructor
     *
     * @param Context $context
     * @param CollectionFactory $categoryCollectionFactory
     * @param CategoryRepository $categoryRepository
     * @param CategoryFactory $categoryFactory
     * @param ProductCollection $productCollectionFactory
     * @param Image $productImage
     * @param ScopeConfigInterface $scopeConfig
     * @param CatalogMvp $catalogMvpHelper
     * @param ToggleConfig $toggleConfig
     * @param ConfigProvider $dyesubConfigprovider
     * @param array $data
     */
    public function __construct(
        Context $context,
        private CollectionFactory $categoryCollectionFactory,
        public CategoryRepository $categoryRepository,
        private CategoryFactory $categoryFactory,
        private ProductCollection $productCollectionFactory,
        public Image $productImage,
        private ScopeConfigInterface $scopeConfig,
        private CatalogMvp $catalogMvpHelper,
        protected ToggleConfig $toggleConfig,
        protected ConfigProvider $dyesubConfigprovider,
        protected AuthorizationInterface $authorization,
        protected State $appState,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getCategoryCollection()
    {
        $printProductCategory = $this->getPrintProductCategory();
        if($printProductCategory > 0) {
            $category = $this->categoryRepository->get($printProductCategory);
            return [$category];
        }
        $rooatCategoryDeatail = $this->catalogMvpHelper->getRootCategoryDetailFromStore('ondemand');
        $b2bCategoryName = $rooatCategoryDeatail['name'] ?? "B2B Root Category";
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addFieldToFilter('name', ['eq' => $b2bCategoryName]);

        foreach ($collection as $category) {

            $b2bcategiryid = $category->getId();
        }

        $category = $this->categoryRepository->get($b2bcategiryid);

        return $category->getChildrenCategories();
    }

    public function getPrintProductCategory()
    {
        return $this->getConfigurationValue('ondemand_setting/category_setting/epro_print');
    }

     /**
     *
      * @throws LocalizedException
      */

    public function getProductCollectionByCategories()
    {
        $printProductCategory = $this->getPrintProductCategory();
        if($printProductCategory > 0) {
            $printproid = $printProductCategory;
        } else {
            $rooatCategoryDeatail = $this->catalogMvpHelper->getRootCategoryDetailFromStore('ondemand');
            $b2bCategoryName = $rooatCategoryDeatail['name'] ?? "B2B Root Category";
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addFieldToFilter('name', ['eq' => $b2bCategoryName ]);

            foreach ($collection as $category) {
                $b2bcategiryid = $category->getId();
            }
            $category = $this->categoryRepository->get($b2bcategiryid);
            $subCategories = $category->getChildrenCategories();
            $printproid = '';
            foreach ($subCategories as $subCategory) {
                if ($subCategory->getName() === 'Print Products') {
                    $printproid = $subCategory->getId();
                }
            }
        }
        $subcategoryId = [];
        $categoryFactory =  $this->categoryRepository->get($printproid);
        $subcategoryId = explode(',', $categoryFactory->getAllChildren(false));
        $isNonStandardCatalogToggle = $this->getConfigurationValue(
            'environment_toggle_configuration/environment_toggle/explorers_non_standard_catalog'
        );
        // Exclude custom product category from ctc admin
        if (!$isNonStandardCatalogToggle || !$this->catalogMvpHelper->isSelfRegCustomerAdmin()) {
            $customProductCategoryId =
                $this->getConfigurationValue('ondemand_setting/category_setting/epro_print_custom_product');
            if (!empty($customProductCategoryId)) {
                $removeCustomProductCatKey = array_search($customProductCategoryId, $subcategoryId);
                if ($removeCustomProductCatKey !== false) {
                    unset($subcategoryId[$removeCustomProductCatKey]);
                }
            }
        }

        $productcollection = $this->productCollectionFactory->create();
        $productcollection->addAttributeToSelect('*');
        $productcollection->addCategoriesFilter(['in' => $subcategoryId]);

        if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D_233890)) {
            $productcollection->addFieldToFilter(
                'mirakl_mcm_product_id',
                ['or' => [
                    ['null' => true],
                    ['eq' => '']
                ]]
            );
        }

        $attributeSetId = $this->catalogMvpHelper->getAttrSetIdByName("FXOPrintProducts");
        if ($attributeSetId) {
            $productcollection->addFieldToFilter('product_attribute_sets_id', $attributeSetId);
        }
        if($this->dyesubConfigprovider->isDyeSubEnabled()){
            if (($this->appState->getAreaCode() === Area::AREA_ADMINHTML)) {
                $productcollection =  $this->dyesubConfigprovider->excludeConfigAndDyesubProductCollection($productcollection);
            }
        }
        return $productcollection;
    }

    public function getProductImage()
    {
      return $this->productImage;
    }

    /**
     * Get pending review status
     *
     * @return int
     */
    public function getCatalogPendingReviewStatus()
    {
        $data = $this->getRequest()->getParams();
        if (!empty($data['id'])) {
            return $this->catalogMvpHelper->getCatalogPendingReviewStatus($data['id']);
        }
        return 0;
    }

    /**
     * Get attribute set id by product id
     *
     * @return string
     */
    public function getAttrSetIdByProductId()
    {
        $productId = $this->getRequest()->getParam('id');
        $attributeSetName = null;

        if ($productId) {
            $attributeSetName = $this->catalogMvpHelper->getAttrSetIdByProductId($productId);
        }

        return $attributeSetName;
    }

    /**
     * Get attribute set collection
     *
     * @return string
     */
    public function getProductAttrSetCollection()
    {
        return $this->catalogMvpHelper->getProductAttrSetCollection();
    }

    /**
     * Get Configurator Url
     */
    public function getConfiguratorUrl()
    {
        return $this->scopeConfig->getValue(
            'fedex/general/configurator_url'
        );
    }
    /**
     * Get Fxo Menu Id
     */
    public function getFxoMenuId()
    {
        $fxoMenuId = null;
        $data = $this->getRequest()->getParams();
        if(isset($data['id'])) {
            $fxoMenuId =  $this->catalogMvpHelper->getFxoMenuId($data['id']);
        }
        return $fxoMenuId;
    }

    /**
     * Get Configuration value
     *
     * @param string $path
     * @return string
     */
    public function getConfigurationValue($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }
}
