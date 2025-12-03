<?php
declare(strict_types=1);
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Plugin\ProductMainQueryPlugin;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Sql\ExpressionFactory;

class ProductMainQueryPluginTest extends TestCase
{
    private $loggerMock;
    private $toggleConfigMock;
    private $plugin;
    private $selectMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPart', 'setPart', 'assemble'])
            ->getMock();

        $this->plugin = new ProductMainQueryPlugin(
            $this->loggerMock,
            $this->toggleConfigMock
        );
    }

    public function testAfterGetQueryWithToggleEnabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('tech_titans_e_484727')
            ->willReturn(true);

        $columns = [
            ['main_table', 'sku', 'sku'],
            ['main_table', 'entity_id', 'productId'],
            ['main_table', 'type_id', 'type'],
        ];

        $this->selectMock->expects($this->once())
            ->method('getPart')
            ->with(\Zend_Db_Select::COLUMNS)
            ->willReturn($columns);

        $this->selectMock->expects($this->once())
            ->method('setPart')
            ->with(
                \Zend_Db_Select::COLUMNS,
                $this->callback(function ($modifiedColumns) {
                    foreach ($modifiedColumns as $column) {
                        if ($column[2] === 'type') {
                            return (string)$column[1] === "CASE WHEN main_table.type_id = 'commercial' THEN 'simple' ELSE main_table.type_id END";
                        }
                    }
                    return false;
                })
            );
        $this->selectMock->expects($this->never())->method('assemble');

        $result = $this->plugin->afterGetQuery(
            $this->createMock(\Magento\CatalogDataExporter\Model\Query\ProductMainQuery::class),
            $this->selectMock
        );

        $this->assertSame($this->selectMock, $result);
    }


    public function testAfterGetQueryWithToggleDisabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('tech_titans_e_484727')
            ->willReturn(false);

        $this->selectMock->expects($this->never())->method('getPart');
        $this->selectMock->expects($this->never())->method('setPart');
        $this->selectMock->expects($this->never())->method('assemble');

        $result = $this->plugin->afterGetQuery(
            $this->createMock(\Magento\CatalogDataExporter\Model\Query\ProductMainQuery::class),
            $this->selectMock
        );

        $this->assertSame($this->selectMock, $result);
    }
}