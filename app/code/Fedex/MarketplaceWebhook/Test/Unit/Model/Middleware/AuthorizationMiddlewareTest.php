<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Test\Unit\Model\Middleware;

use Fedex\MarketplaceWebhook\Model\Middleware\AuthorizationMiddleware;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\IntegrationException;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AuthorizationMiddlewareTest extends TestCase
{
    protected $authorizationMiddleware;
    /**
     * @var LoggerInterface
     */
    private $loggerMock;

    /**
     * @var JsonFactory
     */
    private $jsonFactoryMock;

    /**
     * @var HttpRequest
     */
    private $requestMock;

    /**
     * @var ScopeConfigInterface
     */
    private $configInterfaceMock;

    /**
     * @var Http
     */
    private $httpResponseMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(
                [
                    'create',
                    'setData'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->createMock(HttpRequest::class);
        $this->configInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->httpResponseMock = $this->createMock(Http::class);

        $this->authorizationMiddleware = new AuthorizationMiddleware(
            $this->loggerMock,
            $this->jsonFactoryMock,
            $this->requestMock,
            $this->configInterfaceMock,
            $this->httpResponseMock
        );
    }

    /**
     * Test validateAuthorizationHeader method with valid authorization.
     *
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    public function testValidateAuthorizationHeaderWithValidAuthorization()
    {
        $authorizationHeader = 'valid_authorization';
        $expectedAuthorization = 'valid_authorization';

        $this->requestMock->expects($this->once())
            ->method('getHeader')
            ->with('Authorization')
            ->willReturn($authorizationHeader);

        $this->configInterfaceMock->expects($this->once())
            ->method('getValue')
            ->with('fedex/marketplacewebhook/authorization_code', 'store')
            ->willReturn($expectedAuthorization);

        $this->authorizationMiddleware->validateAuthorizationHeader();
    }

    /**
     * Test validateAuthorizationHeader method with invalid authorization.
     *
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    public function testValidateAuthorizationHeaderWithInvalidAuthorization()
    {
        $authorizationHeader = 'invalid_authorization';
        $expectedAuthorization = 'valid_authorization';
        $errorMessage = 'Invalid Authorization Header from Mirakl Webhook, Authorization: '.$authorizationHeader;

        $this->requestMock->expects($this->once())
            ->method('getHeader')
            ->with('Authorization')
            ->willReturn($authorizationHeader);

        $this->configInterfaceMock->expects($this->once())
            ->method('getValue')
            ->with('fedex/marketplacewebhook/authorization_code', 'store')
            ->willReturn($expectedAuthorization);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonFactoryMock);

        $this->jsonFactoryMock->expects($this->once())
            ->method('setData')
            ->with($errorMessage);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage($errorMessage);

        $this->authorizationMiddleware->validateAuthorizationHeader();
    }

    /**
     * Test sendErrorAuthenticationResponse method.
     *
     * @return void
     * @throws AuthorizationException
     */
    public function testSendErrorAuthenticationResponse(): void
    {
        $message = 'Authentication error message';

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonFactoryMock);

        $this->jsonFactoryMock->expects($this->once())
            ->method('setData')
            ->with($message);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage($message);

        $this->authorizationMiddleware->sendErrorAuthenticationResponse($message);
    }

    /**
     * Test sendErrorResponse method.
     *
     * @return void
     * @throws IntegrationException
     */
    public function testSendErrorResponse(): void
    {
        $message = 'Integration error message';

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonFactoryMock);

        $this->jsonFactoryMock->expects($this->once())
            ->method('setData')
            ->with($message);

        $this->expectException(IntegrationException::class);
        $this->expectExceptionMessage($message);

        $this->authorizationMiddleware->sendErrorResponse($message);
    }

    /**
     * Test sendSuccessResponse method.
     *
     * @return void
     */
    public function testSendSuccessResponse(): void
    {
        $message = 'Success message';

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonFactoryMock);

        $this->jsonFactoryMock->expects($this->once())
            ->method('setData')
            ->with($message);

        $result = $this->authorizationMiddleware->sendSuccessResponse($message);

        $this->assertSame($this->jsonFactoryMock, $result);
    }
}
