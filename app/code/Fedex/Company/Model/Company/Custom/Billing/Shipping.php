<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Model\Company\Custom\Billing;

use Fedex\Company\Api\Data\CustomBillingShippingInterface;
use Magento\Framework\DataObject;

class Shipping extends DataObject implements CustomBillingShippingInterface
{
    /**
     * Record id field key
     */
    private const RECORD_ID = 'record_id';

    /**
     * Field name field key
     */
    private const FIELD_NAME = 'field_name';

    /**
     * Field label field key
     */
    private const FIELD_LABEL = 'field_label';

    /**
     * Default field key
     */
    private const DEFAULT = 'default';

    /**
     * Visible field key
     */
    private const VISIBLE = 'visible';

    /**
     * Editable field key
     */
    private const EDITABLE = 'editable';

    /**
     * Required field key
     */
    private const REQUIRED = 'required';

    /**
     * Mask field key
     */
    private const MASK = 'mask';

    /**
     * Custom mask field key
     */
    private const CUSTOM_MASK = 'custom_mask';

    /**
     * Error message field key
     */
    private const ERROR_MESSAGE = 'error_message';

    /**
     * @inheritDoc
     */
    public function getRecordId(): string
    {
        return $this->getData(self::RECORD_ID) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setRecordId(string $recordId): CustomBillingShippingInterface
    {
        return $this->setData(self::RECORD_ID, $recordId);
    }

    /**
     * @inheritDoc
     */
    public function getFieldName(): string
    {
        return $this->getData(self::FIELD_NAME) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setFieldName(string $fieldName): CustomBillingShippingInterface
    {
        return $this->setData(self::FIELD_NAME, $fieldName);
    }

    /**
     * @inheritDoc
     */
    public function getFieldLabel(): string
    {
        return $this->getData(self::FIELD_LABEL) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setFieldLabel(string $fieldLabel): CustomBillingShippingInterface
    {
        return $this->setData(self::FIELD_LABEL, $fieldLabel);
    }

    /**
     * @inheritDoc
     */
    public function getDefault(): string
    {
        return $this->getData(self::DEFAULT) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setDefault(string $default): CustomBillingShippingInterface
    {
        return $this->setData(self::DEFAULT, $default);
    }

    /**
     * @inheritDoc
     */
    public function getVisible(): string
    {
        return $this->getData(self::VISIBLE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setVisible(string $visible): CustomBillingShippingInterface
    {
        return $this->setData(self::VISIBLE, $visible);
    }

    /**
     * @inheritDoc
     */
    public function getEditable(): string
    {
        return $this->getData(self::EDITABLE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setEditable(string $editable): CustomBillingShippingInterface
    {
        return $this->setData(self::EDITABLE, $editable);
    }

    /**
     * @inheritDoc
     */
    public function getRequired(): string
    {
        return $this->getData(self::REQUIRED) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setRequired(string $required): CustomBillingShippingInterface
    {
        return $this->setData(self::REQUIRED, $required);
    }

    /**
     * @inheritDoc
     */
    public function getMask(): string
    {
        return $this->getData(self::MASK) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setMask(string $mask): CustomBillingShippingInterface
    {
        return $this->setData(self::MASK, $mask);
    }

    /**
     * @inheritDoc
     */
    public function getCustomMask(): string
    {
        return $this->getData(self::CUSTOM_MASK) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setCustomMask(string $customMask): CustomBillingShippingInterface
    {
        return $this->setData(self::CUSTOM_MASK, $customMask);
    }

    /**
     * @inheritDoc
     */
    public function getErrorMessage(): string
    {
        return $this->getData(self::ERROR_MESSAGE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setErrorMessage(string $errorMessage): CustomBillingShippingInterface
    {
        return $this->setData(self::ERROR_MESSAGE, $errorMessage);
    }
}
