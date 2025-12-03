<?php

declare(strict_types=1);

namespace Fedex\ShippingEstimator\Controller\Index;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ActionInterface;
use Fedex\ShippingEstimator\Model\Service\Delivery;
use Magento\Framework\Controller\Result\JsonFactory;

class Index implements ActionInterface
{
    /**
     * Index constructor.
     * @param Http $request
     * @param Delivery $deliveryApi
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        private readonly Http $request,
        private readonly Delivery $deliveryApi,
        private readonly JsonFactory $jsonFactory
    ) {
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $response = $this->deliveryApi->getDeliveryInfo($params);
        $result = $this->jsonFactory->create();
        return $result->setData($response);
    }
}
