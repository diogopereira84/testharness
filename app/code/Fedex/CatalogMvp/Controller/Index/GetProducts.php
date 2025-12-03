<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\CategoryRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class GetProducts implements ActionInterface
{
    public const TIGER_D_233890 = 'tiger_d233890';

    public function __construct(
        private Context $context,
        private ProductCollection $productCollectionFactory,
        private CategoryFactory $categoryFactory,
        private Image $productImage,
        private ResultFactory $resultFactory,
        private ScopeConfigInterface $scopeConfig,
        protected CategoryRepository $categoryRepository,
        /**
         * @param \Magento\Framework\App\Action\Context $context
         * @param CategoryRepository $categoryRepository
         * @param ToggleConfig $toggleConfig
         */
        protected ToggleConfig $toggleConfig,
        private RequestInterface $request,
        protected CatalogMvp $catalogMvpHelper
    )
    {
    }

    /**
     * @return string
     * @codeCoverageIgnore|Resize of image is in protected fuction
     */
    public function execute()
    {
        $categoryid[] = $this->request->getParam('id');
        $category =  $this->categoryRepository->get($categoryid[0]);
        $category =  $this->categoryFactory->create()->load($categoryid);
        $subcategoryId = explode(',', $category->getAllChildren(false));
        $eproSkuProduct = $this->scopeConfig->getValue('ondemand_setting/category_setting/epro_print_skuonly_product');
        $skuCategoryIds = !empty($eproSkuProduct) ? [$eproSkuProduct]: [];
        $productcollection = $this->productCollectionFactory->create();
        $productcollection->addAttributeToSelect('*');
        $productcollection->addCategoriesFilter(['in' => $subcategoryId]);
        $productcollection->addCategoriesFilter(['nin' =>  $skuCategoryIds]);
        $html = '';
        $attributeSetId = $this->catalogMvpHelper->getAttrSetIdByName("FXOPrintProducts");
        if ($attributeSetId) {
            $productcollection->addFieldToFilter('product_attribute_sets_id', $attributeSetId);
        }
        if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D_233890)) {
            $productcollection->addFieldToFilter(
                'mirakl_mcm_product_id',
                ['or' => [
                    ['null' => true],
                    ['eq' => '']
                ]]
            );
        }
        foreach ($productcollection as $printproductcolls) {
            $productname = $printproductcolls->getName();
            if (!empty($printproductcolls)) {
                $productimage =  $this->productImage->init($printproductcolls, 'new_products_content_widget_grid')
                                        ->setImageFile($printproductcolls->getSmallImage())
                                        ->keepFrame(true)
                                        ->resize(140)
                                        ->getUrl();
            } else {
                $productimage = $this->productImage->getDefaultPlaceholderUrl('thumbnail');
            }
            if (str_contains($productimage, 'placeholder/.jpg')) {
                $productimage = str_replace('placeholder/.jpg', 'placeholder/thumbnail.jpg', $productimage);
            }
            $isCustomerCanvas = (int)$printproductcolls->getIsCustomerCanvas();
            $productUrlAttr = "";
            if($isCustomerCanvas){
                $productUrl = $printproductcolls->getProductUrl();
                $delimiter = (parse_url($productUrl, PHP_URL_QUERY)) ? '&' : '?';
                $productUrlWithParam = $productUrl . $delimiter . 'isDyeSubFromCatalog=1';
                $productUrlAttr =  'data-product-url="' . htmlspecialchars($productUrlWithParam) . '"';
            }
            $html .= '<li class="item product product-item"
            data-index-engineid="'.$printproductcolls->getProductId().'"
            data-index-id="'.$printproductcolls->getSku().'"
            data-index-name="'. $printproductcolls->getName().'"
            data-index-customerCanvas="'. $isCustomerCanvas.'"
            ' . $productUrlAttr . '>
                    <div class="product-item-info">
                        <span class="catalogmvp-product-image-container">
                            <span class="catalogmvp-product-image-wrapper">
                                <img class="catalogmvp-product-image-photo"
                                src="' .$productimage. '"
                                loading="lazy"
                                style="width: 173px;height: 146px;" alt="'.$productname. '">
                            </span>
                        </span>
                    <div class="product-name-list">
                        <p class="product name product-item-name">'.$productname .'</p>
                    </div>
                </li>';
        }
        /** @var Raw $rawResult */
        $rawResult = $this->resultFactory->create(ResultFactory::TYPE_RAW);

        return $rawResult->setContents($html);
    }
}
