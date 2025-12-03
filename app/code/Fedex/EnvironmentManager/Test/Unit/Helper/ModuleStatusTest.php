<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnvironmentManager\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\Helper\ModuleStatus;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\Manager;
use Magento\Framework\App\Helper\AbstractHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ModuleStatusTest extends TestCase
{
    /**
     * @var ModuleStatus $moduleStatus
     */
    protected $moduleStatus;

    /**
     * @var ObjectManager $objectManager
     */
    protected $objectManager;
    
    /**
     * @var Manager $moduleManager
     */
    protected $moduleManager;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->moduleManager = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'isEnabled'
                ]
            )
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->moduleStatus = $this->objectManager->getObject(
            ModuleStatus::class,
            [
                'moduleManager' => $this->moduleManager,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * Test Get Section Data
     */
    public function testIsModuleEnable()
    {
        $this->moduleManager->expects($this->any())
                            ->method('isEnabled')
                            ->willReturn(true);

        $this->assertEquals(true, $this->moduleStatus->isModuleEnable('EnhancedProfile'));
    }
}
