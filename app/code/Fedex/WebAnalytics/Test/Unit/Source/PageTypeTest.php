<?php

namespace Fedex\WebAnalytics\Test\Unit\Model\Source;

use Fedex\WebAnalytics\Model\Source\PageType;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Data\OptionSourceInterface;
use Fedex\WebAnalytics\Api\Data\GDLConfigInterface;
use PHPUnit\Framework\TestCase;

class PageTypeTest extends TestCase
{
    protected $objectManager;
    /**
     * @var $objectManager Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */

    protected $model;
    /**
     * Block model
     *
     * @var Object
     */
    protected function setUp(): void
    {
        $jsonMock = $this->createMock(Json::class);
        $jsonValidatorMock = $this->createMock(JsonValidator::class);
        $gdlConfigInterfaceMock = $this->createMock(GDLConfigInterface::class);
        $optionSourceMock = $this->getMockBuilder(OptionSourceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $this->objectManager->getObject(PageType::class);
    }

    /**
     * Test for toOptionArray method.
     *
     * @return array
     */
    public function testToOptionArray()
    {
        $jsonConfigValue = '{"_1686865595148_148":{"label":"Product Page","value":"productpage"}}';
        $arrayConfig = [
            '0' => [
                'label' => 'Product Page',
                'value' => 'productpage',
            ]
        ];

        $gdlConfig = $this->createMock(GDLConfigInterface::class);
        $gdlConfig->expects($this->once())->method('getPageTypes')->willReturn($jsonConfigValue);

        $jsonValidator = $this->createMock(JsonValidator::class);
        $jsonValidator->expects($this->once())->method('isValid')->with($jsonConfigValue)
            ->willReturn(true);

        $json = $this->createMock(Json::class);
        $json->expects($this->once())->method('unserialize')->with($jsonConfigValue)
            ->willReturn($arrayConfig);

        $pageType = new PageType($gdlConfig, $jsonValidator, $json);
        $this->assertEquals($arrayConfig, $pageType->toOptionArray());
    }
}
