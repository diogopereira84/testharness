<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Model;

use Fedex\OktaMFTF\Model\Config\General as GeneralConfig;
use Magento\Framework\Logger\LoggerProxy;
use Fedex\OktaMFTF\Model\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    private GeneralConfig $configMock;
    private LoggerProxy $logMock;

    protected function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(GeneralConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock->expects($this->once())->method('isLogEnabled')->willReturn(true);
        $this->logMock = $this->getMockBuilder(LoggerProxy::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testEmergency()
    {
        $this->logMock->expects($this->once())->method('emergency');

        $logger = new Logger($this->logMock, $this->configMock);
        $logger->emergency('test');
    }

    public function testAlert()
    {
        $this->logMock->expects($this->once())->method('alert');

        $logger = new Logger($this->logMock, $this->configMock);
        $logger->alert('test');
    }

    public function testCritical()
    {
        $this->logMock->expects($this->once())->method('critical');

        $logger = new Logger($this->logMock, $this->configMock);
        $logger->critical('test');
    }

    public function testError()
    {
        $this->logMock->expects($this->once())->method('error');

        $logger = new Logger($this->logMock, $this->configMock);
        $logger->error('test');
    }

    public function testWarning()
    {
        $this->logMock->expects($this->once())->method('warning');

        $logger = new Logger($this->logMock, $this->configMock);
        $logger->warning('test');
    }

    public function testNotice()
    {
        $this->logMock->expects($this->once())->method('notice');

        $logger = new Logger($this->logMock, $this->configMock);
        $logger->notice('test');
    }

    public function testInfo()
    {
        $this->logMock->expects($this->once())->method('info');

        $logger = new Logger($this->logMock, $this->configMock);
        $logger->info('test');
    }

    public function testDebug()
    {
        $this->logMock->expects($this->once())->method('debug');

        $logger = new Logger($this->logMock, $this->configMock);
        $logger->debug('test');
    }

    public function testLog()
    {
        $this->logMock->expects($this->once())->method('log');

        $logger = new Logger($this->logMock, $this->configMock);
        $logger->log('emergency', 'test');
    }
}