<?php
namespace Fedex\CatalogMvp\Plugin;

use Magento\Store\Model\StoreManagerInterface;
use Fedex\CustomizedMegamenu\Block\Html\Topmenu as ParentTopmenu;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\Session;
use Magento\Catalog\Model\CategoryFactory;

// B-1569415
class Topmenu
{
    public function __construct(
        private StoreManagerInterface $storeManagerInterface,
        private CatalogMvp $catalogMvpHelper,
        private DeliveryHelper $deliveryHelper,
        private SdeHelper $sdeHelper,
        private UrlInterface $urlInterface,
        private CategoryFactory $categoryFactory,
        private Session $customerSession
    )
    {
    }

    /* Magento 2 after plugin example */
    public function afterGetMegaMenuHtml(ParentTopmenu $subject, $results)
    {
        $isCommercialCustomer = $this->deliveryHelper->isCommercialCustomer();
        $isSdeStore = $this->sdeHelper->getIsSdeStore();
        $sharedCatName = $this->catalogMvpHelper->getCompanySharedCatName();
        if ($isCommercialCustomer && !$isSdeStore) {

            $homeUrl = $this->storeManagerInterface->getStore()->getBaseUrl();
            if ($this->customerSession->getOndemandCompanyInfo() != "" && $urlExtension = $this->customerSession->getOndemandCompanyInfo()['company_data']['company_url_extention']
            ) {
                if (!str_contains($homeUrl, $urlExtension . "/")) {
                    $homeUrl = trim($homeUrl,"/");
                    $homeUrl .= "/".$urlExtension . "/";
                }
            }
            $currentUrl = $this->urlInterface->getCurrentUrl();

            $activeClass = '';
            if (rtrim($homeUrl, '/') == rtrim($currentUrl, '/')) {
                $activeClass = 'active';
            }
            if ($sharedCatName) {
                $results = str_replace($sharedCatName, 'Shared Catalog', $results);
            }

            $results = str_replace('Browse Catalog', 'Shared Catalog', $results);

            $homeLink = '<li class="level0 nav-0 home-menu ui-menu-item '.$activeClass.'" role="presentation">
					<a href="'.$homeUrl.'" class="level-top ui-menu-item-wrapper" aria-haspopup="false" id="ui-id-0"
					data-href="'.$homeUrl.'">
					<span>Home</span>
					</a>
				</li>';
            $results = $homeLink . $results;
        }

        return $results;
    }
}
