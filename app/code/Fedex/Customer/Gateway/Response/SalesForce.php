<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <ilima@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Gateway\Response;

use Fedex\Customer\Api\Data\SalesForceResponseInterface;
use Magento\Framework\DataObject;

class SalesForce extends DataObject implements SalesForceResponseInterface
{
    /**
     * @inheritDoc
     */
    public function getStatus(): bool|string
    {
        return $this->getData(static::STATUS) ?? false;
    }

    /**
     * @inheritDoc
     */
    public function setStatus(bool|string $status): SalesForceResponseInterface
    {
        return $this->setData(static::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getSubscriberResponse(): bool
    {
        return $this->getData(static::SUBSCRIBER_RESPONSE) ?? false;
    }

    /**
     * @inheritDoc
     */
    public function setSubscriberResponse(bool $subscriberResponse): SalesForceResponseInterface
    {
        return $this->setData(static::SUBSCRIBER_RESPONSE, $subscriberResponse);
    }

    /**
     * @inheritDoc
     */
    public function getFxoSubscriberResponse(): bool
    {
        return $this->getData(static::FXO_SUBSCRIBER_RESPONSE) ?? false;
    }

    /**
     * @inheritDoc
     */
    public function setFxoSubscriberResponse(bool $fxoSubscriberResponse): SalesForceResponseInterface
    {
        return $this->setData(static::FXO_SUBSCRIBER_RESPONSE, $fxoSubscriberResponse);
    }

    /**
     * @inheritDoc
     */
    public function getEmailSendResponse(): string
    {
        return $this->getData(static::EMAIL_SEND_RESPONSE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setEmailSendResponse(string $emailSendResponse): SalesForceResponseInterface
    {
        return $this->setData(static::EMAIL_SEND_RESPONSE, $emailSendResponse);
    }

    /**
     * @inheritDoc
     */
    public function getErrorMessage(): string
    {
        return $this->getData(static::ERROR_MESSAGE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setErrorMessage(string $errorMessage): SalesForceResponseInterface
    {
        return $this->setData(static::ERROR_MESSAGE, $errorMessage);
    }
}
