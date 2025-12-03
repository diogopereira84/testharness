<?php
declare(strict_types=1);
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Plugin;

use Psr\Log\LoggerInterface;
use Magento\Framework\DB\Select;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ProductMainQueryPlugin
{
    public function __construct(
        private LoggerInterface $logger,
        private ToggleConfig $toggleConfig
    ) {}

    public function afterGetQuery(
        \Magento\CatalogDataExporter\Model\Query\ProductMainQuery $subject,
        Select $select
    ): Select {
        try {
            if (!$this->toggleConfig->getToggleConfigValue('tech_titans_e_484727')) {
                return $select;
            }

            $columns = $select->getPart(\Zend_Db_Select::COLUMNS);
            foreach ($columns as &$column) {
                if ($column[2] === 'type') {
                    $column[1] = new \Zend_Db_Expr(
                        "CASE WHEN main_table.type_id = 'commercial' THEN 'simple' ELSE main_table.type_id END"
                    );
                }
            }
            $select->setPart(\Zend_Db_Select::COLUMNS, $columns);

        } catch (\Exception $e) {
            $this->logger->error(
                __('An Exception occurred while syncing commercial product from ALS at line ' . __LINE__ . ' in file ' . __FILE__)
            );
        }

        return $select;
    }
}