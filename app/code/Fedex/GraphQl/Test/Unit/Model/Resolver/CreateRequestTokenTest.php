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
use Fedex\GraphQl\Model\Resolver\CreateRequestToken;
use Fedex\GraphQl\Model\Token\UpdateToken;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Magento\Integration\Api\OauthServiceInterface as IntegrationOauthService;
use Magento\Integration\Model\Integration;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\Token\Provider as TokenProvider;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Integration\Model\Oauth\Consumer;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Fedex\GraphQl\Model\NewRelicHeaders;

class CreateRequestTokenTest extends TestCase
{
    /**
     * @var (\Fedex\GraphQl\Model\NewRelicHeaders & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $newRelicHeaders;
    const EXPIRES_AT = '1970-01-01 04:00:00';
    const OAUTH_TOKEN = '6f7imbi7a6hr76nm2mfm3uhl5f4gnzqz';
    const OAUTH_TOKEN_SECRET = '0zofgfp98923s0g97zreq2ig497wovtd';
    private CreateRequestToken $createRequestToken;

    private MockObject $requestCommandFactoryMock;
    private MockObject $batchResponseMockFactory;
    private MockObject $loggerHelperMock;
    private MockObject $validationCompositeMock;
    private MockObject $oauthServiceMock;
    private MockObject $tokenProviderMock;
    private MockObject $updateTokenMock;
    private MockObject $tokenFactoryMock;
    private MockObject $batchResponseMock;
    private MockObject $contextMock;
    private MockObject $fieldMock;
    private MockObject $tokenMock;

    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->requestCommandFactoryMock = $this->createMock(RequestCommandFactory::class);
        $this->batchResponseMockFactory = $this->createMock(BatchResponseFactory::class);
        $this->loggerHelperMock = $this->createMock(LoggerHelper::class);
        $this->validationCompositeMock = $this->createMock(ValidationComposite::class);
        $this->oauthServiceMock = $this->createMock(IntegrationOauthService::class);
        $this->tokenProviderMock = $this->createMock(TokenProvider::class);
        $this->updateTokenMock = $this->createMock(UpdateToken::class);
        $this->tokenFactoryMock = $this->createMock(TokenFactory::class);
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->fieldMock = $this->createMock(Field::class);
        $this->tokenMock = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByToken', 'createVerifierToken', 'createRequestToken'])
            ->addMethods(['getToken', 'getSecret', 'getExpiresAt', 'getType'])
            ->getMockForAbstractClass();
        $this->batchResponseMockFactory = $this->createMock(
            BatchResponseFactory::class
        );
        $this->batchResponseMock = $this->createMock(
            BatchResponse::class
        );
        $this->batchResponseMockFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->batchResponseMock);
        $this->createRequestToken = new CreateRequestToken(
            $this->requestCommandFactoryMock,
            $this->batchResponseMockFactory,
            $this->loggerHelperMock,
            $this->validationCompositeMock,
            $this->newRelicHeaders,
            $this->oauthServiceMock,
            $this->tokenProviderMock,
            $this->updateTokenMock,
            $this->tokenFactoryMock,
            []
        );
    }

    public function testResultResolveSuccess()
    {
        $resolveRequestMock = $this->createMock(ResolveRequest::class);
        $requests = [$resolveRequestMock];

        $args['input'] = [
            'oauth_consumer_key' => 'fn8pe04nnn8gymfuvbwlffnjem4ogj6v',
            'oauth_consumer_secret' => 'hgfap0hgxoeggsif5063azv9gvjnsn8u',
        ];
        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($args);

        $consumerMock = $this->getMockBuilder(Consumer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMockForAbstractClass();

        $this->tokenProviderMock->expects($this->once())->method('getConsumerByKey')->willReturn($consumerMock);

        $integrationMock = $this->getMockBuilder(Integration::class)
            ->addMethods(['setStatus', 'update'])
            ->disableOriginalConstructor()
            ->getMock();
        $integrationMock->expects($this->any())->method('setStatus')->willReturnSelf();
        $integrationMock->expects($this->any())->method('update')->willReturnSelf();

        $this->tokenMock->expects($this->once())
            ->method('createVerifierToken')
            ->willReturnSelf();

        $this->tokenMock->expects($this->once())
            ->method('getType')
            ->willReturn('verifier');

        $this->tokenMock->expects($this->once())
            ->method('createRequestToken')
            ->willReturnSelf();

        $this->tokenMock->expects($this->once())
            ->method('getToken')
            ->willReturn(self::OAUTH_TOKEN);

        $this->tokenMock->expects($this->once())
            ->method('getSecret')
            ->willReturn(self::OAUTH_TOKEN_SECRET);

        $this->tokenMock->expects($this->once())
            ->method('getExpiresAt')
            ->willReturn(self::EXPIRES_AT);

        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->tokenMock);

        $this->createRequestToken->proceed(
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
            'oauth_consumer_key' => 'fn8pe04nnn8gymfuvbwlffnjem4ogj6v',
            'oauth_consumer_secret' => 'hgfap0hgxoeggsif5063azv9gvjnsn8u',
        ];

        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($args);

        $consumerMock = $this->getMockBuilder(Consumer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMockForAbstractClass();

        $this->tokenProviderMock->expects($this->once())->method('getConsumerByKey')->willReturn($consumerMock);

        $integrationMock = $this->getMockBuilder(Integration::class)
            ->addMethods(['setStatus', 'update'])
            ->disableOriginalConstructor()
            ->getMock();
        $integrationMock->expects($this->any())->method('setStatus')->willReturnSelf();
        $integrationMock->expects($this->any())->method('update')->willReturnSelf();

        $this->tokenMock->expects($this->once())
            ->method('createVerifierToken')
            ->willReturnSelf();

        $this->tokenMock->expects($this->exactly(3))
            ->method('getType')
            ->willReturn('request');

        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->tokenMock);

        $this->expectExceptionMessage('Cannot create request token because consumer token is a "request" token');
        $this->expectException(GraphQlAuthenticationException::class);

        $this->createRequestToken->proceed(
            $this->contextMock,
            $this->fieldMock,
            $requests,
            []
        );
    }
}
