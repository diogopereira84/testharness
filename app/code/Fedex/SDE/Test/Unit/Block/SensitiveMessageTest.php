<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\Test\Unit\Block;

use Fedex\SDE\Block\SensitiveMessage;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SensitiveMessageTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $sensitiveMessageMock;
    /**
     * @var SdeHelper
     */
    protected $sdeHelper;
    
    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(
                [
                    'isFacingMsgEnable',
                    'getIsSdeStore',
                    'getSdeSecureImagePath',
                    'getSdeSecureTitle',
                    'getSdeSecureContent'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->sensitiveMessageMock = $this->objectManager->getObject(
            SensitiveMessage::class,
            [
                'sdeHelper' => $this->sdeHelper
            ]
        );
    }

    /**
     * Test Check if sensitive message block can be shown
     */
    public function testCanShowSensitiveMessageBlock()
    {
        $this->sdeHelper->expects($this->any())->method('isFacingMsgEnable')->willReturn(true);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);

        $this->assertEquals(true, $this->sensitiveMessageMock->canShowSensitiveMessageBlock());
    }

    /**
     * Test Check if sensitive message block can be shown
     */
    public function testCanShowSensitiveMessageBlockForNonSde()
    {
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);

        $this->assertEquals(false, $this->sensitiveMessageMock->canShowSensitiveMessageBlock());
    }

    /**
     * Test Get Sde secure Image path
     *
     * @return string
     */
    public function testGetSdeSecureImagePath()
    {
        $imagePath = 'image_path.png';
        $this->sdeHelper->expects($this->any())->method('getSdeSecureImagePath')->willReturn($imagePath);

        $this->assertEquals($imagePath, $this->sensitiveMessageMock->getSdeSecureImagePath());
    }

    /**
     * Test Get Sde message block title
     *
     * @return string
     */
    public function testGetSdeSecureTitle()
    {
        $title = 'sensitive data title';
        $this->sdeHelper->expects($this->any())->method('getSdeSecureTitle')->willReturn($title);

        $this->assertEquals($title, $this->sensitiveMessageMock->getSdeSecureTitle());
    }

    /**
     * Test Get Sde message block content
     *
     * @return string
     */
    public function testGetSdeSecureContent()
    {
        $content = 'sensitive data content';
        $this->sdeHelper->expects($this->any())->method('getSdeSecureContent')->willReturn($content);

        $this->assertEquals($content, $this->sensitiveMessageMock->getSdeSecureContent());
    }
}
