<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Oauth;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Psr\Log\LoggerInterface;
use Fedex\OKTA\Model\Oauth\UrlBuilder;
use Fedex\OKTA\Model\Oauth\UrlBuilder\ChallengeResolver;
use Fedex\OKTA\Model\Oauth\UrlBuilder\Code;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UrlBuilderTest extends TestCase
{
    protected $challengerResolverMock;
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
     * Challenge method alternative value
     */
    private const CHALLENGE_METHOD_CHANGED_VALUE = 'md5';

    /**
     * Challenge value
     */
    private const CHALLENGE_VALUE = 'NkZCNzg0RTkyRTU4QjNDMUMzMzJGM0MwQjc2MzUwQUEz';

    /**
     * Challenge alternative value
     */
    private const CHALLENGE_CHANGED_VALUE = '3SsMTYH6Mh9TX9nMWirkcAbDbL3oFm7-_QMlPywUeb8';

    /**
     * Verifier value
     */
    private const VERIFIER_VALUE = 'NkZCNzg0RTkyRTU4QjNDMUMzMzJGM0MwQjc2MzUwQUEzN';

    /**
     * Verifier alternative value
     */
    private const VERIFIER_CHANGED_VALUE = 'cSZ4Io8fwyHweTvLcM-DRHWPD55zmk1TKinUjFaTcjM';

    /**
     * @var UrlBuilder
     */
    private UrlBuilder $builder;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $loggerMock;

    /**
     * @var UrlHelper|MockObject
     */
    private UrlHelper $urlHelperMock;

    /**
     * @var MockObject|ChallengeResolver
     */
    private MockObject|ChallengeResolver $challengeResolver;


    /**
     * @var Code
     */
    private Code $code;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->urlHelperMock = $this->createMock(UrlHelper::class);
        $context = $this->createMock(\Magento\Framework\App\Helper\Context::class);
        $this->challengerResolverMock = $this->createMock(ChallengeResolver::class);
        $this->code = new Code([
            self::CHALLENGE_METHOD => self::CHALLENGE_METHOD_VALUE,
            self::CHALLENGE => self::CHALLENGE_VALUE,
            self::VERIFIER => self::VERIFIER_VALUE,
        ]);
        $this->urlHelperMock = new UrlHelper($context);
        $this->builder = new UrlBuilder(
            $this->urlHelperMock,
            $this->loggerMock,
            $this->challengerResolverMock
        );
    }

    public function testBuild(): void
    {
        $this->builder->setDomain('domain.com/oauth2/default/v1');
        $this->builder->setNonce('some_nonce');
        $this->builder->setClientId('123');
        $this->builder->setRedirectUrl('http://domain.com/redirect/url/');
        $this->builder->setScope('openid profile email groups');
        $this->builder->setState('okta_sso');
        $this->builder->setResponseType('code');
        $this->builder->setResponseMode('form_post');

        $this->assertEquals(
            'domain.com/oauth2/default/v1/authorize?client_id=123' .
            '&redirect_uri=http://domain.com/redirect/url/&scope=openid profile email groups' .
            '&state=okta_sso&response_type=code&response_mode=form_post&code_challenge_method=&code_challenge=&nonce=some_nonce',
            $this->builder->build()
        );
    }

    public function testBuildNoClient(): void
    {
        $this->builder->setDomain('domain.com/oauth2/default/v1');
        $this->builder->setNonce('some_nonce');
        $this->builder->setRedirectUrl('http://domain.com/redirect/url/');
        $this->builder->setScope('openid profile email groups');
        $this->builder->setState('okta_sso');
        $this->builder->setResponseType('code');
        $this->builder->setResponseMode('form_post');
        $this->expectException(LocalizedException::class);

        $this->assertEquals(
            'domain.com/oauth2/default/v1/authorize?client_id=123' .
            '&redirect_uri=http://domain.com/redirect/url/&scope=openid profile email groups' .
            '&state=okta_sso&response_type=code&response_mode=form_post&nonce=some_nonce',
            $this->builder->build()
        );
    }

    public function testBuildNoDomain(): void
    {
        $this->builder->setNonce('some_nonce');
        $this->builder->setClientId('123');
        $this->builder->setRedirectUrl('http://domain.com/redirect/url/');
        $this->expectException(LocalizedException::class);

        $this->assertEquals(
            'domain.com/oauth2/default/v1/authorize?client_id=123' .
            '&redirect_uri=http://domain.com/redirect/url/&scope=openid profile email groups' .
            '&state=okta_sso&response_type=code&response_mode=form_post&nonce=some_nonce',
            $this->builder->build()
        );
    }

    public function testBuildNoRedirectUrl(): void
    {
        $this->builder->setDomain('domain.com');
        $this->builder->setNonce('some_nonce');
        $this->builder->setClientId('123');
        $this->expectException(LocalizedException::class);

        $this->assertEquals(
            'domain.com/oauth2/default/v1/authorize?client_id=123' .
            '&redirect_uri=http://domain.com/redirect/url/&scope=openid profile email groups' .
            '&state=okta_sso&response_type=code&response_mode=form_post&nonce=some_nonce',
            $this->builder->build()
        );
    }

    /**
     * Test getRequestParams() method
     *
     * @return void
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    public function testGetRequestParams(): void
    {
        $this->challengerResolverMock->expects($this->once())->method('resolve')->willReturn($this->code);

        $params = $this->builder->getRequestParams();

        $this->assertArrayHasKey(self::CHALLENGE_METHOD, $params);
        $this->assertArrayHasKey(self::CHALLENGE, $params);
    }
}
