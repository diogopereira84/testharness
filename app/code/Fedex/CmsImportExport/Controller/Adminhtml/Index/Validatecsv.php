<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CmsImportExport\Controller\Adminhtml\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CmsImportExport\Model\Import\Validate;

class Validatecsv implements ActionInterface
{
    /**
     * Validate CSV File when Import
     *
     * @param Validate $validate
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        protected Validate $validate,
        protected JsonFactory $resultJsonFactory
    )
    {
    }
    
    /**
     * Call model to validate Csv data
     *
     * @return string
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $resultData = $this->validate->validateCsv();
        return $result->setData($resultData);
    }
}
