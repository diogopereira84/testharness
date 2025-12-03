<?php

declare(strict_types=1);

namespace Fedex\Logger\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\Logger\Model\Log;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Stdlib\CookieManagerInterface\Proxy as CookieManagerInterface;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class LogTest extends TestCase
{
    private const PHP_SESS_ID = '27a126c9tb94e150e55c85a5a45442ca';
    /**
     * @var MockObject|CookieManagerInterface
     */
    private $cookieManagerMock;

    /**
     * @var MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * @var MockObject|Log
     */
    protected $log;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->cookieManagerMock->method('getCookie')->willReturn(self::PHP_SESS_ID);

        $this->objectManager = new ObjectManager($this);
        $this->log = $this->objectManager->getObject(
            Log::class,
            [
                'name' => 'test',
                'cookieManager' => $this->cookieManagerMock
            ]
        );
    }

    /**
     * Test Method for addRecord.
     */
    public function testAddRecord()
    {
        $logMessageString = 'Order Creation Successful';
        
        $handler = new TestHandler();
        $this->log->pushHandler($handler);
        
        $this->log->addRecord(Logger::INFO, $logMessageString);
        list($record) = $handler->getRecords();
        $this->assertSame(self::PHP_SESS_ID.' '.$logMessageString, $record['message']);  

        $this->assertEquals(self::PHP_SESS_ID, $this->cookieManagerMock->getCookie('PHPSESSID'));

        $this->cookieManagerMock->expects($this->once())->method('getCookie')->with('PHPSESSID');

       $this->log->addRecord(Logger::INFO, $logMessageString);
    }
}
