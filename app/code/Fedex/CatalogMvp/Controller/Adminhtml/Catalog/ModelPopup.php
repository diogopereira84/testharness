<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CustomerCanvas\Model\ConfigProvider;

class ModelPopup extends \Magento\Backend\App\Action
{
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param CategoryRepository $categoryRepository
     * @param ToggleConfig $toggleConfig
     */
     protected $toogleConfig;
    private ProductCollection $_productCollectionFactory;
    public const TIGER_D_233890 = 'tiger_d233890';


    public function __construct(
        Context $context,
        ProductCollection $productCollectionFactory,
        private CategoryFactory $categoryFactory,
        private CategoryRepository $categoryRepository,
        private Image $productImage,
        ResultFactory $resultFactory,
        private Raw $rawResult,
        private ToggleConfig $toggleConfig,
        protected CatalogMvp $catalogMvpHelper,
        protected ConfigProvider $dyesubConfigprovider
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->resultFactory = $resultFactory;
        parent::__construct($context);
    }

    /**
     * @return string
     * @codeCoverageIgnore|Resize of image is in protected fuction
     */
    public function execute()
    {
        $categoryid[] = $this->getRequest()->getParam('id');
        $category =  $this->categoryRepository->get($categoryid[0]);
        $subcategoryId = explode(',', $category->getAllChildren(false));
        $productcollection = $this->_productCollectionFactory->create();
        $productcollection->addAttributeToSelect('*');
        $productcollection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
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
        if($this->dyesubConfigprovider->isDyeSubEnabled()) {
            $productcollection =  $this->dyesubConfigprovider->excludeConfigAndDyesubProductCollection($productcollection);
        }

        $html = '';
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

        $html .= '<li class="item product product-item"
        data-index-engineid="'.$printproductcolls->getProductId().'"
        data-index-id="'.$printproductcolls->getSku().'"
        data-index-name="'. $printproductcolls->getName().'">
                <div class="product-item-info">
                    <span class="product-image-container">
                        <span class="product-image-wrapper">
                            <img class="product-image-photo"
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
