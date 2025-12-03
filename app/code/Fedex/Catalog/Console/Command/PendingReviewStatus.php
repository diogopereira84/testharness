<?php

declare(strict_types=1);

namespace Fedex\Catalog\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Console command class to update pending review status
 */
class PendingReviewStatus extends Command
{
    private const LIMIT = 'limit';
    private const ATTRIBUTE_CODE = 'is_pending_review';
    private const CATALOG_PRODUCT_ENTITY_TABLE = 'catalog_product_entity_int';

    /**
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository
     * @param \Magento\Catalog\Model\ResourceModel\Attribute $attributeResource
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     */
    public function __construct(
        private ResourceConnection $resourceConnection
    ) {
        parent::__construct();
    }

    /**
     * Configure Method
     */
    protected function configure(): void
    {
        $this->setName('catalog:pendingReviewUpdate:command');
        $this->setDescription('Console command to update pending review status with items where not available.');
        $this->addOption(
            self::LIMIT,
            null,
            InputOption::VALUE_REQUIRED,
            'Limit'
        );

        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = 0;
        
        try {
            if ($limit = $input->getOption(self::LIMIT)) {
                $output->writeln('<info>Provided limit is `' . $limit . '`</info>');
            }

            $connection = $this->resourceConnection->getConnection();
            $eavAttributeTable = $connection->getTableName('eav_attribute');
            $productEntityIntTable = $connection->getTableName('catalog_product_entity_int');

            $attributeQuery = $connection->select('attribute_id')->from($eavAttributeTable)->where('attribute_code = ?', self::ATTRIBUTE_CODE);
            $attributeId = $connection->fetchOne($attributeQuery);

            if ($attributeId) {
               $countQuery = "select count(DISTINCT(row_id)) as totalCount from ".$productEntityIntTable." where row_id not in(select DISTINCT(row_id) from catalog_product_entity_int where attribute_id = $attributeId)";
                $totalCount = $connection->fetchOne($countQuery);

                $query = "select DISTINCT(row_id) from ".$productEntityIntTable." where row_id not in(select DISTINCT(row_id) from catalog_product_entity_int where attribute_id = $attributeId) limit ".$limit;

                $result = $connection->fetchAll($query);
                $recordsCount = count($result);
                if (!empty($result[0])) {
                    foreach ($result as $row) {
                        $insertData = ['attribute_id' => $attributeId, 'value' => '0', 'row_id' => $row['row_id']];
                        $connection->insert(
                            $productEntityIntTable,
                            $insertData,
                            []
                        );
                    }
                }
                
            }

            $output->writeln('<info>'.$recordsCount.' / ' . $totalCount .' rows were successfully inserted.</info>');

        } catch (Exception $e) {
            $output->writeln(sprintf('<error>%s</error>',$e->getMessage()));
            $exitCode = 1;
        }
         
        return $exitCode;
    }
}
