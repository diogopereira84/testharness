<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SelfReg\Test\Unit\Plugin;

use Fedex\SelfReg\Plugin\LinksPlugin;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\View\Element\Html\Links;
use Magento\Framework\View\Element\AbstractBlock;

class LinksPluginTest extends TestCase
{
    protected $selfReg;
    protected $links;
    protected $abstractBlock;
    protected $linksPlugin;
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->selfReg = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegCustomerAdmin','toggleUserRolePermissionEnable'])
            ->getMock();

        $this->links = $this->getMockBuilder(Links::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractBlock = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNameInLayout'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->linksPlugin = $objectManagerHelper->getObject(
            LinksPlugin::class,
            [
                'selfReg' => $this->selfReg
            ]
        );
    }

    /**
     * Test Case for afterRenderLink
     *
     * @return void
     */
    public function testAfterRenderLink()
    {
        $result = ['customer-account'];
        $nameLayout = 'customer-account-company-company-users-link';
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(false);
        $this->abstractBlock->expects($this->any())
            ->method('getNameInLayout')
            ->willReturn($nameLayout);
        $this->selfReg->expects($this->any())
            ->method('toggleUserRolePermissionEnable')
            ->willReturn(false);
        $this->assertNull($this->linksPlugin->afterRenderLink($this->links, $result, $this->abstractBlock));
    }
}
