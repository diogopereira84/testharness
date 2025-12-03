<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomizedMegamenu\Block\Widget;

use Fedex\CustomizedMegamenu\Block\Widget\StaticLink;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class StaticLinkTest extends TestCase
{
    /**
     * @var ObjectManager $objectManager
     */
    protected $objectManager;

    /**
     * @var StaticLink $staticLinkBlock
     */
    protected $staticLinkBlock;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->staticLinkBlock = $this->objectManager->getObject(
            StaticLink::class,
            []
        );
    }

    /**
     * Test template
     *
     * @return void
     */
    public function testTemplate()
    {
        $expectedTemplate = "widget/staticlink.phtml";

        $this->assertEquals($expectedTemplate, $this->staticLinkBlock->getTemplate());
    }
}
