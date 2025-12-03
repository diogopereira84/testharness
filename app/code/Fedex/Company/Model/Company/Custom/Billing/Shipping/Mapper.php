<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Model\Company\Custom\Billing\Shipping;

use Exception;
use Fedex\Company\Api\Data\CustomBillingShippingInterfaceFactory;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;

class Mapper
{
    /**
     * Construct Mapper class
     *
     * Maps from <-> to multiple formats of invoiced collection data structure
     *
     * @param CustomBillingShippingInterfaceFactory $billingShippingFactory
     * @param CollectionFactory                     $collectionFactory
     * @param Json                                  $json
     * @param JsonValidator                         $jsonValidator
     */
    public function __construct(
        private readonly CustomBillingShippingInterfaceFactory $billingShippingFactory,
        private readonly CollectionFactory                     $collectionFactory,
        private readonly Json                                  $json,
        private readonly JsonValidator                         $jsonValidator
    ) {
    }

    /**
     * @param array $data
     *
     * @return Collection
     *
     * @throws Exception
     */
    public function fromArray(array $data): Collection
    {
        $collection = $this->collectionFactory->create();
        $this->map($collection, $data);

        return $collection;
    }

    /**
     * Build collection from json
     *
     * @param string $json
     *
     * @return Collection
     *
     * @throws Exception
     */
    public function fromJson(string $json): Collection
    {
        $collection = $this->collectionFactory->create();

        if ($this->jsonValidator->isValid($json)) {
            $data = $this->json->unserialize($json);
            $this->map($collection, $data);
        }

        return $collection;
    }

    /**
     * @param array $data
     *
     * @return bool|string
     *
     * @throws Exception
     */
    public function fromArrayToJson(array $data): bool|string
    {
        return $this->toJson($this->fromArray($data));
    }

    /**
     * Converts collection to json
     *
     * @param Collection $collection
     *
     * @return bool|string
     */
    public function toJson(Collection $collection): bool|string
    {
        return $this->json->serialize($collection->toArray()['items']);
    }

    /**
     * Map data array to the collection object
     *
     * @param Collection $collection
     * @param array $data
     *
     * @return void
     *
     * @throws Exception
     */
    private function map(Collection $collection, array $data): void
    {
        foreach ($data as $item) {
            $collection->addItem(
                $this->billingShippingFactory->create([
                    'data' => $item
                ])
            );
        }
    }
}
