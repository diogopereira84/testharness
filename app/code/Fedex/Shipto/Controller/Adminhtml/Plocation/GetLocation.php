<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipto\Controller\Adminhtml\Plocation;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\View\Result\PageFactory;
use Fedex\Shipto\Helper\Data;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Customer\Model\Session;
use Fedex\Shipto\Model\ProductionLocationFactory;

class GetLocation implements ActionInterface
{
    /**
     * @var customerSession
     */

    protected $customerSession;

    /**
     * @var LoggerInterface
     */
    protected $logger;


    public function __construct(
        private RequestInterface $request,
        Logger $logger,
        private PageFactory $pageFactory,
        private Data $data,
        private JsonFactory $jsonFactory,
        protected SerializerInterface $serializer,
        Session $customerSession,
        protected ProductionLocationFactory $productionLocationFactory
    ) {
        $this->logger = $logger;
        $this->customerSession = $customerSession;
    }

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {

        $data = $this->request->getParams();
        $isRestrictedRecommendedToggle = false;
        $isCalledFromEnhancedProfile = true;
        if (isset($data['zipcode']) && $data['zipcode'] !="") {

            $zipcode = $data['zipcode'];
            if(isset($data['is_restricted_product_location_toggle']) && $data['is_restricted_product_location_toggle'] == '1') {
                $isRestrictedRecommendedToggle = true;
                $isCalledFromEnhancedProfile = false;
            }
            $dataAllocationResponse = $this->data->getAllLocationsByZip($data ,$isCalledFromEnhancedProfile);
            $response = [];
            if (!$isRestrictedRecommendedToggle && $dataAllocationResponse && isset($dataAllocationResponse['success'])
                    && isset($dataAllocationResponse['locations'])
                    && $dataAllocationResponse['success'] == 1
                    && $dataAllocationResponse['locations'] != "") {
                    $response['locations'] = $dataAllocationResponse['locations'];
                    $this->logger->info(__METHOD__.':'.__LINE__.': Location retrieved successfully');
            } elseif($isRestrictedRecommendedToggle && isset($dataAllocationResponse) && count($dataAllocationResponse) > 0) {
                $response['locations'] = $dataAllocationResponse;
                $this->logger->info(__METHOD__.':'.__LINE__.': Location retrieved successfully');
            } else {
                $response['status'] = 'error';
                $response['message'] ='Unknown Error';
                $this->logger->error(__METHOD__.':'.__LINE__.
                ': Unknown error during location retrival for zipcode: '.$zipcode);
            }
        } else {
                $response['status'] = 'error';
                $response['message'] = 'Please provide postal code.';
                $this->logger->error(__METHOD__.':'.__LINE__.': Postal code not provided');
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);
        return $result;
    }
}
