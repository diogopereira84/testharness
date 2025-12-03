<?php
/**
 * @category Fedex
 * @package  Fedex_WebAnalytics
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Model;

use Fedex\WebAnalytics\Api\Data\GDLConfigInterface;
use Fedex\WebAnalytics\Model\CmsPage\PageTypeResolverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config.
 * Provide access to GDL database configuration.
 */
class GDLConfig implements GDLConfigInterface
{
    public const XML_PATH_FEDEX_GDL_ACTIVE = 'web/gdl/gdl_active';
    public const XML_PATH_FEDEX_GDL_SCRIPT_CODE = 'web/gdl/script_code';
    public const XML_PATH_FEDEX_GDL_SUBDOMAIN_PREFIX = 'web/gdl/subdomain_prefix';
    public const XML_PATH_FEDEX_GDL_PAGE_TYPES = 'web/gdl/page_types';


    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected SecureHtmlRenderer $secureHtmlRenderer,
        protected UrlProcessor $urlProcessor,
        protected PageTypeResolverInterface $pageTypeResolver
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FEDEX_GDL_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getScriptCode(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_GDL_SCRIPT_CODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getSubDomainPrefix(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_GDL_SUBDOMAIN_PREFIX,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getPageTypes(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_GDL_PAGE_TYPES,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getScriptFullyRendered(): ?string
    {
        if (!$this->isActive() || !$this->getScriptCode()) {
            return '';
        }

        $scriptCode = $this->getScriptCode();
        $domain = $this->getSubDomainPrefix();
        $pageId = $this->urlProcessor->getPageId($domain);
        $pageType = $this->pageTypeResolver->resolve();
        $pageType = $pageType ?? $this->urlProcessor->getPageType();
        $scriptWithPageId = sprintf($scriptCode, $pageId, $pageType);

        $gdlScriptNonce = '';
        $gdlScriptNonce .= $this->getFirstScript($scriptWithPageId);
        $gdlScriptNonce .= $this->getSecondScript($scriptWithPageId);

        return $gdlScriptNonce;
    }

    /**
     * @param $gdlScript
     * @return array|false
     */
    private function getFirstScript($gdlScript)
    {
        $pattern = '/<script[^>]*>(.*?)<\/script>/s';
        preg_match($pattern, $gdlScript, $matches);
        if (isset($matches[1])) {
            return $this->buildRenderTag(['type' => 'text/javascript'], $matches[1]);
        }

        return false;
    }

    /**
     * @param $gdlScript
     * @return array|false
     */
    private function getSecondScript($gdlScript)
    {
        $pattern = '/<script([^>]*)>(.*?)<\/script>/s';
        preg_match_all($pattern, $gdlScript, $matches, PREG_SET_ORDER);
        if (isset($matches[1])) {
            $attributes_string = $matches[1][1];
            preg_match_all('/(\w+)\s*=\s*([^"]*) async/', $attributes_string, $attribute_matches, PREG_SET_ORDER);
            $attributes = ['type' => 'text/javascript'];
            $attributes['async'] = true;
            foreach ($attribute_matches as $match) {
                $key = $match[1];
                $value = $match[2];
                if(strpos($value, 'http') === false && strpos($value, 'https') === false) {
                    $value = 'https:'.$value;
                }
                $attributes[$key] = $value;
            }


            return $this->buildRenderTag($attributes, ' ');
        }

        return false;
    }

    /**
     * @param $attributes
     * @param $content
     * @return string
     */
    private function buildRenderTag($attributes, $content)
    {
        return $this->secureHtmlRenderer->renderTag(
            'script',
            $attributes,
            $content,
            false
        );
    }
}
