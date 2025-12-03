<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Data;

use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Model\Data\Alert;

class AlertTest extends TestCase
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
     * Code value
     */
    private const CODE_VALUE = 'ADDRESS_SERVICE_FAILURE';

    /**
     * Code value alternative
     */
    private const CODE_VALUE_ALTERNATIVE = 'COUPONS.CODE.INVALID';

    /**
     * Message value
     */
    private const MESSAGE_VALUE = 'Address service return failed response';

    /**
     * Message value alternative
     */
    private const MESSAGE_VALUE_ALTERNATIVE = 'Invalid Coupon please try again';

    /**
     * AlertType value
     */
    private const ALERT_TYPE_VALUE = 'WARNING';

    /**
     * AlertType value alternative
     */
    private const ALERT_TYPE_VALUE_ALTERNATIVE = 'ERROR';

    /**
     * @var Alert
     */
    private Alert $alert;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->alert = new Alert([
            self::CODE => self::CODE_VALUE,
            self::MESSAGE => self::MESSAGE_VALUE,
            self::ALERT_TYPE => self::ALERT_TYPE_VALUE
        ]);
    }

    /**
     * Test method getCode
     *
     * @return void
     */
    public function testGetCode(): void
    {
        $this->assertEquals(self::CODE_VALUE, $this->alert->getCode());
    }

    /**
     * Test method setCode
     *
     * @return void
     */
    public function testSetCode(): void
    {
        $this->alert->setCode(self::CODE_VALUE_ALTERNATIVE);
        $this->assertEquals(self::CODE_VALUE_ALTERNATIVE, $this->alert->getCode());
    }

    /**
     * Test method getMessage
     *
     * @return void
     */
    public function testGetMessage(): void
    {
        $this->assertEquals(self::MESSAGE_VALUE, $this->alert->getMessage());
    }

    /**
     * Test method setMessage
     *
     * @return void
     */
    public function testSetMessage(): void
    {
        $this->alert->setMessage(self::MESSAGE_VALUE_ALTERNATIVE);
        $this->assertEquals(self::MESSAGE_VALUE_ALTERNATIVE, $this->alert->getMessage());
    }

    /**
     * Test method getAlertType
     *
     * @return void
     */
    public function testGetAlertType(): void
    {
        $this->assertEquals(self::ALERT_TYPE_VALUE, $this->alert->getAlertType());
    }

    /**
     * Test method setAlertType
     *
     * @return void
     */
    public function testSetAlertType(): void
    {
        $this->alert->setAlertType(self::ALERT_TYPE_VALUE_ALTERNATIVE);
        $this->assertEquals(self::ALERT_TYPE_VALUE_ALTERNATIVE, $this->alert->getAlertType());
    }
}
