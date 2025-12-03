<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\Data;

use Fedex\SubmitOrderSidebar\Api\Data\BillingFieldOptionInterface;
use Magento\Framework\DataObject;

class BillingField extends DataObject implements BillingFieldOptionInterface
{
    private const FIELD_NAME = 'fieldName';
    private const VALUE = 'value';

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
    public function setFieldName(string $fieldName)
    {
        return $this->setData(self::FIELD_NAME, $fieldName);
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return $this->getData(self::VALUE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $value)
    {
        return $this->setData(self::VALUE, $value);
    }
}
