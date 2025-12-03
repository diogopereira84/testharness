<?php
namespace Fedex\Company\Test\Unit\Model;

use Fedex\Company\Model\ImageUploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;


class ImageUploaderTest extends TestCase
{

    protected $filesystem;
    /**
     * @var (\Magento\Framework\Exception\LocalizedException & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $localizedExceptionMock;
    /**
     * @var (\Magento\Framework\Exception\NoSuchEntityException & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $NoSuchEntityExceptionMock;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlInterfaceMock;
    /**
     * @var (\Magento\MediaStorage\Helper\File\Storage\Database & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $databaseMock;
    /**
     * @var (\Magento\Framework\Filesystem & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $filesystemMock;
    protected $storeManagerInterfaceMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $LoggerInterfaceMock;
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManagerInstance;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $imageUploaderMock;
    const BASE_TMP_PATH = "Company/Logo";
    const BASE_PATH = "Company/Logo";
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];
    const ERROR_MSG = 'Something went wrong while saving the file(s).';

    /**
     * @var string
     */
    public $baseTmpPath;
    /**
     * @var string
     */
    public $basePath;
    /**
     * @var string[]
     */
    public $allowedExtensions;
    /**
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    private $coreFileStorageDatabase;
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private $mediaDirectory;
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    private $uploaderFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {

        $this->mediaDirectory = $this->getMockBuilder(WriteInterface::class)
        
            ->getMockForAbstractClass();

        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDirectoryWrite','renameFile','copyFile','getAbsolutePath'])
            ->getMock();
        
        $this->filesystem->expects($this->any())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->mediaDirectory);

            $this->uploaderFactory = $this->getMockBuilder(UploaderFactory::class)
            ->setMethods(['create','save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizedExceptionMock = $this->getMockBuilder(LocalizedException::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->NoSuchEntityExceptionMock = $this->getMockBuilder(NoSuchEntityException::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->databaseMock = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseUrl','getStore'])
            ->getMockForAbstractClass();

        $this->LoggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerInstance = \Magento\Framework\App\ObjectManager::getInstance();
        $this->objectManager = new ObjectManager($this);
        $this->imageUploaderMock = $this->objectManager->getObject(
            ImageUploader::class,
            [
                'coreFileStorageDatabase' => $this->databaseMock,
                'mediaDirectory' => $this->filesystem,
                'uploaderFactory' => $this->uploaderFactory,
                'storeManager' => $this->storeManagerInterfaceMock,
                'logger' => $this->LoggerInterfaceMock,
                'baseTmpPath' => self::BASE_TMP_PATH,
                'basePath' => self::BASE_PATH,
                'allowedExtensions' => self::ALLOWED_EXTENSIONS,

            ]
        );
    }

    public function testSetBaseTmpPath()
    {
        $baseTmpPath = 'https://staging3.office.fedex.com/stage3fedex7id4w/company/';
        $this->assertNull($this->imageUploaderMock->setBaseTmpPath($baseTmpPath));
    }

    public function testSetBasePath()
    {
        $baseTmpPath = 'https://staging3.office.fedex.com/stage3fedex7id4w/company/';
        $this->assertNull($this->imageUploaderMock->setBasePath($baseTmpPath));
    }

    public function testsetAllowedExtensions()
    {
        $this->assertNull($this->imageUploaderMock->setAllowedExtensions(SELF::ALLOWED_EXTENSIONS));
    }

    public function testgetAllowedExtensions()
    {
        $this->assertNotNull($this->imageUploaderMock->getAllowedExtensions());
    }
    public function testgetBasePath()
    {
        $this->assertNotNull($this->imageUploaderMock->getBasePath());
    }

    public function testgetFilePath()
    {
        $baseTmpPath = 'https://staging3.office.fedex.com/stage3fedex7id4w/company/';
        $imageName = 'Logo.png';
        $this->assertNotNull($this->imageUploaderMock->getFilePath($baseTmpPath, $imageName));
    }

    public function testgetBaseTmpPath()
    {
        $this->assertNotNull($this->imageUploaderMock->getBaseTmpPath());
    }
    public function testsaveMediaImage()
    {
        $baseTmpPath = 'https://staging3.office.fedex.com/stage3fedex7id4w/company/';
        $imageName = 'Logo.png';

        $this->assertNotNull($this->imageUploaderMock->saveMediaImage($imageName, $baseTmpPath));
    }

    public function testSaveFileToTmpDir()
    {
        $result = [
            "name" => "the-test-fun-for-friends-screenshot_1.png",
            "full_path" => "the-test-fun-for-friends-screenshot.png",
            "type" => "image/png",
            "tmp_name" => "/tmp/phpz2Ju4Z",
            "error" => "0",
            "size"=> "20363",
            "path"=> "/var/www/html/staging3.office.fedex.com/pub/media/Company/Logo",
            "file" => "the-test-fun-for-friends-screenshot_1.png",
            "url" =>"https://staging3.office.fedex.com/media/Company/Logo/the-test-fun-for-friends-screenshot_1.png"
        ];
        $uploaderMock = $this->getMockBuilder(Uploader::class)
            ->disableOriginalConstructor()
            ->setMethods(['save','setAllowedExtensions'])
            ->getMock();

            $uploaderMock->expects($this->any())
            ->method('setAllowedExtensions')
            ->with(SELF::ALLOWED_EXTENSIONS)
            ->willReturnSelf();

            $this->uploaderFactory->expects($this->any())
            ->method('create')
            ->willReturn($uploaderMock);

            $uploaderMock->expects($this->any())
            ->method('save')
            ->willReturn($result);

            $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getStore')
            ->willReturnSelf();

            $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn(SELF::BASE_TMP_PATH);

        $this->assertNotNull($this->imageUploaderMock->saveFileToTmpDir());

    }

}
