<?php
namespace Fedex\MarketplacePunchout\Test\Model;

use Fedex\MarketplacePunchout\Model\FclLogin;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FclLoginTest extends TestCase
{
    /** @var TimezoneInterface|MockObject */
    private $timezoneMock;

    /** @var FclLogin */
    private $fclLogin;

    protected function setUp(): void
    {
        $this->timezoneMock = $this->createMock(TimezoneInterface::class);

        $this->fclLogin = new FclLogin(
            $this->timezoneMock
        );
    }

    public function testSetDataAndGetData()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];

        $this->fclLogin->setData($data);
        $result = $this->fclLogin->getData();

        $this->assertEquals($data, $result);
    }

    public function testGetTimeStamp()
    {
        $expectedDate = '2024-01-01T12:34:56';
        $expectedTimestamp = strtotime($expectedDate);

        $dateTimeMock = $this->createMock(\DateTime::class);

        $this->timezoneMock->expects($this->once())
            ->method('formatDateTime')
            ->with(
                $dateTimeMock,
                null,
                null,
                null,
                null,
                'yyyy-MM-dd\'T\'HH:mm:ss'
            )
            ->willReturn($expectedDate);

        $this->timezoneMock->expects($this->once())
            ->method('date')
            ->willReturn($dateTimeMock);

        $result = $this->fclLogin->getTimeStamp();

        $this->assertEquals($expectedTimestamp, $result);
    }
}
