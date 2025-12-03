<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\CoreApi\Test\Model\Config;

use Fedex\CoreApi\Model\Config\AbstractConfig;
use Fedex\CoreApi\Model\Config\Backend;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BackendTest extends TestCase
{
    /**
     * @var AbstractConfig|Backend
     */
    private AbstractConfig $configMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private ScopeConfigInterface|MockObject $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            ['getValue', 'isSetFlag']
        );

        $this->configMock = new Backend(
            $this->scopeConfigMock
        );
    }

    public function testGetApiTimeOut(): void
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with('fedex/general/api_timeout', ScopeInterface::SCOPE_STORE, null)->willReturn(10);

        $timeOut = $this->configMock->getApiTimeOut();
        $this->assertIsInt($timeOut);
        $this->assertEquals(10, $timeOut);
    }
}
