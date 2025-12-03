<?php
namespace Fedex\Ondemand\Observer\Frontend;

use Fedex\LiveSearch\Api\Data\SharedCatalogSkipInterface;
use Fedex\Ondemand\Api\Data\ConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Model\Config;

class BlockBefore implements ObserverInterface
{
    /**
     * B-2107362 SGC Tab Name Updates Toggle Xpath
     */
    protected const SGC_TAB_NAME_UPDATES = 'sgc_b_2107362';

    protected $printProduct = 'Print Products';

    public function __construct(
        protected \Magento\Framework\View\Page\Config $pageConfig,
        protected CatalogMvp $catalogMvpHelper,
        protected StoreManagerInterface $storeManager,
        protected SharedCatalogSkipInterface $sharedCatalogSkip,
        protected ConfigInterface $ondemandConfig,
        protected ToggleConfig $toggleConfig,
        public Config $config
    )
    {
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $storeCode = $this->storeManager->getStore()->getCode();
        if ($storeCode != "ondemand") {
            return $this;
        }

        /** @var \Magento\Framework\App\Action\Action $controller */
        $fullActionName = $observer->getFullActionName();
        $layout = $observer->getEvent()->getLayout();
        $isUpdateTabNameToggleEnabled = (bool) $this->toggleConfig->getToggleConfigValue(
            self::SGC_TAB_NAME_UPDATES
        );

        if ($fullActionName == 'cms_index_index') {
            if ($isUpdateTabNameToggleEnabled) {
                $tabNameTitle = $this->config->getHomepageTabNameValue();
            } else {
                $tabNameTitle = $this->config->getOndemandHomepageTabNameValue();
            }
            $this->pageConfig->getTitle()->set(__($tabNameTitle));

            return $this;
        }

        if ($fullActionName == 'catalog_category_view') {
            /** @var \Magento\Catalog\Model\Category $category */
            $category =  $this->catalogMvpHelper->getCurrentCategory();
            $catId = $category->getId();
            $catName = $category->getName();
            if ($catId == $this->ondemandConfig->getB2bPrintProductsCategory()) {
                $category->setName($catName);
                if ($category) {
                    if ($catName == 'Print Products' && $isUpdateTabNameToggleEnabled) {
                        $tabNameTitle = $this->config->getBrowsePrintProductsTabNameValue();
                        $this->pageConfig->getTitle()->set(__($tabNameTitle));
                    } else {
                        $this->pageConfig->getTitle()->set(__($catName));
                    }

                    if($layout->getBlock('page.main.title')) {
                        $layout->getBlock('page.main.title')->setPageTitle($catName);
                    }
                }
            }

            $browseCatId = $this->catalogMvpHelper->getCompanySharedCatId();

            if (strpos(strtolower($category->getName()), 'browse catalog') !== false
             || $browseCatId && $browseCatId == $category->getId()) {
                if ($isUpdateTabNameToggleEnabled) {
                    $tabNameTitle = $this->config->getFedexSharedCatalogTabNameValue();
                } else {
                    $tabNameTitle = $this->config->getSharedCatalogTabNameValue();
                }
                $this->pageConfig->getTitle()->set(__($tabNameTitle));
                
                if ($layout->getBlock('page.main.title')) {
                    $pageNameTitle = $this->config->getSharedCatalogTabNameValue();
                    $layout->getBlock('page.main.title')->setPageTitle($pageNameTitle);
                }
            }

            $breadcrumbsBlock = $layout->getBlock('breadcrumbs');
            if ($breadcrumbsBlock) {
                $crumbs = $breadcrumbsBlock->getCacheKeyInfo();
                $crumbs = base64_decode($crumbs['crumbs']);
                $crumbs = json_decode($crumbs, true);
                foreach ($crumbs as $key => $crumb) {
                    if (!$this->sharedCatalogSkip->getLivesearchProductListingEnable() &&
                        strpos($crumb['label'], $this->printProduct) !== false) {
                            $crumb['label'] = $this->printProduct;
                    }
                    $breadcrumbsBlock->addCrumb(
                        $key,
                        $crumb
                    );
                }
            }
        }
        return $this;
    }
}
