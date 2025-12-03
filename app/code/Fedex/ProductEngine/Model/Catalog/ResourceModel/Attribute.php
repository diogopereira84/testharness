<?php
/**
 * @category Fedex
 * @package Fedex_ProductEngine
 * @copyright Fedex (c) 2021.
 * @author Iago Lima <ilima@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\ProductEngine\Model\Catalog\ResourceModel;

use Magento\Framework\Model\AbstractModel;

class Attribute extends \Magento\Catalog\Model\ResourceModel\Attribute
{
    CONST CHOICE_ID = 'choice_id';
    CONST VALUE = 'value';
    /**
     * Overriden to insert choice_id logic
     */
    protected function _updateAttributeOption($object, $optionId, $option)
    {
        $connection = $this->getConnection();
        $table = $this->getTable('eav_attribute_option');
        // ignore strings that start with a number
        $intOptionId = is_numeric($optionId) ? (int)$optionId : 0;

        if (!empty($option['delete'][$optionId])) {
            if ($intOptionId) {
                $connection->delete($table, ['option_id = ?' => $intOptionId]);
                $this->clearSelectedOptionInEntities($object, $intOptionId);
            }
            return false;
        }

        $sortOrder = empty($option['order'][$optionId]) ? 0 : $option['order'][$optionId];
        $choiceId = empty($option[SELF::CHOICE_ID][$optionId]) ? 0 : $option[SELF::CHOICE_ID][$optionId];
        if (!$intOptionId) {
            $data = ['attribute_id' => $object->getId(), 'sort_order' => $sortOrder, SELF::CHOICE_ID => $choiceId];
            $connection->insert($table, $data);
            $intOptionId = $connection->lastInsertId($table);
        } else {
            $data = ['sort_order' => $sortOrder, SELF::CHOICE_ID => $choiceId];
            $where = ['option_id = ?' => $intOptionId];
            $connection->update($table, $data, $where);
        }

        return $intOptionId;
    }

    private function clearSelectedOptionInEntities(AbstractModel $object, int $optionId)
    {
        $backendTable = $object->getBackendTable();
        $attributeId = $object->getAttributeId();
        if (!$backendTable || !$attributeId) {
            return;
        }

        $connection = $this->getConnection();
        $where = $connection->quoteInto('attribute_id = ?', $attributeId);
        $update = [];

        if ($object->getBackendType() === 'varchar') {
            $where.= ' AND ' . $connection->prepareSqlCondition(SELF::VALUE, ['finset' => $optionId]);
            $concat = $connection->getConcatSql(["','", SELF::VALUE, "','"]);
            $expr = $connection->quoteInto(
                "TRIM(BOTH ',' FROM REPLACE($concat,',?,',','))",
                $optionId
            );
            $update[SELF::VALUE] = new \Zend_Db_Expr($expr);
        } else {
            $where.= $connection->quoteInto(' AND value = ?', $optionId);
            $update[SELF::VALUE] = null;
        }

        $connection->update($backendTable, $update, $where);
    }
}
