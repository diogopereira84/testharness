<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth;

use Magento\Framework\App\RequestInterface;

interface PostbackValidatorInterface
{
    /**
     * OKTA request params codes
     */
    public const REQUEST_KEY_STATE    = 'okta_sso';
    public const REQUEST_KEY_ID_TOKEN = 'id_token';
    public const REQUEST_KEY_CODE = 'code';

    /**
     * @param RequestInterface $request
     * @return bool
     */
    public function validate(RequestInterface $request): bool;
}
