<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Test\Unit\Model\Source\Type;

use Fedex\Import\Model\Source\Type\Dropbox;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Phrase;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Catalog\Model\Session;
use Magento\Catalog\Model\SessionFactory;
use Psr\Log\LoggerInterface;
use ReflectionException;

class DropboxTest extends TestCase
{

    protected $directory;
    /**
     * @var (\Fedex\Import\Model\Source\Type\Dropbox & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dropbox;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $Mock;
    /**
     * Set up method
     */
    public function setUp():void
    {
        $this->directory = $this->getMockBuilder(Read::class)
        ->setMethods(['getAbsolutePath'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->dropbox = $this->getMockBuilder(Dropbox::class)
        ->disableOriginalConstructor()
        ->setMethods(['getData'])
        ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->Mock = $objectManagerHelper->getObject(
            Dropbox::class,
            [
                'directory' => $this->directory,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test method for setAccessToken
     *
     * @return void
     * @throws ReflectionException
     */
    public function testSetAccessToken()
    {
        $this->Mock->setAccessToken('Test');
        $testMethod = new \ReflectionMethod(
            \Fedex\Import\Model\Source\Type\Dropbox::class,
            'getSourceClient'
        );
        $testMethod->setAccessible(true);
        $testMethod->invoke($this->Mock);

        $testMethod = new \ReflectionMethod(
            \Fedex\Import\Model\Source\Type\Dropbox::class,
            'getMetadata'
        );
        $testMethod->setAccessible(true);
        $testMethod->invokeArgs($this->Mock, [null]);

        $testMethod = new \ReflectionMethod(
            \Fedex\Import\Model\Source\Type\Dropbox::class,
            'downloadFile'
        );
        $testMethod->setAccessible(true);
        $testMethod->invokeArgs($this->Mock, ['var/import']);
    }

    /**
     * Test method for uploadSource
     *
     * @return void
     * @throws ReflectionException
     */
    public function testUploadSource()
    {
        $this->directory->expects($this->any())->method('getAbsolutePath')->willReturn('var/import/dropbox/dropbox');
        $testMethod = new \ReflectionMethod(
            \Fedex\Import\Model\Source\Type\Dropbox::class,
            'downloadFile'
        );
        $testMethod->setAccessible(true);
        $testMethod->invokeArgs($this->Mock, ['var/import']);
        $this->Mock->uploadSource();
    }
    /**
     * Test method for uploadSourceWithException
     *
     * @return void
     */
    public function testUploadSourceWithException()
    {
        $this->directory->expects($this->any())->method('getAbsolutePath')->willReturn('var/import/dropbox/');
        $this->assertSame(
            $this->expectExceptionMessage("File not found on Dropbox"),
            $this->Mock->uploadSource()
        );
    }

    /**
     * Test method for uploadSource withDirectoryException
     *
     * @return void
     */
    public function testuploadSourceWithDirectoryException()
    {
        $this->directory->expects($this->any())->method('getAbsolutePath')->willReturn(null);
        $this->assertSame(
            $this->expectExceptionMessage(
                "Can't create local file /var/import/dropbox'. Please check files permissions."
            ),
            $this->Mock->uploadSource()
        );
    }

    /**
     * Test method for importImage
     *
     * @return void
     */
    public function testImportImage()
    {
        $this->directory->expects($this->any())->method('getAbsolutePath')->willReturn('var/import/dropbox/dropbox');
        $this->Mock->importImage('nature1', 'test');
    }

    /**
     * Test method for importImageWithImportImageink
     *
     * @return void
     */
    public function testImportImageWithImportImageink()
    {
        $this->Mock->importImage('https://fedex.com', 'test');
    }

    /**
     * Test method for importImageWithException
     *
     * @return void
     */
    public function testImportImageWithException()
    {
        $this->directory->expects($this->any())->method('getAbsolutePath')->willReturn('var/import/dropbox/');
        $this->assertSame(
            $this->expectExceptionMessage("Dropbox API Exception: "),
            $this->Mock->importImage('nature1', 'test')
        );
    }

    /**
     * Test method for importImageWithoutDirectory
     *
     * @return void
     */
    public function testImportImageWithoutDirectory()
    {
        $this->directory->expects($this->any())->method('getAbsolutePath')->willReturn('test/valid7/unknownpath');
        $this->Mock->importImage('nature1', 'test');
    }
}
