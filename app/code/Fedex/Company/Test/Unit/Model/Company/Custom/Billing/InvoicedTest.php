<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Model\Company\Custom\Billing;

use PHPUnit\Framework\TestCase;
use Fedex\Company\Model\Company\Custom\Billing\Invoiced;

class InvoicedTest extends TestCase
{
    /**
     * Record id field key
     */
    private const RECORD_ID = 'record_id';

    /**
     * Record id field value
     */
    private const RECORD_ID_VALUE = '0';

    /**
     * Field name field key
     */
    private const FIELD_NAME = 'field_name';

    /**
     * Field name field value
     */
    private const FIELD_NAME_VALUE = 'IA_Reference_1';

    /**
     * Field label field key
     */
    private const FIELD_LABEL = 'field_label';

    /**
     * Field label field value
     */
    private const FIELD_LABEL_VALUE = 'IA Reference';

    /**
     * Default field key
     */
    private const DEFAULT = 'default';

    /**
     * Default field value
     */
    private const DEFAULT_VALUE = 'Default value';

    /**
     * Visible field key
     */
    private const VISIBLE = 'visible';

    /**
     * Visible field value
     */
    private const VISIBLE_VALUE = '1';

    /**
     * Editable field key
     */
    private const EDITABLE = 'editable';

    /**
     * Editable field value
     */
    private const EDITABLE_VALUE = '1';

    /**
     * Required field key
     */
    private const REQUIRED = 'required';

    /**
     * Required field value
     */
    private const REQUIRED_VALUE = '1';

    /**
     * Mask field key
     */
    private const MASK = 'mask';

    /**
     * Mask field value
     */
    private const MASK_VALUE = 'custom';

    /**
     * Custom mask field key
     */
    private const CUSTOM_MASK = 'custom_mask';

    /**
     * Custom mask field value
     */
    private const CUSTOM_MASK_VALUE = '\d{5}(-\d{4})?';

    /**
     * Error message field key
     */
    private const ERROR_MESSAGE = 'error_message';

    /**
     * Error message field value
     */
    private const ERROR_MESSAGE_VALUE = 'Test message';

    /**
     * @var Invoiced
     */
    private Invoiced $invoiced;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->invoiced = new Invoiced([
            self::RECORD_ID => self::RECORD_ID_VALUE,
            self::FIELD_NAME => self::FIELD_NAME_VALUE,
            self::FIELD_LABEL => self::FIELD_LABEL_VALUE,
            self::DEFAULT => self::DEFAULT_VALUE,
            self::VISIBLE => self::VISIBLE_VALUE,
            self::EDITABLE => self::EDITABLE_VALUE,
            self::REQUIRED => self::REQUIRED_VALUE,
            self::MASK => self::MASK_VALUE,
            self::CUSTOM_MASK => self::CUSTOM_MASK_VALUE,
            self::ERROR_MESSAGE => self::ERROR_MESSAGE_VALUE,
        ]);
    }

    /**
     * Test getRecordId method
     *
     * @return void
     */
    public function testGetRecordId(): void
    {
        $this->assertEquals(self::RECORD_ID_VALUE, $this->invoiced->getRecordId());
    }

    /**
     * Test setRecordId method
     *
     * @return void
     */
    public function testSetRecordId(): void
    {
        $recordId = "12";
        $this->invoiced->setRecordId($recordId);
        $this->assertEquals($recordId, $this->invoiced->getRecordId());
    }

    /**
     * Test getFieldName method
     *
     * @return void
     */
    public function testGetFieldName(): void
    {
        $this->assertEquals(self::FIELD_NAME_VALUE, $this->invoiced->getFieldName());
    }

    /**
     * Test setFieldName method
     *
     * @return void
     */
    public function testSetFieldName(): void
    {
        $fieldName = "Test Name";
        $this->invoiced->setFieldName($fieldName);
        $this->assertEquals($fieldName, $this->invoiced->getFieldName());
    }

    /**
     * Test getFieldLabel method
     *
     * @return void
     */
    public function testGetFieldLabel(): void
    {
        $this->assertEquals(self::FIELD_LABEL_VALUE, $this->invoiced->getFieldLabel());
    }

    /**
     * Test setFieldLabel method
     *
     * @return void
     */
    public function testSetFieldLabel(): void
    {
        $fieldLabel = "New field Label";
        $this->invoiced->setFieldLabel($fieldLabel);
        $this->assertEquals($fieldLabel, $this->invoiced->getFieldLabel());
    }

    /**
     * Test getDefault method
     *
     * @return void
     */
    public function testGetDefault(): void
    {
        $this->assertEquals(self::DEFAULT_VALUE, $this->invoiced->getDefault());
    }

    /**
     * Test setDefault method
     *
     * @return void
     */
    public function testSetDefault(): void
    {
        $default = "New Default";
        $this->invoiced->setDefault($default);
        $this->assertEquals($default, $this->invoiced->getDefault());
    }

    /**
     * Test getVisible method
     *
     * @return void
     */
    public function testGetVisible(): void
    {
        $this->assertEquals(self::VISIBLE_VALUE, $this->invoiced->getVisible());
    }

    /**
     * Test setVisible method
     *
     * @return void
     */
    public function testSetVisible(): void
    {
        $visible = "0";
        $this->invoiced->setVisible($visible);
        $this->assertEquals($visible, $this->invoiced->getVisible());
    }

    /**
     * Test getEditable method
     *
     * @return void
     */
    public function testGetEditable(): void
    {
        $this->assertEquals(self::EDITABLE_VALUE, $this->invoiced->getEditable());
    }

    /**
     * Test setEditable method
     *
     * @return void
     */
    public function testSetEditable(): void
    {
        $editable = "0";
        $this->invoiced->setEditable($editable);
        $this->assertEquals($editable, $this->invoiced->getEditable());
    }

    /**
     * Test getRequired method
     *
     * @return void
     */
    public function testGetRequired(): void
    {
        $this->assertEquals(self::REQUIRED_VALUE, $this->invoiced->getRequired());
    }

    /**
     * Test setRequired method
     *
     * @return void
     */
    public function testSetRequired(): void
    {
        $required = "0";
        $this->invoiced->setRequired($required);
        $this->assertEquals($required, $this->invoiced->getRequired());
    }

    /**
     * Test getMask method
     *
     * @return void
     */
    public function testGetMask(): void
    {
        $this->assertEquals(self::MASK_VALUE, $this->invoiced->getMask());
    }

    /**
     * Test setMask method
     *
     * @return void
     */
    public function testSetMask(): void
    {
        $mask = "validate-alpha";
        $this->invoiced->setMask($mask);
        $this->assertEquals($mask, $this->invoiced->getMask());
    }

    /**
     * Test getCustomMask method
     *
     * @return void
     */
    public function testGetCustomMask(): void
    {
        $this->assertEquals(self::CUSTOM_MASK_VALUE, $this->invoiced->getCustomMask());
    }

    /**
     * Test setCustomMask method
     *
     * @return void
     */
    public function testSetCustomMask(): void
    {
        $customMask = "[2-9]|[12]\d|3[0-6]";
        $this->invoiced->setCustomMask($customMask);
        $this->assertEquals($customMask, $this->invoiced->getCustomMask());
    }

    /**
     * Test getErrorMessage method
     *
     * @return void
     */
    public function testGetErrorMessage(): void
    {
        $this->assertEquals(self::ERROR_MESSAGE_VALUE, $this->invoiced->getErrorMessage());
    }

    /**
     * Test setErrorMessage method
     *
     * @return void
     */
    public function testSetErrorMessage(): void
    {
        $errorMessage = "New message";
        $this->invoiced->setErrorMessage($errorMessage);
        $this->assertEquals($errorMessage, $this->invoiced->getErrorMessage());
    }
}
