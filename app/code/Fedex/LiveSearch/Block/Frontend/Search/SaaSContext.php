<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\Block\Frontend\Search;
use Magento\CatalogSearch\Helper\Data;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Currency\Exception\CurrencyException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\LiveSearch\Model\ModuleVersionReader;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\Customer\Model\Session;



/**
 * @api
 */
class SaaSContext extends \Magento\LiveSearchProductListing\Block\Frontend\Search\SaaSContext
{
        /**
     * Constant For Metatitle
     */
    public const  META_TIELE ='Search Results | FedEx Office';
    /**
     * Constant For Metadescription
     */
    public const  META_DESCRIPTION ='Navigate through our search results for print products with advanced filters,'.
     ' ensuring you discover the product that fits your needs.';

    public function __construct(
        Context $context,
        ServicesConfigInterface $servicesConfig,
        ProductMetadata $productMetadata,
        ModuleVersionReader $moduleVersionReader,
        CurrencyInterface $localeCurrency,
        Session $customerSession,
        private Data $catalogSearchData
    ){
        parent::__construct($context,$servicesConfig,$productMetadata,$moduleVersionReader,$localeCurrency,$customerSession);
    }

    /**
     * @return $this|SaaSContext
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _prepareLayout()
    {
         $title = $this->getSearchQueryText();
         $this->pageConfig->getTitle()->set($title);
         $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            )->addCrumb(
                'search',
                ['label' => $title, 'title' => $title]
            );
        }

         $this->pageConfig->setMetaTitle(self::META_TIELE);
         $this->pageConfig->setDescription(self::META_DESCRIPTION);
        return $this;
    }
        /**
     * Get search query text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getSearchQueryText()
    {
        return __('Search results for: "%1"', $this->catalogSearchData->getEscapedQueryText());
    }
       /**
     * Returns config for frontend url
     *
     * @return string
     */
    public function getFrontendUrl(): string
    {
        return  $this->getViewFileUrl('Fedex_LiveSearch::js/search.js');
    }
}
