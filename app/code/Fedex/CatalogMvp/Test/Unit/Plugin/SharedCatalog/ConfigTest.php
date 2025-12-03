<?php

namespace Fedex\CatalogMvp\Test\Unit\Plugin\SharedCatalog;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Customer\Model\Session;
use Magento\SharedCatalog\Model\Config as ParentConfig;
use Fedex\CatalogMvp\Plugin\SharedCatalog\Config;

class ConfigTest extends TestCase
{
    /**
     * @var (\Fedex\CatalogMvp\Helper\CatalogMvp & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $helper;
    protected $customerSession;
    protected $subject;
    protected $config;
    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFromMvpProductCreate'])
            ->getMock();

        $this->subject = $this->getMockBuilder(ParentConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->config = $objectManagerHelper->getObject(
            Config::class,
            [
                'helper' => $this->helper,
                'customerSession' => $this->customerSession
            ]
        );
    }

    /**
     * Test case for afterIsActive with active session value
     */
    public function testAfterIsActive()
    {
        $this->customerSession->expects($this->any())
            ->method('getFromMvpProductCreate')
            ->willReturn(true);
        $this->assertEquals(false, $this->config->afterIsActive($this->subject, true));
    }

    /**
     * Test case for afterIsActive with inactive session value
     */
    public function testAfterIsActiveWithInactive()
    {
        $this->customerSession->expects($this->any())
            ->method('getFromMvpProductCreate')
            ->willReturn(false);
        $this->assertEquals(true, $this->config->afterIsActive($this->subject, true));
    }
}
