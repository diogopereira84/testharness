<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Gateway\Request\Builder;

use Fedex\CoreApi\Gateway\Request\BuilderInterface;
use Fedex\Automation\Gateway\Request\Builder\Header\Authorization;

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
     * @var Authorization
     */
    private Authorization $authorization;

    /**
     * @param Authorization $authorization
     */
    public function __construct(
        Authorization $authorization
    ) {
        $this->authorization = $authorization;
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
