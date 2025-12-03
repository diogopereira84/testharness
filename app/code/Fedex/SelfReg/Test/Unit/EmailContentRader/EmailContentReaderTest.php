<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Test\Unit\EmailContentRaders;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SelfReg\EmailContentReader\EmailContentReader;
use Magento\Framework\Filesystem\Io\File as FileReader;
use Magento\Framework\Module\Dir\Reader as DirReader;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Test class for EmailContentReader
 */
class EmailContentReaderTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $simpleContentReaderTestData;
    /**
     * @var DirReader $dirReader
     */
    protected $dirReader;

    /**
     * @var FileReader $fileReader
     */
    protected $fileReader;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->dirReader = $this->getMockBuilder(DirReader::class)
            ->setMethods(
                [
                    'getModuleDir'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileReader = $this->getMockBuilder(FileReader::class)
            ->setMethods(
                [
                    'fileExists',
                    'read'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->simpleContentReaderTestData = $this->objectManager->getObject(
            EmailContentReader::class,
            [
                'dirReader' => $this->dirReader,
                'fileReader' => $this->fileReader
            ]
        );
    }

    /**
     * Test getContent function
     *
     * @return void
     */
    public function testGetContent()
    {
        $this->assertEquals(null, $this->simpleContentReaderTestData->getContent('test_file'));
    }

    /**
     * Test getContent with file exist function
     *
     * @return void
     */
    public function testGetContentWithFileExist()
    {
        $readString = 'string';
        $this->fileReader->expects($this->any(0))->method('fileExists')->willReturn(1);
        $this->fileReader->expects($this->any(0))->method('read')->willReturn($readString);
        $this->assertEquals($readString, $this->simpleContentReaderTestData->getContent('test_file'));
    }

    /**
     * Test getContent with exception function
     *
     * @return void
     */
    public function testGetContentWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->dirReader->expects($this->any())->method('getModuleDir')->willThrowException($exception);
        $this->assertEquals(null, $this->simpleContentReaderTestData->getContent('test_file'));
    }
}
