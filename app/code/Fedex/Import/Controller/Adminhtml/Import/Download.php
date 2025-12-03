<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Controller\Adminhtml\Import;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\App\Filesystem\DirectoryList;
use \Psr\Log\LoggerInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\ImportExport\Model\Import\SampleFileProvider;

/**
 * Controller Class Download
 */
class Download extends \Magento\ImportExport\Controller\Adminhtml\Import\Download
{
    /**
     * Sample File
     */
    public const URL_REWRITE_SAMPLE_FILE = 'Fedex_Import';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param RawFactory $resultRawFactory
     * @param ReadFactory $readFactory
     * @param ComponentRegistrar $componentRegistrar
     * @param SampleFileProvider $sampleFileProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        RawFactory $resultRawFactory,
        ReadFactory $readFactory,
        ComponentRegistrar $componentRegistrar,
        SampleFileProvider $sampleFileProvider = null,
        protected LoggerInterface $logger
    ) {
        parent::__construct(
            $context,
            $fileFactory,
            $resultRawFactory,
            $readFactory,
            $componentRegistrar,
            $sampleFileProvider
        );
    }

    /**
     * Execute method for controller class
     *
     * @return mixed
     */
    public function execute()
    {
        
        $fileName = $this->getRequest()->getParam('filename') . '.csv';
        if ($this->getRequest()->getParam('filename') == 'fedex_product') {
            $moduleDir = $this->componentRegistrar->getPath(
                ComponentRegistrar::MODULE,
                self::URL_REWRITE_SAMPLE_FILE
            );
        } else {
            $moduleDir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, static::SAMPLE_FILES_MODULE);
        }
        $fileAbsolutePath = $moduleDir . '/Files/Sample/' . $fileName;
        $directoryRead = $this->readFactory->create($moduleDir);
        $filePath = $directoryRead->getRelativePath($fileAbsolutePath);

        if (!$directoryRead->isFile($filePath)) {
            $this->logger->error(__METHOD__.':'.__LINE__.' There is no sample file for this entity.');
            $this->messageManager->addErrorMessage(__('There is no sample file for this entity.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/import');
            return $resultRedirect;
        }

        $fileSize = isset($directoryRead->stat($filePath)['size'])
            ? $directoryRead->stat($filePath)['size'] : null;

        $this->fileFactory->create(
            $fileName,
            null,
            DirectoryList::VAR_DIR,
            'application/octet-stream',
            $fileSize
        );
        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($directoryRead->readFile($filePath));
        return $resultRaw;
    }

    /**
     * Check to access allowed or not
     *
     * @return mixed
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Fedex_Import::import_product_import');
    }
}
