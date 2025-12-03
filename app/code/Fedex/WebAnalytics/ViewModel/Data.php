<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2023.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;

/**
 * Data Helper
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Data implements ArgumentInterface
{
    const ENABLE = 'fedex/confirmit/enabled';
    const MIDFLOW_SCRIPT = 'fedex/confirmit/midflow_script';

    /**
     * @param Http $request
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param \Fedex\Company\Helper\Data $companyHelper
     * @param SecureHtmlRenderer $secureHtmlRenderer
     */
    public function __construct(
        protected Http $request,
        protected ScopeConfigInterface $scopeConfig,
        protected StoreManagerInterface $storeManager,
        protected \Fedex\Company\Helper\Data $companyHelper,
        private SecureHtmlRenderer $secureHtmlRenderer
    )
    {
    }

    /**
     * Get Forsta configuration
     */
    public function getForstaConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get current store id
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getStoreId();
    }

    /**
     * check if Forsta configuration enable or disable
     * @return boolean
     */
    public function isEnabledForsta()
    {
        $company = $this->companyHelper->getCustomerCompany();
        $storeId = $this->getCurrentStoreId();
        return $this->getForstaConfig(self::ENABLE, $storeId) || ($company && $company->getForsta());
    }

    /**
     * Get Forsta Midflow Script
     * @return string
     */
    public function getMidflowScript()
    {
        $storeId = $this->getCurrentStoreId();
        $forstaScript = $this->getForstaConfig(self::MIDFLOW_SCRIPT, $storeId);
        if ($forstaScript) {
            $forstaScript = preg_replace('/<!--.*?-->/s', '', $forstaScript);
            preg_match('/<script(.*?)>/i', $forstaScript, $matches);

            $attributesString = $matches[1];
            preg_match_all('/(\w+)\s*=\s*[\'"]([^\'"]+)[\'"]/', $attributesString, $attributeMatches);

            $keys = $attributeMatches[1];
            $keys[] = 'type';
            $keys[] = 'async';

            $values = $attributeMatches[2];
            $values[] = 'text/javascript';
            $values[] = true;

            $attributes = array_combine($keys, $values);

            return $this->secureHtmlRenderer->renderTag(
                'script',
                $attributes,
                ' ',
                false);
        }

        return false;
    }

    /**
     * Get Full Action Name
     *
     * @return string $fullActionName
     */
    public function getFullActionName()
    {
        return $this->request->getFullActionName();
    }
}
