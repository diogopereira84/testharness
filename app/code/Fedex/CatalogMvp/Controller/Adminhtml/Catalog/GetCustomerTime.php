<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\JsonFactory;

class GetCustomerTime extends \Magento\Backend\App\Action
{
    private \Magento\Catalog\Api\ProductRepositoryInterface $_productRepository;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        private JsonFactory $resultJsonFactory
    ) {
        $this->resultFactory = $resultFactory;
        $this->_productRepository = $productRepository;
        parent::__construct($context);
    }

     /**
     * @return string
     * @codeCoverageIgnore|Resize of image is in protected fuction
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $response = [];
        if (array_key_exists('custimezone', $params) && array_key_exists('productId', $params)) {
            $startpstTime = "";
            $endpstTime = "";
            $product = $this->_productRepository->getById($params['productId']);
            if($product) {
                $productStartDate = $product->getData('start_date_pod');
                $productEndDate = $product->getData('end_date_pod');
                if ($productStartDate) {
                    $startDate = new \DateTime($productStartDate, new \DateTimeZone('America/Los_Angeles'));
                    $startpstTime = $startDate->setTimezone(new \DateTimeZone($params['custimezone']));
                    $startpstTime = $startpstTime->format('m/d/Y h:s A');
                }
                if ($productEndDate) {
                    $endDate = new \DateTime($productEndDate, new \DateTimeZone('America/Los_Angeles'));
                    $endpstTime = $endDate->setTimezone(new \DateTimeZone($params['custimezone']));
                    $endpstTime = $endpstTime->format('m/d/Y h:s A');
                }
            }
            $response['startpsttime'] = $startpstTime;
            $response['endpstTime'] = $endpstTime;
            $response['success'] = 'true';
        }
        $json = $this->resultJsonFactory->create();
        $json->setData($response);
        return $json;
    }
}
