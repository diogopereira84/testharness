<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\CoreApi\Test\Model\Config;

use Fedex\CoreApi\Model\Config\AbstractConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractConfigTest extends TestCase
{
    public const XPATH_API_TIMEOUT   = 'api_timeout';

    /**
     * @var AbstractConfig|MockObject
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

        $this->configMock = $this->getMockForAbstractClass(
            AbstractConfig::class,
            [$this->scopeConfigMock]
        );
    }

    public function testGetApiTimeOut(): int
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with('/api_timeout', ScopeInterface::SCOPE_STORE, null)->willReturn(10);

        return $this->configMock->getApiTimeOut();
    }
}
