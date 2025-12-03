<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2023.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Model;

use Fedex\WebAnalytics\Api\Data\ContentSquareInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config.
 * Provide access to Contentsquare database configuration.
 */
class ContentSquare implements ContentSquareInterface
{
    public const XML_PATH_FEDEX_CONTENTSQUARE_ACTIVE = 'web/contentsquare/contentsquare_active';
    public const XML_PATH_FEDEX_CONTENTSQUARE_SCRIPT_CODE = 'web/contentsquare/script_code';

    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FEDEX_CONTENTSQUARE_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getScriptCode(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_CONTENTSQUARE_SCRIPT_CODE,
            ScopeInterface::SCOPE_STORE
        );
    }
}
