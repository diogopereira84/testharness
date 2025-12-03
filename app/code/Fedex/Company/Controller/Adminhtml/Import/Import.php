<?php
/**
 * Copyright © FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Controller\Adminhtml\Import;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;

class Import implements ActionInterface
{
    /**
     * Import Constructor
     *
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param File $driverInterface
     * @return void
     */
    public function __construct(
        protected RequestInterface $request,
        protected JsonFactory $resultJsonFactory,
        protected File $driverInterface
    )
    {
    }

    /**
     * Get CSV row and column data
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $importFileCompanyFile = $this->request->getFiles('file');
        $file_data = $this->driverInterface->fileOpen($importFileCompanyFile['tmp_name'], 'r');
        $file_header = $this->driverInterface->fileGetCsv($file_data, 100000);
        $data = [];

        for($count = 0; $count < count($file_header); $count++) {
            while (($row = $this->driverInterface->fileGetCsv($file_data, 100000)) !== false) {
                $data = $row;
            }
        }

        return $this->resultJsonFactory->create()->setData($data);
    }
}
