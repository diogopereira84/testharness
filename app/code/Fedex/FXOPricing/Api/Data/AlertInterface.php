<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api\Data;

interface AlertInterface
{
    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * @param string $code
     * @return AlertInterface
     */
    public function setCode(string $code): AlertInterface;

    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @param string $message
     * @return AlertInterface
     */
    public function setMessage(string $message): AlertInterface;

    /**
     * @return string
     */
    public function getAlertType(): string;

    /**
     * @param string $alertType
     * @return AlertInterface
     */
    public function setAlertType(string $alertType): AlertInterface;
}
