<?php

declare(strict_types=1);

namespace Fedex\EnhancedProfile\Test\Unit\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fedex\SelfReg\Model\EnhanceRolePermissionFactory;
use Fedex\SelfReg\Model\EnhanceRolePermission;
use Fedex\EnhancedProfile\Setup\Patch\Data\InsertSiteSettingsPermission;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class InsertSiteSettingsPermissionTest extends TestCase
{
    protected $enhanceRolePermissionCollection;
    /**
     * @var ModuleDataSetupInterface|MockObject
     */
    private $moduleDataSetup;

    /**
     * @var EnhanceRolePermissionFactory|MockObject
     */
    private $enhanceRolePermissionFactory;

    /**
     * @var EnhanceRolePermission|MockObject
     */
    private $enhanceRolePermission;

    /**
     * @var InsertSiteSettingsPermission
     */
    private $dataPatch;

    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->enhanceRolePermissionFactory = $this->createMock(EnhanceRolePermissionFactory::class);
        $this->enhanceRolePermission = $this->getMockBuilder(EnhanceRolePermission::class)
            ->setMethods(['create', 'setData', 'setLabel', 'save','getCollection', 'load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->enhanceRolePermissionCollection =
        $this->getMockBuilder(\Fedex\SelfReg\Model\ResourceModel\EnhanceRolePermission\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getItems', 'getSize','getIterator'])
            ->getMock();

        $this->dataPatch = new InsertSiteSettingsPermission(
            $this->moduleDataSetup,
            $this->enhanceRolePermissionFactory,
            $this->enhanceRolePermission
        );
    }

    public function testApply()
    {
        // Mock the factory to return the model when create is called
        $this->enhanceRolePermissionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->enhanceRolePermission);

        // Mock the model's methods
        $this->enhanceRolePermission->expects($this->once())
            ->method('setData')
            ->with([
                'label' => 'Site Settings::site_settings',
                'sort_order' => '6',
                'tooltip' => "Users with this permission will be able to access the 'Site Settings' tab to change the site settings."
            ])
            ->willReturnSelf();

        $this->enhanceRolePermission->expects($this->once())
            ->method('getCollection')
            ->willReturn($this->enhanceRolePermissionCollection);

        $this->enhanceRolePermissionCollection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->enhanceRolePermission]));

        $this->enhanceRolePermissionCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('label', ['eq' => 'Shared Credit Cards::shared_credit_cards'])
            ->willReturnSelf();

        $this->enhanceRolePermissionCollection->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $this->enhanceRolePermission->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->enhanceRolePermission->expects($this->once())
            ->method('setLabel')
            ->with('Site Level Payments::shared_credit_cards')
            ->willReturnSelf();

        $this->enhanceRolePermission->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        // Mock the startSetup and endSetup methods
        $this->moduleDataSetup->expects($this->once())
            ->method('startSetup');

        $this->moduleDataSetup->expects($this->once())
            ->method('endSetup');

        // Run the apply method
        $this->dataPatch->apply();
    }
}
