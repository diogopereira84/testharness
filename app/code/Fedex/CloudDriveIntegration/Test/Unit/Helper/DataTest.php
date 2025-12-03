<?php
/**
 * @category    Fedex
 * @package     Fedex_CloudDriveIntegration
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CloudDriveIntegration\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\CloudDriveIntegration\Helper\Data;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var Data
     */
    private Data $data;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private ScopeConfigInterface $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->data = new Data($this->scopeConfigMock);
    }

    public function testIsEnabled()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with('fedex/cloud_drive_integration/enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);
        $this->assertTrue($this->data->isEnabled());
    }

    public function testIsBoxEnabled()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with('fedex/cloud_drive_integration/box_enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);
        $this->assertTrue($this->data->isBoxEnabled());
    }

    public function testIsDropboxEnabled()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with('fedex/cloud_drive_integration/dropbox_enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);
        $this->assertTrue($this->data->isDropboxEnabled());
    }

    public function testIsGoogleEnabled()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with('fedex/cloud_drive_integration/google_enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);
        $this->assertTrue($this->data->isGoogleEnabled());
    }

    public function testIsMicrosoftEnabled()
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with('fedex/cloud_drive_integration/microsoft_enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);
        $this->assertTrue($this->data->isMicrosoftEnabled());
    }
}
