<?php

namespace Fedex\CustomerGroup\Model;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array|mixed
     */
    private mixed $loadedData;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $groupFactory,
        protected Registry $coreRegistry,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $groupFactory->create();
    }

    public function getData()
    {
        // Get the customer group ID from the request data
        $groupId = $this->coreRegistry->registry(RegistryConstants::CURRENT_GROUP_ID);
        $this->loadedData = [];
        // Load data for the specific customer group
        if ($groupId) {
            $this->loadedData['customergroup_general']['id'] = $groupId;
        }
        return $this->loadedData;
    }
}
