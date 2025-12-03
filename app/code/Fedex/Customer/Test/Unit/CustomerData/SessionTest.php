<?php
/**
 * @category    Fedex
 * @package     Fedex_Customer
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Test\Unit\CustomerData;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Stdlib\CookieManagerInterface;
use PHPUnit\Framework\TestCase;
use Fedex\Customer\CustomerData\Session;

class SessionTest extends TestCase
{
    /**
     * Cookie mock
     */
    private const COOKIE = '81738172187281728';

    /**
     * @var CookieManagerInterface|MockObject
     */
    private CookieManagerInterface $cookieManagerMock;


    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test getSectionData method
     * @return void
     */
    public function testGetSectionData(): void
    {
        $this->cookieManagerMock->expects($this->once())
            ->method('getCookie')->willReturn(self::COOKIE);

        $session = (new ObjectManager($this))->getObject(Session::class, [
            'cookieManager' => $this->cookieManagerMock
        ]);
        $this->assertEquals([
            'CUSTOMERSESSIONID' => self::COOKIE
        ], $session->getSectionData());
    }
}
