<?php
/**
 * @category Fedex
 * @package  Fedex_EnvironmentManager
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Plugin\Frontend;

use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Framework\View\Page\Config as PageConfig;
use Fedex\EnvironmentManager\Model\Frontend\Toggle\Composite;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
/**
 * Class AddScriptToHeader.
 * Insert EnvironmentManager script before all scripts included in the HEAD tag.
 */
class AddScriptToHeader
{

    private const SCRIPT_HEADER_ARRAY_FROM = 'window.fixArrayFrom = window.Array.from;';

    /**
     * @param Composite $toggleComposite
     * @param SecureHtmlRenderer $secureHtmlRenderer
     */
    public function __construct(
        private Composite $toggleComposite,
        private SecureHtmlRenderer $secureHtmlRenderer,
        private ToggleConfig $toggleConfig
    ){
    }

    /**
     * Plugin to concat scripts together on all pages

     *
     * @param PageConfig $subject
     * @param ?string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIncludes(PageConfig $subject, ?string $result): ?string
    {
        // Initialize toggle variable with empty string and conditionally set it in one line
        $toggleCompress = $this->toggleConfig->getToggleConfigValue('techtitans_d_198297')
            ? 'window.d198297_toggle = 1;'
            : '';

        // Combine script generation and return statement to reduce variable usage
        $scriptContent = static::SCRIPT_HEADER_ARRAY_FROM . $toggleCompress;

        return $this->toggleComposite->build() .
               $this->secureHtmlRenderer->renderTag('script', ['type' => 'text/javascript'], $scriptContent, false) .
               $result;
    }
}
