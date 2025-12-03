<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Filesystem\Driver\File;

class DownloadFile implements ActionInterface
{
    /**
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param CatalogDocumentRefranceApi $catalogDocumentRefranceApi
     * @param File $file
     */
    public function __construct(
        private Context $context,
        protected ResultFactory $resultFactory,
        private RequestInterface $request,
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        private RawFactory $rawResultFactory,
        private readonly File $file
    )
    {
    }

    /**
     * Download file
     *
     * @return string
     */
    public function execute()
    {
        $downloadUrl = $this->request->getParam('fileurl');
        $fileName = $this->request->getParam('filename');
        if($downloadUrl && $fileName) {
            $zipContent = $this->catalogDocumentRefranceApi->readZipFileContent($downloadUrl);
            if($zipContent) {
                // @codeCoverageIgnoreStart
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment;filename="'.$fileName.'.zip"');
                header('Cache-Control: max-age=0');
                $fp = $this->file->fileOpen("php://output", 'w');
                $this->file->fileWrite($fp, $zipContent);
                $this->file->fileClose($fp);
                // @codeCoverageIgnoreEnd
            }
        }

    }
}
