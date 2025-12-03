<?php
declare(strict_types=1);

namespace Fedex\Shipment\Service;

use Fedex\Shipment\Api\ProducingAddressServiceInterface;
use Fedex\Shipment\Model\ProducingAddress;
use Fedex\Shipment\Model\ResourceModel\ProducingAddress\CollectionFactory;

class ProducingAddressService implements ProducingAddressServiceInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * ProducingAddressService constructor.
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Load ProducingAddress by order_id
     *
     * @param int|string $orderId
     * @return ProducingAddress|null
     */
    public function getByOrderId($orderId): ?ProducingAddress
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('order_id', $orderId);
        $collection->setPageSize(1);
        $item = $collection->getFirstItem();
        if ($item && $item->getId()) {
            return $item;
        }
        return null;
    }
}
