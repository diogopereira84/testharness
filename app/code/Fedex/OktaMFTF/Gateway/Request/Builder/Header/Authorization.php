<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway\Request\Builder\Header;

use Fedex\CoreApi\Gateway\Request\BuilderInterface;
use Fedex\OktaMFTF\Model\Config\Credentials as Config;
use Magento\Framework\App\RequestInterface;

class Authorization implements BuilderInterface
{
    /**
     * Separator used for basic auth
     */
    private const AUTH_SEPARATOR = ':';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        private RequestInterface $request
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject = []): array
    {
        if (empty($this->request->getParam('client_id')) || empty($this->request->getParam('client_secret'))) {
            return $buildSubject;
        }

        return array_replace_recursive($buildSubject, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(
                    $this->request->getParam('client_id')
                    . self::AUTH_SEPARATOR
                    . $this->request->getParam('client_secret')
                )
            ]
        ]);
    }
}
