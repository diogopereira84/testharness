<?php
/**
 * @category    Fedex
 * @package     Fedex_CoreApi
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CoreApi\Test\Gateway\Http;

use Fedex\CoreApi\Gateway\Http\Transfer;
use Fedex\CoreApi\Gateway\Http\TransferInterface;
use PHPUnit\Framework\TestCase;

class TransferTest extends TestCase
{
    private string $method = 'POST';
    private array $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];
    private array $body = [
        'data' => [
            'data' => 'data'
        ]
    ];
    private string $uri = '/';
    private array $params = [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'body' => '{"data":{"data":"data"}}'
    ];

    /**
     * @var Transfer
     */
    private Transfer $transfer;

    protected function setUp(): void
    {
        $this->transfer = new Transfer([
            TransferInterface::METHOD => $this->method,
            TransferInterface::HEADERS => $this->headers,
            TransferInterface::BODY => $this->body,
            TransferInterface::URI => $this->uri,
            TransferInterface::PARAMS => $this->params
        ]);
    }

    public function testGetParams()
    {
        $this->assertEquals($this->params, $this->transfer->getParams());
    }

    public function testGetMethod()
    {
        $this->assertEquals($this->method, $this->transfer->getMethod());
    }

    public function testGetHeaders()
    {
        $this->assertEquals($this->headers, $this->transfer->getHeaders());
    }

    public function testGetBody()
    {
        $this->assertEquals($this->body, $this->transfer->getBody());
    }

    public function testGetUri()
    {
        $this->assertEquals($this->uri, $this->transfer->getUri());
    }
}
