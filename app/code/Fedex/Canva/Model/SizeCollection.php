<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Model;

use ArrayIterator;
use Fedex\Canva\Model\Exception\DuplicatedCollectionItem;
use Fedex\Canva\Api\Data\SizeCollectionInterface;
use Fedex\Canva\Api\Data\SizeInterfaceFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class SizeCollection implements SizeCollectionInterface
{
    /**
     * Collection items
     *
     * @var DataObject[]
     */
    protected array $items = [];

    /**
     * @param SizeInterfaceFactory $sizeFactory
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private SizeInterfaceFactory $sizeFactory,
        private SerializerInterface $serializer,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Return object as Array
     *
     * @param array $arrRequiredFields
     * @return array
     */
    public function toArray(array $arrRequiredFields = []): array
    {
        $arrItems = [];
        foreach ($this as $item) {
            $arrItems[] = $item->toArray($arrRequiredFields);
        }
        return $arrItems;
    }

    /**
     * Sort items
     *
     * @throws \Exception
     */
    public function sort(): void
    {
        $arrItems = $this->toArray();
        $this->items = [];
        usort($arrItems, function ($a, $b) {
            return ((int)$a['position']) <=> ((int)$b['position']);
        });
        foreach ($arrItems as $item) {
            $this->addItem($this->sizeFactory->create(['data' => $item]));
        }
    }

    /**
     * @inheritDoc
     * @return \Traversable
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Converts object to json
     *
     * @return bool|string
     */
    public function toJson()
    {
        return $this->serializer->serialize($this->toArray());
    }

    /**
     * Return the Default Option
     *
     * @return Size
     */
    public function getDefault(): Size
    {
        $defaultOption = last(array_filter($this->items, function ($item) {
            return $item[Size::DEFAULT] === Size::DEFAULT_VALUE_TRUE;
        }));
        if (!$defaultOption) {
            $defaultOption = $this->sizeFactory->create();
        }
        return $defaultOption;
    }

    /**
     * Return the Default OptionId
     *
     * @return string
     */
    public function getDefaultOptionId(): string
    {
        return Size::DEFAULT_PREFIX . ($this->getDefault()->getRecordId() ?? '0');
    }

    /**
     * Set Default Option
     *
     * @param int $id
     * @return $this
     */
    public function setDefaultOption(int $id): SizeCollection
    {
        $keys = array_keys($this->items);
        foreach ($keys as $key) {
            $this->items[$key]['default'] = Size::DEFAULT_VALUE_FALSE;
        }
        if (isset($this->items[$id])) {
            $this->items[$id]['default'] = Size::DEFAULT_VALUE_TRUE;
        }
        return $this;
    }

    /**
     * Adding item to item array
     *
     * @param DataObject $item
     * @return $this
     * @throws \Exception
     */
    public function addItem(DataObject $item)
    {
        $itemId = $item->getId();

        if ($itemId !== null) {
            if (isset($this->items[$itemId])) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Item (' . get_class($item) . ') with the same ID "' . $item->getId() . '" already exists.');
                //phpcs:ignore Magento2.Exceptions.DirectThrow
                throw new DuplicatedCollectionItem(
                    'Item (' . get_class($item) . ') with the same ID "' . $item->getId() . '" already exists.'
                );
            }
            $this->items[$itemId] = $item;
        } else {
            $this->items[] = $item;
        }
        return $this;
    }
}
