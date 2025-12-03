<?php
/**
 * @category  Fedex
 * @package   Fedex_AllPrintProducts
 * @copyright Copyright (c) 2023 Fedex.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\AllPrintProducts\Block;

use Fedex\MarketplaceCheckout\Helper\Data as ToggleHelperData;
use Fedex\MarketplaceProduct\Model\Config\Backend\MarketplaceProduct;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\CategoryRepository;

class AllPrintProducts extends Template
{

    public const SUB_HEADING = 'allprintproducts/general/sub_heading';
    public const HEADING = 'allprintproducts/general/heading';
    public const CMS_CONFIG_FOR_SPECIAL_PRODUCT = 'allprintproducts/general/retail_cms_block_identifier';

    /**
     * @param ToggleHelperData $toggleHelperData
     * @param MarketplaceProduct $marketplaceProduct
     * @param Resolver $layerResolver
     * @param Context $context
     * @param CategoryRepository $categoryRepository
     * @param array $data
     */
    public function __construct(
        private ToggleHelperData $toggleHelperData,
        private MarketplaceProduct $marketplaceProduct,
        private Resolver $layerResolver,
        Template\Context $context,
        private CategoryRepository $categoryRepository,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * Get Category attributes
     *
     * @param ProductInterface|null $product
     * @param int|null $categoryId
     * @return array
     * @throws LocalizedException
     */
    public function getCategoryAttributes(?ProductInterface $product = null, ?int $categoryId = null): array
    {
        return $this->marketplaceProduct->getCategoryAttributes($product, $categoryId);
    }

    /**
     * Get the current category ID
     *
     * @return int|null
     */
    public function getCurrentCategoryId(): ?int
    {
        $category = $this->layerResolver->get()->getCurrentCategory();
        return $category ? (int) $category->getId() : null;
    }

    /**
     * Get Reference FromStore ToCategory Toggle Enabled.
     *
     * @return bool
     */
    public function isMoveReferenceFromStoreToCategoryToggleEnabled() :bool
    {
        return $this->toggleHelperData->isMoveReferenceFromStoreToCategoryToggleEnabled();
    }

    /**
     * @return mixed
     */
    public function getHeading()
    {
        return $this->_scopeConfig->getValue(self::HEADING, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getSubHeading()
    {
        return $this->_scopeConfig->getValue(self::SUB_HEADING, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string|null
     */
    public function getRetailCMSBlockConfigForSpecialProduct(): ?string
    {
        if($this->toggleHelperData->isToggleD221721Enabled()){
            $categoryId = $this->getCurrentCategoryId();
            if (!$categoryId) {
                return null;
            }
            try {
                $category = $this->categoryRepository->get($categoryId);
                $blockIdentifier = $category->getCustomAttribute('specialty_block_identifier')?->getValue();

                if ($blockIdentifier) {
                    return $blockIdentifier;
                }
                return null;
            } catch (\Exception $e) {
                $this->_logger->error('Error loading category block identifier: ' . $e->getMessage());
                return null;
            }
        }
        return $this->_scopeConfig->getValue(self::CMS_CONFIG_FOR_SPECIAL_PRODUCT, ScopeInterface::SCOPE_STORE);
    }
}
