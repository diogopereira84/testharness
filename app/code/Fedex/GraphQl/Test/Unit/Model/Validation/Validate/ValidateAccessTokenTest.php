<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model\Validation\Validate;

use Fedex\GraphQl\Model\GraphQlRequestCommand;
use Fedex\GraphQl\Model\Validation\Validate\ValidateAccessToken;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ValidateAccessTokenTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    private const ARGS = [
        'input' => [
            'oauth_token' => 'some_oauth_token',
            'oauth_token_secret' => 'some_oauth_token_secret'
        ]
    ];
    /**
     * @var ValidateAccessToken
     */
    protected ValidateAccessToken $validateAccessToken;

    /**
     * @var GraphQlRequestCommand|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $graphQlRequestCommandMock;

    /**
     * @var TokenFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $tokenFactoryMock;

    /**
     * @var Token|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $tokenMock;

    protected function setUp(): void
    {
        $this->graphQlRequestCommandMock = $this->getMockBuilder(GraphQlRequestCommand::class)
            ->onlyMethods(['getArgs'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->tokenMock = $this->getMockBuilder(Token::class)
            ->onlyMethods(['loadByToken'])
            ->addMethods(['getSecret'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenFactoryMock = $this->getMockBuilder(TokenFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->tokenFactoryMock->method('create')->willReturn($this->tokenMock);

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
			->getMockForAbstractClass();

        $this->validateAccessToken = new ValidateAccessToken($this->tokenFactoryMock, $this->loggerMock);
    }

    public function testValidate()
    {
        $this->graphQlRequestCommandMock->method('getArgs')->willReturn(self::ARGS);

        $this->tokenMock->expects($this->once())->method('loadByToken')->willReturnSelf();
        $this->tokenMock->expects($this->once())->method('getSecret')
            ->willReturn(self::ARGS['input']['oauth_token_secret']);

        $this->validateAccessToken->validate($this->graphQlRequestCommandMock);
    }

    public function testValidateException()
    {
        $this->graphQlRequestCommandMock->method('getArgs')->willReturn(self::ARGS);

        $this->tokenMock->expects($this->once())->method('loadByToken')->willReturnSelf();
        $this->tokenMock->expects($this->once())->method('getSecret')
            ->willReturn('another_oauth_token_secret');

        $this->expectExceptionMessage('Invalid oauth consumer secret');
        $this->expectException(GraphQlAuthenticationException::class);

        $this->validateAccessToken->validate($this->graphQlRequestCommandMock);
    }
}
