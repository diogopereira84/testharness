<?php
/**
 * @category Fedex
 * @package  Fedex_WebAnalytics
 * @copyright   Copyright (c) 2025 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CSP\Plugin;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Csp\Helper\CspNonceProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Page\Config as PageConfig;

class CspNonceMetaTag
{
    /** @var string */
    protected const TIGER_D196844 = 'tiger_d196844';

    /**
     * @param CspNonceProvider $cspNonceProvider
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly CspNonceProvider $cspNonceProvider,
        private readonly RequestInterface $request,
        private readonly ToggleConfig $toggleConfig
    ) {}
    /**
     * Add script to header
     * @param PageConfig $subject
     * @param string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIncludes(PageConfig $subject, ?string $result): ?string
    {
        if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D196844) && $this?->request?->getFullActionName() === 'checkout_index_index') {
            $nonceTag = $this->cspNonceProvider->generateNonce();
            if ($nonceTag) {
                return $result . '<meta name="csp-nonce" content="' . $nonceTag . '" />';
            }
        }
        return $result;
    }
}
