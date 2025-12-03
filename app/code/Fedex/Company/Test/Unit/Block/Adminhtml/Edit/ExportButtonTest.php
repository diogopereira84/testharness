<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Block\Adminhtml\Edit;

use Fedex\Company\Block\Adminhtml\Edit\ExportButton;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Fedex\Company\Block\Adminhtml\Edit\ExportButton class.
 */
class ExportButtonTest extends TestCase
{
    protected $toggleConfig;
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ExportButton|MockObject
     */
    private $exportButton;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilderMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->exportButton = $this->objectManagerHelper->getObject(
            ExportButton::class,
            [
                'urlBuilder' => $this->urlBuilderMock,
                'request' => $this->requestMock,
                'toggleConfig' => $this->toggleConfig,
            ]
        );
    }

    /**
     * Test for method getButtonData.
     *
     * @return array
     */
    public function testGetButtonData()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->requestMock->expects($this->any())->method('getParam')->with('id')->willReturn(48);
        $this->urlBuilderMock->expects($this->any())->method('getUrl')->willReturn('company/index/export');

        $this->assertNotNull($this->exportButton->getButtonData());
    }

    /**
     * Test for method getButtonData.
     *
     * @return array
     */
    public function testGetButtonDataWithEmptyParam()
    {
        $this->requestMock->expects($this->any())->method('getParam')->with('id')->willReturn(0);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);

        $this->assertEquals([], $this->exportButton->getButtonData());
    }
}
