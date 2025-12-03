<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2025.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\ViewModel;

use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\WebAnalytics\Api\Data\AppDynamicsConfigInterface;
use Fedex\WebAnalytics\Api\Data\ContentSquareInterface;
use Fedex\WebAnalytics\Api\Data\GDLConfigInterface;
use Fedex\WebAnalytics\Api\Data\NewRelicInterface;
use Fedex\WebAnalytics\Api\Data\NuanceInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * GeneralWebAnalytics ViewModel
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class GeneralWebAnalytics implements ArgumentInterface
{
    const TOGGLE_D219954 = 'tiger_d219954';
    const TIGER_D239988 = 'tiger_d239988';

    /**
     * @param ToggleConfig $toggleConfig
     * @param CompanyHelper $companyHelper
     * @param AppDynamicsConfigInterface $appDynamicsConfig
     * @param ContentSquareInterface $contentSquareInterface
     * @param GDLConfigInterface $GDLConfigInterface
     * @param NewRelicInterface $newRelicInterface
     * @param NuanceInterface $nuanceInterface
     */
    public function __construct(
        protected ToggleConfig               $toggleConfig,
        protected CompanyHelper              $companyHelper,
        protected AppDynamicsConfigInterface $appDynamicsConfig,
        protected ContentSquareInterface     $contentSquareInterface,
        protected GDLConfigInterface         $GDLConfigInterface,
        protected NewRelicInterface          $newRelicInterface,
        protected NuanceInterface            $nuanceInterface
    ) {}

    /**
     * Return is Toggle D-219954 Enabled
     *
     * @return bool|int
     */
    public function isToggleD219954Enabled()
    {
        return $this->toggleConfig->getToggleConfigValue(self::TOGGLE_D219954);
    }

    /**
     * Return is Toggle D-239988 Enabled
     *
     * @return bool|int
     */
    public function isToggleD239988Enabled()
    {
        return $this->toggleConfig->getToggleConfigValue(self::TIGER_D239988);
    }
    /**
     * Is GDL Enabled for current session
     *
     * @return int
     */
    public function isGDLEnabledForCurrentSession()
    {
        $company = $this->companyHelper->getCustomerCompany();
        $adobeAnalyticsForCompany = $company && $company->getAdobeAnalytics();
        if ((!$this->GDLConfigInterface->isActive() && !$adobeAnalyticsForCompany)
            || !$this->GDLConfigInterface->getScriptCode()) {
            return false;
        }

        return true;
    }
}
