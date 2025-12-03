<?php
/**
 * DTO Alerts
 *
 * Interface for getting and settings alert parameters.
 *
 * @category     Fedex
 * @package      Fedex_UploadToQuote
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Athira Indrakumar <athiraindrakumar.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\UploadToQuote\Api\Data;

interface AlertsDtoInterface
{
    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @return string
     */
    public function getAlertType(): string;

    public function setCode($code);

    public function setMessage($message);

    public function setAlertType($alertType);

    /**
     * @return array
     */
    public function toArray(): array;
}
