<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnvironmentManager\Test\Unit\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Customer\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Framework\Module\Status;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\ResponseFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ToggleConfigTest extends TestCase
{

    protected $_scopeConfigInterface;
    protected $_cache;
    protected $_serializer;
    protected $_typeListInterface;
    /**
     * @var (\Magento\Framework\App\Cache\Frontend\Pool & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $_pool;
    /**
     * @var (\Magento\Framework\App\ResponseFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $_responseFactory;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $_toggleConfig;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Status
     */
    protected $status;

    /**
     * @var TypeListInterface
     */
    protected $typeListInterface;

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->_scopeConfigInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(
                [
                    'getValue'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->_cache = $this->getMockBuilder(CacheInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMockForAbstractClass();

        $this->_serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize', 'unserialize'])
            ->getMockForAbstractClass();

        $this->_typeListInterface = $this->getMockBuilder(TypeListInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['cleanType'])
            ->getMockForAbstractClass();

        $this->_pool = $this->getMockBuilder(Pool::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIterator'])
            ->getMock();

        $this->_responseFactory = $this->getMockBuilder(ResponseFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'create',
                    'setRedirect',
                    'sendResponse',
                    'getResponse'
                ]
            )
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->_toggleConfig = $this->objectManager->getObject(
            ToggleConfig::class,
            [
                'scopeConfigInterface' => $this->_scopeConfigInterface,
                'cache' => $this->_cache,
                'serializer' => $this->_serializer,
                'cacheTypeList' => $this->_typeListInterface,
                'cacheFrontendPool' => $this->_pool,
            ]
        );
    }

    /**
     * Config Value test
     */
    public function testGetToggleConfig()
    {
        $mixedValue = 1;
        $this->_scopeConfigInterface->expects($this->any())
            ->method('getValue')
            ->willReturn($mixedValue);
        $this->assertEquals($mixedValue, $this->_toggleConfig->getToggleConfig("is_enable"));
    }

    /**
     * Save Toggle Config Cache
     */
    public function testSaveToggleConfigCache()
    {
        $this->_cache->expects($this->any())
            ->method('load')
            ->willReturn(""); // simulate empty cache
        $this->_serializer->expects($this->any())
            ->method('serialize')
            ->willReturn('serialized_data');
        $this->assertNull($this->_toggleConfig->saveToggleConfigCache());
    }

    /**
     * Save Toggle Config Cache with value already present
     */
    public function testSaveToggleConfigCacheWithValue()
    {
        $this->_cache->expects($this->any())
            ->method('load')
            ->willReturn('already_present');
        $this->assertNull($this->_toggleConfig->saveToggleConfigCache());
    }

    /**
     * Get Toggle Config Cache
     */
    public function testGetToggleConfigValue()
    {
        $this->_scopeConfigInterface->expects($this->any())
            ->method('getValue')
            ->willReturn(1);

        $this->_cache->expects($this->any())
            ->method('load')
            ->willReturn(""); // simulate empty cache

        $this->_serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn([]); // ensure unserialize returns array

        $this->assertEquals(1, $this->_toggleConfig->getToggleConfigValue("enable_fcl"));
    }

    /**
     * Get Toggle Config Cache with empty value
     */
    public function testGetToggleConfigValueWithEmptyValue()
    {
        $this->_scopeConfigInterface->expects($this->any())
            ->method('getValue')
            ->willReturn(null);

        $this->_cache->expects($this->any())
            ->method('load')
            ->willReturn(""); // simulate empty cache

        $this->_serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn([]); // ensure unserialize returns array

        $this->assertEquals(0, $this->_toggleConfig->getToggleConfigValue("enable_fcl"));
    }

    /**
     * Get Disable Enable Module
     */
    public function testDisableEnableModule()
    {
        $this->assertInstanceOf(ToggleConfig::class, $this->_toggleConfig->disableEnableModule('sso', true));
    }
}
