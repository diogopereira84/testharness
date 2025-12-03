<?php
declare(strict_types=1);

namespace Fedex\Catalog\Plugin\Helper\Product;

use Fedex\Catalog\Model\Config;
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\DataObject;
use Magento\Framework\View\Result\Page;

class View
{
    /**
     * @param \Magento\Framework\View\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        protected \Magento\Framework\View\Context $context,
        /**
         * Core registry
         */
        protected \Magento\Framework\Registry $coreRegistry,
        private \Magento\Framework\View\Page\Config $pageConfig,
        private Config $catalogConfig
    )
    {
    }

    /**
     * AFTER used to apply Product Name as title for the page
     *
     * @param \Magento\Catalog\Helper\Product\View $view
     * @param \Magento\Catalog\Helper\Product\View $result
     * @return \Magento\Catalog\Helper\Product\View
     */
    public function afterPrepareAndRender(
        \Magento\Catalog\Helper\Product\View $view,
        \Magento\Catalog\Helper\Product\View $result
    ) {
        $pageLayout = $this->context->getLayout();
        if ($product = $this->coreRegistry->registry('current_product')) {

            $pageMainTitleInStore = $pageLayout->getBlock('page.main.title-in-store');
            $pageMainTitleFirstParty = $pageLayout->getBlock('page.main.title-first-party');
            if ($pageMainTitleInStore) {

                $pageMainTitleInStore->setPageTitle($product->getName());
            } elseif ($pageMainTitleFirstParty) {

                $pageMainTitleFirstParty->setPageTitle($product->getName());
            }

            if ($product->getPageLayout() == 'commercial-product-full-width')
            {
                $this->pageConfig->addBodyClass('page-layout-first-party-product-full-width');
            }
        }
        return $result;
    }
}
