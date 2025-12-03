<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth\UrlBuilder;

use Magento\Framework\Url\EncoderInterface;

class Encoder implements EncoderInterface
{
    /**
     * @inheritDoc
     */
    public function encode($url): string
    {
        return strtr(trim(base64_encode($url), "="), '+/', '-_');
    }
}
