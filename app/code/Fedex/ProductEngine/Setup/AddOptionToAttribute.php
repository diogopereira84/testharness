<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Setup;

use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class AddOptionToAttribute
{
    CONST VALUE = 'value';
    CONST CHOICE_ID = 'choice_id';
    CONST SORT_ORDER = 'sort_order';
    CONST OPTION_ID = 'option_id';

    public function __construct(
        private ModuleDataSetupInterface $setup
    )
    {
    }

    public function execute(array $option): void
    {
        $optionTable = $this->setup->getTable('eav_attribute_option');
        $optionValueTable = $this->setup->getTable('eav_attribute_option_value');
        $this->addValues($option, $optionTable, $optionValueTable);
    }

    private function addValues(array $option, string $optionTable, string $optionValueTable): void
    {
        $values = $option['values'];
        $attributeId = (int)$option['attribute_id'];
        $existingOptions = $this->getExistingAttributeOptions($attributeId, $optionTable, $optionValueTable);
        foreach ($values as $sortOrder => $value) {
            $optionValue = $value[SELF::VALUE];
            $optionChoiceId = $value[SELF::CHOICE_ID];
            // add option
            $data = ['attribute_id' => $attributeId, SELF::SORT_ORDER => $sortOrder, SELF::CHOICE_ID => $optionChoiceId];
            if (!$this->isExistingOptionValue($optionValue, $existingOptions)) {

                $this->setup->getConnection()->insert($optionTable, $data);

                //add option value
                $intOptionId = $this->setup->getConnection()->lastInsertId($optionTable);
                $data = [SELF::OPTION_ID => $intOptionId, 'store_id' => 0, SELF::VALUE => $optionValue];
                $this->setup->getConnection()->insert($optionValueTable, $data);

            } elseif ($optionId = $this->getExistingOptionIdWithDiffSortOrder(
                $sortOrder,
                $optionValue,
                $existingOptions
            )) {

                $this->setup->getConnection()->update(
                    $optionTable,
                    [SELF::SORT_ORDER => $sortOrder, SELF::CHOICE_ID => $optionChoiceId],
                    ['option_id = ?' => $optionId]
                );

            } elseif ($optionId = $this->getExistingOptionId(
                $optionValue,
                $existingOptions
            )) {

                $this->setup->getConnection()->update(
                    $optionTable,
                    [SELF::CHOICE_ID => $optionChoiceId],
                    ['option_id = ?' => $optionId]
                );
            }
        }
    }

    private function isExistingOptionValue(string $value, array $existingOptions): bool
    {
        foreach ($existingOptions as $option) {
            if ($option[SELF::VALUE] == $value) {
                return true;
            }
        }

        return false;
    }

    private function getExistingAttributeOptions(int $attributeId, string $optionTable, string $optionValueTable): array
    {
        $select = $this->setup
            ->getConnection()
            ->select()
            ->from(['o' => $optionTable])
            ->reset('columns')
            ->columns([SELF::OPTION_ID, SELF::SORT_ORDER])
            ->join(['ov' => $optionValueTable], 'o.option_id = ov.option_id', SELF::VALUE)
            ->where(AttributeInterface::ATTRIBUTE_ID . ' = ?', $attributeId)
            ->where('store_id = 0');

        return $this->setup->getConnection()->fetchAll($select);
    }

    private function getExistingOptionIdWithDiffSortOrder(int $sortOrder, string $value, array $existingOptions): ?int
    {
        foreach ($existingOptions as $option) {
            if ($option[SELF::VALUE] == $value && $option[SELF::SORT_ORDER] != $sortOrder) {
                return (int)$option[SELF::OPTION_ID];
            }
        }

        return null;
    }

    private function getExistingOptionId(string $value, array $existingOptions): ?int
    {
        foreach ($existingOptions as $option) {
            if ($option[SELF::VALUE] == $value) {
                return (int)$option[SELF::OPTION_ID];
            }
        }

        return null;
    }
}
