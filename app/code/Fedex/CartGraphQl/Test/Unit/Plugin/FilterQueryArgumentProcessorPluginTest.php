<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Plugin;

use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\LiveSearchAdapter\Model\QueryArgumentProcessor\FilterQueryArgumentProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Plugin\FilterQueryArgumentProcessorPlugin;

class FilterQueryArgumentProcessorPluginTest extends TestCase
{
    private FilterQueryArgumentProcessorPlugin $plugin;

    /**
     * @var InstoreConfig|MockObject
     */
    private $instoreConfigMock;

    protected function setUp(): void
    {
        $this->instoreConfigMock = $this->createMock(InstoreConfig::class);
        $this->plugin = new FilterQueryArgumentProcessorPlugin($this->instoreConfigMock);
    }

    public function testAfterGetQueryArgumentValueReturnsOriginalResultWhenLiveSearchDisabledAndNoCustomSharedCatalogId(): void
    {
        $searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $this->instoreConfigMock->expects($this->once())
            ->method('getLivesearchCustomSharedCatalogId')
            ->willReturn(null);
        $subjectMock = $this->createMock(FilterQueryArgumentProcessor::class);
        $result = [
            'attribute' => 'some_attribute',
            'eq' => 'same_value'
        ];

        $actualResult = $this->plugin->afterGetQueryArgumentValue($subjectMock, $result, $searchCriteriaMock);
        $this->assertSame($result, $actualResult);
    }

    public function testAfterGetQueryArgumentValueReturnsModifiedResultWhenLiveSearchEnabledAndCustomSharedCatalogIdExists(): void
    {
        $searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $sharedCatalogId = "123";
        $this->instoreConfigMock->expects($this->any())
            ->method('getLivesearchCustomSharedCatalogId')
            ->willReturn($sharedCatalogId);
        $subjectMock = $this->createMock(FilterQueryArgumentProcessor::class);
        $result = [['attribute' => 'some_attribute', 'eq' => 'same_value']];

        $actualResult = $this->plugin->afterGetQueryArgumentValue($subjectMock, $result, $searchCriteriaMock);

        $expectedResult = [
            [
                'attribute' => 'some_attribute',
                'eq' => 'same_value'
            ], [
                'attribute' => 'shared_catalogs',
                'eq' => $sharedCatalogId
            ]
        ];
        $this->assertSame($expectedResult, $actualResult);
    }
}
