<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Model;

use Fedex\Canva\Api\TemplateImageConverterInterface;
use Fedex\Canva\Model\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Image\Adapter\AdapterInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Magento\Framework\Api\ImageContent;
use Magento\Framework\Api\ImageContentFactory;
use Magento\Framework\Api\ImageContentValidator;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Magento\MediaStorage\Helper\File\Storage\Database;
use PHPUnit\Framework\TestCase;
use Fedex\Canva\Model\TemplateImageConverter;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;

class TemplateImageConverterTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected TemplateImageConverter $templateImageConverter;
    protected Filesystem|MockObject $fileSystemMock;
    protected WriteInterface|MockObject $writeInterfaceMock;
    protected ImageContentValidator|MockObject $imageContentValidatorMock;
    protected ImageContent|MockObject $imageContentMock;
    protected ImageContentFactory|MockObject $imageContentFactoryMock;
    protected Database|MockObject $databaseMock;
    protected AdapterFactory|MockObject $adapterFactoryMock;
    protected AdapterInterface|MockObject $adapterInterfaceMock;
    protected LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->fileSystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->writeInterfaceMock = $this->getMockBuilder(WriteInterface::class)
            ->onlyMethods(['writeFile'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->imageContentValidatorMock = $this->getMockBuilder(ImageContentValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->imageContentFactoryMock = $this->getMockBuilder(ImageContentFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->imageContentMock = $this->getMockBuilder(ImageContent::class)
            ->onlyMethods(['setBase64EncodedData', 'setType', 'setName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->imageContentFactoryMock->expects($this->atMost(3))->method('create')
            ->willReturn($this->imageContentMock);

        $this->databaseMock = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapterFactoryMock = $this->getMockBuilder(AdapterFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->onlyMethods(['open', 'resize', 'save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->templateImageConverter = $this->objectManager->getObject(
            TemplateImageConverter::class,
            [
                'filesystem' => $this->fileSystemMock,
                'imageContentValidator' => $this->imageContentValidatorMock,
                'imageContentFactory' => $this->imageContentFactoryMock,
                'mediaStorage' => $this->databaseMock,
                'imageAdapterFactory' => $this->adapterFactoryMock,
                'imageContent' => $this->imageContentFactoryMock->create(),
                'logger' => $this->loggerMock
            ]
        );
    }
    /**
     * @param mixed $imagePath
     * @dataProvider getImagePathDataProvider
     */
    public function testGetImagePath($imagePath): void
    {
        $reflectionClass = new ReflectionClass(TemplateImageConverter::class);
        $reflectionProperty = $reflectionClass->getProperty('imagePath');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->templateImageConverter, $imagePath);
        $this->assertEquals($imagePath, $this->templateImageConverter->getImagePath());
    }

    public function testConvert(): void
    {
        $base64Image = 'R0lGODlhEAAOALMAAOazToeHh0tLS/7LZv/0jvb29t/f3//Ub//ge8WSLf/rhf/3kdbW1mxsbP//mf///yH5BAAAAAAALAAAAAAQAA4AAARe8L1Ekyky67QZ1hLnjM5UUde0ECwLJoExKcppV0aCcGCmTIHEIUEqjgaORCMxIC6e0CcguWw6aFjsVMkkIr7g77ZKPJjPZqIyd7sJAgVGoEGv2xsBxqNgYPj/gAwXEQA7';
        $name = '';
        $image = base64_decode($base64Image);
        $imageProperties = @getimagesizefromstring($image);

        $this->imageContentMock->expects($this->once())->method('setBase64EncodedData')->with($base64Image)->willReturnSelf();
        $this->imageContentMock->expects($this->once())->method('setType')->with($imageProperties['mime'])->willReturnSelf();

        $this->fileSystemMock->expects($this->once())->method('getDirectoryWrite')->with(DirectoryList::MEDIA)->willReturn($this->writeInterfaceMock);
        $this->imageContentMock->expects($this->once())->method('setName')->withAnyParameters()->willReturnSelf();

        $this->imageContentValidatorMock->expects($this->once())->method('isValid')->with($this->imageContentMock)->willReturn(true);

        $this->writeInterfaceMock->expects($this->once())->method('writeFile')->withAnyParameters()->willReturnSelf();

        $this->adapterFactoryMock->expects($this->once())->method('create')->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->once())->method('open')->withAnyParameters()->willReturnSelf();
        $this->adapterInterfaceMock->expects($this->once())->method('resize')->with(350)->willReturnSelf();
        $this->adapterInterfaceMock->expects($this->once())->method('save')->withAnyParameters()->willReturnSelf();

        $this->databaseMock->expects($this->atMost(2))->method('saveFile')->withAnyParameters()->willReturnSelf();

        $this->assertInstanceOf(TemplateImageConverterInterface::class, $this->templateImageConverter->convert($base64Image, $name));
    }

    public function testConvertImageInvalid(): void
    {
        $base64Image = 'R0lGODlhEAAOALMAAOazToeHh0tLS/7LZv/0jvb29t/f3//Ub//ge8WSLf/rhf/3kdbW1mxsbP//mf///yH5BAAAAAAALAAAAAAQAA4AAARe8L1Ekyky67QZ1hLnjM5UUde0ECwLJoExKcppV0aCcGCmTIHEIUEqjgaORCMxIC6e0CcguWw6aFjsVMkkIr7g77ZKPJjPZqIyd7sJAgVGoEGv2xsBxqNgYPj/gAwXEQA7';
        $name = '';
        $image = base64_decode($base64Image);
        $imageProperties = @getimagesizefromstring($image);

        $this->imageContentMock->expects($this->once())->method('setBase64EncodedData')->with($base64Image)->willReturnSelf();
        $this->imageContentMock->expects($this->once())->method('setType')->with($imageProperties['mime'])->willReturnSelf();

        $this->fileSystemMock->expects($this->once())->method('getDirectoryWrite')->with(DirectoryList::MEDIA)->willReturn($this->writeInterfaceMock);

        $this->imageContentMock->expects($this->once())->method('setName')->withAnyParameters()->willReturnSelf();

        $this->imageContentValidatorMock->expects($this->once())->method('isValid')->with($this->imageContentMock)->willReturn(false);

        $this->assertInstanceOf(TemplateImageConverterInterface::class, $this->templateImageConverter->convert($base64Image, $name));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testConvertWillThrowException(): void
    {
        $this->expectException(InputException::class);
        $this->expectExceptionMessage('Unable to get properties from image.');

        $logMessage = 'Fedex\Canva\Model\TemplateImageConverter::convert:75 Unable to get properties from image.';
        $this->loggerMock->expects($this->once())->method('info')->with($logMessage);

        $imagebase64 = 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
        $this->assertSame(null, $this->templateImageConverter->convert($imagebase64, 'teste'));
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getImagePathDataProvider(): array
    {
        return [
            ["imagePath.jpg"]
        ];
    }
}
