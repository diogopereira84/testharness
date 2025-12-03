<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Test\Unit\Plugin\Frontend\Magento\Checkout\Block\Cart\Item;

use Fedex\Orderhistory\Helper\Data;
use Fedex\Orderhistory\Plugin\Frontend\Magento\Checkout\Block\Cart\Item\Renderer as Plugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Block\Cart\Item\Renderer;

class RendererTest extends \PHPUnit\Framework\TestCase
{

    protected $helper;
    protected $subject;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $plugin;
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {

        $this->helper = $this->getMockBuilder(Data::class)
                        ->setMethods(['isModuleEnabled'])
                        ->disableOriginalConstructor()
                        ->getMock();

        $this->subject = $this->getMockBuilder(Renderer::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->plugin = $this->objectManager->getObject(
            Plugin::class,
            [
                'helper' => $this->helper
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function testAfterHasProductUrl()
    {
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);

        $this->assertEquals(
            false,
            $this->plugin->afterHasProductUrl($this->subject, true)
        );
    }

    /**
     * @inheritDoc
     */
    public function testAfterHasProductUrlWithFalse()
    {
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(false);

        $this->assertEquals(
            false,
            $this->plugin->afterHasProductUrl($this->subject, false)
        );
    }
}
