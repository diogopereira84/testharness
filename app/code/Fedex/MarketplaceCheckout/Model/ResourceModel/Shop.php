<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Niket Kanoi <niketkanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\ResourceModel;

use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Mirakl\MMP\FrontOperator\Domain\Collection\Shop\ShopCollection;
use Mirakl\Process\Model\Process;

class Shop extends \Mirakl\Core\Model\ResourceModel\Shop
{
    private const SHIPPING_METHODS = 'shipping_methods';

    /**
     * @param ShopCollection $shops
     * @param Process $process
     * @param int $chunkSize
     * @return  int
     * @throws  \Exception
     */
    public function synchronize(ShopCollection $shops, Process $process, $chunkSize = 100)
    {
        if (!$shops->count()) {
            throw new \Exception('Shops to synchronize cannot be empty.');
        }

        // Load existing mirakl_shop_ids EAV attribute
        $attribute = $this->attributeRepository->get('mirakl_shop_ids');
        if (!$attribute) {
            throw new \Exception('mirakl_shop_ids attribute is not created.');
        }

        $adapter = $this->getConnection();

        // Load existing EAV option ids associated to shop ids
        $customShops = $this->getEavOptionIds();

        $eavShopOptions = [];
        foreach ($attribute->getOptions() as $option) {
            /** @var \Magento\Eav\Api\Data\AttributeOptionInterface $option */
            if ($option->getValue()) {
                $eavShopOptions[$option->getValue()] = $option;
            }
        }

        $fields = array_keys($adapter->describeTable($this->getMainTable()));

        // Exclude shipping_methods column from the list of fields to sync
        $fields = array_diff($fields, [SELF::SHIPPING_METHODS]);
        
        $insert = [];

        foreach ($shops->toArray() as $shop) {
            // Check if EAV option exists
            if (isset($customShops[$shop['id']]) &&
                isset($eavShopOptions[$customShops[$shop['id']]])) {
                $optionId = $customShops[$shop['id']];
                // Update EAV option if label has changed
                if ($eavShopOptions[$optionId]->getLabel() != $shop['name']) {
                    $this->getConnection()->update(
                        $this->getTable('eav_attribute_option_value'),
                        ['value' => $shop['name']],
                        ['option_id = ?' => $optionId, 'store_id = ?' => 0]
                    );
                }
            } else {
                // Create EAV option
                $optionTable = $this->getTable('eav_attribute_option');
                $optionValueTable = $this->getTable('eav_attribute_option_value');

                $data = ['attribute_id' => $attribute->getId()];
                $this->getConnection()->insert($optionTable, $data);
                $optionId = $this->getConnection()->lastInsertId($optionTable);

                $data = ['option_id' => $optionId, 'store_id' => 0, 'value' => $shop['name']];
                $this->getConnection()->insert($optionValueTable, $data);
            }

            $data = [];

            foreach ($fields as $field) {
                $data[$field] = isset($shop[$field]) ? $shop[$field] : null;
            }
            $data['free_shipping'] = $shop['shipping_info']['free_shipping'];
            $data['eav_option_id'] = $optionId;
            $data['additional_info'] = serialize($shop);

            $dateCreated = new \DateTime($shop['date_created']);
            $data['date_created'] = $dateCreated->format(Mysql::TIMESTAMP_FORMAT);

            if (!empty($shop['closed_from'])) {
                $closedFrom = new \DateTime($shop['closed_from']);
                $data['closed_from'] = $closedFrom->format(Mysql::TIMESTAMP_FORMAT);
            }

            if (!empty($shop['closed_to'])) {
                $closedTo = new \DateTime($shop['closed_to']);
                $data['closed_to'] = $closedTo->format(Mysql::TIMESTAMP_FORMAT);
            }

            $insert[] = $data;
            $process->output(__('Saving shop %1', $data['id']));
        }

        $affected = 0;
        foreach (array_chunk($insert, $chunkSize) as $shopsData) {
            $affected += $adapter->insertOnDuplicate($this->getMainTable(), $shopsData, $fields);
        }

        return $affected;
    }
}

?>