<?php
/**
 * DTO Alerts
 *
 * Class for getting and settings alert parameters.
 *
 * @category     Fedex
 * @package      Fedex_UploadToQuote
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Athira Indrakumar <athiraindrakumar.osv@fedex.com>
 */
declare(strict_types=1);

namespace  Fedex\UploadToQuote\Model;

class AlertsDto
{

    /**
     * @param string $code
     * @param string $message
     * @param string $alertType
     */
    public function __construct 
    (
        private string $code = '',
        private string $message = '',
        private string $alertType = ''
    ) {}

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getAlertType(): string
    {
        return $this->alertType;
    }

    /**
     * @param $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @param $message
     * @return mixed
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @param $alertType
     */
    public function setAlertType($alertType)
    {
        $this->alertType = $alertType;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'alertType' => $this->getAlertType(),
        ];
    }
}
