<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\ViewModel;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\Cart\ViewModel\UnfinishedProjectNotification;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session;
use Fedex\FXOCMConfigurator\ViewModel\FXOCMHelper;

class UnfinishedProjectNotificationTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Block\ArgumentInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $argumentInterface;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var UnfinishedProjectNotification
     */
    protected $unfinishedProjectNotification;

    /**
     * @var AuthHelper
     */
    protected $authHelper;

    /**
     * @var FXOCMHelper
     */
    protected $fxoCMHelper;

    /**
     * @var PerformanceImprovementPhaseTwoConfig
     */
    protected $performanceImprovementPhaseTwoConfig;

    /**
     * Prepare test environment.
     */
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn'])
            ->getMock();

        $this->argumentInterface = $this->getMockBuilder(ArgumentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->fxoCMHelper = $this->getMockBuilder(FXOCMHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWorkspaceData'])
            ->getMockForAbstractClass();

        $this->authHelper = $this->getMockBuilder(AuthHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn'])
            ->getMock();

        $this->performanceImprovementPhaseTwoConfig = $this->getMockBuilder(PerformanceImprovementPhaseTwoConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['isActive'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->unfinishedProjectNotification = $objectManagerHelper->getObject(
            UnfinishedProjectNotification::class,
            [
                'toggleConfig' => $this->toggleConfig,
                'customerSession' => $this->customerSession,
                'fxoCMHelper' => $this->fxoCMHelper,
                'authHelper' => $this->authHelper,
                'performanceImprovementPhaseTwoConfig' => $this->performanceImprovementPhaseTwoConfig
            ]
        );
    }

    /**
     * @test isCartPageUnfinisedPopupEnable
     *
     * @return void
     */
    public function testIsCartPageUnfinisedPopupEnableElse()
    {
        $this->assertNotNull(
            $this->unfinishedProjectNotification->isCartPageUnfinisedPopupEnable()
        );
    }

    /**
     * @test isCartPageUnfinisedPopupEnable
     *
     * @return void
     */
    public function testIsCartPageUnfinisedPopupEnableIf()
    {
        $jsonData = '{
            "files": [
                {
                    "name": "image (1).png",
                    "id": "15989604650130776624104756868130604756274",
                    "size": 1891636,
                    "uploadDateTime": "2023-12-28T12:56:23.990Z"
                }
            ],
            "projects": [
                {
                    "associatedDocumentIds": [
                        "15990093015118369392920033165711093807249"
                    ],
                    "product": {
                  }
                }
            ]
        }';
        $this->fxoCMHelper->expects($this->any())
            ->method('getWorkspaceData')
            ->willReturn($jsonData);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->assertNotNull(
            $this->unfinishedProjectNotification->isCartPageUnfinisedPopupEnable()
        );
    }

    /**
     * @test isProjectAvailable
     *
     * @return void
     */
    public function testIsProjectAvailable()
    {
        $this->fxoCMHelper->expects($this->any())
            ->method('getWorkspaceData')
            ->willReturn(null);

        $this->assertNotNull(
            $this->unfinishedProjectNotification->isProjectAvailable()
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @test Covers cached return when performance config is active
     */
    public function testIsProjectAvailableReturnsCachedValueWhenPerformanceActive()
    {
        $workspaceData = '{"projects":[{"id":"p1"}]}';

        $this->performanceImprovementPhaseTwoConfig->expects($this->exactly(2))
            ->method('isActive')
            ->willReturn(true);

        $this->fxoCMHelper->expects($this->once())
            ->method('getWorkspaceData')
            ->willReturn($workspaceData);

        $first = $this->unfinishedProjectNotification->isProjectAvailable();
        $this->assertTrue($first);

        $second = $this->unfinishedProjectNotification->isProjectAvailable();
        $this->assertTrue($second);
    }
}
