<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;
use Fedex\Canva\Model\ContentReader;
use PHPUnit\Framework\TestCase;

class ContentReaderTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected ContentReader $contentReaderMock;
    protected File|MockObject $fileMock;
    protected MockObject|Reader $readerMock;
    protected LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->fileMock = $this->getMockBuilder(File::class)
            ->onlyMethods(['fileExists', 'read'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->readerMock = $this->getMockBuilder(Reader::class)
            ->onlyMethods(['getModuleDir'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->onlyMethods(['error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->contentReaderMock = $this->objectManager->getObject(
            ContentReader::class,
            [
                'file' => $this->fileMock,
                'reader' => $this->readerMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    public function testGetContent()
    {
        $this->fileMock->expects($this->once())->method('fileExists')->willReturn(true);
        $this->fileMock->expects($this->once())->method('read')->willReturn('content of file');

        $this->assertIsString($this->contentReaderMock->getContent('blocks.json'));
    }

    public function testGetContentWillThrowException()
    {
        $exception = new \InvalidArgumentException("Module Fedex_Canva is not correctly registered.");

        $this->readerMock->expects($this->once())->method('getModuleDir')->with(false, 'Fedex_Canva')->willThrowException($exception);
        $this->loggerMock->expects($this->once())->method('error')->with('Fedex\Canva\Model\ContentReader::getContent:53 Module Fedex_Canva is not correctly registered.');

        $this->assertSame('', $this->contentReaderMock->getContent('blocks.json'));
    }
}
