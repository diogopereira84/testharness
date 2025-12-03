<?php
/**
 * @category    Fedex
 * @package     Fedex_Customer
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Test\Unit\Gateway\Response;

use Fedex\Canva\Api\Data\UserTokenResponseInterface;
use Fedex\Customer\Api\Data\SalesForceResponseInterface;
use Fedex\Customer\Gateway\Response\SalesForce;
use PHPUnit\Framework\TestCase;

class SalesForceTest extends TestCase
{
    private SalesForce $salesForce;
    private array $subscribedData = [
        'status' => 'ok',
        'subscriberResponse' => true,
        'fxoSubscriberResponse' => true,
        'emailSendResponse' => 'OK',
        'errorMessage' => 'Error Message',
    ];

    protected function setUp():void
    {
        $this->salesForce = new SalesForce([
            SalesForceResponseInterface::STATUS => $this->subscribedData['status'],
            SalesForceResponseInterface::SUBSCRIBER_RESPONSE => $this->subscribedData['subscriberResponse'],
            SalesForceResponseInterface::FXO_SUBSCRIBER_RESPONSE => $this->subscribedData['fxoSubscriberResponse'],
            SalesForceResponseInterface::EMAIL_SEND_RESPONSE => $this->subscribedData['emailSendResponse'],
            SalesForceResponseInterface::ERROR_MESSAGE => $this->subscribedData['errorMessage'],
        ]);
    }

    public function testGetStatus()
    {
        $this->assertEquals($this->subscribedData['status'], $this->salesForce->getStatus());
    }

    public function testSetStatus()
    {
        $newStatus = 'ok2';
        $this->salesForce->setStatus($newStatus);
        $this->assertEquals($newStatus, $this->salesForce->getStatus());
    }

    public function testGetSubscriberResponse()
    {
        $this->assertEquals($this->subscribedData['subscriberResponse'], $this->salesForce->getStatus());
    }

    public function testSetSubscriberResponse()
    {
        $newSubscriberResponse = false;
        $this->salesForce->setSubscriberResponse($newSubscriberResponse);
        $this->assertEquals($newSubscriberResponse, $this->salesForce->getSubscriberResponse());
    }

    public function testGetFxoSubscriberResponse()
    {
        $this->assertEquals($this->subscribedData['fxoSubscriberResponse'], $this->salesForce->getFxoSubscriberResponse());
    }

    public function testSetFxoSubscriberResponse()
    {
        $newFxoSubscriberResponse = false;
        $this->salesForce->setFxoSubscriberResponse($newFxoSubscriberResponse);
        $this->assertEquals($newFxoSubscriberResponse, $this->salesForce->getFxoSubscriberResponse());
    }

    public function testGetEmailSendResponse()
    {
        $this->assertEquals($this->subscribedData['emailSendResponse'], $this->salesForce->getEmailSendResponse());
    }

    public function testSetEmailSendResponse()
    {
        $newEmailSendResponse = 'OK2';
        $this->salesForce->setEmailSendResponse($newEmailSendResponse);
        $this->assertEquals($newEmailSendResponse, $this->salesForce->getEmailSendResponse());
    }

    public function testGetErrorMessage()
    {
        $this->assertEquals($this->subscribedData['errorMessage'], $this->salesForce->getErrorMessage());
    }

    public function testSetErrorMessage()
    {
        $newErrorMessage = 'ErrorMessage2';
        $this->salesForce->setErrorMessage($newErrorMessage);
        $this->assertEquals($newErrorMessage, $this->salesForce->getErrorMessage());
    }
}
