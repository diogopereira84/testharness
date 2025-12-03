<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;

class GetCustomerTime implements ActionInterface
{
    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        private Context $context,
        private JsonFactory $resultJsonFactory,
        private ResultFactory $resultFactory,
        private RequestInterface $request
    )
    {
    }

    /**
     * @return string
     * @codeCoverageIgnore|Resize of image is in protected fuction
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $response = [];
        if (array_key_exists('custimezone', $params) && array_key_exists('productStartDate', $params)) {
            $startpstTime = "";
            $endpstTime = "";
            $startDate = new \DateTime($params['productStartDate'], new \DateTimeZone('America/Los_Angeles'));
            $startpstTime = $startDate->setTimezone(new \DateTimeZone($params['custimezone']));
            $startpstTime = $startpstTime->format('m/d/Y h:s A');
            if (array_key_exists('productEndDate', $params) && $params['productEndDate']) {
                $endDate = new \DateTime($params['productEndDate'], new \DateTimeZone('America/Los_Angeles'));
                $endpstTime = $endDate->setTimezone(new \DateTimeZone($params['custimezone']));
                $endpstTime = $endpstTime->format('m/d/Y h:s A');
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
