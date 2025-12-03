<?php

declare(strict_types=1);

namespace Fedex\CustomerCanvas\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class DeleteCustomerCanvasUuidValues implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {}

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        // Get attribute_id for 'customer_canvas_uuid'
        $attributeIdSelect = $connection->select()
            ->from(['ea' => 'eav_attribute'], ['attribute_id'])
            ->where('ea.attribute_code = ?', 'customer_canvas_uuid')
            ->where('ea.entity_type_id = ?', new \Zend_Db_Expr(
                '(SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = "customer" LIMIT 1)'
            ));

        $attributeId = $connection->fetchOne($attributeIdSelect);

        if ($attributeId) {
            // Delete from customer_entity_varchar where value is set
            $connection->delete(
                'customer_entity_varchar',
                [
                    'attribute_id = ?' => $attributeId,
                    'value IS NOT NULL',
                    'value != ?' => ''
                ]
            );
        }

        $connection->endSetup();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}

