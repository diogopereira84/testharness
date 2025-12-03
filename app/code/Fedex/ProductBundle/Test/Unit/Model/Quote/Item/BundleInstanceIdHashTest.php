<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Model\Quote\Item;

use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\ProductBundle\Model\Quote\Item\BundleInstanceIdHash;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

class BundleInstanceIdHashTest extends TestCase
{
    private $serializer;
    private $request;
    private $config;
    private $comparator;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(Json::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->comparator = new BundleInstanceIdHash(
            $this->serializer,
            $this->request,
            $this->config
        );
    }

    public function testCompareReturnsTrueWhenToggleDisabled()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(false);
        $option1 = new DataObject(['value' => 'foo']);
        $option2 = new DataObject(['value' => 'bar']);
        $result = $this->comparator->compare($option1, $option2);
        $this->assertTrue($result);
    }

    public function testCompareReturnsTrueWhenValuesAreEqualAndToggleEnabled()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $option1 = new DataObject(['value' => 'same']);
        $option2 = new DataObject(['value' => 'same']);
        $result = $this->comparator->compare($option1, $option2);
        $this->assertTrue($result);
    }

    public function testCompareReturnsFalseWhenValuesAreDifferentAndToggleEnabled()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $option1 = new DataObject(['value' => 'foo']);
        $option2 = new DataObject(['value' => 'bar']);
        $result = $this->comparator->compare($option1, $option2);
        $this->assertFalse($result);
    }

    public function testCompareHandlesNullValues()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $option1 = new DataObject(['value' => null]);
        $option2 = new DataObject(['value' => null]);
        $result = $this->comparator->compare($option1, $option2);
        $this->assertTrue($result);
    }

    public function testCompareHandlesMissingValueKeys()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $option1 = new DataObject([]);
        $option2 = new DataObject([]);
        $result = $this->comparator->compare($option1, $option2);
        $this->assertTrue($result);
    }

    public function testCompareHandlesOneNullOneValue()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $option1 = new DataObject(['value' => null]);
        $option2 = new DataObject(['value' => 'bar']);
        $result = $this->comparator->compare($option1, $option2);
        $this->assertFalse($result);
    }
}

