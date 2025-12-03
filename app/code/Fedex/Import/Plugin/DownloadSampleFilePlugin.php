<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Plugin;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\ImportExport\Model\Import\SampleFileProvider;
use Psr\Log\LoggerInterface;
use Magento\ImportExport\Controller\Adminhtml\Import\Download;

/**
 * Plugin Class DownloadSampleFilePlugin
 */
class DownloadSampleFilePlugin
{
    protected const PRODUCT_ATTRIBUTES_SAMPLE_FILE = 'Fedex_Import';

    /**
     * @var MessageManagerInterface $messageManager
     */
    protected MessageManagerInterface $messageManager;

    /**
     * DownloadSampleFilePlugin constructor
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param RawFactory $resultRawFactory
     * @param ReadFactory $readFactory
     * @param ComponentRegistrar $componentRegistrar
     * @param LoggerInterface $logger
     * @param SampleFileProvider $sampleFileProvider
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $request
     */
    public function __construct(
        Context $context,
        protected FileFactory $fileFactory,
        protected RawFactory $resultRawFactory,
        protected ReadFactory $readFactory,
        protected ComponentRegistrar $componentRegistrar,
        protected LoggerInterface $logger,
        private SampleFileProvider $sampleFileProvider,
        protected RedirectFactory $resultRedirectFactory,
        protected RequestInterface $request
    )
    {
    }

    /**
     * Around method for execute method
     *
     * @param Download $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundExecute(
        Download $subject,
        $proceed
    ) {
        $fileName = $this->request->getParam('filename') . '.csv';
        if ($this->request->getParam('filename') == 'catalog_product') {
            try {
                $moduleDir = $this->componentRegistrar->getPath(
                    ComponentRegistrar::MODULE,
                    self::PRODUCT_ATTRIBUTES_SAMPLE_FILE
                );

                $fileAbsolutePath = $moduleDir . '/Files/Sample/' . $fileName;
                $directoryRead = $this->readFactory->create($moduleDir);
                $filePath = $directoryRead->getRelativePath($fileAbsolutePath);

                if (!$directoryRead->isFile($filePath)) {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' There is no sample file for this entity.');
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
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $proceed();
    }
}
