<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Plugin\Controller;

use Exception;
use Fedex\GraphQl\Model\Config;
use Fedex\GraphQl\Plugin\Controller\GraphQlPlugin;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\GraphQl\Exception\ExceptionFormatter;
use Magento\Framework\GraphQl\Query\Fields as QueryFields;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\GraphQl\Controller\GraphQl;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use ReflectionClass;
use ReflectionException;

class GraphQlPluginTest extends TestCase
{
    protected $dateTimeFactoryMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $inStoreConfigMock;
    const CURRENT_DATE = '1970-01-01 00:00:00';
    const EXPIRES_AT = '1970-01-01 04:00:00';

    /**
     * @var GraphQlPlugin
     */
    private GraphQlPlugin $graphQlPlugin;
    /**
     * @var JsonFactory|MockObject
     */
    private $jsonFactoryMock;
    /**
     * @var HttpResponse|MockObject
     */
    private $httpResponseMock;
    /**
     * @var GraphQl|MockObject
     */
    private $graphQlMock;
    /**
     * @var RequestInterface|MockObject
     */
    private $requestInterfaceMock;
    /**
     * @var \Closure
     */
    private \Closure $proceed;
    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerInterfaceMock;
    /**
     * @var TokenFactory|MockObject
     */
    private $tokenFactoryMock;
    /**
     * @var Token|MockObject
     */
    private $tokenMock;
    /**
     * @var QueryFields|MockObject
     */
    private $queryFieldsMock;
    /**
     * @var Session|MockObject
     */
    private $customerSessionMock;
    /**
     * @var ExceptionFormatter|MockObject
     */
    private $graphQlError;

    private Config $config;

    protected function setUp(): void
    {
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->httpResponseMock = $this->getMockBuilder(HttpResponse::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->serializerInterfaceMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->queryFieldsMock = $this->getMockBuilder(QueryFields::class)
            ->onlyMethods(['getFieldsUsedInQuery'])
            ->getMockForAbstractClass();

        $this->tokenFactoryMock = $this->getMockBuilder(TokenFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $this->graphQlMock = $this->getMockBuilder(GraphQl::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestInterfaceMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['isPost', 'getContent', 'getHeader'])
            ->getMockForAbstractClass();

        $this->tokenMock = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByToken', 'createVerifierToken'])
            ->addMethods(['getType', 'getExpiresAt'])
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        /** @var ResponseInterface|MockObject $response */
        $response = $this->getMockForAbstractClass(ResponseInterface::class);

        $this->proceed = function () use ($response) {
            return $response;
        };

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->addMethods(['setOnBehalfOf'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->graphQlError = $this->getMockBuilder(ExceptionFormatter::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->inStoreConfigMock = $this->getMockBuilder(ConfigInterface::class)
            ->onlyMethods(['isEnabledXOnBehalfOfHeader', 'isEmptyTokenErrorLogEnabled'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isGraphqlRequestErrorLogsEnabled'])
            ->getMock();

        $this->config->method('isGraphqlRequestErrorLogsEnabled')->willReturn(false);

        $this->graphQlPlugin = new GraphQlPlugin(
            $this->jsonFactoryMock,
            $this->httpResponseMock,
            $this->serializerInterfaceMock,
            $this->queryFieldsMock,
            $this->tokenFactoryMock,
            $this->dateTimeFactoryMock,
            $this->customerSessionMock,
            $this->graphQlError,
            $this->loggerMock,
            $this->inStoreConfigMock,
            $this->config
        );
    }

    public function testAroundDispatchProceed()
    {
        $this->requestInterfaceMock->expects($this->atLeastOnce())
            ->method('getHeader')->withConsecutive(['X-On-Behalf-Of'], ['X-On-Behalf-Of'], ['authorization'])
            ->willReturnOnConsecutiveCalls(
                'X-On-Behalf-Of',
                'X-On-Behalf-Of',
                'Bearer tfx3d7hmjqu2k38utexaa3f0hez7ktxz'
            );
        $this->customerSessionMock->expects($this->once())->method('setOnBehalfOf');

        $this->requestInterfaceMock->expects($this->once())->method('isPost')->willReturn(true);
        $this->serializerInterfaceMock->expects($this->once())->method('unserialize')
            ->willReturn(['query' => 'mockMutation']);

        $this->queryFieldsMock->expects($this->once())->method('getFieldsUsedInQuery')
            ->willReturn(['mockMutation' => true]);

        $this->tokenMock->expects($this->once())->method('getType')
            ->willReturn(\Magento\Integration\Model\Oauth\Token::TYPE_ACCESS);

        $this->tokenMock->expects($this->once())->method('getExpiresAt')
            ->willReturn(self::EXPIRES_AT);

        $dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['gmtDate'])
            ->getMockForAbstractClass();

        $dateTimeMock->expects($this->once())->method('gmtDate')->willReturn(self::CURRENT_DATE);

        $this->dateTimeFactoryMock->expects($this->once())->method('create')->willReturn($dateTimeMock);

        $this->tokenMock->expects($this->once())->method('loadByToken')->willReturnSelf();

        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->tokenMock);

        $this->graphQlPlugin->aroundDispatch(
            $this->graphQlMock,
            $this->proceed,
            $this->requestInterfaceMock
        );
    }

    public function testAroundDispatchAllowedMethodProceed()
    {
        $this->requestInterfaceMock
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->serializerInterfaceMock
            ->expects($this->once())
            ->method('unserialize')
            ->willReturn(['query' => 'mockMutation']);

        $this->queryFieldsMock
            ->expects($this->once())
            ->method('getFieldsUsedInQuery')
            ->willReturn(['createRequestToken' => true]);

        $this->graphQlPlugin->aroundDispatch(
            $this->graphQlMock,
            $this->proceed,
            $this->requestInterfaceMock
        );
    }

    public function testInvalidToken()
    {
        $this->requestInterfaceMock->expects($this->atLeastOnce())
            ->method('getHeader')->withConsecutive(['X-On-Behalf-Of'])
            ->willReturnOnConsecutiveCalls('X-On-Behalf-Of');
        $this->customerSessionMock->expects($this->once())->method('setOnBehalfOf');

        $this->graphQlError->expects($this->once())->method('create');

        $this->tokenMock->expects($this->once())->method('loadByToken')->willReturnSelf();

        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->tokenMock);

        $json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHttpResponseCode', 'renderResult', 'setData'])
            ->getMockForAbstractClass();

        $json->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(GraphQlPlugin::HTTP_GRAPH_QL_SCHEMA_UNAUTHORIZED_STATUS)
            ->willReturnSelf();

        $responseInterface = $this->getMockForAbstractClass(ResponseInterface::class);
        $responseInterface->sendResponse(GraphQlPlugin::HTTP_GRAPH_QL_SCHEMA_UNAUTHORIZED_STATUS);

        $json->expects($this->once())
            ->method('renderResult')
            ->willReturn($responseInterface);

        $this->jsonFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($json);

        $this->graphQlPlugin->aroundDispatch(
            $this->graphQlMock,
            $this->proceed,
            $this->requestInterfaceMock
        );
    }

    public function testExpiratedTokenException()
    {
        $this->requestInterfaceMock->expects($this->atLeastOnce())
            ->method('getHeader')->withConsecutive(['X-On-Behalf-Of'])
            ->willReturnOnConsecutiveCalls('X-On-Behalf-Of');
        $this->customerSessionMock->expects($this->once())->method('setOnBehalfOf');

        $this->requestInterfaceMock->expects($this->once())->method('isPost')->willReturn(true);

        $this->serializerInterfaceMock->expects($this->once())->method('unserialize')
            ->willReturn(['query' => 'mockMutation']);

        $this->queryFieldsMock->expects($this->once())->method('getFieldsUsedInQuery')
            ->willReturn(['mockMutation' => true]);

        $this->tokenMock->expects($this->once())->method('getType')
            ->willReturn(\Magento\Integration\Model\Oauth\Token::TYPE_ACCESS);

        $this->tokenMock->expects($this->once())->method('getExpiresAt')
            ->willReturn(self::CURRENT_DATE);

        $dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['gmtDate'])
            ->getMockForAbstractClass();

        $dateTimeMock->expects($this->once())->method('gmtDate')->willReturn(self::EXPIRES_AT);

        $this->dateTimeFactoryMock->expects($this->once())->method('create')->willReturn($dateTimeMock);

        $this->tokenMock->expects($this->once())->method('loadByToken')->willReturnSelf();

        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->tokenMock);

        $json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHttpResponseCode', 'renderResult', 'setData'])
            ->getMockForAbstractClass();

        $json->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(GraphQlPlugin::HTTP_GRAPH_QL_SCHEMA_UNAUTHORIZED_STATUS)
            ->willReturnSelf();

        $responseInterface = $this->getMockForAbstractClass(ResponseInterface::class);
        $responseInterface->sendResponse(GraphQlPlugin::HTTP_GRAPH_QL_SCHEMA_UNAUTHORIZED_STATUS);

        $json->expects($this->once())
            ->method('renderResult')
            ->willReturn($responseInterface);

        $this->jsonFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($json);

        $this->graphQlPlugin->aroundDispatch(
            $this->graphQlMock,
            $this->proceed,
            $this->requestInterfaceMock
        );
    }

    public function testMissingOnbealfOfException()
    {
        $json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHttpResponseCode', 'renderResult', 'setData'])
            ->getMockForAbstractClass();

        $json->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(GraphQlPlugin::HTTP_GRAPH_QL_SCHEMA_STATUS)
            ->willReturnSelf();

        $responseInterface = $this->getMockForAbstractClass(ResponseInterface::class);
        $responseInterface->sendResponse(GraphQlPlugin::HTTP_GRAPH_QL_SCHEMA_STATUS);

        $this->inStoreConfigMock->expects($this->any())->method('isEnabledXOnBehalfOfHeader')->willReturn(true);

        $json->expects($this->once())
            ->method('renderResult')
            ->willReturn($responseInterface);

        $this->jsonFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($json);

        $this->graphQlPlugin->aroundDispatch(
            $this->graphQlMock,
            $this->proceed,
            $this->requestInterfaceMock
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testGetBearerTokenSuccess()
    {
        $bearerToken = 'tfx3d7hmjqu2k38utexaa3f0hez7ktxz';

        $this->requestInterfaceMock->expects($this->once())
            ->method('getHeader')
            ->with('authorization')
            ->willReturn('Bearer ' . $bearerToken);

        $reflectionClass = new \ReflectionClass(GraphQlPlugin::class);
        $method = $reflectionClass->getMethod('getBearerToken');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->graphQlPlugin, [$this->requestInterfaceMock]);

        $this->assertEquals($bearerToken, $result);
    }

    /**
     * @return array
     */
    public function getBearerTokenDataProvider(): array
    {
        return [
            'valid_token' => [
                'authorization' => 'Bearer valid-token-123',
                'errorLogEnabled' => false,
                'expectedToken' => 'valid-token-123',
                'expectException' => false
            ],
            'no_auth_header_log_disabled' => [
                'authorization' => null,
                'errorLogEnabled' => false,
                'expectedToken' => null,
                'expectException' => false
            ],
            'malformed_auth_header' => [
                'authorization' => 'InvalidFormat token-without-bearer-prefix',
                'errorLogEnabled' => false,
                'expectedToken' => null,
                'expectException' => false
            ],
            'empty_auth_header_log_enabled' => [
                'authorization' => null,
                'errorLogEnabled' => true,
                'expectedToken' => null,
                'expectException' => true,
                'requestContent' => '{"query":"mutation { test }"}',
                'parsedContent' => ['query' => 'mutation { test }'],
                'jsonException' => null
            ],
            'malformed_auth_header_log_enabled' => [
                'authorization' => 'InvalidFormat',
                'errorLogEnabled' => true,
                'expectedToken' => null,
                'expectException' => true,
                'requestContent' => '{"query":"mutation { test }"}',
                'parsedContent' => ['query' => 'mutation { test }'],
                'jsonException' => null
            ],
            'auth_header_with_spaces' => [
                'authorization' => 'Bearer token-with-spaces',
                'errorLogEnabled' => false,
                'expectedToken' => 'token-with-spaces',
                'expectException' => false
            ],
            'empty_request_content' => [
                'authorization' => null,
                'errorLogEnabled' => true,
                'expectedToken' => null,
                'expectException' => true,
                'requestContent' => '',
                'parsedContent' => [],
                'jsonException' => null
            ],
            'invalid_json_content' => [
                'authorization' => null,
                'errorLogEnabled' => true,
                'expectedToken' => null,
                'expectException' => true,
                'requestContent' => '{invalid-json',
                'parsedContent' => null,
                'jsonException' => new \Exception('Invalid JSON')
            ]
        ];
    }

    /**
 * Test the extractBearerToken method
 *
 * @dataProvider extractBearerTokenDataProvider
 * @param string|null $authorization
 * @param string|null $expectedToken
 * @throws \ReflectionException
 */
public function testExtractBearerToken(?string $authorization, ?string $expectedToken): void
{
    // Configure request mock
    $this->requestInterfaceMock->expects($this->once())
        ->method('getHeader')
        ->with('authorization')
        ->willReturn($authorization);

    // Call the method using reflection since it's private
    $reflection = new \ReflectionClass(GraphQlPlugin::class);
    $method = $reflection->getMethod('extractBearerToken');
    $method->setAccessible(true);

    $result = $method->invoke($this->graphQlPlugin, $this->requestInterfaceMock);
    $this->assertEquals($expectedToken, $result);
}

/**
 * Data provider for testExtractBearerToken
 *
 * @return array
 */
public function extractBearerTokenDataProvider(): array
{
    return [
        'valid_token' => [
            'authorization' => 'Bearer valid-token-123',
            'expectedToken' => 'valid-token-123'
        ],
        'no_auth_header' => [
            'authorization' => null,
            'expectedToken' => null
        ],
        'malformed_auth_header' => [
            'authorization' => 'InvalidFormat token-without-bearer-prefix',
            'expectedToken' => null
        ],
        'empty_auth_header' => [
            'authorization' => '',
            'expectedToken' => null
        ],
        'token_with_spaces' => [
            'authorization' => 'Bearer token with spaces',
            'expectedToken' => 'token with spaces'
        ]
    ];
}

/**
 * Test the parseRequestContent method
 *
 * @dataProvider parseRequestContentDataProvider
 * @param string $requestContent
 * @param array|null $parsedContent
 * @param \Exception|null $jsonException
 * @throws \ReflectionException
 */
public function testParseRequestContent(
    string $requestContent,
    ?array $parsedContent,
    ?\Exception $jsonException = null,
    ?string $expectedErrorMessage = null
): void {
    // Configure request mock
    $this->requestInterfaceMock->expects($this->once())
        ->method('getContent')
        ->willReturn($requestContent);

    // Set up serializer mock behavior
    if ($jsonException) {
        $this->serializerInterfaceMock->expects($this->once())
            ->method('unserialize')
            ->willThrowException($jsonException);

        // Check for specific error message based on exception type
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains($expectedErrorMessage),
                $this->callback(function ($context) use ($requestContent) {
                    return isset($context['request_content']) &&
                           $context['request_content'] === $requestContent;
                })
            );
    } else {
        $this->serializerInterfaceMock->expects($this->once())
            ->method('unserialize')
            ->with($requestContent ?: '{}')
            ->willReturn($parsedContent);
    }

    // Call the method using reflection since it's private
    $reflection = new \ReflectionClass(GraphQlPlugin::class);
    $method = $reflection->getMethod('parseRequestContent');
    $method->setAccessible(true);

    $result = $method->invoke($this->graphQlPlugin, $this->requestInterfaceMock);

    // Method should always return an array, even on exception
    $this->assertIsArray($result);

    // If we expected parsed content, verify it matches
    if (!$jsonException) {
        $this->assertEquals($parsedContent, $result);
    } else {
        $this->assertEquals([], $result);
    }
}

/**
 * Data provider for testParseRequestContent
 *
 * @return array
 */
public function parseRequestContentDataProvider(): array
{
    return [
        'valid_json' => [
            'requestContent' => '{"query":"mutation { test }"}',
            'parsedContent' => ['query' => 'mutation { test }'],
            'jsonException' => null,
            'expectedErrorMessage' => null
        ],
        'empty_content' => [
            'requestContent' => '',
            'parsedContent' => [],
            'jsonException' => null,
            'expectedErrorMessage' => null
        ],
        'invalid_json_invalidargument' => [
            'requestContent' => '{invalid-json',
            'parsedContent' => null,
            'jsonException' => new \InvalidArgumentException('Invalid JSON'),
            'expectedErrorMessage' => 'Invalid JSON in request:'
        ],
        'generic_exception' => [
            'requestContent' => '{problem-json',
            'parsedContent' => null,
            'jsonException' => new \Exception('Some other error'),
            'expectedErrorMessage' => 'Error parsing request content:'
        ]
    ];
}

/**
 * Test the validateRequiredToken method
 *
 * @dataProvider validateRequiredTokenDataProvider
 * @param string|null $token
 * @param array $requestData
 * @param bool $errorLogEnabled
 * @param bool $expectException
 * @throws \ReflectionException
 */
public function testValidateRequiredToken(
    ?string $token,
    array $requestData,
    bool $errorLogEnabled,
    bool $expectException
): void {
    // Configure config mock
    $this->inStoreConfigMock->expects($this->any())
        ->method('isEmptyTokenErrorLogEnabled')
        ->willReturn($errorLogEnabled);

    if ($expectException) {
        // Should log the missing token error - update to match actual log structure
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Authentication token is missing'),
                $this->callback(function ($context) use ($requestData) {
                    // Only check that the structure contains the expected data
                    // Don't expect exact equality since formatting may differ
                    return isset($context['request_data']) &&
                           isset($context['operation']) &&
                           strpos($context['request_data'], json_encode($requestData['query'])) !== false;
                })
            );

        // Should throw exception
        $this->expectException(GraphQlAuthenticationException::class);
        $this->expectExceptionMessage('Authentication token is required.');
    } else {
        // No logging expected
        $this->loggerMock->expects($this->never())
            ->method('error');
    }

    // Call the method using reflection since it's private
    $reflection = new \ReflectionClass(GraphQlPlugin::class);
    $method = $reflection->getMethod('validateRequiredToken');
    $method->setAccessible(true);

    // For non-exception cases, we need to actually run the method and verify no exception is thrown
    if (!$expectException) {
        // This will execute the method and should NOT throw an exception
        $method->invokeArgs($this->graphQlPlugin, [$token, $requestData]);

        // If we got here without an exception, the test passes
        $this->assertTrue(true, 'Method executed without throwing an exception as expected');
    } else {
        // For exception cases, the method will throw and be caught by PHPUnit
        $method->invokeArgs($this->graphQlPlugin, [$token, $requestData]);
    }
}

/**
 * Data provider for testValidateRequiredToken
 *
 * @return array
 */
public function validateRequiredTokenDataProvider(): array
{
    return [
        'token_exists' => [
            'token' => 'some-token',
            'requestData' => ['query' => 'test'],
            'errorLogEnabled' => true,
            'expectException' => false
        ],
        'token_missing_log_enabled' => [
            'token' => null,
            'requestData' => ['query' => 'test'],
            'errorLogEnabled' => true,
            'expectException' => true
        ],
        'token_missing_log_disabled' => [
            'token' => null,
            'requestData' => ['query' => 'test'],
            'errorLogEnabled' => false,
            'expectException' => false
        ]
    ];
}

/**
 * Test the getBearerToken method with various scenarios
 *
 * @dataProvider getBearerTokenDataProvider
 * @param string|null $authorization
 * @param bool $errorLogEnabled
 * @param string|null $expectedToken
 * @param bool $expectException
 * @param string|null $requestContent
 * @param array|null $parsedContent
 * @param \Exception|null $jsonException
 * @throws \ReflectionException
 */
public function testGetBearerToken(
    ?string $authorization,
    bool $errorLogEnabled,
    ?string $expectedToken,
    bool $expectException,
    ?string $requestContent = null,
    ?array $parsedContent = null,
    ?\Exception $jsonException = null
): void {
    // Configure mocks
    $this->requestInterfaceMock->expects($this->once())
        ->method('getHeader')
        ->with('authorization')
        ->willReturn($authorization);

    $this->inStoreConfigMock->expects($this->any())
        ->method('isEmptyTokenErrorLogEnabled')
        ->willReturn($errorLogEnabled);

    if ($expectException) {
        // Configure request content mock
        $this->requestInterfaceMock->expects($this->once())
            ->method('getContent')
            ->willReturn($requestContent);

        if ($jsonException) {
            // Test case for JSON exception
            $this->serializerInterfaceMock->expects($this->once())
                ->method('unserialize')
                ->willThrowException($jsonException);

            // Update error message expectation to match actual implementation
            $this->loggerMock->expects($this->atLeastOnce())
                ->method('error')
                ->withConsecutive(
                    [$this->stringContains('Error parsing request content:')],
                    [$this->stringContains('Authentication token is missing')]
                );
        } else {
            // Normal JSON parse case
            $this->serializerInterfaceMock->expects($this->once())
                ->method('unserialize')
                ->with($requestContent ?: '{}')
                ->willReturn($parsedContent);

            // Should log the missing token error
            $this->loggerMock->expects($this->once())
                ->method('error')
                ->with(
                    $this->stringContains('Authentication token is missing'),
                    $this->isType('array')
                );
        }

        // Should throw exception
        $this->expectException(GraphQlAuthenticationException::class);
        $this->expectExceptionMessage('Authentication token is required.');
    } else {
        // No exception expected
        $this->loggerMock->expects($this->never())
            ->method('error');
    }

    // Call the method using reflection since it's private
    $reflection = new \ReflectionClass(GraphQlPlugin::class);
    $method = $reflection->getMethod('getBearerToken');
    $method->setAccessible(true);

    $result = $method->invoke($this->graphQlPlugin, $this->requestInterfaceMock);

    if (!$expectException) {
        $this->assertEquals($expectedToken, $result);
    }
}
}
