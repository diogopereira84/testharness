<?php

namespace Fedex\CatalogMvp\Observer;

use Magento\Framework\Event\ObserverInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CategoryView implements ObserverInterface
{
    /**
     * @param CatalogMvp $catalogMvpHelper
     * @param CategoryFactory $categoryFactory
     * @param ScopeConfigInterface $scopeInterface
     * @param UrlInterface $urlInterface
     * @param LoggerInterface $logger
     * @param CategoryRepositoryInterface $categoryRepoistoryInterface
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private CatalogMvp $catalogMvpHelper,
        private CategoryFactory $categoryFactory,
        private ScopeConfigInterface $scopeInterface,
        private UrlInterface $urlInterface,
        private LoggerInterface $logger,
        protected CategoryRepositoryInterface $categoryRepositoryInterface,
        protected ToggleConfig $toggleConfig
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
        if($this->catalogMvpHelper->isMvpSharedCatalogEnable() && !$this->catalogMvpHelper->isSelfRegCustomerAdmin()) {
            $data = $observer->getRequest()->getParams();
            if(isset($data['id'])) {

                    $category = $this->categoryRepositoryInterface->get($data['id']);

                if(!$category->getIsPublish() && $category->getLevel() > 2){
                    $defaultNoRouteUrl = $this->scopeInterface->getValue(
                        'web/default/no_route',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                    $redirectUrl = $this->urlInterface->getUrl($defaultNoRouteUrl);
                    $observer->getControllerAction()
                        ->getResponse()
                        ->setRedirect($redirectUrl);
                }
            }
        }
    }
}
