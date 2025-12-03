<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <ilima@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Api\Data;

/**
 * @class SalesForceResponseInterface
 *
 * This class represents the response from Sales Force API
 */
interface SalesForceResponseInterface
{
    /**
     * Access Status
     */
    const STATUS = 'status';

    /**
     * Access Subscriber Response
     */
    const SUBSCRIBER_RESPONSE = 'subscriberResponse';

    /**
     * Access FXO Subscriber Response
     */
    const FXO_SUBSCRIBER_RESPONSE = 'fxoSubscriberResponse';

    /**
     * Access Email Send Response
     */
    const EMAIL_SEND_RESPONSE = 'emailSendResponse';

    /**
     * Access Email Send Response
     */
    const ERROR_MESSAGE = 'errorMessage';

    /**
     * @return bool
     */
    public function getStatus(): bool|string;

    /**
     * @param bool|string $status
     * @return SalesForceResponseInterface
     */
    public function setStatus(bool|string $status): SalesForceResponseInterface;

    /**
     * @return string
     */
    public function getSubscriberResponse(): bool;

    /**
     * @param string $subscriberResponse
     * @return SalesForceResponseInterface
     */
    public function setSubscriberResponse(bool $subscriberResponse): SalesForceResponseInterface;

    /**
     * @return string
     */
    public function getFxoSubscriberResponse(): bool;

    /**
     * @param string $fxoSubscriberResponse
     * @return SalesForceResponseInterface
     */
    public function setFxoSubscriberResponse(bool $fxoSubscriberResponse): SalesForceResponseInterface;

    /**
     * @return string
     */
    public function getEmailSendResponse(): string;

    /**
     * @param string $emailSendResponse
     * @return SalesForceResponseInterface
     */
    public function setEmailSendResponse(string $emailSendResponse): SalesForceResponseInterface;

    /**
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * @param string $errorMessage
     * @return SalesForceResponseInterface
     */
    public function setErrorMessage(string $errorMessage): SalesForceResponseInterface;
}
