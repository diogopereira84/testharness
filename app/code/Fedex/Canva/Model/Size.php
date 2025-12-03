<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Model;

use Fedex\Canva\Api\Data\SizeInterface;
use Magento\Framework\DataObject;

/**
 * @codeCoverageIgnore
 */
class Size extends DataObject implements SizeInterface
{
    public const RECORD_ID = 'record_id';
    public const DEFAULT = 'default';
    public const DEFAULT_PREFIX = 'option_';
    public const DEFAULT_VALUE_TRUE = '1';
    public const DEFAULT_VALUE_FALSE = '0';
    public const DEFAULT_VALUES = [ self::DEFAULT_VALUE_TRUE, self::DEFAULT_VALUE_FALSE ];
    public const PRODUCT_MAPPING_ID = 'product_mapping_id';
    public const DISPLAY_WIDTH = 'display_width';
    public const DISPLAY_HEIGHT = 'display_height';
    public const ORIENTATION = 'orientation';
    public const POSITION = 'position';
    public const INITIALIZE = 'initialize';
    public const CANVA_SIZE_GROUP_NAME = 'Canva Sizes';
    public const CANVA_SIZE_GROUP_ID = 'canva_size';

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
    public function setRecordId(string $recordId): SizeInterface
    {
        return $this->setData(self::RECORD_ID, $recordId);
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
    public function setDefault(string $default): SizeInterface
    {
        return $this->setData(self::DEFAULT, $default);
    }

    /**
     * @inheritDoc
     */
    public function geProductMappingId(): string
    {
        return $this->getData(self::PRODUCT_MAPPING_ID) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function seProductMappingId(string $productMappingId): SizeInterface
    {
        return $this->setData(self::PRODUCT_MAPPING_ID, $productMappingId);
    }

    /**
     * @inheritDoc
     */
    public function getDisplayWidth(): string
    {
        return $this->getData(self::DISPLAY_WIDTH) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setDisplayWidth(string $displayWidth): SizeInterface
    {
        return $this->setData(self::DISPLAY_WIDTH, $displayWidth);
    }

    /**
     * @inheritDoc
     */
    public function getDisplayHeight(): string
    {
        return $this->getData(self::DISPLAY_HEIGHT) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setDisplayHeight(string $displayHeight): SizeInterface
    {
        return $this->setData(self::DISPLAY_HEIGHT, $displayHeight);
    }

    /**
     * @inheritDoc
     */
    public function getOrientation(): string
    {
        return $this->getData(self::ORIENTATION) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setOrientation(string $orientation): SizeInterface
    {
        return $this->setData(self::ORIENTATION, $orientation);
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): string
    {
        return $this->getData(self::POSITION) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setPosition(string $position): SizeInterface
    {
        return $this->setData(self::POSITION, $position);
    }
}
