<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model\Xml\AuthRequestBuilder;

use Magento\Framework\Simplexml\Element;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplacePunchout\Model\Xml\AuthRequestBuilder\Request;
use Magento\Framework\Simplexml\ElementFactory;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;

class RequestTest extends TestCase
{
    protected $config;
    protected $request;
    // phpcs:disable
    private const EXPECTED_XML = '<?xml version="1.0"?>'.PHP_EOL.
'<Request><AuthRequest><Credential domain="NetworkId"><Identity>accountNumber</Identity></Credential></AuthRequest></Request>'.PHP_EOL;
    // phpcs:enable

    protected function setUp(): void
    {
        $xmlFactory = $this->createMock(ElementFactory::class);
        $xml = new Element('<Request/>');
        $xmlFactory->method('create')
            ->willReturn($xml);

        $this->config = $this->createMock(MarketplaceConfig::class);

        $this->request = new Request($xmlFactory, $this->config);
    }

    public function testBuildReturnsValidXml()
    {
        $this->config->expects($this->once())->method('getAccountNumber')->willReturn('accountNumber');
        $result = $this->request->build()->asXML();
        $this->assertEquals($result, self::EXPECTED_XML);
    }
}
