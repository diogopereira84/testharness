<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToggleCsv\Test\Unit\Controller\Adminhtml\FeatureToggle;

use Fedex\UploadToggleCsv\Controller\Adminhtml\FeatureToggle\Apply;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\Glob;
use Fedex\UploadToggleCsv\Model\CsvProcessor;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\Http;

class ApplyTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Apply
     */
    protected $apply;

    /**
     * @var Action\Context
     */
    protected $context;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var FileDriver
     */
    protected $fileDriver;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var CsvProcessor
     */
    protected $csvProcessor;

    /**
     * @var Glob
     */
    protected $glob;

    /**
     * @var FileIo
     */
    protected $fileIo;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * Set up method for the ApplyTest case.
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Action\Context::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->fileDriver = $this->getMockBuilder(FileDriver::class)
            ->setMethods(['fileOpen', 'fileGetCsv', 'endOfFile', 'fileClose'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryList = $this->getMockBuilder(DirectoryList::class)
            ->setMethods(['getPath'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->csvProcessor = $this->getMockBuilder(CsvProcessor::class)
            ->setMethods(['createDirectoryIfNotExists', 'validateHeaders', 'validateContent', 'applyListCsvUpdates'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->glob = new Glob();

        $this->fileIo = $this->getMockBuilder(FileIo::class)
            ->setMethods(['getPathInfo', 'cp', 'rm', 'mv'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->json = $this->getMockBuilder(Json::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getFiles'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->context->method('getRequest')->willReturn($this->requestInterface);

        $this->objectManager = new ObjectManager($this);

        $this->apply = $this->objectManager->getObject(
            Apply::class,
            [
                'context' => $this->context,
                'resultJsonFactory' => $this->jsonFactory,
                'fileDriver' => $this->fileDriver,
                'directoryList' => $this->directoryList,
                'csvProcessor' => $this->csvProcessor,
                'glob' => $this->glob,
                'fileIo' => $this->fileIo
            ]
        );
    }

    /**
     * Tests the execute method when no file is uploaded.
     */
    public function testExecuteWithNoFileUploaded()
    {
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);

        $this->requestInterface->expects($this->any())
            ->method('getFiles')
            ->with('toggle_file')
            ->willReturn(null);

        $this->json->expects($this->any())
            ->method('setData')
            ->with([
                'success' => false,
                'message' => __('Upload CSV failed: %1', __('No file uploaded.'))
            ])
            ->willReturn($this->json);

        $result = $this->apply->execute();
        $this->assertEquals($this->json, $result);
    }

    /**
     * Tests the execute method when a non-CSV file is uploaded.
     *
     */
    public function testExecuteWithNonCsvFile()
    {
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);

        $file = [
            'tmp_name' => '/tmp/php12345',
            'name' => 'invalid.txt'
        ];

        $this->requestInterface->expects($this->any())
            ->method('getFiles')
            ->with('toggle_file')
            ->willReturn($file);

        $this->fileIo->expects($this->any())
            ->method('getPathInfo')
            ->with('invalid.txt')
            ->willReturn(['filename' => 'invalid', 'extension' => 'txt']);

        $this->json->expects($this->any())
            ->method('setData')
            ->with([
                'success' => false,
                'message' => __('Upload CSV failed: %1', __('Only CSV files are allowed.'))
            ])
            ->willReturn($this->json);

        $result = $this->apply->execute();
        $this->assertEquals($this->json, $result);
    }

    /**
     * Tests the execute method with a valid CSV file and content.
     *
     */
    public function testExecuteWithValidCsvAndContent()
    {
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);

        $file = [
            'tmp_name' => '/tmp/php12345',
            'name' => 'toggles.csv'
        ];

        $this->requestInterface->expects($this->any())
            ->method('getFiles')
            ->willReturn($file);

        $this->fileIo->expects($this->any())
            ->method('getPathInfo')
            ->willReturn(['filename' => 'toggles', 'extension' => 'csv']);

        $this->directoryList->method('getPath')->willReturn('/var');

        $this->csvProcessor->expects($this->exactly(2))
            ->method('createDirectoryIfNotExists');

        $this->fileIo->expects($this->any())->method('cp');
        $this->fileIo->expects($this->any())->method('rm')->with('/tmp/php12345');
        $this->csvProcessor->expects($this->any())->method('validateHeaders');
        $this->csvProcessor->expects($this->any())->method('validateContent');

        $this->fileIo->expects($this->any())->method('mv');

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "key,value\nfeature1,YES\nfeature2,NO\n");
        rewind($handle);

        $this->fileDriver->expects($this->any())->method('fileOpen')->willReturn($handle);
        $this->fileDriver->method('fileGetCsv')->willReturnOnConsecutiveCalls(
            ['key', 'value'],
            ['feature1', 'YES'],
            ['feature2', 'NO']
        );
        $this->fileDriver->method('endOfFile')->willReturnOnConsecutiveCalls(false, false, true);
        $this->fileDriver->expects($this->any())->method('fileClose')->with($handle);

        $this->csvProcessor->expects($this->any())->method('applyListCsvUpdates')->with([
            ['key' => 'feature1', 'value' => '1', 'line' => 2],
            ['key' => 'feature2', 'value' => '0', 'line' => 3],
        ]);

        $this->json->expects($this->any())
            ->method('setData')
            ->with($this->callback(function ($data) {
                return $data['success'] === true
                    && isset($data['message'])
                    && str_contains((string)$data['file'], 'toggles_');
            }))
            ->willReturn($this->json);

        $result = $this->apply->execute();
        $this->assertEquals($this->json, $result);
    }

    /**
     * Tests the execute method when a valid CSV file is uploaded,
     * the file contains content, but the parsed data is empty.
     */
    public function testExecuteWithValidCsvAndContentAndEmptyData()
    {
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);

        $file = [
            'tmp_name' => '/tmp/php12345',
            'name' => 'toggles.csv'
        ];

        $this->requestInterface->expects($this->any())
            ->method('getFiles')
            ->willReturn($file);

        $this->fileIo->expects($this->any())
            ->method('getPathInfo')
            ->willReturn(['filename' => 'toggles', 'extension' => 'csv']);

        $this->directoryList->method('getPath')->willReturn('/var');

        $this->csvProcessor->expects($this->exactly(2))
            ->method('createDirectoryIfNotExists');

        $this->fileIo->expects($this->any())->method('cp');
        $this->fileIo->expects($this->any())->method('rm')->with('/tmp/php12345');
        $this->csvProcessor->expects($this->any())->method('validateHeaders');
        $this->csvProcessor->expects($this->any())->method('validateContent');

        $this->fileIo->expects($this->any())->method('mv');

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "key,value\nfeature1,YES\nfeature2,NO\n");
        rewind($handle);

        $this->fileDriver->expects($this->any())->method('fileOpen')->willReturn($handle);
        $this->fileDriver->method('fileGetCsv')->willReturn([]);
        $this->fileDriver->method('endOfFile')->willReturnOnConsecutiveCalls(false, false, true);
        $this->fileDriver->expects($this->any())->method('fileClose')->with($handle);

        $this->json->expects($this->any())
            ->method('setData')
            ->willReturn($this->json);

        $result = $this->apply->execute();
        $this->assertEquals($this->json, $result);
    }

    /**
     * Tests the execute method of the Apply controller when an exception occurs during file upload.
     */
    public function testExecuteWithException()
    {
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);

        $this->requestInterface->expects($this->any())
            ->method('getFiles')
            ->willThrowException(new \Exception('Something went wrong'));

        $this->json->expects($this->any())
            ->method('setData')
            ->with([
                'success' => false,
                'message' => __('Upload CSV failed: %1', 'Something went wrong')
            ])
            ->willReturn($this->json);

        $result = $this->apply->execute();
        $this->assertEquals($this->json, $result);
    }

    /**
     * Tests the execute method to ensure it throws an exception when file opening fails.
     *
     */
    public function testExecuteThrowsExceptionWhenFileOpenFails()
    {
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);

        $file = [
            'tmp_name' => '/tmp/php12345',
            'name' => 'toggles.csv'
        ];

        $this->requestInterface->expects($this->any())
            ->method('getFiles')
            ->willReturn($file);

        $this->fileIo->expects($this->any())
            ->method('getPathInfo')
            ->willReturn(['filename' => 'toggles', 'extension' => 'csv']);

        $this->directoryList->method('getPath')->willReturn('/var');

        $this->csvProcessor->expects($this->exactly(2))
            ->method('createDirectoryIfNotExists');

        $this->fileDriver->expects($this->any())
            ->method('fileOpen')
            ->willReturn(false);

        $this->json->expects($this->any())
            ->method('setData')
            ->with($this->callback(function ($data) {
                return $data['success'] === false
                    && str_contains($data['message']->render(), 'Cannot open CSV file for reading.');
            }))
            ->willReturn($this->json);

        $result = $this->apply->execute();

        $this->assertEquals($this->json, $result);
    }

    /**
     * Test that the execute method properly removes existing temporary files.
     *
     * @return void
     */
    public function testExecuteRemovesExistingTempFiles()
    {
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);

        $file = [
            'tmp_name' => '/tmp/php12345',
            'name' => 'toggles.csv'
        ];

        $this->requestInterface->expects($this->any())
            ->method('getFiles')
            ->willReturn($file);

        $this->fileIo->expects($this->any())
            ->method('getPathInfo')
            ->willReturn(['filename' => 'toggles', 'extension' => 'csv']);

        $this->directoryList->method('getPath')->willReturn(sys_get_temp_dir());
        $this->csvProcessor->expects($this->exactly(2))
            ->method('createDirectoryIfNotExists');

        $tempDir = sys_get_temp_dir() . '/tmp_upload/featuretoggle';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $oldFile = $tempDir . '/old.csv';
        file_put_contents($oldFile, "dummy\n");

        $this->apply = new Apply(
            $this->context,
            $this->jsonFactory,
            $this->fileDriver,
            $this->directoryList,
            $this->csvProcessor,
            new \Magento\Framework\Filesystem\Glob(),
            $this->fileIo
        );

        $this->fileIo->expects($this->atLeastOnce())->method('rm')->withConsecutive(
            [$oldFile],
            ['/tmp/php12345']
        );

        $this->fileIo->expects($this->any())->method('cp');
        $this->csvProcessor->expects($this->any())->method('validateHeaders');
        $this->csvProcessor->expects($this->any())->method('validateContent');
        $this->fileIo->expects($this->any())->method('mv');

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "key,value\nfeature1,YES\n");
        rewind($handle);

        $this->fileDriver->expects($this->any())->method('fileOpen')->willReturn($handle);
        $this->fileDriver->method('fileGetCsv')->willReturnOnConsecutiveCalls(
            ['key', 'value'],
            ['feature1', 'YES']
        );
        $this->fileDriver->method('endOfFile')->willReturnOnConsecutiveCalls(false, true);
        $this->fileDriver->expects($this->any())->method('fileClose')->with($handle);

        $this->csvProcessor->expects($this->any())->method('applyListCsvUpdates');

        $this->json->expects($this->any())
            ->method('setData')
            ->with($this->callback(fn ($data) => $data['success'] === true))
            ->willReturn($this->json);

        $result = $this->apply->execute();
        $this->assertEquals($this->json, $result);

        @unlink($oldFile);
    }

    /**
     * Tests the execute method when a CSV file contains both valid and invalid keys.
     *
     * @return void
     */
    public function testExecuteWithInvalidKeys()
    {
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->json);

        $file = [
            'tmp_name' => '/tmp/php12345',
            'name' => 'toggles.csv'
        ];

        $this->requestInterface->expects($this->any())
            ->method('getFiles')
            ->willReturn($file);

        $this->fileIo->expects($this->any())
            ->method('getPathInfo')
            ->willReturn(['filename' => 'toggles', 'extension' => 'csv']);

        $this->directoryList->method('getPath')->willReturn('/var');

        $this->csvProcessor->expects($this->exactly(2))
            ->method('createDirectoryIfNotExists');

        $this->fileIo->expects($this->any())->method('cp');
        $this->fileIo->expects($this->any())->method('rm')->with('/tmp/php12345');
        $this->csvProcessor->expects($this->any())->method('validateHeaders');
        $this->csvProcessor->expects($this->any())->method('validateContent');

        $this->fileIo->expects($this->any())->method('mv');

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "key,value\nfeature1,YES\ninvalid_feature,YES\nfeature2,NO\n");
        rewind($handle);

        $this->fileDriver->expects($this->any())->method('fileOpen')->willReturn($handle);
        $this->fileDriver->method('fileGetCsv')->willReturnOnConsecutiveCalls(
            ['key', 'value'],
            ['feature1', 'YES'],
            ['invalid_feature', 'YES'],
            ['feature2', 'NO']
        );
        $this->fileDriver->method('endOfFile')->willReturnOnConsecutiveCalls(false, false, false, true);
        $this->fileDriver->expects($this->any())->method('fileClose')->with($handle);

        $this->csvProcessor->expects($this->any())
            ->method('applyListCsvUpdates')
            ->with([
                ['key' => 'feature1', 'value' => '1', 'line' => 2],
                ['key' => 'invalid_feature', 'value' => '1', 'line' => 3],
                ['key' => 'feature2', 'value' => '0', 'line' => 4],
            ])
            ->willReturn([
                'invalid' => [
                    ['key' => 'invalid_feature', 'line' => 3]
                ],
                'applied' => ['feature1', 'feature2']
            ]);

        $this->json->expects($this->any())
            ->method('setData')
            ->with($this->callback(function ($data) {
                return $data['success'] === true
                    && isset($data['message'])
                    && str_contains($data['message']->render(), 'applied with warnings')
                    && isset($data['warnings'])
                    && $data['warnings'] === true
                    && isset($data['invalidKeys'])
                    && count($data['invalidKeys']) === 1
                    && str_contains($data['invalidKeys'][0]->render(), 'Invalid key "invalid_feature" at line 3')
                    && isset($data['appliedCount'])
                    && $data['appliedCount'] === 2;
            }))
            ->willReturn($this->json);

        $result = $this->apply->execute();
        $this->assertEquals($this->json, $result);
    }
}
