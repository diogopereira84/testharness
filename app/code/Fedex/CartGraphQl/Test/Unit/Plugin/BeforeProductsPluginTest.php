<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Plugin;

use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Plugin\BeforeProductsPlugin;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Magento\CatalogGraphQl\Model\Resolver\Products;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class BeforeProductsPluginTest extends TestCase
{
    private LoggerHelper&MockObject $loggerHelper;
    private NewRelicHeaders&MockObject $newRelicHeaders;
    private BeforeProductsPlugin $plugin;

    protected function setUp(): void
    {
        $this->loggerHelper = $this->createMock(LoggerHelper::class);
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);

        $this->plugin = new BeforeProductsPlugin(
            $this->loggerHelper,
            $this->newRelicHeaders
        );
    }

    public function testBeforeResolveLogsStartAndReturnsArguments(): void
    {
        $subject = $this->createMock(Products::class);
        $field = $this->createMock(Field::class);
        $context = ['user_id' => 42];
        $info = $this->createMock(ResolveInfo::class);
        $value = ['some' => 'value'];
        $args = ['arg1' => 'val1'];

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
                $this->stringContains('Magento graphQL start:'),
                $headerArray
            );

        $result = $this->plugin->beforeResolve($subject, $field, $context, $info, $value, $args);
        $this->assertSame([$field, $context, $info, $value, $args], $result);
    }
}
