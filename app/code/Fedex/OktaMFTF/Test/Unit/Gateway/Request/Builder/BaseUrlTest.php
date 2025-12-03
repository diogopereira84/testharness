<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Gateway\Request\Builder;

use Fedex\OktaMFTF\Model\Config\Credentials;
use Fedex\OktaMFTF\Gateway\Request\Builder\BaseUrl;
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
