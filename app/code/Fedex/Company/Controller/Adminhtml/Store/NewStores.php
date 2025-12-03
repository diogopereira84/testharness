<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Controller\Adminhtml\Store;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\DB\Helper as DbHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ResourceModel\Group\CollectionFactory as GroupCollection;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollection;
use Magento\Framework\App\RequestInterface;

/**
 * Controller for obtaining stores suggestions by query.
 */
class NewStores implements HttpPostActionInterface, HttpGetActionInterface
{
    /**
     * constructor function
     *
     * @param DbHelper $dbHelper
     * @param GroupCollection $groupCollection
     * @param StoreCollection $storeCollection
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @return void
     */
    public function __construct(
        private DbHelper $dbHelper,
        private GroupCollection $groupCollection,
        private StoreCollection $storeCollection,
        private RequestInterface $request,
        protected ResultFactory $resultFactory
    )
    {
    }
    /**
     * Get Store/Store view list
     *
     * @return Json
     */
    public function execute()
    {
        $name = $this->request->getParam('name');
        $storeId = $this->request->getParam('new_store_id');
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            if ($storeId) {
                $result->setData(
                    $this->getSuggestedStoreViews($name, $storeId)
                );
            } else {
                $result->setData(
                    $this->getSuggestedStores($name)
                );
            }
        } catch (LocalizedException $e) {
            $result->setData(['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Get suggested store by query.
     *
     * @param string $query
     * @return array
     */
    private function getSuggestedStores($query)
    {
        $escapedQuery = $this->dbHelper->escapeLikeValue(
            $query,
            ['position' => 'start']
        );

        $searchResult = $this->groupCollection->create()
            ->addFieldToFilter(
                'name',
                ['like' => $escapedQuery]
            )
            ->addFieldToFilter(
                'group_id',
                ['gt' => 0]
            );
        $stores = $searchResult->getData();

        return array_map(
            function ($store) {
                return [
                    'id' => $store['group_id'],
                    'name' => $store['name'],
                ];
            },
            array_values($stores)
        );
    }

    /**
     * Get suggested store view by query.
     *
     * @param string $query
     * @return array
     */
    private function getSuggestedStoreViews($query, $store_id)
    {
        $escapedQuery = $this->dbHelper->escapeLikeValue(
            $query,
            ['position' => 'start']
        );

        $searchResult = $this->storeCollection->create()
            ->addFieldToFilter(
                'group_id',
                ['eq' => $store_id]
            )->addFieldToFilter(
                'name',
                ['like' => $escapedQuery]
            );

        $stores = $searchResult->getData();
        return array_map(
            function ($store) {
                return [
                    'id' => $store['store_id'],
                    'name' => $store['name'],
                ];
            },
            array_values($stores)
        );
    }
}
