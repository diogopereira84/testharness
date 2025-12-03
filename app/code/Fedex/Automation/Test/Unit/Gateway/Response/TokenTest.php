<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Test\Unit\Gateway\Response;

use Fedex\Automation\Gateway\Response\TokenInterface;
use PHPUnit\Framework\TestCase;
use Fedex\Automation\Gateway\Response\Token;

class TokenTest extends TestCase
{
    private Token $token;
    private string $tokenType = 'grant';
    private string $expireIn = 'VALID_EXPIRATION_DATE';
    private string $accessToken = 'VALID_ACCESS_TOKEN';
    private string $scope = 'oob';

    protected function setUp():void
    {
        $this->token = new Token([
            TokenInterface::TOKEN_TYPE => $this->tokenType,
            TokenInterface::EXPIRES_IN => $this->expireIn,
            TokenInterface::ACCESS_TOKEN => $this->accessToken,
            TokenInterface::SCOPE => $this->scope,
        ]);
    }

    public function testGetTokenType()
    {
        $this->assertEquals($this->tokenType, $this->token->getTokenType());
    }

    public function testSetTokenType()
    {
        $newTokenType = 'ACCESS_TOKEN_TYPE_CHANGED';
        $this->token->setTokenType($newTokenType);
        $this->assertEquals($newTokenType, $this->token->getTokenType());
    }

    public function testGetAccessToken()
    {
        $this->assertEquals($this->accessToken, $this->token->getAccessToken());
    }

    public function testSetAccessToken()
    {
        $newAccessToken = 'ACCESS_TOKEN_CHANGED';
        $this->token->setAccessToken($newAccessToken);
        $this->assertEquals($newAccessToken, $this->token->getAccessToken());
    }

    public function testGetExpiresIn()
    {
        $this->assertEquals($this->expireIn, $this->token->getExpiresIn());
    }

    public function testSetExpiresIn()
    {
        $expireIn = 'VALID_EXPIRATION_DATE_CHANGED';
        $this->token->setExpiresIn($expireIn);
        $this->assertEquals($expireIn, $this->token->getExpiresIn());
    }

    public function testGetScope()
    {
        $this->assertEquals($this->scope, $this->token->getScope());
    }

    public function testSetScope()
    {
        $newScope = 'VALID_SCOPE_CHANGED';
        $this->token->setScope($newScope);
        $this->assertEquals($newScope, $this->token->getScope());
    }
}
