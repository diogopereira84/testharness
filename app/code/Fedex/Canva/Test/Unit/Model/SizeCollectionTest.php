<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Model;

use Fedex\Canva\Model\Exception\DuplicatedCollectionItem;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\Canva\Api\Data\SizeInterfaceFactory;
use Fedex\Canva\Model\Size;
use Fedex\Canva\Model\SizeCollection;
use Psr\Log\LoggerInterface;

class SizeCollectionTest extends TestCase
{
    protected SizeInterfaceFactory|MockObject $sizeFactoryMock;
    protected Json|MockObject $jsonMock;
    protected array $sizesArray = [];
    protected SizeCollection|MockObject $sizesCollection;
    protected LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->sizeFactoryMock = $this->getMockBuilder(SizeInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sizesArray = [];

        $this->sizeFactoryMock->method('create')
            ->willReturn($this->createMock(Size::class));
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sizesCollection = new SizeCollection($this->sizeFactoryMock, $this->jsonMock, $this->loggerMock);
    }

    public function testToArray(): void
    {
        foreach (range(0, 10) as $number) {
            $size = [
                Size::RECORD_ID => (string)$number,
                Size::DEFAULT => 'false',
                Size::PRODUCT_MAPPING_ID => "poster_{$number}",
                Size::DISPLAY_WIDTH => (string)$number,
                Size::DISPLAY_HEIGHT => (string)$number,
                Size::ORIENTATION => 'Landscape',
            ];
            $this->sizesArray[] = $size;
            $this->sizesCollection->addItem(new Size($size));
        }
        $this->assertEquals($this->sizesArray, $this->sizesCollection->toArray());
    }

    public function testCount(): void
    {
        foreach (range(0, 99) as $number) {
            $this->sizesCollection->addItem(new Size([
                Size::RECORD_ID => (string)$number,
                Size::POSITION => (string)$number,
            ]));
        }
        $this->assertEquals(100, $this->sizesCollection->count());
    }

    public function testToJson(): void
    {
        $json = '[{"record_id":"0","position":"0"},{"record_id":"1","position":"1"},{"record_id":"2","position":"2"}]';
        foreach (range(0, 2) as $number) {
            $this->sizesCollection->addItem(new Size([
                Size::RECORD_ID => (string)$number,
                Size::POSITION => (string)$number,
            ]));
        }
        $this->jsonMock->expects($this->once())
            ->method('serialize')
            ->willReturn($json);
        $this->assertEquals($json, $this->sizesCollection->toJson());
    }

    public function testGetDefault(): void
    {
        $defaultId = 6;
        foreach (range(0, 10) as $number) {
            $this->sizesCollection->addItem(new Size([
                Size::RECORD_ID => (string)$number,
                Size::DEFAULT => ($number == $defaultId) ? Size::DEFAULT_VALUE_TRUE : Size::DEFAULT_VALUE_FALSE,
                Size::POSITION => (string)$number,
            ]));
        }
        $this->assertEquals($defaultId, $this->sizesCollection->getDefault()->getRecordId());
    }

    public function testGetDefaultOptionId(): void
    {
        $defaultId = 6;
        $this->sizesCollection->addItem(new Size([
            Size::RECORD_ID => (string)$defaultId,
            Size::DEFAULT => Size::DEFAULT_VALUE_TRUE,
            Size::POSITION => (string)$defaultId,
        ]));
        $this->assertEquals(Size::DEFAULT_PREFIX . $defaultId, $this->sizesCollection->getDefaultOptionId());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testGetDefaultOptionFromCreate(): void
    {
        $defaultId = '';
        $this->sizesCollection->addItem(new Size([]));
        $this->assertEquals(Size::DEFAULT_PREFIX . $defaultId, $this->sizesCollection->getDefaultOptionId());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testSetDefaultOption(): void
    {
        $defaultId = 1;
        foreach (range(0, 10) as $number) {
            $this->sizesCollection->addItem(new Size([
                Size::RECORD_ID => (string)$number,
                Size::POSITION => (string)$number,
            ]));
        }
        $this->sizesCollection->setDefaultOption($defaultId);
        $this->assertEquals($defaultId, $this->sizesCollection->getDefault()->getRecordId());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testAddItem()
    {
        foreach (range(0, 10) as $number) {
            $size = [ Size::RECORD_ID => (string)$number ];
            $this->sizesArray[] = $size;
            $this->sizesCollection->addItem(new Size($size));
        }
        $this->assertEquals($this->sizesArray, $this->sizesCollection->toArray());
        $this->assertEquals(count($this->sizesArray), $this->sizesCollection->count());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testAddItemPresentInItems()
    {
        $this->expectException(DuplicatedCollectionItem::class);
        $this->expectExceptionMessage('Item (' . Size::class . ') with the same ID "1" already exists.');
        foreach (range(0, 10) as $number) {
            $size = [ Size::RECORD_ID => (string)$number ];
            $this->sizesArray[] = $size;
            $this->sizesCollection->addItem(new Size($size));
        }
        $logInfo = 'Fedex\Canva\Model\SizeCollection::addItem:158 Item (' . Size::class . ') with the same ID "1" already exists.';
        $this->loggerMock->expects($this->once())->method('info')->with($logInfo);

        $size = [ 'id' => (string)1 ];
        $this->sizesCollection->addItem(new Size($size));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testAddItemNotPresentInItems()
    {
        foreach (range(2, 10) as $number) {
            $size = [ 'id' => (string)$number ,Size::RECORD_ID => (string)$number ];
            $this->sizesArray[] = $size;
            $this->sizesCollection->addItem(new Size($size));
        }

        $size = [ 'id' => (string)1 ];
        $this->sizesCollection->addItem(new Size($size));
        $this->assertNotEquals(count($this->sizesArray), $this->sizesCollection->count());
    }
}
