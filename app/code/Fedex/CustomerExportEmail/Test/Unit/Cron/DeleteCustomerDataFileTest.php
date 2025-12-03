<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerExportEmail\Test\Unit\Cron;

use Exception;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CustomerExportEmail\Cron\DeleteCustomerDataFile;
use Magento\Framework\Filesystem;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Filesystem\Driver\File;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class DeleteCustomerDataFileTest extends TestCase
{
    protected $loggerMock;
    protected $deleteCustomerDataFileMock;
    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var Filesystem|MockObject
     */
    protected $filesystem;

    /**
     * @var File|MockObject
     */
    protected $file;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    /**
     * Main set up method
     */
    public function setUp() : void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDirectoryRead', 'getDirectoryWrite', 'isDirectory', 'getAbsolutePath'])
            ->getMock();

        $this->file = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->setMethods(['readDirectory', 'isExists', 'deleteFile','deleteDirectory'])
            ->getMock();
        
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deleteCustomerDataFileMock = (new ObjectManager($this))->getObject(
            DeleteCustomerDataFile::class,
            [
                'logger' => $this->loggerMock,
                'fileSystem' => $this->filesystem,
                'file' => $this->file,
                'toggleConfig' =>$this->toggleConfigMock
            ]
        );
    }

    /**
     * Delete Customer data file
     */
    public function testDeleteCustomerDataFile()
    {
        $this->filesystem->expects($this->once())
            ->method('getDirectoryRead')
            ->willReturnSelf();

        $this->filesystem->expects($this->once())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystem->expects($this->once())
            ->method('isDirectory')
            ->willReturnSelf();

        $this->filesystem->expects($this->once())
            ->method('getAbsolutePath')
            ->willReturn("/var/www/html/staging3.office.fedex.com/pub/media/");

        $this->file->expects($this->any())
            ->method('readDirectory')
            ->willReturn(['test.csv']);

        $this->file->expects($this->any())
            ->method('isExists')
            ->willReturn(true);

        $this->file->expects($this->any())
            ->method('deleteFile')
            ->willReturn(true);

        $this->assertEquals(true, $this->deleteCustomerDataFileMock->deleteCustomerDataFile());
    }

    /**
     * Delete authorized csv file with exception
     */
    public function testDeleteAuthorizedExcelFileWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->filesystem->expects($this->once())
            ->method('getDirectoryRead')
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->willReturnSelf();

        $this->assertEquals(false, $this->deleteCustomerDataFileMock->deleteCustomerDataFile());
    }

    /**
     * Delete authorized csv file with directory as false
     */
    public function testDeleteCustomerDataFileWithDirectoyFalse()
    {
        $this->filesystem->expects($this->once())
        ->method('getDirectoryRead')
        ->willReturnSelf();

        $this->filesystem->expects($this->once())
        ->method('getDirectoryWrite')
        ->willReturnSelf();

        $this->filesystem->expects($this->once())
        ->method('isDirectory')
        ->willReturn(0);

        $this->assertEquals(false, $this->deleteCustomerDataFileMock->deleteCustomerDataFile());
    }

    /**
     * Delete authorized csv file with nfr toggle
     */
    public function testDeleteCustomerDataFileFileWithNFRToggleCheck()
    {
        $this->filesystem->expects($this->once())
            ->method('getDirectoryRead')
            ->willReturnSelf();

        $this->filesystem->expects($this->once())
            ->method('getDirectoryWrite')
            ->willReturnSelf();

        $this->filesystem->expects($this->once())
            ->method('isDirectory')
            ->willReturnSelf();

        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('xmen_B2041064_NFR_delete_operation')
            ->willReturn(true);

        $this->file->expects($this->once())
            ->method('deleteDirectory')
            ->willReturnSelf();

        $this->assertEquals(true, $this->deleteCustomerDataFileMock->deleteCustomerDataFile());
    }
}
