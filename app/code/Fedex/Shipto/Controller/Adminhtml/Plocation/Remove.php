<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipto\Controller\Adminhtml\Plocation;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\View\Result\PageFactory;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Magento\Framework\Controller\Result\JsonFactory;

class Remove implements ActionInterface
{
    protected $customerSession;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    public function __construct(
        private RequestInterface $request,
        private ManagerInterface $messageManager,
        Logger $logger,
        private PageFactory $pageFactory,
        private ProductionLocationFactory $productionLocationFactory,
        private JsonFactory $jsonFactory
    ) {
        $this->logger = $logger;
    }

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $data = $this->request->getParams();
        $locationModel = $this->productionLocationFactory->create();
        if ($data && isset($data['location_id']) && !empty($data['location_id'])) {

            try {
                $locationIds = $data['location_id'];

                foreach ($locationIds as $locationId) {
                        $locationId = trim($locationId);
                        $locationModel->load($locationId);
                        $locationModel->delete();
                }

                $response['status'] = 'success';
                $response['message'] = 'Location Remove Successfully';
                if (isset($data['is_recommended_store']) && $data['is_recommended_store'] == true) {
                    $this->messageManager->addSuccess(__('Recommended Store Removed successfully'));
                    $this->logger->info(__METHOD__.':'.__LINE__.': Recommended store removed successfully');
                } else {
                    $this->messageManager->addSuccess(__('Restricted Store Removed successfully'));
                    $this->logger->info(__METHOD__.':'.__LINE__.': Restricted store removed successfully');
                }

            } catch (\Exception $e) {
                $response['status'] = 'error';
                $response['message'] = $e->getMessage();
                $this->logger->error(__METHOD__.':'.__LINE__.': '.$e->getMessage());
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Location Id required';
            $this->logger->error(__METHOD__.':'.__LINE__.': Location ID not provided');
        }

        $result = $this->jsonFactory->create();
        $result->setData($response);
        return $result;
    }
}
