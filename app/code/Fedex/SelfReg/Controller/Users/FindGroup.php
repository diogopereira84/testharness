<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Controller\Users;

use Fedex\SelfReg\Model\FindGroupModel;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class FindGroup implements HttpPostActionInterface
{
    /**
     * Find Group class constructor
     *
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param FindGroupModel $findGroupModel
     */
    public function __construct(
        private JsonFactory $resultJsonFactory,
        private RequestInterface $request,
        private LoggerInterface $logger,
        private FindGroupModel $findGroupModel
    ) {}

    /**
     * Execute function
     *
     * @return Json
     */
    public function execute()    
    {
        $resultJson = $this->resultJsonFactory->create();
        $postData = $this->request->getParams() ?? [];
        $groupNameArray = [];
        
        try{
            if (isset($postData['selectedUserIds']) && !empty($postData['selectedUserIds'])) {
                $groupNameArray = $this->findGroupModel->getAllCustomersGroupName($postData['selectedUserIds']);
                if(count(array_unique($groupNameArray)) > 1) {
                    $resultJson->setData(['status' => 'success','value' => 'Multiple Groups']);
                } else {
                    $resultJson->setData(['status' => 'success','value' => $groupNameArray[0]]);
                }
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                                     ' An error occurred while fetching the user group. ' . $e->getMessage());
            $resultJson->setData([
                'status' => 'error',
                'message' => __('An error occurred while fetching the user group.')
            ]);
        } 
        return $resultJson;
    }
}