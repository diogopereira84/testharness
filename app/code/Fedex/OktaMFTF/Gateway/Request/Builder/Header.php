<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway\Request\Builder;

use Fedex\CoreApi\Gateway\Request\BuilderInterface;
use Fedex\OktaMFTF\Gateway\Request\Builder\Header\Authorization;

class Header implements BuilderInterface
{
    /**
     * Default headers used in the request
     */
    private const DEFAULT_HEADERS = [
        'Accept' => 'application/json',
        'cache-control' => 'no-cache',
        'Content-Type' => 'application/x-www-form-urlencoded',
    ];

    /**
     * @param Authorization $authorization
     */
    public function __construct(
        private Authorization $authorization
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject = []): array
    {
        return array_replace_recursive($this->authorization->build($buildSubject), [
            'headers' => self::DEFAULT_HEADERS
        ]);
    }
}
