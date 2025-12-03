<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceCheckout
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory;
use Psr\Log\LoggerInterface;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    // @codingStandardsIgnoreStart
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $shopCollectionFactory,
        protected LoggerInterface $logger,
        protected Json $json,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $shopCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    // @codingStandardsIgnoreEnd
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $shop = $this->collection->getFirstItem();
        $data = $shop->getData();
        $this->loadedData[$shop->getId()] = $data;
        if (!empty($data['shipping_methods'])) {
            $this->loadedData[$shop->getId()]['shipping_method'] = $this->json->unserialize($data['shipping_methods']);
        }
        return $this->loadedData;
    }
}