<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model\Resolver;

use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Resolver\CreateAccessToken;
use Fedex\GraphQl\Model\Token\UpdateToken;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Magento\Framework\Oauth\TokenProviderInterface;
use Magento\Integration\Api\IntegrationServiceInterface as IntegrationService;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Integration\Model\Oauth\Consumer;
use Magento\Integration\Model\Integration;
use Fedex\GraphQl\Model\NewRelicHeaders;

class CreateAccessTokenTest extends TestCase
{
    /**
     * @var (\Fedex\GraphQl\Model\NewRelicHeaders & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $newRelicHeaders;
    const EXPIRES_AT = '1970-01-01 04:00:00';

    private CreateAccessToken $createAccessToken;

    private MockObject $tokenProviderMock;
    private MockObject $tokenFactoryMock;
    private MockObject $oauthServiceMock;
    private MockObject $integrationServiceMock;
    private MockObject $updateTokenMock;
    private MockObject $batchResponseMockFactory;
    private MockObject $batchResponseMock;
    private MockObject $contextMock;
    private MockObject $fieldMock;
    private MockObject $tokenMock;
    private MockObject $loggerHelperMock;
    private MockObject $validationCompositeMock;

    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->tokenProviderMock = $this->createMock(TokenProviderInterface::class);
        $this->tokenFactoryMock = $this->createMock(TokenFactory::class);
        $this->oauthServiceMock = $this->createMock(OauthServiceInterface::class);
        $this->integrationServiceMock = $this->createMock(IntegrationService::class);
        $this->updateTokenMock = $this->createMock(UpdateToken::class);
        $this->batchResponseMockFactory = $this->createMock(BatchResponseFactory::class);
        $this->batchResponseMock = $this->createMock(BatchResponse::class);
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->fieldMock = $this->createMock(Field::class);
        $this->tokenMock = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByToken'])
            ->addMethods(['getExpiresAt'])
            ->getMockForAbstractClass();        $this->loggerHelperMock = $this->createMock(LoggerHelper::class);
        $this->validationCompositeMock = $this->createMock(ValidationComposite::class);

        $this->batchResponseMockFactory->method('create')->willReturn($this->batchResponseMock);

        $this->createAccessToken = new CreateAccessToken(
            $this->createMock(RequestCommandFactory::class),
            $this->batchResponseMockFactory,
            $this->loggerHelperMock,
            $this->validationCompositeMock,
            $this->newRelicHeaders,
            $this->tokenFactoryMock,
            $this->oauthServiceMock,
            $this->tokenProviderMock,
            $this->updateTokenMock,
            $this->integrationServiceMock
        );
    }

    public function testResultResolveSuccess()
    {
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [$resolveRequestMock];

        $args['input'] = [
            'oauth_token' => 'fn8pe04nnn8gymfuvbwlffnjem4ogj6v',
            'oauth_token_secret' => 'hgfap0hgxoeggsif5063azv9gvjnsn8u',
        ];

        $response = [
            'access_token' => '6f7imbi7a6hr76nm2mfm3uhl5f4gnzqz',
            'expires_at' => self::EXPIRES_AT
        ];

        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($args);

        $this->tokenMock->expects($this->once())->method('loadByToken')->willReturnSelf();

        $this->tokenMock->expects($this->once())
            ->method('getExpiresAt')
            ->willReturn('1970-01-01 04:00:00');

        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->tokenMock);

        $this->tokenProviderMock->expects($this->once())->method('getAccessToken')->willReturn(
            ['oauth_token' => '6f7imbi7a6hr76nm2mfm3uhl5f4gnzqz']
        );

        $consumerMock = $this->getMockBuilder(Consumer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMockForAbstractClass();
        $consumerMock->expects($this->once())->method('getId')->willReturn(1);
        $this->oauthServiceMock
            ->expects($this->once())
            ->method('loadConsumer')
            ->willReturn($consumerMock);

        $integrationMock = $this->getMockBuilder(Integration::class)
            ->addMethods(['setStatus', 'findByConsumerId', 'update'])
            ->disableOriginalConstructor()
            ->getMock();
        $integrationMock->expects($this->any())->method('setStatus')->willReturnSelf();
        $integrationMock->expects($this->any())->method('findByConsumerId')->willReturnSelf();
        $integrationMock->expects($this->any())->method('update')->willReturnSelf();

        $this->integrationServiceMock
            ->expects($this->once())
            ->method('findByConsumerId')
            ->willReturn($integrationMock);


        $this->updateTokenMock->expects($this->once())->method('execute');

        $this->createAccessToken->proceed(
            $this->contextMock,
            $this->fieldMock,
            $requests,
            []
        );
    }

    public function testResultResolveException()
    {
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [$resolveRequestMock];

        $args['input'] = [
            'oauth_token' => 'fn8pe04nnn8gymfuvbwlffnjem4ogj6v',
            'oauth_token_secret' => 'hgfap0hgxoeggsif5063azv9gvjnsn8u',
        ];

        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($args);

        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->tokenMock);

        $this->tokenMock->expects($this->once())->method('loadByToken')->willThrowException(
            new \Exception("Some exception message")
        );

        $this->expectExceptionMessage('Some exception message');
        $this->expectException(GraphQlAuthenticationException::class);
        $this->createAccessToken->proceed($this->contextMock, $this->fieldMock, $requests, []);
    }

}
