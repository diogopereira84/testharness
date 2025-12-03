<?php
/**
 * @category  Fedex
 * @package   Fedex_Canva
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Plugin\Model\Page;

use Exception;
use Fedex\Canva\Model\Builder;
use Fedex\Canva\Model\SizeCollection;
use Fedex\Canva\Plugin\Model\Page\DataProviderPlugin;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Cms\Model\Page\DataProvider;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderPluginTest extends TestCase
{
    private DataProviderPlugin $dataProviderPlugin;
    private MockObject $builderMock;
    private MockObject $serializerMock;
    private MockObject $toggleConfigMock;

    protected function setUp(): void
    {
        $this->builderMock = $this->createMock(Builder::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);

        $this->dataProviderPlugin = new DataProviderPlugin(
            $this->builderMock,
            $this->serializerMock,
            $this->toggleConfigMock
        );
    }

    /**
     * @throws Exception
     */
    public function testAfterGetDataWithToggleConfigValueFalse(): void
    {
        $canvaSizeValue = '[{"id":"option_0","sort_order":0,"product_mapping_id":"TEST","display_width":"TEST","display_height":"test","orientation":"test","is_default":true}]';
        $canvaSizesUnserialized = json_decode($canvaSizeValue,true);
        $subjectMock = $this->createMock(DataProvider::class);
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tigers_b2185176_remove_adobe_commerce_overrides')
            ->willReturn(false);
        $result = $this->dataProviderPlugin->afterGetData($subjectMock, $canvaSizesUnserialized);
        $this->assertEquals(
            $canvaSizesUnserialized,
            $result
        );
    }

    /**
     * @throws Exception
     */
    public function testAfterGetDataWithToggleConfigValueTrueAndCanvaSizesIsString(): void
    {
        $subjectMock = $this->createMock(DataProvider::class);
        $canvaSizeValue = '[{"id":"option_0","sort_order":0,"product_mapping_id":"TEST","display_width":"TEST","display_height":"test","orientation":"test","is_default":true}]';
        $canvaSizesUnserialized = json_decode($canvaSizeValue,true);
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('tigers_b2185176_remove_adobe_commerce_overrides')
            ->willReturn(true);
        $this->serializerMock->method('unserialize')
            ->with($canvaSizeValue)
            ->willReturn($canvaSizesUnserialized);
        $sizeCollection = $this->getMockBuilder(SizeCollection::class)
            ->onlyMethods(['toArray', 'getDefaultOptionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sizeCollection->expects($this->any())->method('toArray')->willReturn([]);
        $this->builderMock->method('build')->with($canvaSizesUnserialized)->willReturn($sizeCollection);
        $response = $this->dataProviderPlugin->afterGetData($subjectMock, $canvaSizesUnserialized);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('canva_sizes', $response[0]);
        $this->assertArrayHasKey('default', $response[0]);
    }


}
