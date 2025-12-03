<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Block\Adminhtml\PSGCustomerform\Edit;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Fedex\CIDPSG\Block\Adminhtml\PSGCustomerform\Edit\BackButton;
use Magento\Backend\Block\Widget\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class BackButtonTest Block
 */
class BackButtonTest extends TestCase
{
    /**
     * @var ObjectManager $objectManager
     */
    protected $objectManager;

    /**
     * @var UrlInterface|MockObject $urlBuilderMock
     */
    protected $urlBuilderMock;

    /**
     * @var BackButton $backButton
     */
    protected $backButton;

    /**
     * @var Context $context
     */
    protected $context;

    /**
     * Set up method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->context = $this->objectManager->getObject(
            Context::class,
            [
                'urlBuilder' => $this->urlBuilderMock
            ]
        );
        $this->backButton = $this->objectManager->getObject(
            BackButton::class,
            [
                'context' => $this->context
            ]
        );
    }

    /**
     * Test getButtonData method
     *
     * @return void
     */
    public function testGetButtonData()
    {
        $url = '/back/url';
        $buttonData = [
            'label' => __('Back'),
            'on_click' => 'location.href = \'/back/url\';',
            'class' => 'back'
        ];

        $this->urlBuilderMock->expects($this->any())
            ->method('getUrl')
            ->willReturn($url);

        $this->assertEquals($buttonData, $this->backButton->getButtonData());
    }
}
