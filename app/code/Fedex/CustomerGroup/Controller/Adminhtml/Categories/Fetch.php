<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Controller\Adminhtml\Categories;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

/**
 * Fetch class for getting category based on customergroup
 */
class Fetch implements ActionInterface
{
    const COMPANY_TABLE = 'company';

    /**
     * Fetch Class Constructor
     *
     * @param Context $context
     * @param ResourceConnection $resourceConnection
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     *
     */
    public function __construct(
        protected Context $context,
        protected ResourceConnection $resourceConnection,
        protected JsonFactory $resultJsonFactory,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * Execute class
     *
     * @return Json
     */
    public function execute():Json
    {
        $parentId = $this->context->getRequest()->getParam('parent_id');
        $parentId = isset($parentId) ? $parentId : null;
        $categoryId = null;
        $resultJson = $this->resultJsonFactory->create();
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName(static::COMPANY_TABLE);
        try {

            if ($parentId) {
                $select = $connection->select()->from(
                    $tableName,
                    ['shared_catalog_id']
                )->where('customer_group_id = ?', $parentId);

                $categoryId = $connection->fetchOne($select);
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . 
            ' Error with category permission fetch for the group: '. $parentId . 'is '. $e->getMessage());
        }
        $resultJson->setData([
                'categoryId' => $categoryId
        ]);
        return $resultJson;
    }
}
