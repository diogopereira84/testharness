<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Model\Config;

use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\UrlInterface;

class Backend extends AbstractConfig
{
    private const PREFIX_KEY = 'fedex_okta/backend';

    /**
     * Special admin user config
     */
    public const XPATH_ROLES = 'roles';

    public function __construct(
        private Json $json,
        private JsonValidator $jsonValidator,
        ScopeConfigInterface $scopeConfig,
        CookieMetadataFactory $cookieMetadataFactory,
        CookieManagerInterface $cookieManager,
        UrlInterface $urlInterface
    ) {
        parent::__construct($scopeConfig,
         $cookieMetadataFactory,
         $cookieManager,
         $urlInterface);
    }

    /**
     * @return string
     */
    protected function getConfigPrefix(): string
    {
        return self::PREFIX_KEY;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        $roles = $this->getScopeValue(self::XPATH_ROLES);
        if($this->jsonValidator->isValid($roles)){
            return  json_decode((string)$roles, true);
        }
        return [];
    }
}
