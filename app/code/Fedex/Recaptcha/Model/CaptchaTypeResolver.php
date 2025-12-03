<?php
declare(strict_types=1);

namespace Fedex\Recaptcha\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\ReCaptchaUi\Model\CaptchaTypeResolverInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @inheritdoc
 */
class CaptchaTypeResolver implements CaptchaTypeResolverInterface
{
    public const XML_PATH_TYPE_FOR = 'recaptcha_frontend/type_for/';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    /**
     * @inheritdoc
     */
    public function getCaptchaTypeFor(string $key): ?string
    {
        $type = $this->scopeConfig->getValue(
            self::XML_PATH_TYPE_FOR . $key,
            ScopeInterface::SCOPE_STORE
        );
        return $type;
    }
}
