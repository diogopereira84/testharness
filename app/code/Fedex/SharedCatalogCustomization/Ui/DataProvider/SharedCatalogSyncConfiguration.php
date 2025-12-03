<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Ui\DataProvider;

use Magento\Framework\App\Request\Http;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration\CollectionFactory;

/**
 * Shared catalog sync edit form data provider.
 */
class SharedCatalogSyncConfiguration extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var array
     */
    private $loadedData;

    /**
     * @var CollectionFactory $collectionFactory
     */
    private $collectionFactory;

    /**
     * SharedCatalogSyncConfiguration  constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param Http $request
     * @param array $meta [optional]
     * @param array $data [optional]
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private Http $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        
        $items = $this->collection->getItems();

        if ($this->collection->count() > 0) {
            foreach ($items as $item) {
                $this->loadedData[$item->getSharedCatalogId()]['catalog_sync_config'] = $item->getData();
            }
        } else {
            $sharedCatalogId = $this->request->getParam('shared_catalog_id');
            $this->loadedData[$sharedCatalogId]['catalog_sync_config']['shared_catalog_id'] = $sharedCatalogId;
        }
        
        return $this->loadedData;
    }
}
