<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway\Request\Token;

use Fedex\CoreApi\Gateway\Request\BuilderInterface;
use Fedex\OktaMFTF\Model\Config\Credentials as config;

class Body implements BuilderInterface
{
    /**
     * @var config
     */
    private config $config;

    /**
     * @param config $credentialsConfig
     */
    public function __construct(
        config $credentialsConfig
    ) {
        $this->config = $credentialsConfig;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject = []): array
    {
        if (empty($this->config->getGrantType()) || empty($this->config->getScope())) {
            return $buildSubject;
        }
        $buildSubject['body'] = "grant_type={$this->config->getGrantType()}&scope={$this->config->getScope()}";
        return $buildSubject;
    }
}
