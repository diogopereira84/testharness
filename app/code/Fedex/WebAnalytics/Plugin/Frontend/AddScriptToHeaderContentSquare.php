<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2023.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Plugin\Frontend;

use Fedex\Company\Helper\Data;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Framework\View\Page\Config as PageConfig;
use Fedex\WebAnalytics\Api\Data\ContentSquareInterface;

/**
 * Class AddScriptToHeaderContentSquare.
 * Insert Contentsquare script after all scripts included in the HEAD tag.
 */
class AddScriptToHeaderContentSquare
{
    /**
     * @param ContentSquareInterface $config
     * @param Data $companyHelper
     * @param SecureHtmlRenderer $secureHtmlRenderer
     */
    public function __construct(
        protected ContentSquareInterface $config,
        protected Data $companyHelper,
        private SecureHtmlRenderer $secureHtmlRenderer
    )
    {
    }

    /**
     * @param PageConfig $subject
     * @param ?string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIncludes(PageConfig $subject, ?string $result): ?string //NOSONAR
    {
        $company = $this->companyHelper->getCustomerCompany();
        if (($this->config->isActive() || ($company && $company->getContentSquare())) && $this->config->getScriptCode())
        {

            $contentSquare = $this->config->getScriptCode();
            $contentSquareNonce = '';
            $pattern = '/<script\b[^>]*>(.*?)<\/script>/is';
            preg_match_all($pattern, $contentSquare, $matches);
            foreach ($matches[1] as $scriptWithoutTag) {
                $contentSquareNonce .= $this->buildRenderTag($scriptWithoutTag).PHP_EOL;
            }
            return $result . $contentSquareNonce;
        }
        return $result;
    }

    /**
     * @param $content
     * @return string
     */
    private function buildRenderTag($content)
    {
        return $this->secureHtmlRenderer->renderTag(
            'script',
            ['type' => 'text/javascript'],
            $content,
            false
        );
    }
}
