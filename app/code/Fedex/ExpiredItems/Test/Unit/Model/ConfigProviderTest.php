<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\ExpiredItems\Model\ConfigProvider;

/**
 * Test class ConfigProviderTest
 */
class ConfigProviderTest extends TestCase
{
    protected $configProviderMock;
    private const XML_PATH_TEST_DATA = 'ABC';

    /**
     * @var ScopeConfigInterface $scopeConfigMock
     */
    private $scopeConfigMock;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSetFlag','getValue'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->configProviderMock = $objectManagerHelper->getObject(
            ConfigProvider::class,
            [
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    /**
     * Test method for get expiry time
     *
     * @return void
     */
    public function testGetExpiryTime()
    {
        $expectedResult = 14;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(14);

        $this->assertEquals($expectedResult, $this->configProviderMock->getExpiryTime());
    }

    /**
     * Test getExpiryThresholdTime
     *
     * @return void
     */
    public function testGetExpiryThresholdTime()
    {
        $expectedResult = 14;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(14);

        $this->assertEquals($expectedResult, $this->configProviderMock->getExpiryThresholdTime());
    }

    /**
     * Test method for get expiry title
     *
     * @return void
     */
    public function testGetCartExpiryTitle()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartExpiryTitle());
    }

    /**
     * Test method for get expiry message
     *
     * @return void
     */
    public function testGetCartExpiryMessage()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartExpiryMessage());
    }

    /**
     * Test method for get expired title
     *
     * @return void
     */
    public function testGetCartExpiredTitle()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartExpiredTitle());
    }

    /**
     * Test method for get expired message
     *
     * @return void
     */
    public function testGetCartExpiredMessage()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartExpiredMessage());
    }

    /**
     * Test method for get cart item expiry title
     *
     * @return void
     */
    public function testGetCartItemExpiryTitle()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartItemExpiryTitle());
    }

    /**
     * Test method for get cart item expiry message
     *
     * @return void
     */
    public function testGetCartItemExpiryMessage()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartItemExpiryMessage());
    }

    /**
     * Test method for get cart item expired title
     *
     * @return void
     */
    public function testGetCartItemExpiredTitle()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartItemExpiredTitle());
    }

    /**
     * Test method for get cart item expired message
     *
     * @return void
     */
    public function testGetCartItemExpiredMessage()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartItemExpiredMessage());
    }

    /**
     * Test method for get cart item expiry third party product title
     *
     * @return void
     */
    public function testGetCartItemExpiry3pTitle()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartItemExpiry3pTitle());
    }

    /**
     * Test method for get cart item expiry third party product message
     *
     * @return void
     */
    public function testGetCartItemExpiry3pMessage()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartItemExpiry3pMessage());
    }

    /**
     * Test method for get cart item expired third party product title
     *
     * @return void
     */
    public function testGetCartItemExpired3pTitle()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartItemExpired3pTitle());
    }

    /**
     * Test method for get cart item expired third party product message
     *
     * @return void
     */
    public function testGetCartItemExpired3pMessage()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getCartItemExpired3pMessage());
    }




    /**
     * Test method for get minicart expiry message
     *
     * @return void
     */
    public function testGetMiniCartExpiryMessage()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getMiniCartExpiryMessage());
    }

    /**
     * Test method for get minicart expired message
     *
     * @return void
     */
    public function testGetMiniCartExpiredMessage()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getMiniCartExpiredMessage());
    }

    /**
     * Test method for get popup expiry title
     *
     * @return void
     */
    public function testGetPopUpExpiryTitle()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getPopUpExpiryTitle());
    }

    /**
     * Test method for get popup expiry message
     *
     * @return void
     */
    public function testGetPopUpExpiryMessage()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getPopUpExpiryMessage());
    }

    /**
     * Test method for get popup expired title
     *
     * @return void
     */
    public function testGetPopUpExpiredTitle()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getPopUpExpiredTitle());
    }

    /**
     * Test method for get popup expired message
     *
     * @return void
     */
    public function testGetPopUpExpiredMessage()
    {
        $expectedResult = self::XML_PATH_TEST_DATA;
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(self::XML_PATH_TEST_DATA);

        $this->assertEquals($expectedResult, $this->configProviderMock->getPopUpExpiredMessage());
    }
}
