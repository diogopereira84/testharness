<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Controller\Catalog;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Framework\Filesystem;
use \Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\Driver\File;

class DownloadFile implements ActionInterface
{
    protected $fileSystem;

   /**
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param DownloadHelper $downloadHelper
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
     * @param FileSystem $fileSystem
     * @param LoggerInterface $loggerInterface
     * @param File $file
     */
    public function __construct(
        private Context $context,
        protected ResultFactory $resultFactory,
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        private RequestInterface $request,
        private \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        protected ToggleConfig $toggleConfig,
        Filesystem $filesystem,
        protected LoggerInterface $loggerInterface,
        private readonly File $file
    ) {
        $this->fileSystem = $filesystem;
    }

    /**
     * Download file
     *
     * @return string
     */
    public function execute()
    {
        $this->loggerInterface->info("\n".__METHOD__ . ':' . __LINE__ . ':Inside Download File Controller');
        $downloadUrl = $this->request->getParam('fileurl');
        $fileName = $this->request->getParam('filename');
        $this->loggerInterface->info("\n".__METHOD__ . ':' . __LINE__ . ':Download Url:'.$downloadUrl);
        $this->loggerInterface->info("\n".__METHOD__ . ':' . __LINE__ . ':Filename"'.$fileName);
        if($downloadUrl && $fileName) {
            $zipContent = $this->catalogDocumentRefranceApi->readZipFileContent($downloadUrl);
            if($zipContent) {
                    $this->loggerInterface->info("\n".__METHOD__ . ':' . __LINE__ . ':Zip Content Received');
                    $this->loggerInterface->info("\n".__METHOD__ . ':' . __LINE__ . ':content length:'.strlen($zipContent));
                    $downloadedfile = $fileName.".zip";
                    $var = $this->fileSystem->getDirectoryWrite(DirectoryList::TMP);
                    $var->writeFile($downloadedfile,$zipContent);
                    $content['type'] = 'filename';
                    $content['value'] = $var->getAbsolutePath($downloadedfile);
                    $content['rm'] = 1;
                    return $this->fileFactory->create($downloadedfile,
                    $content);
        }
    }
}}
