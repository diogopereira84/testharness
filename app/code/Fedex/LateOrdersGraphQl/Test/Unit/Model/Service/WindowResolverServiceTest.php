<?php

declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Service;

use Fedex\LateOrdersGraphQl\Model\Config;
use Fedex\LateOrdersGraphQl\Model\Data\TimeWindowDTO;
use Fedex\LateOrdersGraphQl\Model\Service\WindowResolverService;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WindowResolverServiceTest extends TestCase
{
    /** @var Config|MockObject */
    private $config;
    /** @var \Magento\Framework\Stdlib\DateTime\Timezone|MockObject */
    private $timezone;
    /** @var WindowResolverService */
    private $service;
    private $fixedNow;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->timezone = $this->createMock(\Magento\Framework\Stdlib\DateTime\Timezone::class);
        $this->fixedNow = new \DateTimeImmutable('2025-10-22T12:00:00Z');
        $this->timezone->method('date')->willReturn($this->fixedNow);
        $this->service = new WindowResolverService($this->config, $this->timezone);
    }

    public function testThrowsIfBothSinceAndUntilNull(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(1);
        $this->expectException(LocalizedException::class);
        $this->service->resolveAndCapWindow(null, null);
    }

    public function testThrowsIfSinceInvalidFormat(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(1);
        $this->expectException(\Magento\Framework\GraphQl\Exception\GraphQlInputException::class);
        $this->service->resolveAndCapWindow('notadate', null);
    }

    public function testThrowsIfUntilInvalidFormat(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(1);
        $this->expectException(\Magento\Framework\GraphQl\Exception\GraphQlInputException::class);
        $this->service->resolveAndCapWindow(null, 'notadate');
    }

    public function testUntilCappedToNowIfInFuture(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(2);
        $now = $this->fixedNow;
        $future = $now->add(new \DateInterval('PT1H'));
        $dto = $this->service->resolveAndCapWindow(null, $future->format('Y-m-d\TH:i:s\Z'));
        $expectedSince = $now->sub(new \DateInterval('PT2H'));
        $this->assertEquals($expectedSince->getTimestamp(), $dto->since->getTimestamp());
        $this->assertEquals($now->getTimestamp(), $dto->until->getTimestamp());
    }

    public function testSinceAndUntilValidWithinWindow(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(2);
        $now = $this->fixedNow;
        $since = $now->sub(new \DateInterval('PT1H'));
        $dto = $this->service->resolveAndCapWindow($since->format('Y-m-d\\TH:i:s\\Z'), $now->format('Y-m-d\\TH:i:s\\Z'));
        $this->assertInstanceOf(TimeWindowDTO::class, $dto);
        $this->assertEquals($since->getTimestamp(), $dto->since->getTimestamp());
        $this->assertEquals($now->getTimestamp(), $dto->until->getTimestamp());
    }

    public function testSinceAndUntilWindowTooLarge(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(1);
        $now = $this->fixedNow;
        $since = $now->sub(new \DateInterval('PT2H'));
        $this->expectException(LocalizedException::class);
        $this->service->resolveAndCapWindow($since->format('Y-m-d\\TH:i:s\\Z'), $now->format('Y-m-d\\TH:i:s\\Z'));
    }

    public function testSinceAndUntilUntilBeforeSince(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(2);
        $now = $this->fixedNow;
        $since = $now;
        $until = $now->sub(new \DateInterval('PT1H'));
        $this->expectException(LocalizedException::class);
        $this->service->resolveAndCapWindow($since->format('Y-m-d\\TH:i:s\\Z'), $until->format('Y-m-d\\TH:i:s\\Z'));
    }

    public function testSinceOnlyWithinWindow(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(2);
        $now = $this->fixedNow;
        $since = $now->sub(new \DateInterval('PT1H'));
        $dto = $this->service->resolveAndCapWindow($since->format('Y-m-d\\TH:i:s\\Z'), null);
        $this->assertInstanceOf(TimeWindowDTO::class, $dto);
        $this->assertEquals($since->getTimestamp(), $dto->since->getTimestamp());
        $this->assertEquals($now->getTimestamp(), $dto->until->getTimestamp());
    }

    public function testSinceOnlyWindowTooLarge(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(1);
        $now = $this->fixedNow;
        $since = $now->sub(new \DateInterval('PT2H'));
        $this->expectException(LocalizedException::class);
        $this->service->resolveAndCapWindow($since->format('Y-m-d\\TH:i:s\\Z'), null);
    }

    public function testUntilOnlyReturnsWindow(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(2);
        $now = $this->fixedNow;
        $until = $now;
        $since = $until->sub(new \DateInterval('PT2H'));
        $dto = $this->service->resolveAndCapWindow(null, $until->format('Y-m-d\\TH:i:s\\Z'));
        $this->assertInstanceOf(TimeWindowDTO::class, $dto);
        $this->assertEquals($since->getTimestamp(), $dto->since->getTimestamp());
        $this->assertEquals($until->getTimestamp(), $dto->until->getTimestamp());
    }

    public function testWindowExactlyAtMaxHours(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(2);
        $now = $this->fixedNow;
        $since = $now->sub(new \DateInterval('PT2H'));
        $dto = $this->service->resolveAndCapWindow($since->format('Y-m-d\\TH:i:s\\Z'), $now->format('Y-m-d\\TH:i:s\\Z'));
        $this->assertInstanceOf(TimeWindowDTO::class, $dto);
        $this->assertEquals($since->getTimestamp(), $dto->since->getTimestamp());
        $this->assertEquals($now->getTimestamp(), $dto->until->getTimestamp());
    }

    public function testWindowJustOverMaxHours(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(2);
        $now = $this->fixedNow;
        $since = $now->sub(new \DateInterval('PT2H1S'));
        $this->expectException(LocalizedException::class);
        $this->service->resolveAndCapWindow($since->format('Y-m-d\\TH:i:s\\Z'), $now->format('Y-m-d\\TH:i:s\\Z'));
    }

    public function testWindowJustUnderMaxHours(): void
    {
        $this->config->method('getLateOrderQueryWindowHours')->willReturn(2);
        $now = $this->fixedNow;
        $since = $now->sub(new \DateInterval('PT1H59M59S'));
        $dto = $this->service->resolveAndCapWindow($since->format('Y-m-d\\TH:i:s\\Z'), $now->format('Y-m-d\\TH:i:s\\Z'));
        $this->assertInstanceOf(TimeWindowDTO::class, $dto);
        $this->assertEquals($since->getTimestamp(), $dto->since->getTimestamp());
        $this->assertEquals($now->getTimestamp(), $dto->until->getTimestamp());
    }
}

