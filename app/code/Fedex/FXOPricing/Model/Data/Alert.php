<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data;

use Fedex\Base\Model\DataObject;
use Fedex\FXOPricing\Api\Data\AlertInterface;

class Alert extends DataObject implements AlertInterface
{
    /**
     * Code key
     */
    private const CODE = 'code';

    /**
     * Message key
     */
    private const MESSAGE = 'message';

    /**
     * AlertType key
     */
    private const ALERT_TYPE = 'alertType';

    /**
     * Invalid coupon code
     */
    private const CODE_COUPONS_CODE_INVALID = 'COUPONS.CODE.INVALID';

    /**
     * Minimum purchase required code
     */
    private const CODE_MINIMUM_PURCHASE_REQUIRED = 'MINIMUM.PURCHASE.REQUIRED';

    /**
     * Invalid address code
     */
    private const CODE_ADDRESS_SERVICE_FAILURE = 'ADDRESS_SERVICE_FAILURE';

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return (string)$this->getData(self::CODE);
    }

    /**
     * @inheritDoc
     */
    public function setCode(string $code): AlertInterface
    {
        return $this->setData(self::CODE, $code);
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string
    {
        return (string)$this->getData(self::MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setMessage(string $message): AlertInterface
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * @inheritDoc
     */
    public function getAlertType(): string
    {
        return (string)$this->getData(self::ALERT_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setAlertType(string $alertType): AlertInterface
    {
        return $this->setData(self::ALERT_TYPE, $alertType);
    }
}
