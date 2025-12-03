<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer;

use Fedex\SubmitOrderSidebar\Api\DataSourceCompositeInterface;
use Fedex\SubmitOrderSidebar\Model\Data\UnifiedDataLayerFactory;

class DataSourceComposite implements DataSourceCompositeInterface
{
    /**
     * @param UnifiedDataLayerFactory $unifiedDataLayerFactory
     * @param array $sources
     */
    public function __construct(
        private readonly UnifiedDataLayerFactory $unifiedDataLayerFactory,
        private readonly array $sources = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function compose(array $checkoutData = []): array
    {
        $data = [];
        $unifiedDataLayer = $this->unifiedDataLayerFactory->create();

        foreach ($this->sources as $source) {
            $source->map($unifiedDataLayer, $checkoutData);
        }

        $keyDelivery = "deliveries[" . count($unifiedDataLayer->getDeliveries()) ."]";
        $data[$keyDelivery] = $unifiedDataLayer->getDeliveries();
        foreach ($data[$keyDelivery] as $key => $delivery) {
            $data[$keyDelivery][$key]["lineItems[" . count($delivery['lineItems']) ."]"] = $delivery['lineItems'];
            unset($data[$keyDelivery][$key]["lineItems"]);
        }
        $unifiedDataLayer->addData($data);
        $unifiedDataLayer->unsetData(['deliveries']);
        return $unifiedDataLayer->toArray();
    }
}
