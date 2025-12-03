<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Model;

use Fedex\Canva\Model\Builder;
use Fedex\Canva\Model\Size;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Canva\Model\SizeCollection;
use Fedex\Canva\Api\Data\SizeCollectionInterfaceFactory;
use Fedex\Canva\Api\Data\SizeInterfaceFactory;
use Psr\Log\LoggerInterface;

class BuilderTest extends TestCase
{
    /**
     * @param array $data
     * @dataProvider buildDataProvider
     */
    public function testBuild(array $data)
    {
        $size = (new ObjectManager($this))->getObject(Size::class);
        $size->setData($data);
        $sizeFactoryMock = $this->getMockBuilder(SizeInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sizeFactoryMock->expects($this->any())->method('create')
            ->willReturn($size);
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sizeCollectionFactoryMock = $this->getMockBuilder(SizeCollectionInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sizeCollection = (new ObjectManager($this))->getObject(SizeCollection::class, [
            'sizeFactory' => $sizeFactoryMock,
            'serializer' => (new ObjectManager($this))->getObject(Json::class),
            'logger' => $loggerMock
        ]);
        $sizeCollectionFactoryMock->expects($this->once())->method('create')
            ->willReturn($sizeCollection);
        $builder = new Builder($sizeFactoryMock, $sizeCollectionFactoryMock);
        $collection = $builder->build([$data]);
        $this->assertEquals([$data], $collection->toArray());
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function buildDataProvider(): array
    {
        return [
            [
                [
                    Size::RECORD_ID => '01',
                    Size::DEFAULT => Size::DEFAULT_VALUE_FALSE,
                    Size::PRODUCT_MAPPING_ID => 'TESTING01',
                    Size::DISPLAY_WIDTH => '7"',
                    Size::DISPLAY_HEIGHT => '5"',
                    Size::ORIENTATION => 'Portrait',
                    Size::POSITION => '1',
                    Size::INITIALIZE => 'true',
                ]
            ]
        ];
    }
}
