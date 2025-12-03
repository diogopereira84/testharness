<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PageBuilderPromoBanner\ViewModel;

use Magento\Widget\Model\Widget\Instance;
use Magento\Cms\Model\BlockFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\Request\Http;
use Magento\Cms\Model\Page;
use Fedex\Delivery\Helper\Data;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class WidgetData implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    public const CMS_PAGE = 'cms_page_view';
    public const CATEGORY_PAGE = 'catalog_category_view';
    public const CONFIGURATOR_PAGE = 'configurator_index_index';
    public const CHECKOUT_PAGE = 'checkout_index_index';
    public const PROMO_BANNER_WIDGET_ID = 'header_promo_banner/promobanner_group/widget_id';

    /**
     * @var catalogMvpHelper
     */
    protected $catalogMvpHelper;

    /**
     *  Initialize dependencies.
     *
     * @param Instance $widgetInstance
     * @param BlockFactory $blockFactory
     * @param StoreManagerInterface $storeManager
     * @param FilterProvider $filterProvider
     * @param Http $request
     * @param Registry $registry
     * @param Page $cmsPage
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        protected Instance $widgetInstance,
        protected BlockFactory $blockFactory,
        protected StoreManagerInterface $storeManager,
        protected FilterProvider $filterProvider,
        protected Http $request,
        protected Page $cmsPage,
        protected Data $helper,
        protected LoggerInterface $logger,
        protected ScopeConfigInterface $scopeConfigInterface,
        CatalogMvp $catalogMvpHelper
    ) {
        $this->catalogMvpHelper = $catalogMvpHelper;
    }

    /**
     * Get Widget by id
     *
     * @return Object
     */
    public function getWidgetById()
    {
        try {
            $widgetId = $this->getPromoBannerWidgetId();
            $widgeitCollectionById = $this->widgetInstance->load($widgetId);
            return $widgeitCollectionById->getWidgetParameters();
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Promo Banner Error: ' . $e->getMessage());
        }
    }

    /**
     * Get cms block
     *
     * @param integer $blockId
     * @return CmsBlock|null|false
     */
    public function getBlock($blockId)
    {
        if ($blockId && !$this->helper->isCommercialCustomer()) {
            try {
                $status = $this->getBlockStatus();
                if (!$status) {
                    return null;
                }
                $storeId = $this->storeManager->getStore()->getId();
                $block = $this->blockFactory->create();
                $block->setStoreId($storeId)->load($blockId);
                $content = null;
                if ($block->getContent()) {
                    $content = $this->filterProvider->getBlockFilter()
                                                    ->setStoreId($storeId)
                                                    ->filter($block->getContent());
                }
                return  $content;
            } catch (\Exception $e) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Promo Banner Error: ' . $e->getMessage());
            }
        }
        return null;
    }

    /**
     * Get Promo Banner Widget Id
     *
     * @param integer $storeId
     * @return true|false
     */
    public function getPromoBannerWidgetId($storeId = null)
    {
        return $this->scopeConfigInterface
                    ->getValue(self::PROMO_BANNER_WIDGET_ID, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get block status
     *
     * @return boolean
     */
    public function getBlockStatus()
    {
        $status = true;
        if ($this->request->getFullActionName() == self::CATEGORY_PAGE) {
            $showPromoBanner = $this->catalogMvpHelper->getCurrentCategory()->getShowPromoBanner();
            if (!$showPromoBanner) {
                $status = false;
            }
        } elseif ($this->request->getFullActionName() == self::CMS_PAGE) {
            if (!$this->cmsPage->getShowPromoBanner()) {
                $status = false;
            }
        } elseif ($this->request->getFullActionName() == self::CONFIGURATOR_PAGE) {
            $status = false;
        } elseif ($this->request->getFullActionName() == self::CHECKOUT_PAGE) {
            $status = false;
        }

        return $status;
    }
}
