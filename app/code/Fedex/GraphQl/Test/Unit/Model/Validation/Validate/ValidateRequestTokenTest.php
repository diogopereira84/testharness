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
use Fedex\GraphQl\Model\Validation\Validate\ValidateRequestToken;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\Oauth\TokenProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ValidateRequestTokenTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    private const ARGS = [
        'input' => [
            'oauth_consumer_key' => 'some_oauth_consumer_key',
            'oauth_consumer_secret' => 'some_oauth_consumer_secret'
        ]
    ];
    /**
     * @var ValidateRequestToken
     */
    protected ValidateRequestToken $validateRequestToken;

    /**
     * @var GraphQlRequestCommand|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $graphQlRequestCommandMock;

    /**
     * @var TokenProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $tokenProviderMock;

    protected function setUp(): void
    {
        $this->graphQlRequestCommandMock = $this->getMockBuilder(GraphQlRequestCommand::class)
            ->onlyMethods(['getArgs'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->graphQlRequestCommandMock->method('getArgs')->willReturn(self::ARGS);

        $this->tokenProviderMock = $this->getMockBuilder(TokenProviderInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConsumerByKey'])
            ->addMethods(['getSecret'])
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
			->getMockForAbstractClass();

        $this->validateRequestToken = new ValidateRequestToken($this->tokenProviderMock, $this->loggerMock);
    }

    public function testValidate()
    {
        $this->tokenProviderMock->expects($this->once())->method('getConsumerByKey')->willReturnSelf();
        $this->tokenProviderMock->expects($this->once())->method('getSecret')
            ->willReturn(self::ARGS['input']['oauth_consumer_secret']);

        $this->validateRequestToken->validate($this->graphQlRequestCommandMock);
    }

    public function testValidateException()
    {
        $this->tokenProviderMock->expects($this->once())->method('getConsumerByKey')->willReturnSelf();
        $this->tokenProviderMock->expects($this->once())->method('getSecret')
            ->willReturn('another_oauth_consumer_secret');

        $this->expectExceptionMessage('Invalid oauth consumer secret');
        $this->expectException(GraphQlAuthenticationException::class);

        $this->validateRequestToken->validate($this->graphQlRequestCommandMock);
    }
}
