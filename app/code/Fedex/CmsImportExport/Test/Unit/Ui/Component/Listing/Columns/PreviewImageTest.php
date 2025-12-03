<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CmsImportExport\Test\Unit\Ui\Component\Listing\Columns;

use Magento\Framework\App\Filesystem\DirectoryList;
use Fedex\CmsImportExport\Ui\Component\Listing\Columns\PreviewImage;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PreviewImageTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\UiComponent\ContextInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\View\Element\UiComponentFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $uiComponentFactory;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlInterface;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $filesystem;
    protected $previewImage;
    /**
     * @var Index
     */
    protected $controller;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->uiComponentFactory = $this->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterface = $this->getMockForAbstractClass(UrlInterface::class);

        $this->objectManager = new ObjectManager($this);

        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->setMethods(['getDirectoryRead','getAbsolutePath'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->previewImage = $this->objectManager->getObject(
            PreviewImage::class,
            [
                'contextMock' => $this->contextMock,
                'uiComponentFactory' => $this->uiComponentFactory,
                'urlInterface' => $this->urlInterface,
                'filesystem' => $this->filesystem
            ]
        );
    }

    /**
     * Controller test
     */
    public function testPrepareDataSource()
    {
        $data = [
        'data' => [
                "items" => [[
                    "template_id" => "1",
                    "name" => "test",
                    "preview_image"  => "test",
                    "" => ""
                ],
                [
                    "template_id" => "1",
                    "name" => "test",
                    "preview_image" => "test",
                    "" => ""
                ]]
            ]
        ];

        $directoryMock = $this->getMockBuilder(ReadInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->filesystem->expects($this->any())->method('getDirectoryRead')
            ->willReturn($directoryMock);
        $directoryMock->expects(static::once())
            ->method('getAbsolutePath')
            ->willReturn('/www/www/html/media/p.png');

        $this->previewImage->prepareDataSource($data);
    }
}
