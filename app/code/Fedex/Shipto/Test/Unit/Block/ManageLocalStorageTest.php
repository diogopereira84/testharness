<?php

namespace Fedex\Shipto\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Cms\Model\Page;
use Fedex\Shipto\Block\ManageLocalStorage;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ManageLocalStorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $toggleConfigMock;
    protected $cmsPageMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $localStorageObj;
    /**
     * setup method
     */
    protected function setUp(): void
    {
        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->cmsPageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdentifier'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->localStorageObj = $this->objectManager->getObject(
            ManageLocalStorage::class,
            [
                'toggleConfig' => $this->toggleConfigMock,
                'cmsPage' => $this->cmsPageMock
            ]
        );
    }

    /**
     * Assert removeLocalStorage
     *
     */
    public function testRemoveLocalStorage()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->cmsPageMock->expects($this->any())->method('getIdentifier')->willReturn('success');
        $this->assertEquals(true, $this->localStorageObj->removeLocalStorage());
    }

    /**
     * Assert removeLocalStorageForFalse
     *
     */
    public function testRemoveLocalStorageForFalse()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->assertEquals(false, $this->localStorageObj->removeLocalStorage());
    }
}
