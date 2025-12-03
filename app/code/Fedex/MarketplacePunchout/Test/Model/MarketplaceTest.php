<?php

namespace Fedex\MarketplacePunchout\Test\Unit\Model;

use Magento\Framework\Simplexml\Element;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplacePunchout\Model\Marketplace;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplacePunchout\Model\Xml\Builder;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\MarketplacePunchout\Model\Redirect;
use Psr\Log\LoggerInterface;
use Magento\Setup\Exception;
use SimpleXMLElement;

class MarketplaceTest extends TestCase
{
    private $marketplaceConfig;
    private $curl;
    private $xmlBuilder;
    private $redirect;
    private $logger;
    private $marketplace;

    protected function setUp(): void
    {
        $this->marketplaceConfig = $this->createMock(MarketplaceConfig::class);
        $this->curl = $this->createMock(Curl::class);
        $this->xmlBuilder = $this->createMock(Builder::class);
        $this->redirect = $this->createMock(Redirect::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->marketplace = new Marketplace(
            $this->marketplaceConfig,
            $this->curl,
            $this->xmlBuilder,
            $this->redirect,
            $this->logger
        );
    }

    public function testPunchoutWithShopsConnection()
    {
        $productSku = 'TEST123';
        $punchoutUrl = 'https://example.com/punchout';
        $xmlContent = '<?xml version="1.0"?>'.PHP_EOL.'<request><sku>'.$productSku.'</sku></request>'.PHP_EOL;
        $responseBody = '<?xml version="1.0"?><cXml><Response><Status code="200"/><PunchOutSetupResponse><StartPage><URL>https://example.com/start</URL></StartPage></PunchOutSetupResponse></Response></cXml>';

        $xmlObject = new Element($xmlContent);

        $this->marketplaceConfig->method('isEnableShopsConnection')->willReturn(true);
        $this->marketplaceConfig->method('getShopCustomAttributesByProductSku')
            ->with($productSku)
            ->willReturn(['pdp-punchout-url' => $punchoutUrl]);

        $this->xmlBuilder->method('build')->with($productSku)->willReturn($xmlObject);

        $this->curl->expects($this->once())->method('addHeader')->with('Content-type', 'application/xml');
        $this->curl->expects($this->once())->method('post')->with($punchoutUrl, $xmlContent);
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn($responseBody);

        $this->logger->expects($this->exactly(3))->method('info');

        $this->redirect->expects($this->once())
            ->method('redirect')
            ->with(true, 'https://example.com/start')
            ->willReturn($this->createMock(\Magento\Backend\Model\View\Result\Redirect::class));

        $result = $this->marketplace->punchout($productSku);

        $this->assertInstanceOf(\Magento\Backend\Model\View\Result\Redirect::class, $result);
    }

    public function testPunchoutWithoutShopsConnection()
    {
        $productSku = 'TEST123';
        $marketplaceUrl = 'https://example.com/navitor';
        $xmlContent = '<?xml version="1.0"?>'.PHP_EOL.'<request><sku>'.$productSku.'</sku></request>'.PHP_EOL;
        $responseBody = '<?xml version="1.0"?><cXml><Response><Status code="200"/><PunchOutSetupResponse><StartPage><URL>https://example.com/start</URL></StartPage></PunchOutSetupResponse></Response></cXml>';

        $xmlObject = new Element($xmlContent);

        $this->marketplaceConfig->method('isEnableShopsConnection')->willReturn(false);
        $this->marketplaceConfig->method('getNavitorUrl')->willReturn($marketplaceUrl);

        $this->xmlBuilder->method('build')->with($productSku)->willReturn($xmlObject);

        $this->curl->expects($this->once())->method('post')->with($marketplaceUrl, $xmlContent);
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn($responseBody);

        $this->logger->expects($this->exactly(3))->method('info');

        $this->redirect->expects($this->once())
            ->method('redirect')
            ->with(true, 'https://example.com/start')
            ->willReturn($this->createMock(\Magento\Backend\Model\View\Result\Redirect::class));

        $result = $this->marketplace->punchout($productSku);

        $this->assertInstanceOf(\Magento\Backend\Model\View\Result\Redirect::class, $result);
    }

    public function testPunchoutWithCurlError()
    {
        $productSku = 'TEST123';
        $xmlContent = '<?xml version="1.0"?><request><sku>'.$productSku.'</sku></request>';
        $errorBody = 'Curl error occurred';

        $xmlObject = new Element($xmlContent);

        $this->marketplaceConfig->method('isEnableShopsConnection')->willReturn(false);
        $this->xmlBuilder->method('build')->with($productSku)->willReturn($xmlObject);

        $this->curl->method('getStatus')->willReturn(500);
        $this->curl->method('getBody')->willReturn($errorBody);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($errorBody);

        $this->marketplace->punchout($productSku);
    }

    public function testPunchoutWithInvalidResponseStatus()
    {
        $productSku = 'TEST123';
        $xmlContent = '<?xml version="1.0"?><request><sku>'.$productSku.'</sku></request>';
        $responseBody = '<?xml version="1.0"?><cXml><Response><Status code="400"/></Response></cXml>';

        $xmlObject = new Element($xmlContent);

        $this->marketplaceConfig->method('isEnableShopsConnection')->willReturn(false);
        $this->xmlBuilder->method('build')->with($productSku)->willReturn($xmlObject);

        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn($responseBody);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($responseBody);

        $this->marketplace->punchout($productSku);
    }
}
