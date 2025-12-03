<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Api\Data;

interface DynamicFieldsInterface
{
    /**
     * Record id
     *
     * @return string
     */
    public function getRecordId(): string;

    /**
     *  Set record id
     *
     * @param string $recordId
     * @return DynamicFieldsInterface
     */
    public function setRecordId(string $recordId): DynamicFieldsInterface;

    /**
     * Field Name
     *
     * @return string
     */
    public function getFieldName(): string;

    /**
     * Set field Name
     *
     * @param string $fieldName
     * @return DynamicFieldsInterface
     */
    public function setFieldName(string $fieldName): DynamicFieldsInterface;

    /**
     * Field Label
     *
     * @return string
     */
    public function getFieldLabel(): string;

    /**
     * Set  field Label
     *
     * @param string $fieldLabel
     * @return DynamicFieldsInterface
     */
    public function setFieldLabel(string $fieldLabel): DynamicFieldsInterface;

    /**
     * Default
     *
     * @return string
     */
    public function getDefault(): string;

    /**
     * Set default
     *
     * @param string $default
     * @return DynamicFieldsInterface
     */
    public function setDefault(string $default): DynamicFieldsInterface;

    /**
     * Visible
     *
     * @return string
     */
    public function getVisible(): string;

    /**
     * Set visible
     *
     * @param string $visible
     * @return DynamicFieldsInterface
     */
    public function setVisible(string $visible): DynamicFieldsInterface;

    /**
     * Editable
     *
     * @return string
     */
    public function getEditable(): string;

    /**
     * Set editable
     *
     * @param string $editable
     * @return DynamicFieldsInterface
     */
    public function setEditable(string $editable): DynamicFieldsInterface;

    /**
     * Required
     *
     * @return string
     */
    public function getRequired(): string;

    /**
     * Set required
     *
     * @param string $required
     * @return DynamicFieldsInterface
     */
    public function setRequired(string $required): DynamicFieldsInterface;

    /**
     * Mask
     *
     * @return string
     */
    public function getMask(): string;

    /**
     * Set mask
     *
     * @param string $mask
     * @return DynamicFieldsInterface
     */
    public function setMask(string $mask): DynamicFieldsInterface;

    /**
     * Custom Mask
     *
     * @return string
     */
    public function getCustomMask(): string;

    /**
     * Set custom Mask
     *
     * @param string $customMask
     * @return DynamicFieldsInterface
     */
    public function setCustomMask(string $customMask): DynamicFieldsInterface;

    /**
     * Error message
     *
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * Set error message
     *
     * @param string $errorMessage
     * @return DynamicFieldsInterface
     */
    public function setErrorMessage(string $errorMessage): DynamicFieldsInterface;
}
