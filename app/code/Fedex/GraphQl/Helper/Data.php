<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;

class Data
{
    const XML_PATH_FUSE_EXPIRATION_PERIOD = 'oauth/access_token_lifetime/fuse_integration';

    /**
     * @param DateTimeFactory $dateTimeFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $customerSession
     */
    public function __construct(
        protected DateTimeFactory $dateTimeFactory,
        protected ScopeConfigInterface $scopeConfig,
        protected Session $customerSession
    )
    {
    }

    /**
     * Generate the expiration date of an access token in UTC+0 format
     * @return string
     */
    public function generateAccessTokenExpirationDate(): string
    {
        $dateModel = $this->dateTimeFactory->create();
        return (string) $dateModel->gmtDate(null, strtotime(
            sprintf('%s + %s hours', $dateModel->gmtDate(), $this->getFuseIntegrationTokenLifetime())
        ));
    }

    /**
     * Get fuse integration token lifetime from config.
     * @return int hours
     */
    public function getFuseIntegrationTokenLifetime(): int
    {
        $hours = $this->scopeConfig->getValue(self::XML_PATH_FUSE_EXPIRATION_PERIOD);
        return intval(is_numeric($hours) && $hours > 0 ? $hours : 0);
    }

    /**
     * Decode X-On-Behalf-Of from header and return a specific param
     * @param $key
     * @return string|null
     */
    public function getJwtParamByKey($key): ?string
    {
        $onBehalfOf = $this->customerSession->getOnBehalfOf();
        if ($onBehalfOf) {
            $jwt = explode('.', $onBehalfOf);
            if (isset($jwt[1])) {
                $jwtDecoded = json_decode(base64_decode(explode('.', $onBehalfOf)[1]), true);
            }
        }
        return $jwtDecoded[$key] ?? null;
    }
}
