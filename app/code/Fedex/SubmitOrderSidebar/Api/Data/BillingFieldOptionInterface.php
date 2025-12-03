<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Nathan Alves <nathan.alves.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Api\Data;

interface BillingFieldOptionInterface
{
    /**
     * Get Field Name
     *
     * @return string
     */
    public function getFieldName(): string;

    /**
     * Set Field Name
     *
     * @param string $fieldName
     * @return mixed
     */
    public function setFieldName(string $fieldName);

    /**
     * Get Value
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Set Value
     *
     * @param string $value
     * @return mixed
     */
    public function setValue(string $value);
}
