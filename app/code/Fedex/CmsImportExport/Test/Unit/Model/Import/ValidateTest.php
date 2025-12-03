<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CmsImportExport\Test\Unit\Model\Import;

use Fedex\CmsImportExport\Model\Import\Validate;
use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\UploaderFactory;
use \Fedex\CmsImportExport\Helper\Data;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValidateTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Exception\LocalizedException & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $localizedException;
    protected $uploaderFactory;
    protected $helperData;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $validate;
    /**
     * @var Index
     */
    protected $controller;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->localizedException = $this->getMockBuilder(LocalizedException::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->uploaderFactory = $this->getMockBuilder(UploaderFactory::class)
        ->setMethods(['create','setAllowCreateFolders','setAllowedExtensions',
            'setAllowRenameFiles','checkAllowedExtension','getFileExtension',
            'save', 'getUploadedFileName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperData = $this->getMockBuilder(Data::class)
            ->setMethods(['getDestinationPath','getCsvHeader','convertCsvToArray'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
			->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->validate = $this->objectManager->getObject(
            Validate::class,
            [
                'uploaderFactory' => $this->uploaderFactory,
                'helper' => $this->helperData,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Controller test
     */
    public function testValidateCsv()
    {
        $this->helperData->expects($this->any())
            ->method('getDestinationPath')
            ->willReturn("/var/www/html");
        $this->uploaderFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('setAllowCreateFolders')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('setAllowedExtensions')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('setAllowRenameFiles')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('checkAllowedExtension')
            ->willReturn("true");
        $this->uploaderFactory->expects($this->any())
            ->method('save')
            ->willReturn("/var/www/html");
        $this->uploaderFactory->expects($this->any())
            ->method('getUploadedFileName')
            ->willReturn("test");
        $data = ['name','id'];
        $this->helperData->expects($this->any())
            ->method('getCsvHeader')
            ->willReturn(["name,sku"]);
        $data = ["type" => "cms_block"];
        $this->helperData->expects($this->any())
            ->method('convertCsvToArray')
            ->willReturn([$data]);

        $this->validate->validateCsv();
    }

    public function testValidateCsvWithWrongDestinationPath()
    {
        $this->helperData->expects($this->any())
            ->method('getDestinationPath')
            ->willReturn("/var/www/html");
        $this->uploaderFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('setAllowCreateFolders')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('setAllowedExtensions')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('setAllowRenameFiles')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('checkAllowedExtension')
            ->willReturn("true");
        $this->uploaderFactory->expects($this->any())
            ->method('save')
            ->willReturn("1");

        $this->validate->validateCsv();
    }

    /**
     * Controller test
     */
    public function testValidateCsvWithException()
    {
        $this->helperData->expects($this->any())
            ->method('getDestinationPath')
            ->willReturn("/var/www/html");
        $this->uploaderFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('setAllowCreateFolders')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('setAllowedExtensions')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('setAllowRenameFiles')
            ->willReturnSelf();
        $this->uploaderFactory->expects($this->any())
            ->method('checkAllowedExtension')
            ->willReturn("true");
        $this->uploaderFactory->expects($this->any())
            ->method('save')
            ->willReturn("/var/www/html");
        $this->uploaderFactory->expects($this->any())
            ->method('getUploadedFileName')
            ->willReturn("test");
        $data = ['name','id'];
        $this->helperData->expects($this->any())
            ->method('getCsvHeader')
            ->willReturn(["name","sku"]);
    
        $this->validate->validateCsv();
    }

    public function testValidateRowBlock()
    {
        $data = [
            "type"=>'cms_block'
        ];
        $expectedResult = "1";
        $this->assertSame(
            $expectedResult,
            $this->validate->validateRow("1", $data)
        );
    }

    public function testValidateRowTemplate()
    {
        $data = [
            "type"=>'template'
        ];
        $expectedResult = "1";
        $this->assertSame(
            $expectedResult,
            $this->validate->validateRow("1", $data)
        );
    }

    public function testValidateRowWWidget()
    {
        $data = [
            "type"=>'widget'
        ];
        $expectedResult = "1";
        $this->assertSame($expectedResult, $this->validate
            ->validateRow("1", $data));
    }

    public function testValidateBlockPage()
    {
        $data = [
            "identifier"=>'test',
            "title"=>"test",
            "content"=>"test"
        ];
        $expectedResult = "";
        $this->assertSame($expectedResult, $this->validate
            ->validateBlockPage("1", $data));
    }

    public function testValidateTemplate()
    {
        $data = [
            "name"=>'test',
            "created_for"=>"test",
            "content"=>"test"
        ];
        $expectedResult = "";
        $this->assertSame($expectedResult, $this->validate
            ->validateTemplate("1", $data));
    }

    public function testValidateWidget()
    {
        $data = [
            "instance_type"=>'test',
            "instance_code"=>"test",
            "theme_id"=>"1"
        ];
        $expectedResult = "";
        $this->assertSame($expectedResult, $this->validate
            ->validateWidget("1", $data));
    }
}
