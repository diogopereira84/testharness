<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Test\Unit\Helper;

use Fedex\Import\Helper\Data;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Import\Model\Source\Factory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;
use Magento\Integration\Model\AdminTokenService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class DataTest extends TestCase
{
    protected $storeMock;
    protected $collectionMock;
    /**
     * @var (\Magento\Integration\Model\AdminTokenService & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $adminTokenServiceMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $helperData;
    /**
     * @var Context|MockObject
     */
    protected $contextMock;
    
    /**
     * @var Factory|MockObject
     */
    protected $sourceFactoryMock;

    /**
     * @var EncryptorInterface|MockObject
     */
    protected $encryptorInterfaceMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerInterfaceMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigInterfaceMock;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $collectionFactoryMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
                                ->disableOriginalConstructor()
                                ->setMethods(['getScopeConfig'])
                                ->getMock();

        $this->sourceFactoryMock = $this->getMockBuilder(Factory::class)
                                        ->disableOriginalConstructor()
                                        ->setMethods(['create'])
                                        ->getMock();

        $this->encryptorInterfaceMock = $this->getMockBuilder(EncryptorInterface::class)
                                            ->disableOriginalConstructor()
                                            ->getMockForAbstractClass();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
                                            ->disableOriginalConstructor()
                                            ->setMethods(['getStore'])
                                            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
                                            ->disableOriginalConstructor()
                                            ->setMethods(['getBaseUrl'])
                                            ->getMock();

        $this->scopeConfigInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
                                            ->disableOriginalConstructor()
                                            ->setMethods(['getValue'])
                                            ->getMockForAbstractClass();

        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
                                            ->disableOriginalConstructor()
                                            ->setMethods(['create'])
                                            ->getMock();

        $this->collectionMock = $this->getMockBuilder(Collection::class)
                                            ->disableOriginalConstructor()
                                            ->setMethods(['addFieldToFilter', 'load', 'getData'])
                                            ->getMock();

        $this->adminTokenServiceMock = $this->getMockBuilder(AdminTokenService::class)
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->helperData = $objectManagerHelper->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'coreConfig' => $this->scopeConfigInterfaceMock,
                'sourceFactory' => $this->sourceFactoryMock,
                'encryptor' => $this->encryptorInterfaceMock,
                'storeManager' => $this->storeManagerInterfaceMock,
                'configInterface' => $this->scopeConfigInterfaceMock,
                'collectionFactory' => $this->collectionFactoryMock,
                'adminTokenService' => $this->adminTokenServiceMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test for getSourceModelByType method.
     *
     * @return Null/Exception
     */
    public function testGetSourceModelByType()
    {
        $sourceType1 = 'Dropbox';
        $actualResult = $this->helperData->getSourceModelByType($sourceType1);
        $this->assertNull($actualResult);
    }

   /**
     * Test for getSourceModelByType method.
     *
     * @return Void
     */
    public function testGetSourceModelByTypeWithException()
    {
        $sourceType1 = '';
        $this->expectException(LocalizedException::class);
        $actualResult = $this->helperData->getSourceModelByType($sourceType1);
        $this->assertNull($actualResult);
    }

    /**
     * Test for getBaseUrl method.
     *
     * @return Null|String
     */
    public function testGetBaseUrl()
    {
        $expectedResult = 'https://staging3.office.fedex.com/';
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($expectedResult);

        $actualResult = $this->helperData->getBaseUrl();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * Test for getDebugMode method.
     *
     * @return Null|String
     */
    public function testGetDebugMode()
    {
        $expectedResult = true;
        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->willReturn($expectedResult);

        $actualResult = $this->helperData->getDebugMode();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * Test for getAdminToken method.
     *
     * @return string|array
     */
    public function testGetAdminToken()
    {
        $username = 'admin';
        $password = '0:3:57b1KnIoivsjoETztgLcO39loK87yHi/ItNANgjH2idUh2Gv';
        $this->testGetBaseUrl();

        $actualResult = $this->helperData->getAdminToken();
        $this->assertNull($actualResult);
    }

    /**
     * Test for getByName method.
     *
     * @return Null|String
     */
    public function testGetByName()
    {
        $return = ['0' => ['entity_id' => 1]];
        $param = 'test_name';
        $expectedResult = 1;
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('load')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('getData')->willReturn($return);

        $actualResult = $this->helperData->getByName($param);
        $this->assertEquals($expectedResult, $actualResult);
    }
}
