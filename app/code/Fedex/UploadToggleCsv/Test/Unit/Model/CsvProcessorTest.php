<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToggleCsv\Test\Unit\Model;

use Fedex\UploadToggleCsv\Model\CsvProcessor;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

class CsvProcessorTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var CsvProcessor
     */
    protected $csvProcessor;

    /**
     * @var FileDriver
     */
    protected $fileDriver;

    /**
     * @var WriterInterface
     */
    protected $writerInterface;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var AdapterInterface
     */
    protected $adapterInterface;

    /**
     * @var Select
     */
    protected $select;

    /**
     * Set up method for the CsvProcessorTest case.
     */
    protected function setUp(): void
    {
        $this->fileDriver = $this->getMockBuilder(FileDriver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fileOpen', 'fileGetCsv', 'fileClose', 'isExists', 'createDirectory', 'endOfFile'])
            ->getMock();

        $this->writerInterface = $this->getMockBuilder(WriterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->setMethods(['getConnection', 'getTableName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapterInterface = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['select', 'quoteInto', 'fetchCol'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->select = $this->getMockBuilder(Select::class)
            ->setMethods(['from', 'where'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->csvProcessor = $this->objectManager->getObject(
            CsvProcessor::class,
            [
                'fileDriver' => $this->fileDriver,
                'configWriter' => $this->writerInterface,
                'resource' => $this->resourceConnection,
            ]
        );
    }

    /**
     * Tests that validateHeaders throws a LocalizedException when the file cannot be opened.
     */
    public function testValidateHeadersThrowsExceptionIfFileCannotBeOpened()
    {
        $filePath = '/var/upload/featuretoggle/test.csv';

        $this->fileDriver->expects($this->any())
            ->method('fileOpen')
            ->with($filePath, 'r')
            ->willReturn(false);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Could not open the uploaded file.');

        $result = $this->csvProcessor->validateHeaders($filePath);
        $this->assertNull($result);
    }

    /**
     * Tests the validateHeaders method with valid CSV headers.
     */
    public function testValidateHeadersWithValidHeaders()
    {
        $filePath = '/var/upload/featuretoggle/test.csv';
        $handle = fopen('php://memory', 'r+');
        $headers = ['Key', 'Value'];

        $this->fileDriver->expects($this->any())
            ->method('fileOpen')
            ->with($filePath, 'r')
            ->willReturn($handle);

        $this->fileDriver->expects($this->any())
            ->method('fileGetCsv')
            ->with($handle, 0)
            ->willReturn($headers);

        $this->fileDriver->expects($this->any())
            ->method('fileClose')
            ->with($handle);

        $result = $this->csvProcessor->validateHeaders($filePath);
        $this->assertNull($result);
    }

    /**
     * Tests that the validateHeaders method throws a LocalizedException
     *
     * @return void
     */
    public function testValidateHeadersThrowsExceptionIfHeadersAreInvalid()
    {
        $filePath = '/var/upload/featuretoggle/test.csv';
        $handle = fopen('php://memory', 'r+');
        $headers = ['key1', 'value1'];

        $this->fileDriver->expects($this->any())
            ->method('fileOpen')
            ->with($filePath, 'r')
            ->willReturn($handle);

        $this->fileDriver->expects($this->any())
            ->method('fileGetCsv')
            ->with($handle, 0)
            ->willReturn($headers);

        $this->fileDriver->expects($this->any())
            ->method('fileClose')
            ->with($handle);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid CSV headers. Expected: Key, Value');

        $result = $this->csvProcessor->validateHeaders($filePath);
        $this->assertNull($result);
    }

    /**
     * Tests that the validateContent method throws a LocalizedException
     *
     * @return void
     */
    public function testValidateContentThrowsExceptionIfFileCannotBeOpened()
    {
        $filePath = '/var/upload/featuretoggle/test.csv';

        $this->fileDriver->expects($this->any())
            ->method('fileOpen')
            ->with($filePath, 'r')
            ->willReturn(false);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Unable to read the uploaded file.');

        $this->csvProcessor->validateContent($filePath);
    }

    /**
     * Tests the validateContent method with valid CSV rows.
     */
    public function testValidateContentWithValidRows()
    {
        $filePath = '/var/upload/featuretoggle/test.csv';
        $handle = fopen('php://memory', 'r+');
        $rows = [
            ['key', 'value'],
            ['magegeeks_B_2564807_nbc', 'YES'],
            ['sgc_remove_companyadmin_fields', 'NO']
        ];

        $this->fileDriver->expects($this->any())
            ->method('fileOpen')
            ->with($filePath, 'r')
            ->willReturn($handle);

        $this->fileDriver->expects($this->any())
            ->method('fileGetCsv')
            ->willReturnOnConsecutiveCalls(...$rows);

        $this->fileDriver->expects($this->any())
            ->method('endOfFile')
            ->willReturnOnConsecutiveCalls(false, false, true);

        $this->fileDriver->expects($this->any())
            ->method('fileClose')
            ->with($handle);

        $result = $this->csvProcessor->validateContent($filePath);
        $this->assertNull($result);
    }

    /**
     * Tests that the validateContent method throws a LocalizedException when a CSV row
     */
    public function testValidateContentThrowsExceptionOnInvalidColumnCount()
    {
        $filePath = '/var/upload/featuretoggle/test.csv';
        $handle = fopen('php://memory', 'r+');
        $rows = [
            ['key', 'value'],
            ['magegeeks_B_2564807_nbc']
        ];

        $this->fileDriver->expects($this->any())
            ->method('fileOpen')
            ->with($filePath, 'r')
            ->willReturn($handle);

        $this->fileDriver->expects($this->any())
            ->method('fileGetCsv')
            ->willReturnOnConsecutiveCalls(...$rows);

        $this->fileDriver->expects($this->any())
            ->method('endOfFile')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->fileDriver->expects($this->any())
            ->method('fileClose')
            ->with($handle);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid column count at line 2. Expected exactly 2 columns.');

        $result = $this->csvProcessor->validateContent($filePath);
        $this->assertNull($result);
    }

    /**
     * Test that validateContent method throws an exception when a CSV row contains an empty key.
     *
     * @test
     * @return void
     */
    public function testValidateContentThrowsExceptionOnEmptyKey()
    {
        $filePath = '/var/upload/featuretoggle/test.csv';
        $handle = fopen('php://memory', 'r+');
        $rows = [
            ['key', 'value'],
            ['', 'YES']
        ];

        $this->fileDriver->expects($this->any())
            ->method('fileOpen')
            ->with($filePath, 'r')
            ->willReturn($handle);

        $this->fileDriver->expects($this->any())
            ->method('fileGetCsv')
            ->willReturnOnConsecutiveCalls(...$rows);

        $this->fileDriver->expects($this->any())
            ->method('endOfFile')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->fileDriver->expects($this->any())
            ->method('fileClose')
            ->with($handle);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Empty key found at line 2.');

        $this->csvProcessor->validateContent($filePath);
    }

    /**
     * Test that validateContent method throws an exception when provided with invalid CSV content.
     *
     * @return void
     * @throws \Exception If the test fails
     */
    public function testValidateContentThrowsExceptionOnInvalidValue()
    {
        $filePath = '/var/upload/featuretoggle/test.csv';
        $handle = fopen('php://memory', 'r+');
        $rows = [
            ['key', 'value'],
            ['test_key', 'MAYBE']
        ];

        $this->fileDriver->expects($this->any())
            ->method('fileOpen')
            ->with($filePath, 'r')
            ->willReturn($handle);

        $this->fileDriver->expects($this->any())
            ->method('fileGetCsv')
            ->willReturnOnConsecutiveCalls(...$rows);

        $this->fileDriver->expects($this->any())
            ->method('endOfFile')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->fileDriver->expects($this->any())
            ->method('fileClose')
            ->with($handle);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid value at line 2. Only "YES" or "NO" allowed.');

        $this->csvProcessor->validateContent($filePath);
    }

    /**
     * Tests that the validateContent method correctly skips empty rows in the CSV file.
     *
     */
    public function testValidateContentSkipsEmptyRows()
    {
        $filePath = '/var/upload/featuretoggle/test.csv';
        $handle = fopen('php://memory', 'r+');
        $rows = [
            ['key', 'value'],
            [null],
            ['', 'YES'],
        ];

        $this->fileDriver->expects($this->any())
            ->method('fileOpen')
            ->with($filePath, 'r')
            ->willReturn($handle);

        $this->fileDriver->expects($this->any())
            ->method('fileGetCsv')
            ->willReturnOnConsecutiveCalls(...$rows);

        $this->fileDriver->expects($this->any())
            ->method('endOfFile')
            ->willReturnOnConsecutiveCalls(false, false, true);

        $this->fileDriver->expects($this->any())
            ->method('fileClose')
            ->with($handle);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Empty key found at line 3.');

        $this->csvProcessor->validateContent($filePath);
    }

    /**
     * Tests that the createDirectoryIfNotExists method creates a directory
     */
    public function testCreateDirectoryIfNotExistsCreatesDirectoryWhenNotExists()
    {
        $dir = '/var/upload/featuretoggle';

        $this->fileDriver->expects($this->any())
            ->method('isExists')
            ->with($dir)
            ->willReturn(false);

        $this->fileDriver->expects($this->any())
            ->method('createDirectory')
            ->with($dir);

        $result = $this->csvProcessor->createDirectoryIfNotExists($dir);
        $this->assertNull($result);
    }

    /**
     * Tests that the createDirectoryIfNotExists method creates a directory
     */
    public function testCreateDirectoryIfNotExistsDoesNothingIfDirectoryExists()
    {
        $dir = '/var/upload/featuretoggle';

        $this->fileDriver->expects($this->any())
            ->method('isExists')
            ->with($dir)
            ->willReturn(true);

        $this->fileDriver->expects($this->never())
            ->method('createDirectory');

        $this->csvProcessor->createDirectoryIfNotExists($dir);
    }

    /**
     * Tests that createDirectoryIfNotExists throws a LocalizedException when directory creation fails.
     */
    public function testCreateDirectoryIfNotExistsThrowsExceptionOnFailure()
    {
        $dir = '/var/upload/featuretoggle';

        $this->fileDriver->expects($this->any())
            ->method('isExists')
            ->with($dir)
            ->willReturn(false);

        $this->fileDriver->expects($this->any())
            ->method('createDirectory')
            ->with($dir)
            ->willThrowException(new \Exception('Failed to create directory'));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage("Cannot create directory: $dir");

        $result = $this->csvProcessor->createDirectoryIfNotExists($dir);
        $this->assertNull($result);
    }

    /**
     * Tests that applyListCsvUpdates saves config values for matched keys.
     */
    public function testApplyListCsvUpdatesSavesConfigForMatchedKeys()
    {
        $updates = [
            ['key' => 'magegeeks_B_2564807_nbc', 'value' => 'YES'],
            ['key' => 'sgc_remove_companyadmin_fields', 'value' => 'NO'],
        ];

        $tableName = 'core_config_data';
        $matchedPaths = [
            'environment_toggle_configuration/environment_toggle/magegeeks_B_2564807_nbc',
            'environment_toggle_configuration/environment_toggle/sgc_remove_companyadmin_fields',
        ];

        $this->resourceConnection->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->adapterInterface);

        $this->resourceConnection->expects($this->any())
            ->method('getTableName')
            ->with($tableName)
            ->willReturn($tableName);

        $this->adapterInterface->expects($this->any())
            ->method('select')
            ->willReturn($this->select);

        $this->select->expects($this->any())
            ->method('from')
            ->with($tableName, ['path'])
            ->willReturnSelf();

        $this->adapterInterface->expects($this->exactly(2))
            ->method('quoteInto')
            ->withConsecutive(
                ['path LIKE ?', '%/magegeeks_B_2564807_nbc'],
                ['path LIKE ?', '%/sgc_remove_companyadmin_fields']
            )
            ->willReturnOnConsecutiveCalls(
                "path LIKE '%/magegeeks_B_2564807_nbc'",
                "path LIKE '%/sgc_remove_companyadmin_fields'"
            );

        $this->select->expects($this->any())
            ->method('where')
            ->with("path LIKE '%/magegeeks_B_2564807_nbc' OR path LIKE '%/sgc_remove_companyadmin_fields'")
            ->willReturnSelf();

        $this->adapterInterface->expects($this->any())
            ->method('fetchCol')
            ->with($this->select)
            ->willReturn($matchedPaths);

        $this->writerInterface->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                ['environment_toggle_configuration/environment_toggle/magegeeks_B_2564807_nbc', 'YES'],
                ['environment_toggle_configuration/environment_toggle/sgc_remove_companyadmin_fields', 'NO']
            );

        $result = $this->csvProcessor->applyListCsvUpdates($updates);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('invalid', $result);
        $this->assertArrayHasKey('applied', $result);
        $this->assertEmpty($result['invalid']);
        $this->assertEquals(['magegeeks_B_2564807_nbc', 'sgc_remove_companyadmin_fields'], $result['applied']);
    }

    /**
     * Tests that applyListCsvUpdates skips updates for keys not found in pathMap.
     */
    public function testApplyListCsvUpdatesSkipsUnmatchedKeys()
    {
        $updates = [
            ['key' => 'existing_key', 'value' => 'YES'],
            ['key' => 'missing_key', 'value' => 'NO'],
        ];

        $tableName = 'core_config_data';
        $matchedPaths = [
            'environment_toggle_configuration/environment_toggle/existing_key',
        ];

        $this->resourceConnection->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->adapterInterface);

        $this->resourceConnection->expects($this->any())
            ->method('getTableName')
            ->with($tableName)
            ->willReturn($tableName);

        $this->adapterInterface->expects($this->any())
            ->method('select')
            ->willReturn($this->select);

        $this->select->expects($this->any())
            ->method('from')
            ->with($tableName, ['path'])
            ->willReturnSelf();

        $this->adapterInterface->expects($this->exactly(2))
            ->method('quoteInto')
            ->withConsecutive(
                ['path LIKE ?', '%/existing_key'],
                ['path LIKE ?', '%/missing_key']
            )
            ->willReturnOnConsecutiveCalls(
                "path LIKE '%/existing_key'",
                "path LIKE '%/missing_key'"
            );

        $this->select->expects($this->any())
            ->method('where')
            ->with("path LIKE '%/existing_key' OR path LIKE '%/missing_key'")
            ->willReturnSelf();

        $this->adapterInterface->expects($this->any())
            ->method('fetchCol')
            ->with($this->select)
            ->willReturn($matchedPaths);

        $this->writerInterface->expects($this->any())
            ->method('save')
            ->with('environment_toggle_configuration/environment_toggle/existing_key', 'YES');

        $result = $this->csvProcessor->applyListCsvUpdates($updates);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('invalid', $result);
        $this->assertArrayHasKey('applied', $result);
        $this->assertNotEmpty($result['invalid']);
        $this->assertCount(1, $result['invalid']);
        $this->assertEquals('missing_key', $result['invalid'][0]['key']);
        $this->assertEquals(['existing_key'], $result['applied']);
    }

    /**
     * Tests that applyListCsvUpdates returns early if updates array is empty.
     */
    public function testApplyListCsvUpdatesReturnsEarlyIfUpdatesIsEmpty()
    {
        $this->writerInterface->expects($this->never())
            ->method('save');

        $result = $this->csvProcessor->applyListCsvUpdates([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
