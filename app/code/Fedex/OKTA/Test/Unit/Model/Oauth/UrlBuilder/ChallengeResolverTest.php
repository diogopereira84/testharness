<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Oauth\UrlBuilder;

use Fedex\OKTA\Model\Oauth\UrlBuilder\ChallengeResolver;
use Fedex\OKTA\Model\Oauth\UrlBuilder\Code;
use Fedex\OKTA\Model\Oauth\UrlBuilder\CodeFactory;
use Fedex\OKTA\Model\Oauth\UrlBuilder\CodeStorage;
use Fedex\OKTA\Model\Oauth\UrlBuilder\Encoder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChallengeResolverTest extends TestCase
{
    /**
     * Challenge method key
     */
    private const CHALLENGE_METHOD = 'code_challenge_method';

    /**
     * Challenge key
     */
    private const CHALLENGE = 'code_challenge';

    /**
     * Verifier key
     */
    private const VERIFIER = 'code_verifier';

    /**
     * Challenge method value
     */
    private const CHALLENGE_METHOD_VALUE = 'S256';

    /**
     * Challenge value
     */
    private const CHALLENGE_VALUE = 'NkZCNzg0RTkyRTU4QjNDMUMzMzJGM0MwQjc2MzUwQUEz';

    /**
     * Verifier value
     */
    private const VERIFIER_VALUE = 'NkZCNzg0RTkyRTU4QjNDMUMzMzJGM0MwQjc2MzUwQUEzN';

    /**
     * Open SSL length value
     */
    private const OPENSSL_LENGTH = 32;

    /**
     * pack format value
     */
    private const PACK_FORMAT = 'H*';

    /**
     * Hash algorithm value
     */
    private const HASH_ALGORITHM = 'sha256';

    /**
     * @var MockObject|CodeFactory
     */
    private MockObject|CodeFactory $codeFactoryMock;

    /**
     * @var MockObject|Encoder
     */
    private MockObject|Encoder $encoderMock;

    /**
     * @var MockObject|CodeStorage
     */
    private MockObject|CodeStorage $codeStorageMock;

    /**
     * @var MockObject|RequestInterface
     */
    private MockObject|RequestInterface $requestMock;

    /**
     * @var ChallengeResolver
     */
    private ChallengeResolver $challengeResolver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->codeFactoryMock = $this->createMock(CodeFactory::class);
        $this->encoderMock = $this->createMock(Encoder::class);
        $this->codeStorageMock = $this->createMock(CodeStorage::class);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods([
                'isGet',
                'getModuleName',
                'setModuleName',
                'getActionName',
                'setActionName',
                'getParam',
                'setParams',
                'getParams',
                'getCookie',
                'isSecure',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->challengeResolver = new ChallengeResolver(
            $this->codeFactoryMock,
            $this->encoderMock,
            $this->codeStorageMock,
            $this->requestMock
        );
    }

    /**
     * Test resolve() method
     *
     * @return void
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    public function testResolve(): void
    {
        $code = new Code([
            self::CHALLENGE_METHOD => self::CHALLENGE_METHOD_VALUE,
            self::CHALLENGE => self::CHALLENGE_VALUE,
            self::VERIFIER => self::VERIFIER_VALUE,
        ]);
        $this->codeFactoryMock->expects($this->once())->method('create')->willReturn($code);
        $this->requestMock->expects($this->once())->method('isGet')->willReturn(true);
        $this->encoderMock->expects($this->exactly(2))->method('encode')->willReturnOnConsecutiveCalls(
            self::VERIFIER_VALUE,
            self::CHALLENGE_VALUE
        );
        $this->codeStorageMock->expects($this->once())->method('store')->with($code->getVerifier());

        $generatedCode = $this->challengeResolver->resolve();

        $this->assertEquals(self::VERIFIER_VALUE, $generatedCode->getVerifier());
        $this->assertEquals(self::CHALLENGE_VALUE, $generatedCode->getChallenge());
        $this->assertEquals(self::CHALLENGE_METHOD_VALUE, $generatedCode->getChallengeMethod());
    }
}
