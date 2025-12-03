<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplacePunchout\Model\ProductInfo;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\MarketplacePunchout\Model\Authorization;

class ProductInfoTest extends TestCase
{
    protected $configMock;
    protected $curlMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $marketplaceAuthorizationMock;
    protected $checkoutSessionMock;
    protected $productInfo;
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(MarketplaceConfig::class);
        $this->curlMock = $this->createMock(Curl::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->marketplaceAuthorizationMock = $this->createMock(Authorization::class);
        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMarketplaceAuthToken', 'setMarketplaceAuthToken'])
            ->getMockForAbstractClass();

        $this->productInfo = new ProductInfo(
            $this->configMock,
            $this->curlMock,
            $this->loggerMock,
            $this->marketplaceAuthorizationMock,
            $this->checkoutSessionMock
        );
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testExecuteReturnsNullWhenCurlStatusIsNot200()
    {
        $brokerConfigId = '123456';
        $productSku = 'abcdefg';
        $token = 'TOKEN';

        $this->checkoutSessionMock->expects($this->once())->method('getMarketplaceAuthToken')->willReturn($token);

        $this->configMock->expects($this->once())->method('getNavitorProductInfoUrl')
            ->willReturn('https://example.com/api/product-info');

        $this->curlMock->expects($this->exactly(3))->method('getStatus')->willReturn(400);

        $result = $this->productInfo->execute($brokerConfigId, $productSku);

        $this->assertNull($result);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testExecuteReturnsResponseBodyWhenCurlStatusIs200()
    {
        $brokerConfigId = '123456';
        $productSku = 'abcdefg';
        $token = 'TOKEN';
        $responseBody = '{"product_info": "example"}';

        $this->checkoutSessionMock->expects($this->once())->method('getMarketplaceAuthToken')
            ->willReturn($token);

        $this->configMock->expects($this->once())->method('getNavitorProductInfoUrl')
            ->willReturn('https://example.com/api/product-info');

        $this->curlMock->expects($this->exactly(3))->method('getStatus')->willReturn(200);
        $this->curlMock->expects($this->once())->method('getBody')->willReturn($responseBody);

        $result = $this->productInfo->execute($brokerConfigId, $productSku);

        $this->assertSame($responseBody, $result);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testExecuteCallsSetNewTokenSessionWhenCurlStatusIs401()
    {
        $brokerConfigId = '123456';
        $productSku = 'abcdefg';
        $token = 'OLD_TOKEN';
        $newToken = 'NEW_TOKEN';

        $this->checkoutSessionMock->expects($this->once())
            ->method('getMarketplaceAuthToken')->willReturn($token);

        $this->configMock->expects($this->exactly(3))
            ->method('getNavitorProductInfoUrl')->willReturn('https://example.com/api/product-info');

        $this->curlMock->expects($this->exactly(6))
            ->method('getStatus')->willReturn(401);

        $this->marketplaceAuthorizationMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn($newToken);

        $this->checkoutSessionMock->expects($this->exactly(3))
            ->method('setMarketplaceAuthToken')
            ->with($newToken);

        $result = $this->productInfo->execute($brokerConfigId, $productSku);

        $this->assertNull($result);
    }
}
