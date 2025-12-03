<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FXOCMConfigurator\Controller\Batchupload;

use Magento\Framework\App\Action\Context;
use Fedex\FXOCMConfigurator\Helper\Batchupload as BatchuploadHelper;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @param Context $context
     * @param BatchuploadHelper $batchuploadhelper
     */

    public function __construct(
        Context $context,
        protected BatchuploadHelper $batchuploadhelper,
        protected JsonFactory $resultJsonFactory
    )
    {
        return parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $workspaceJson = $this->getRequest()->getParam('data');
        $requestDataArray = json_decode($workspaceJson,true);

        if(!empty($requestDataArray['userWorkspace'])){
               $workSpaceData = json_encode($requestDataArray['userWorkspace']);
               $this->batchuploadhelper->addBatchUploadData($workSpaceData);
               return $resultJson->setData(['output' => 'sessionset']);
        }
    }
}
