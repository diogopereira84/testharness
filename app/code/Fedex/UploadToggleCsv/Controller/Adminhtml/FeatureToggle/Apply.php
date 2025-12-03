<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToggleCsv\Controller\Adminhtml\FeatureToggle;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\Glob;
use Fedex\UploadToggleCsv\Model\CsvProcessor;
use Magento\Framework\Filesystem\Io\File as FileIo;

class Apply extends Action
{
    private const TEMP_UPLOAD_PATH = 'tmp_upload/featuretoggle';
    private const FINAL_UPLOAD_PATH = 'upload/featuretoggle';

    /**
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FileDriver $fileDriver
     * @param DirectoryList $directoryList
     * @param CsvProcessor $csvProcessor
     * @param Glob $glob
     * @param FileIo $fileIo
     */
    public function __construct(
        Action\Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly FileDriver $fileDriver,
        private readonly DirectoryList $directoryList,
        private readonly CsvProcessor $csvProcessor,
        private readonly Glob $glob,
        private readonly FileIo $fileIo
    ) {
        parent::__construct($context);
    }

    /**
     * Execute action
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $file = $this->getRequest()->getFiles('toggle_file');

            if (!$file || empty($file['tmp_name'])) {
                throw new LocalizedException(__('No file uploaded.'));
            }

            $fileInfo = $this->fileIo->getPathInfo($file['name']);
            if (strtolower($fileInfo['extension'] ?? '') !== 'csv') {
                throw new LocalizedException(__('Only CSV files are allowed.'));
            }

            $originalName = $fileInfo['filename'] ?? 'upload';
            $timestamp = date('Ymd_His');
            $finalFileName = $originalName . '_' . $timestamp . '.csv';

            $baseVarPath = $this->directoryList->getPath(DirectoryList::VAR_DIR);
            $tempDir = $baseVarPath . DIRECTORY_SEPARATOR . self::TEMP_UPLOAD_PATH;
            $finalDir = $baseVarPath . DIRECTORY_SEPARATOR . self::FINAL_UPLOAD_PATH;

            $this->csvProcessor->createDirectoryIfNotExists($tempDir);
            $this->csvProcessor->createDirectoryIfNotExists($finalDir);

            $existingTempFiles = $this->glob->glob($tempDir . DIRECTORY_SEPARATOR . '*.csv');
            foreach ($existingTempFiles as $existingFile) {
                $this->fileIo->rm($existingFile);
            }

            $tempFilePath = $tempDir . DIRECTORY_SEPARATOR . $finalFileName;
            $finalFilePath = $finalDir . DIRECTORY_SEPARATOR . $finalFileName;

            $this->fileIo->cp($file['tmp_name'], $tempFilePath);
            $this->fileIo->rm($file['tmp_name']);

            $this->csvProcessor->validateHeaders($tempFilePath);
            $this->csvProcessor->validateContent($tempFilePath);

            $this->fileIo->mv($tempFilePath, $finalFilePath);

            $handle = $this->fileDriver->fileOpen($finalFilePath, 'r');
            if (!$handle) {
                throw new LocalizedException(__('Cannot open CSV file for reading.'));
            }

            $this->fileDriver->fileGetCsv($handle, 0);
            $updates = [];
            $line = 2;

            while (!$this->fileDriver->endOfFile($handle)) {
                $row = $this->fileDriver->fileGetCsv($handle, 0);
                if (!$row || $row === [null]) {
                    $line++;
                    continue;
                }

                $key = trim($row[0]);
                $value = strtoupper(trim($row[1]));
                $value = $value === 'YES' ? '1' : '0';

                $updates[] = ['key' => $key, 'value' => $value, 'line' => $line];
                $line++;
            }

            $this->fileDriver->fileClose($handle);

            if (empty($updates)) {
                throw new LocalizedException(__('No valid toggle data found in the file.'));
            }

            $result = $this->csvProcessor->applyListCsvUpdates($updates);

            $message = __('Feature toggle settings successfully applied.');

            $responseData = [
                'success' => true,
                'message' => $message,
                'file' => $finalFileName
            ];

            if (!empty($result['invalid'])) {
                $invalidKeyMessages = [];
                foreach ($result['invalid'] as $invalidKey) {
                    $invalidKeyMessages[] = __('Invalid key "%1" at line %2', $invalidKey['key'], $invalidKey['line']);
                }

                $responseData['warnings'] = true;
                $responseData['invalidKeys'] = $invalidKeyMessages;

                $responseData['message'] =
                    __('Feature toggle settings applied with warnings. Some keys were not found in the system.');
            }

            if (!empty($result['applied'])) {
                $responseData['appliedCount'] = count($result['applied']);
            }

            return $resultJson->setData($responseData);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Upload CSV failed: %1', $e->getMessage())
            ]);
        }
    }
}
