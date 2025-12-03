<?php

declare(strict_types=1);

namespace Fedex\Login\Plugin;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Store\Model\Store as CoreStore;
use Magento\Store\Model\StoreManagerInterface;

class Store
{
    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';

    public function __construct(
        protected SessionFactory                              $sessionFactory,
        protected StoreManagerInterface                       $storeManager,
        protected AuthHelper                                  $authHelper,
        private Session                                       $session,
        private ToggleConfig                                  $toggleConfig,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig
    ) {
    }

    /**
     * Modify Base Url value and append url extension value for commercial user.
     *
     * @param  CoreStore $subject
     * @param  string    $result
     * @param  string    $type
     * @param  boolean   $secure
     * @return string
     */
    public function afterGetBaseUrl(
        CoreStore $subject,
        $result,
        $type = null,
        $secure = null
    ) {
        static $return = [];
        $cacheIndex = $result.'|'.$type;
        if (array_key_exists($cacheIndex, $return)
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $return[$cacheIndex];
        }

        if(!$this->session->isLoggedIn()) {
            $this->session = $this->sessionFactory->create();
        }

        $customerSession = $this->session;
        $isCommercial = str_contains($_SERVER['REQUEST_URI'], '/ondemand');
        if ($isCommercial
            && $this->authHelper->isLoggedIn() && $type == "link"
            && $customerSession->getOndemandCompanyInfo() != "" && $urlExtension = $customerSession->getOndemandCompanyInfo()['company_data']['company_url_extention']
        ) {
            $result .= $urlExtension . "/";
        }

        $return[$cacheIndex] = (string) $result;
        return $return[$cacheIndex];
    }

    /**
     * Modify Url value and append url extension value for commercial user.
     *
     * @param  CoreStore $subject
     * @param  string    $result
     * @param  string    $route
     * @param  array     $params
     * @return string
     */
    public function afterGetUrl(
        CoreStore $subject,
        $result,
        $route = '',
        $params = []
    ) {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            if(!$this->session->isLoggedIn()) {
                $this->session = $this->sessionFactory->create();
            }
            $customerSession = $this->session;
        } else {
            $customerSession = $this->sessionFactory->create();
        }
        $isCommercial = str_contains($_SERVER['REQUEST_URI'], '/ondemand');
        if ($isCommercial
            && $this->authHelper->isLoggedIn()
            && $customerSession->getOndemandCompanyInfo() != "" && $urlExtension = $customerSession->getOndemandCompanyInfo()['company_data']['company_url_extention']
        ) {
            if (!str_contains($result, $urlExtension . "/")) {
                $result .= $urlExtension . "/";
            }
        }
        return (string) $result;
    }

    /**
     * Toggle for Catalog Performance Improvement Phase Two
     *
     * @return bool
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }

}
