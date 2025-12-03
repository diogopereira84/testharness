<?php
/**
 * @category     Fedex
 * @package      Fedex_FujitsuCore
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\FujitsuCore\Test\Unit\Model;

use Fedex\FujitsuCore\Model\DataObjectAdapter;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class DataObjectAdapterTest extends TestCase
{
    /**
     * @var DataObjectAdapter
     */
    private DataObjectAdapter $instance;

    protected function setUp(): void
    {
        $this->instance = new DataObjectAdapter();
    }

    public function testCallGetAndSetMethod(): void
    {
        $methodGet = "getTestData";
        $methodSet = "setTestData";
        $value = 'test-value';
        $data = [$value];
        $this->instance->__call($methodSet, $data);
        static::assertSame($value, $this->instance->__call($methodGet, ['testData' => $value]));
    }

    public function testCallUnsetMethod(): void
    {
        $method = "unsTestData";
        $value = 'test-value';
        $data = ['testData' => $value];
        static::assertInstanceOf(DataObjectAdapter::class, $this->instance->__call($method, $data));
    }

    public function testCallHasMethod(): void
    {
        $method = "hasTestData";
        $value = 'test-value';
        $data = ['testData' => $value];
        $dataObject = new DataObjectAdapter($data);
        static::assertSame(true, $dataObject->__call($method, $data));
    }

    public function testCallWithException(): void
    {
        $method = "subTestData";
        $value = 'test-value';
        $data = ['testData' => $value];
        $this->expectException(LocalizedException::class);
        $this->instance->__call($method, $data);
    }

    public function testConvertAllToArray()
    {
        $this->instance->setData('value', 'test');
        $this->assertEquals(['value' => 'test'], $this->instance->convertAllToArray());
    }
}
