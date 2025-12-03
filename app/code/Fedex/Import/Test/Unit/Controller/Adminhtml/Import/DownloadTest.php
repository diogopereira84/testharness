<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Import\Test\Unit\Controller\Adminhtml\Import;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\ImportExport\Model\Import\SampleFileProvider;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Import\Controller\Adminhtml\Import\Download;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DownloadTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $download;
    public const URL_REWRITE_SAMPLE_FILE = 'Fedex_Import';
    public const SAMPLE_FILES_MODULE = 'Magento_ImportExport';
    public const MODULE_DIR = '/vendor/magento/module-import-export';
    public const FILE_PATH = '/vendor/magento/module-import-export/Files/Sample/catalog_product.csv';

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @var Raw|MockObject
     */
    private $redirectMock;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactoryMock;

    /**
     * @var Filesystem|MockObject
     */
    private $fileSystemMock;

    /**
     * @var FileFactory|MockObject
     */
    private $fileFactoryMock;

    /**
     * @var Download|MockObject
     */
    private $downloadControllerMock;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManagerMock;

    /**
     * @var ReadInterface|MockObject
     */
    private $directoryMock;

    /**
     * @var ReadFactory|MockObject
     */
    private $readFactoryMock;

    /**
     * @var RawFactory|MockObject
     */
    private $rawFactoryMock;

    /**
     * @var Raw|MockObject
     */
    private $rawMock;

    /**
     * @var ComponentRegistrar|MockObject
     */
    private $componentRegistrarMock;

    /**
     * @var SampleFileProvider|MockObject
     */
    private $sampleFileProviderMock;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileSystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryMock = $this->getMockBuilder(ReadInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->fileFactoryMock = $this->getMockBuilder(FileFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rawFactoryMock = $this->getMockBuilder(RawFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rawMock = $this->getMockBuilder(Raw::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->readFactoryMock = $this->getMockBuilder(ReadFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->componentRegistrarMock = $this->createMock(ComponentRegistrar::class);
        $this->sampleFileProviderMock = $this->createMock(SampleFileProvider::class);
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->createMock(Context::class);

        $this->redirectMock = $this->createPartialMock(
            Redirect::class,
            ['setPath']
        );

        $this->resultRedirectFactoryMock = $this->createPartialMock(
            RedirectFactory::class,
            ['create']
        );
        $this->resultRedirectFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->redirectMock);

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->contextMock->expects($this->any())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactoryMock);

        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->downloadControllerMock = $this->objectManagerHelper->getObject(
            Download::class,
            [
                'context' => $this->contextMock,
                '_authorization' => $this->contextMock,
                'fileFactory' => $this->fileFactoryMock,
                'resultRawFactory' => $this->rawFactoryMock,
                'readFactory' => $this->readFactoryMock,
                'componentRegistrar' => $this->componentRegistrarMock,
                'sampleFileProvider' => $this->sampleFileProviderMock,
                'logger' => $this->loggerMock
            ]
        );
        $this->download = $this->objectManagerHelper->getObject(Download::class);
    }

    /**
     * Test Execute Method with Success
     *
     * @return void
     */
    public function testExecuteSuccess()
    {
        $this->requestMock->method('getParam')
            ->with('filename')
            ->willReturn('catalog_product');

        $this->componentRegistrarMock->expects($this->any())
            ->method('getPath')
            ->with(ComponentRegistrar::MODULE, self::SAMPLE_FILES_MODULE)
            ->willReturn(self::MODULE_DIR);

        $this->readFactoryMock->expects($this->any())->method('create')->willReturn($this->directoryMock);
        $this->directoryMock->expects($this->any())->method('getRelativePath')->willReturn(self::FILE_PATH);
        $this->directoryMock->expects($this->any())->method('isFile')->willReturn(true);
        $this->rawFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->rawMock);
        $this->rawMock->expects($this->any())->method('setContents')
            ->willReturn($this->rawMock);

        $testMethod = new \ReflectionMethod(
            Download::class,
            '_isAllowed',
        );
        $testMethod->invoke($this->download);

        $this->downloadControllerMock->execute();
    }

    /**
     * Test download controller with file that doesn't exist
     *
     * @return void
     */
    public function testExecuteFileDoesntExists()
    {
        $this->requestMock->method('getParam')
            ->with('filename')
            ->willReturn('sampleFile');

        $this->componentRegistrarMock->expects($this->any())
            ->method('getPath')
            ->with(ComponentRegistrar::MODULE, self::SAMPLE_FILES_MODULE)
            ->willReturn(self::MODULE_DIR);
        $this->readFactoryMock->expects($this->any())->method('create')->willReturn($this->directoryMock);
        $this->directoryMock->expects($this->any())->method('getRelativePath')->willReturn(self::FILE_PATH);
        $this->directoryMock->expects($this->any())->method('isFile')->willReturn(false);
        $this->messageManagerMock->expects($this->any())->method('addErrorMessage');
        $this->downloadControllerMock->execute();
    }

    /**
     * Test execute with invalid file name
     *
     * @return void
     */
    public function testExecuteInvalidFileName()
    {
        $this->requestMock->method('getParam')->with('filename')->willReturn('fedex_product');
        $this->componentRegistrarMock->expects($this->any())
            ->method('getPath')
            ->with(ComponentRegistrar::MODULE, self::URL_REWRITE_SAMPLE_FILE)
            ->willReturn(self::MODULE_DIR);
        $this->readFactoryMock->expects($this->any())->method('create')->willReturn($this->directoryMock);
        $this->directoryMock->expects($this->any())->method('getRelativePath')->willReturn(self::FILE_PATH);

        $this->messageManagerMock->expects($this->any())->method('addErrorMessage');
        $this->downloadControllerMock->execute();
    }
}
