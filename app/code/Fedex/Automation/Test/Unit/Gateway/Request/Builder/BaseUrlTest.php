<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Test\Unit\Gateway\Request\Builder;

use Fedex\OktaMFTF\Model\Config\Credentials;
use Fedex\Automation\Gateway\Request\Builder\BaseUrl;
use PHPUnit\Framework\TestCase;

class BaseUrlTest extends TestCase
{
    public function testBuild(): void
    {
        $credentialsMock = $this->getMockBuilder(Credentials::class)
            ->disableOriginalConstructor()
            ->getMock();
        $builder = new BaseUrl($credentialsMock);
        $credentialsMock->expects($this->once())->method('getDomain')->willReturn('domain.com');
        $credentialsMock->expects($this->once())->method('getAuthorizationServerId')->willReturn('server-id');
        $result = $builder->build();

        $this->assertEquals("https://domain.com/oauth2/server-id", $result);
    }
}
