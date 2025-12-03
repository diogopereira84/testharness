<?php
declare(strict_types=1);

namespace Fedex\CSP\Block;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Csp\Helper\CspNonceProvider;
use Magento\Framework\View\Element\Template;

class NonceMetaTag extends Template
{
    /** @var string */
    protected const TIGER_D196844 = 'tiger_d196844';

    /**
     * @param Template\Context $context
     * @param CspNonceProvider $cspNonceProvider
     * @param ToggleConfig $toggleConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private CspNonceProvider $cspNonceProvider,
        private ToggleConfig $toggleConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function _toHtml(): string
    {
        if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D196844)) {
            return '';
        }
        return parent::_toHtml();
    }

    public function getNonceMetatag()
    {
        return $this->cspNonceProvider->generateNonce();
    }
}
