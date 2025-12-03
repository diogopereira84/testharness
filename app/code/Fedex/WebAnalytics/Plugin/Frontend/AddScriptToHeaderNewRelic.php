<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2024.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Plugin\Frontend;

use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Framework\View\Page\Config as PageConfig;
use Fedex\WebAnalytics\Api\Data\NewRelicInterface;

/**
 * Class AddScriptToHeaderNewRelic.
 * Insert Newrelic script after all scripts included in the HEAD tag.
 */
class AddScriptToHeaderNewRelic
{
    public function __construct(
        private NewRelicInterface $config,
        private SecureHtmlRenderer $secureHtmlRenderer,
    ) {}

    /**
     * @param PageConfig $subject
     * @param ?string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIncludes(PageConfig $subject, ?string $result): ?string //NOSONAR
    {
        if ($this->config->isActive() && $this->config->getScriptCode())
        {

            $newRelic = $this->config->getScriptCode();
            $newRelicNonce = '';
            $pattern = '/<script\b[^>]*>(.*?)<\/script>/is';
            preg_match_all($pattern, $newRelic, $matches);
            foreach ($matches[1] as $scriptWithoutTag) {
                $newRelicNonce .= $this->buildRenderTag($scriptWithoutTag).PHP_EOL;
            }
            return $result . $newRelicNonce;
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
