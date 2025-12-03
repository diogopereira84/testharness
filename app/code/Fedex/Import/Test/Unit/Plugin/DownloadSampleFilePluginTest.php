<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
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
use Fedex\Import\Plugin\DownloadSampleFilePlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Import\Controller\Adminhtml\Import\Download;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DownloadSampleFilePluginTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $redirectFactoryMock;
    protected $MockDownload;
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
     * @var RequestInterface|MockObject
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
    private \Closure $proceed;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

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

        $this->redirectFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\RedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Redirect::class)
            ->setMethods(['setPath'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->MockDownload = $this->objectManagerHelper->getObject(
            DownloadSampleFilePlugin::class,
            [
                'context' => $this->contextMock,
                '_authorization' => $this->contextMock,
                'fileFactory' => $this->fileFactoryMock,
                'resultRawFactory' => $this->rawFactoryMock,
                'readFactory' => $this->readFactoryMock,
                'componentRegistrar' => $this->componentRegistrarMock,
                'sampleFileProvider' => $this->sampleFileProviderMock,
                'logger' => $this->loggerMock,
                'request' => $this->requestMock,
                'messageManager' => $this->messageManagerMock,
                'resultRedirectFactory' => $this->redirectFactoryMock
            ]
        );
    }

    /**
     * Test method for arounExecute
     *
     * @return void
     */
    public function testaroundExecute()
    {
        $className = Download::class;
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject = $this->createMock($className);

        $this->proceed = function () use ($subject) {
            return $subject;
        };

        $this->requestMock->method('getParam')
        ->with('filename')
        ->willReturn('catalog_product');

        $this->componentRegistrarMock->expects($this->any())
        ->method('getPath')->willReturn(self::MODULE_DIR);

        $this->readFactoryMock->expects($this->any())->method('create')->willReturn($this->directoryMock);
        $this->directoryMock->expects($this->any())->method('getRelativePath')->willReturn(self::FILE_PATH);
        $this->directoryMock->expects($this->any())->method('isFile')->willReturn(true);
        $this->rawFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->rawMock);
        $this->rawMock->expects($this->any())->method('setContents')
        ->willReturn($this->rawMock);

        $this->redirectMock->expects($this->any())->method('setPath')
        ->willReturnSelf();

        $this->MockDownload->aroundExecute($subject, $this->proceed);
    }

    /**
     * Test method for arounExecute with No file path
     *
     * @return void
     */
    public function testaroundExecuteWithNoFilePath()
    {
        $className = Download::class;
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject = $this->createMock($className);

        $this->proceed = function () use ($subject) {
            return $subject;
        };

        $this->requestMock->method('getParam')
        ->with('filename')
        ->willReturn('catalog_product');

        $this->componentRegistrarMock->expects($this->any())
        ->method('getPath')->willReturn('/vendor/magento/module-import-export');

        $this->readFactoryMock->expects($this->any())->method('create')->willReturn($this->directoryMock);
        $this->directoryMock->expects($this->any())->method('getRelativePath')->willReturn(self::FILE_PATH);
        $this->directoryMock->expects($this->any())->method('isFile')->willReturn(false);
        $this->rawFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->rawMock);
        $this->rawMock->expects($this->any())->method('setContents')
        ->willReturn($this->rawMock);

        $this->redirectFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->redirectMock);

        $this->redirectMock->expects($this->any())->method('setPath')
        ->willReturnSelf();

        $this->MockDownload->aroundExecute($subject, $this->proceed);
    }

    /**
     * Test method for arounExecute with different product
     *
     * @return void
     */
    public function testaroundExecuteWithDifferentProduct()
    {

        $className = Download::class;
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject = $this->createMock($className);

        $this->proceed = function () use ($subject) {
            return $subject;
        };

        $this->requestMock->method('getParam')
        ->with('filename')
        ->willReturn('test1');

        $this->componentRegistrarMock->expects($this->any())
        ->method('getPath')->willReturn('/vendor/magento/module-import-export');

        $this->readFactoryMock->expects($this->any())->method('create')->willReturn($this->directoryMock);
        $this->directoryMock->expects($this->any())->method('getRelativePath')->willReturn(self::FILE_PATH);
        $this->directoryMock->expects($this->any())->method('isFile')->willReturn(false);
        $this->rawFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->rawMock);
        $this->rawMock->expects($this->any())->method('setContents')
        ->willReturn($this->rawMock);

        $this->MockDownload->aroundExecute($subject, $this->proceed);
    }

    /**
     * Test method for arounExecute with exception
     *
     * @return void
     */
    public function testaroundExecuteWithException()
    {
        $className = \Magento\ImportExport\Controller\Adminhtml\Import\Download::class;
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject = $this->createMock($className);

        $this->proceed = function () use ($subject) {
            return $subject;
        };
        $this->requestMock->method('getParam')
            ->with('filename')
            ->willReturn('catalog_product');

        $this->componentRegistrarMock->expects($this->any())
            ->method('getPath')
            ->with(ComponentRegistrar::MODULE, self::SAMPLE_FILES_MODULE)
            ->willReturn(self::MODULE_DIR);
        $this->readFactoryMock->expects($this->any())->method('create')->willReturn($this->directoryMock);
        $this->directoryMock->expects($this->any())->method('getRelativePath')->willReturn(self::FILE_PATH);
        $this->directoryMock->expects($this->any())->method('isFile')->willReturn(false);
        $this->messageManagerMock->expects($this->any())->method('addErrorMessage');

        $this->MockDownload->aroundExecute($subject, $this->proceed);
    }
}
