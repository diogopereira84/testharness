<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Frontend\Toggle;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/*
 * E376312ProfileApiFailureMessaging toggle class
 */
class E376312ProfileApiFailureMessaging implements ResolverInterface
{
    public const SCRIPT_TOGGLE_ENABLE = 'window.mazegeeks_profile_api_failure_messaging = true';

    /**
     * @param ToggleConfig $toggleConfig
     * @param SecureHtmlRenderer $secureHtmlRenderer
     */
    public function __construct(
        private ToggleConfig $toggleConfig,
        private SecureHtmlRenderer $secureHtmlRenderer
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function build(): string
    {
        return $this->secureHtmlRenderer->renderTag(
            'script',
            ['type' => 'text/javascript'],
            static::SCRIPT_TOGGLE_ENABLE,
                false
        );
    }
}
