<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Plugin;

use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Plugin\AfterProductsPlugin;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Magento\CatalogGraphQl\Model\Resolver\Products;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AfterProductsPluginTest extends TestCase
{
    private LoggerHelper&MockObject $loggerHelper;
    private NewRelicHeaders&MockObject $newRelicHeaders;
    private AfterProductsPlugin $plugin;

    protected function setUp(): void
    {
        $this->loggerHelper = $this->createMock(LoggerHelper::class);
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);

        $this->plugin = new AfterProductsPlugin(
            $this->loggerHelper,
            $this->newRelicHeaders
        );
    }

    public function testAfterResolveLogsHeadersAndReturnsResult(): void
    {
        $subject = $this->createMock(Products::class);
        $field = $this->createMock(Field::class);
        $info = $this->createMock(ResolveInfo::class);
        $context = ['context' => 'data'];
        $value = ['some' => 'value'];
        $args = ['arg1' => 'val1'];
        $result = ['items' => ['product1', 'product2']];

        $mutationName = 'products';
        $headerArray = ['x-newrelic-id' => 'abc123'];

        $field->expects($this->once())
            ->method('getName')
            ->willReturn($mutationName);

        $this->newRelicHeaders->expects($this->once())
            ->method('getHeadersForMutation')
            ->with($mutationName)
            ->willReturn($headerArray);

        $this->loggerHelper->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Magento graphQL end:'),
                $headerArray
            );

        $output = $this->plugin->afterResolve($subject, $result, $field, $context, $info, $value, $args);
        $this->assertSame($result, $output);
    }
}
