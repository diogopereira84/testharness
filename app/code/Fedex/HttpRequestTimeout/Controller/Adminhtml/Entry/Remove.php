<?php
/**
 * @category Fedex
 * @package  Fedex_HttpRequestTimeout
 * @copyright   Copyright (c) 2024 FedEx
 */
declare(strict_types=1);

namespace Fedex\HttpRequestTimeout\Controller\Adminhtml\Entry;

use Exception;
use Fedex\HttpRequestTimeout\Model\ConfigManagement;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json as JsonResponse;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class Remove implements HttpPostActionInterface
{
    /**
     * @param JsonFactory $resultJsonFactory
     * @param ConfigManagement $configManagement
     */
    public function __construct(
        private JsonFactory $resultJsonFactory,
        private ConfigManagement $configManagement
    ) {
    }

    /**
     * @return ResponseInterface|JsonResponse|ResultInterface|void
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $serializedUpdatedValue = $this->configManagement->removedEntries();
            if ($serializedUpdatedValue) {

                $this->configManagement->saveEntries($serializedUpdatedValue);

                return $result->setData([
                    'status' => true,
                    'entries_value' => $serializedUpdatedValue
                ]);
            }
        } catch (Exception $e) {
            return $result->setData([
                'status'    => false,
                'error'     => $e->getMessage()
            ]);
        }
    }
}
