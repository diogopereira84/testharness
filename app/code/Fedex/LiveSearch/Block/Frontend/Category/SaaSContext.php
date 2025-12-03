<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\Block\Frontend\Category;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\LiveSearch\Model\ModuleVersionReader;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Store\Model\ScopeInterface;
use Fedex\LiveSearch\Model\SharedCatalogSkip;
/**
 * @api
 */
class SaaSContext extends \Magento\LiveSearchProductListing\Block\Frontend\Category\SaaSContext
{
    public function __construct(
        Context $context,
        ServicesConfigInterface $servicesConfig,
        ProductMetadata $productMetadata,
        ModuleVersionReader $moduleVersionReader,
        CurrencyInterface $localeCurrency,
        Session $customerSession,
        Resolver $layerResolver,
        private CategoryRepository $categoryRepository,
        private SharedCatalogSkip $sharedCatalogCheck
    ) {
            parent::__construct(
            $context,
            $servicesConfig,
            $productMetadata,
            $moduleVersionReader,
            $localeCurrency,
            $customerSession,
            $layerResolver
        );
    }

    /**
     * @return string
     */
    public function getFrontendUrl(): string
    {
        return  $this->getViewFileUrl('Fedex_LiveSearch::js/search.js');
    }

    /**
     * @return CategoryInterface|Category|mixed|null
     * @throws NoSuchEntityException
     */
    public function getCategory(){
        return $this->categoryRepository->get($this->getCategoryId());
    }

    /**
     * Unset elements conditionally before html is rendered
     *
     * @return string
     */
    protected function _toHtml()
    {
        //note: we cannot do this in _prepareLayout because the elements do not exist yet
        if ($this->_scopeConfig->getValue(parent::PLP_ACTIVE, ScopeInterface::SCOPE_STORE) && $this->getCategoryDisplayMode() != 'PAGE')
        {
             if ($this->sharedCatalogCheck->checkCommercialStoreWithArea()
                 &&  $this->sharedCatalogCheck->checkIsSharedCatalogPage())
             {
                 return '';
             }
            $this->getLayout()->unsetElement('sidebar.main');
            $this->getLayout()->unsetElement('sidebar.additional');
        }

        return parent::_toHtml();
    }
}
