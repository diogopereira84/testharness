<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Gateway\Response;

use Fedex\Canva\Api\Data\UserTokenResponseInterface;
use PHPUnit\Framework\TestCase;
use Fedex\Canva\Gateway\Response\UserToken;

class UserTokenTest extends TestCase
{
    private UserToken $userToken;
    private bool $status = true;
    private string $accessToken = 'VALID_ACCESS_TOKEN';
    private string $clientId = 'VALID_CLIENT_ID';
    private string $expirationDateTime = '2022-03-04T12:41:04.885+0000';

    protected function setUp():void
    {
        $this->userToken = new UserToken([
            UserTokenResponseInterface::STATUS => $this->status,
            UserTokenResponseInterface::ACCESS_TOKEN => $this->accessToken,
            UserTokenResponseInterface::CLIENT_ID => $this->clientId,
            UserTokenResponseInterface::EXPIRATION_DATE_TIME => $this->expirationDateTime,
        ]);
    }

    public function testGetStatus()
    {
        $this->assertEquals($this->status, $this->userToken->getStatus());
    }

    public function testSetStatus()
    {
        $newStatus = false;
        $this->userToken->setStatus($newStatus);
        $this->assertEquals($newStatus, $this->userToken->getStatus());
    }

    public function testGetAccessToken()
    {
        $this->assertEquals($this->accessToken, $this->userToken->getAccessToken());
    }

    public function testSetAccessToken()
    {
        $newAccessToken = 'ACCESS_TOKEN_CHANGED';
        $this->userToken->setAccessToken($newAccessToken);
        $this->assertEquals($newAccessToken, $this->userToken->getAccessToken());
    }

    public function testGetClientId()
    {
        $this->assertEquals($this->clientId, $this->userToken->getClientId());
    }

    public function testSetClientId()
    {
        $newClientId = 'VALID_CLIENT_ID_CHANGED';
        $this->userToken->setClientId($newClientId);
        $this->assertEquals($newClientId, $this->userToken->getClientId());
    }

    public function testGetExpirationDateTime()
    {
        $this->assertEquals($this->clientId, $this->userToken->getClientId());
    }

    public function testSetExpirationDateTime()
    {
        $newExpirationDate = 'VALID_EXPIRATION_DATE_TIME_CHANGED';
        $this->userToken->setExpirationDateTime($newExpirationDate);
        $this->assertEquals($newExpirationDate, $this->userToken->getExpirationDateTime());
    }
}
