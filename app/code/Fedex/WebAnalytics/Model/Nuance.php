<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Model;

use Fedex\Company\Helper\Data;
use Fedex\WebAnalytics\Api\Data\NuanceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Config.
 * Provide access to Nuance database configuration.
 */
class Nuance implements NuanceInterface,ArgumentInterface
{
    public const XML_PATH_FEDEX_NUANCE_ACTIVE = 'web/nuance/nuance_active';
    public const XML_PATH_FEDEX_NUANCE_SCRIPT_CODE = 'web/nuance/script_code';

    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly SecureHtmlRenderer $secureHtmlRenderer,
        private readonly Data $companyHelper
    ) {}

    /**
     * @inheritDoc
     */
    public function isActive($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FEDEX_NUANCE_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @inheritDoc
     */
    public function getScriptCode($storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_NUANCE_SCRIPT_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @inheritDoc
     */
    public function isEnabledNuanceForCompany()
    {
        $company = $this->companyHelper->getCustomerCompany();
        $storeId = $this->getCurrentStoreId();
        return $this->isActive($storeId) || ($company && $company->getNuance());
    }

    /**
     * @inheritDoc
     */
    public function getScriptCodeWithNonce()
    {
        $storeId = $this->getCurrentStoreId();
        $nuanceScript = $this->getScriptCode($storeId);
        if ($nuanceScript)
        {
            preg_match('/<script(.*?)>/i', $nuanceScript, $matches);

            $attributesString = $matches[1];
            preg_match_all('/\s*(\w+)\s*=\s*["\'“]?([^"\'”>\s]+)["\'”]?/', $attributesString, $attributeMatches);

            $attributes = array_combine($attributeMatches[1], $attributeMatches[2]);

            return $this->secureHtmlRenderer->renderTag(
                'script',
                $attributes,
                ' ',
                false);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()?->getStoreId();
    }
}
