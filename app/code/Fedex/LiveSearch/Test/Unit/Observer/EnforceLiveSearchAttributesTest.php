<?php

namespace Fedex\LiveSearch\Test\Unit\Observer;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\LiveSearch\Observer\EnforceLiveSearchAttributes;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EnforceLiveSearchAttributesTest extends TestCase
{
    private $toggleConfig;
    private $eavSetup;
    private $logger;
    private $attributeSetRepository;
    private $attributeRepository;
    private $observer;
    private $product;
    private $enforceLiveSearchAttributes;

    protected function setUp(): void
    {
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->eavSetup = $this->createMock(EavSetup::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->attributeSetRepository = $this->createMock(AttributeSetRepositoryInterface::class);
        $this->attributeRepository = $this->createMock(AttributeRepositoryInterface::class);
        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProduct'])
            ->getMock();
        $this->product = $this->createMock(Product::class);

        $this->enforceLiveSearchAttributes = new EnforceLiveSearchAttributes(
            $this->toggleConfig,
            $this->eavSetup,
            $this->logger,
            $this->attributeSetRepository,
            $this->attributeRepository
        );
    }

    public function testExecuteWithToggleEnabledAndFxOPrintProductsAttributeSet()
    {
        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->with(EnforceLiveSearchAttributes::XML_PATH_TOGGLE)
            ->willReturn(true);

        $this->observer
            ->method('getProduct')
            ->willReturn($this->product);

        $this->product
            ->method('getAttributeSetId')
            ->willReturn(1);

        $this->eavSetup
            ->method('getAttributeSetId')
            ->withConsecutive(
                [Product::ENTITY, EnforceLiveSearchAttributes::UPLOAD_FILE_ATTRIBUTE_SET_NAME],
                [Product::ENTITY, EnforceLiveSearchAttributes::CUSTOMIZE_ATTRIBUTE_SET_NAME]
            )
            ->willReturnOnConsecutiveCalls(1, 2);

        $this->product
            ->expects($this->once())
            ->method('setData')
            ->with(EnforceLiveSearchAttributes::UPLOAD_FILE_ATTRIBUTE_NAME, 1);

        $this->enforceLiveSearchAttributes->execute($this->observer);
    }

    public function testExecuteWithToggleEnabledAndPrintOnDemandAttributeSet()
    {
        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->with(EnforceLiveSearchAttributes::XML_PATH_TOGGLE)
            ->willReturn(true);

        $this->observer
            ->method('getProduct')
            ->willReturn($this->product);

        $this->product
            ->method('getAttributeSetId')
            ->willReturn(2);

        $this->eavSetup
            ->method('getAttributeSetId')
            ->withConsecutive(
                [Product::ENTITY, EnforceLiveSearchAttributes::UPLOAD_FILE_ATTRIBUTE_SET_NAME],
                [Product::ENTITY, EnforceLiveSearchAttributes::CUSTOMIZE_ATTRIBUTE_SET_NAME]
            )
            ->willReturnOnConsecutiveCalls(1, 2);

        $this->product
            ->expects($this->once())
            ->method('setData')
            ->with(EnforceLiveSearchAttributes::CUSTOMIZE_ATTRIBUTE_NAME, 1);

        $this->enforceLiveSearchAttributes->execute($this->observer);
    }

    public function testExecuteWithToggleEnabledAndOtherAttributeSet()
    {
        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->with(EnforceLiveSearchAttributes::XML_PATH_TOGGLE)
            ->willReturn(true);

        $this->observer
            ->method('getProduct')
            ->willReturn($this->product);

        $this->product
            ->method('getAttributeSetId')
            ->willReturn(3);

        $this->eavSetup
            ->method('getAttributeSetId')
            ->withConsecutive(
                [Product::ENTITY, EnforceLiveSearchAttributes::UPLOAD_FILE_ATTRIBUTE_SET_NAME],
                [Product::ENTITY, EnforceLiveSearchAttributes::CUSTOMIZE_ATTRIBUTE_SET_NAME]
            )
            ->willReturnOnConsecutiveCalls(1, 2);

        $this->product
            ->expects($this->never())
            ->method('setData');

        $this->enforceLiveSearchAttributes->execute($this->observer);
    }

    public function testExecuteWithToggleDisabled()
    {
        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->with(EnforceLiveSearchAttributes::XML_PATH_TOGGLE)
            ->willReturn(false);

        $this->observer
            ->expects($this->never())
            ->method('getProduct');

        $this->enforceLiveSearchAttributes->execute($this->observer);
    }

    public function testExecuteWithException()
    {
        $this->toggleConfig
            ->method('getToggleConfigValue')
            ->with(EnforceLiveSearchAttributes::XML_PATH_TOGGLE)
            ->willReturn(true);

        $this->observer
            ->method('getProduct')
            ->willReturn($this->product);

        $this->product
            ->method('getAttributeSetId')
            ->willThrowException(new \Exception('Test exception'));

        $this->logger
            ->expects($this->once())
            ->method('alert')
            ->with($this->stringContains('Test exception'));

        $this->enforceLiveSearchAttributes->execute($this->observer);
    }
}
