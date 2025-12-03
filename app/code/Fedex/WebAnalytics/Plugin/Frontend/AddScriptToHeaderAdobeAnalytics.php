<?php
/**
 * @category Fedex
 * @package  Fedex_WebAnalytics
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Plugin\Frontend;

use Fedex\Company\Helper\Data;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Framework\View\Page\Config as PageConfig;
use Fedex\WebAnalytics\Api\Data\GDLConfigInterface;
use Fedex\WebAnalytics\Model\UrlProcessor;
use Fedex\WebAnalytics\Model\CmsPage\PageTypeResolverInterface;
use Magento\Framework\Escaper;

/**
 * Class AddScriptToHeaderAdobeAnalytics.
 * Insert GDL script after all scripts included in the HEAD tag.
 */
class AddScriptToHeaderAdobeAnalytics
{

    /**
     * @param GDLConfigInterface $configInterface
     * @param UrlProcessor $urlProcessor
     * @param PageTypeResolverInterface $pageTypeResolver
     * @param Data $companyHelper
     * @param SecureHtmlRenderer $secureHtmlRenderer
     * @param Escaper $escaper
     */
    public function __construct(
        protected GDLConfigInterface $configInterface,
        private UrlProcessor $urlProcessor,
        private PageTypeResolverInterface $pageTypeResolver,
        protected Data $companyHelper,
        private SecureHtmlRenderer $secureHtmlRenderer,
        private Escaper $escaper
    )
    {
    }

    /**
     * Add script to header
     * @param PageConfig $subject
     * @param string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIncludes(PageConfig $subject, ?string $result): ?string
    {
        $company = $this->companyHelper->getCustomerCompany();
        if ((!$this->configInterface->isActive() && !($company && $company->getAdobeAnalytics()))
            || !$this->configInterface->getScriptCode()) {
            return $result;
        }

        $scriptCode = $this->configInterface->getScriptCode();
        $domain = $this->configInterface->getSubDomainPrefix();
        $pageId = $this->escaper->escapeJs($this->urlProcessor->getPageId($domain));
        $pageType = $this->pageTypeResolver->resolve();
        $pageType = $pageType ?? $this->urlProcessor->getPageType();
        $scriptWithPageId = sprintf($scriptCode, $pageId, $pageType);

        $gdlScriptNonce = '';
        $gdlScriptNonce .= $this->getFirstScript($scriptWithPageId);
        $gdlScriptNonce .= $this->getSecondScript($scriptWithPageId);

        return $result . $gdlScriptNonce;
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
