<?php
namespace Fedex\SSO\Test\Unit\Model;

use Fedex\SSO\Model\ToggleConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig as ToggleManager;
use PHPUnit\Framework\TestCase;

class ToggleConfigTest extends TestCase
{
    /**
     * @var ToggleManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $toggleManagerMock;

    /**
     * @var ToggleConfig
     */
    private $toggleConfig;

    protected function setUp(): void
    {
        $this->toggleManagerMock = $this->getMockBuilder(ToggleManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = new ToggleConfig($this->toggleManagerMock);
    }

    public function testIsToggleD201662Enabled()
    {
        $this->toggleManagerMock->expects($this->once())
            ->method('getToggleConfig')
            ->with(ToggleConfig::XPATH_TOGGLE_D_201662)
            ->willReturn(true);

        $this->assertTrue($this->toggleConfig->isToggleD201662Enabled());
    }

    public function testIsToggleD201662Disabled()
    {
        $this->toggleManagerMock->expects($this->once())
            ->method('getToggleConfig')
            ->with(ToggleConfig::XPATH_TOGGLE_D_201662)
            ->willReturn(false);

        $this->assertFalse($this->toggleConfig->isToggleD201662Enabled());
    }
}
