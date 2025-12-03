<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Config\Backend;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\UploadToQuote\Config\Backend\ArraySerialized;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use PHPUnit\Framework\TestCase;

/**
 * ArraySerialized unit test class
 */
class ArraySerializedTest extends TestCase
{
    protected $arraySerialized;
    /**
     * @var ObjectManager $objectManager
     */
    private $objectManagerHelper;

    /**
     * @var Context $context
     */
    private $context;

    /**
     * @var Registry $registry
     */
    private $registry;

    /**
     * @var TypeListInterface $cacheTypeList
     */

    private $cacheTypeList;

    /**
     * @var AbstractDb $resourceCollection
     */
    private $resourceCollection;

    /**
     * @var AbstractResource $resource
     */
    private $resource;

    /**
     * @var SerializerInterface $serializer
     */
    private $serializer;

    /**
     * @var ScopeConfigInterface $scopeConfigMock
     */
    private $scopeConfigMock;

    /**
     * @var ConfigValue $configValue
     */
    private $configValue;

    /**
     * Set up method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheTypeList = $this->getMockBuilder(TypeListInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceCollection = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resource = $this->getMockBuilder(AbstractResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize', 'unserialize'])
            ->getMockForAbstractClass();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();
            
        $this->configValue = $this->getMockBuilder(ConfigValue::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->arraySerialized = $this->objectManagerHelper->getObject(
            ArraySerialized::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'serializer' => $this->serializer,
                'resourceCollection' => $this->resourceCollection,
                'resource' => $this->resource,
                'registry' => $this->registry,
                'context' => $this->context,
                'cacheTypeList' => $this->cacheTypeList,
                'data' => []
            ]
        );
    }

    /**
     * Test testBeforeSave method
     *
     * @return void
     */
    public function testBeforeSave()
    {
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturnSelf();

        $this->assertNull($this->arraySerialized->beforeSave());
    }

    /**
     * Test testAfterLoad method
     *
     * @return void
     */
    public function testAfterLoad()
    {
        $reflection = new \ReflectionClass(ArraySerialized::class);
        $getAfterLoad = $reflection->getMethod('_afterLoad');
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturnSelf();
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturn('123');
        $expectedResult = $getAfterLoad->invoke($this->arraySerialized);

        $this->assertNull($expectedResult);
    }
}
