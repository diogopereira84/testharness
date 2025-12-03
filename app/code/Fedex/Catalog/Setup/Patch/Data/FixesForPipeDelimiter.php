<?php
declare (strict_types = 1);

namespace Fedex\Catalog\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * @codeCoverageIgnore
 */
class FixesForPipeDelimiter implements DataPatchInterface
{

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $valuesSeparatedByPipe = $this->getValuesSeparatedByPipe();
        foreach ($valuesSeparatedByPipe as &$data) {
            $data['value'] = str_replace('|', ',',$data['value']);
        }
        $this->updatePipeValuesWithComma($valuesSeparatedByPipe);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    private function updatePipeValuesWithComma($valuesSeparatedByPipe)
    {
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select();
        $select->reset();
        $connection->insertOnDuplicate(
            'catalog_product_entity_text',
            $valuesSeparatedByPipe,
            ['value_id', 'value']
        );
    }

    private function getValuesSeparatedByPipe()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select();
        $select->reset();
        $select->from(
            ['cpet' => 'catalog_product_entity_text'],
            ['value_id', 'value']
        )
        ->where('cpet.value REGEXP ?', '[0-9]+\|[0-9]+');

        return $connection->fetchAll($select);
    }
}
