<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CmsImportExport\Test\Unit\Plugin\Ui\Component\Listing\Columns;

use Fedex\CmsImportExport\Plugin\Ui\Component\Listing\Columns\PreviewImage;
use Magento\PageBuilder\Ui\Component\Listing\Columns\PreviewImage as ParentPreviewImage;
use Magento\Framework\UrlInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test class for plugin class PreviewImageTest
 */
class PreviewImageTest extends TestCase
{

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $objPreviewImage;
    /**
     * @var UrlInterface $urlBuilder
     */
    private $urlBuilder;

    /**
     * @var Filesystem $filesystem
     */
    protected $filesystem;


    /**
     * @var ParentPreviewImage $parentPreviewImage
     */
    protected $parentPreviewImage;

    /**
     * Create mock for all injection
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
        ->setMethods(['getBaseUrl'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->filesystem = $this->getMockBuilder(Filesystem::class)
        ->setMethods(['getDirectoryRead', 'getAbsolutePath'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->parentPreviewImage = $this->getMockBuilder(ParentPreviewImage::class)
        ->setMethods(['getData'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->objPreviewImage = $this->objectManager->getObject(
            PreviewImage::class,
            [
                'urlBuilder' => $this->urlBuilder,
                'filesystem' => $this->filesystem
            ]
        );
    }

    /**
     * Test afterPrepareDataSource
     *
     * @return void
     */
    public function testAfterPrepareDataSource()
    {
        $arrResult = [
            'data' => [
                'items' => [
                    [
                        'preview_image' => 'test'
                    ]
                ]
            ]
        ];
        $this->filesystem->expects($this->once())->method('getDirectoryRead')->willReturnSelf();
        $this->filesystem->expects($this->once())->method('getAbsolutePath')->willReturn('../static/adminhtml');
        $this->parentPreviewImage->expects($this->once())->method('getData')->willReturn('preview_image');

        $this->assertIsArray($this->objPreviewImage->afterPrepareDataSource($this->parentPreviewImage, $arrResult));
    }
}
