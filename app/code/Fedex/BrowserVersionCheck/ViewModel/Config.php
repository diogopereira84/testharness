<?php
/**
 * Browser version check configuration
 *
 * @category Fedex
 * @package  Fedex_BrowserVersionCheck
 */

declare(strict_types=1);

namespace Fedex\BrowserVersionCheck\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config implements ArgumentInterface
{
    private const XML_PATH_ENABLE = 'web/browser_version_check/enable';
    private const XML_PATH_HEADING = 'web/browser_version_check/heading';
    private const XML_PATH_SUBHEADING = 'web/browser_version_check/subheading';
    private const XML_PATH_CHROME_MIN_VERSION = 'web/browser_version_check/chrome_minimum_version';
    private const XML_PATH_EDGE_MIN_VERSION = 'web/browser_version_check/edge_minimum_version';
    private const XML_PATH_FIREFOX_MIN_VERSION = 'web/browser_version_check/firefox_minimum_version';
    private const XML_PATH_SAFARI_MIN_VERSION = 'web/browser_version_check/safari_minimum_version';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Retrieve browser version check configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'enable' => (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE),
            'heading' => $this->scopeConfig->getValue(self::XML_PATH_HEADING),
            'subheading' => $this->scopeConfig->getValue(self::XML_PATH_SUBHEADING),
            'chrome_minimum_version' => $this->scopeConfig->getValue(self::XML_PATH_CHROME_MIN_VERSION),
            'edge_minimum_version' => $this->scopeConfig->getValue(self::XML_PATH_EDGE_MIN_VERSION),
            'firefox_minimum_version' => $this->scopeConfig->getValue(self::XML_PATH_FIREFOX_MIN_VERSION),
            'safari_minimum_version' => $this->scopeConfig->getValue(self::XML_PATH_SAFARI_MIN_VERSION),
        ];
    }
}
