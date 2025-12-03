<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceProduct
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Setup\Patch\Data;

use Fedex\MarketplaceProduct\Setup\Patch\Data\UpdateWeightUnitAttributeOptions;
use Fedex\MarketplaceProduct\Setup\Patch\Data\CreateWeightUnitAttribute;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateWeightUnitAttributeOptionsTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ProductAttributeRepositoryInterface|MockObject
     */
    private $productAttributeRepository;

    /**
     * @var UpdateWeightUnitAttributeOptions
     */
    private $patch;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->productAttributeRepository = $this->createMock(ProductAttributeRepositoryInterface::class);

        $this->patch = new UpdateWeightUnitAttributeOptions(
            $this->logger,
            $this->productAttributeRepository
        );
    }

    /**
     * @return void
     */
    public function testInstanceOfDataPatchInterface(): void
    {
        $this->assertInstanceOf(DataPatchInterface::class, $this->patch);
    }

    /**
     * @return void
     */
    public function testApplyMethod(): void
    {
        $attribute = $this->createMock(\Magento\Catalog\Api\Data\ProductAttributeInterface::class);
        $attribute->expects($this->once())->method('getOptions')->willReturn([
            $this->createOption('ounce'),
            $this->createOption('pound'),
            $this->createOption('invalid_option')
        ]);
        $attribute->expects($this->once())->method('setOptions');

        $this->productAttributeRepository->expects($this->once())
            ->method('get')->with('weight_unit')
            ->willReturn($attribute);

        $this->productAttributeRepository->expects($this->once())
            ->method('save')->with($attribute);

        $this->logger->expects($this->never())->method('error');

        $this->patch->apply();
    }

    /**
     * @param $label
     * @return AttributeOptionInterface|MockObject
     */
    private function createOption($label): AttributeOptionInterface|MockObject
    {
        $option = $this->createMock(AttributeOptionInterface::class);
        $option->expects($this->any())->method('getLabel')->willReturn($label);
        $option->expects($this->any())->method('setLabel')->willReturnSelf();

        return $option;
    }

    /**
     * @return void
     */
    public function testGetAliasesMethod(): void
    {
        $this->assertSame([], $this->patch->getAliases());
    }

    /**
     * @return void
     */
    public function testGetDependenciesMethod(): void
    {
        $this->assertSame([CreateWeightUnitAttribute::class], $this->patch->getDependencies());
    }
}
