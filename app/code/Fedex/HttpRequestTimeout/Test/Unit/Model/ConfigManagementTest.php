<?php
/**
 * @category Fedex
 * @package  Fedex_HttpRequestTimeout
 * @copyright   Copyright (c) 2024 FedEx
 */

declare(strict_types=1);

namespace Fedex\HttpRequestTimeout\Test\Unit\Model;

use Exception;
use Fedex\HttpRequestTimeout\Model\ConfigManagement;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

class ConfigManagementTest extends TestCase
{
    private ConfigManagement $configManagement;
    private ScopeConfigInterface $scopeConfigMock;
    private WriterInterface $configWriterMock;
    private TypeListInterface $cacheTypeListMock;
    private Json $serializerMock;
    private RequestInterface $requestMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->addMethods(['clean'])
            ->onlyMethods(['getValue', 'isSetFlag'])
            ->getMock();
        $this->configWriterMock = $this->createMock(WriterInterface::class);
        $this->cacheTypeListMock = $this->createMock(TypeListInterface::class);
        $this->serializerMock = $this->createMock(Json::class);
        $this->requestMock = $this->createMock(RequestInterface::class);

        $this->configManagement = new ConfigManagement(
            $this->scopeConfigMock,
            $this->configWriterMock,
            $this->cacheTypeListMock,
            $this->serializerMock,
            $this->requestMock
        );
    }

    /**
     * @return void
     */
    public function testIsFeatureEnabled()
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with(ConfigManagement::XML_PATH_TO_ENABLED)
            ->willReturn(true);

        $this->assertTrue($this->configManagement->isFeatureEnabled());
    }

    /**
     * @return void
     */
    public function testIsDefaultTimeoutEnabled()
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with(ConfigManagement::XML_PATH_TO_DEFAULT_TIMEOUT_ENABLED)
            ->willReturn(false);

        $this->assertFalse($this->configManagement->isDefaultTimeoutEnabled());
    }

    /**
     * @return void
     */
    public function testGetDefaultTimeout()
    {
        $this->scopeConfigMock->method('getValue')
            ->with(ConfigManagement::XML_DEFAULT_TIMEOUT)
            ->willReturn(30);

        $this->assertEquals(30, $this->configManagement->getDefaultTimeout());
    }

    /**
     * @return void
     */
    public function testGetCurrentEntriesValueUnserialized()
    {
        $serializedData = '{"key1":{"timeout":30},"key2":{"timeout":60}}';
        $unserializedData = ['key1' => ['timeout' => 30], 'key2' => ['timeout' => 60]];

        $this->scopeConfigMock->method('getValue')
            ->with(ConfigManagement::XML_PATH_TO_ENTRIES_LIST)
            ->willReturn($serializedData);
        $this->serializerMock->method('unserialize')
            ->with($serializedData)
            ->willReturn($unserializedData);

        $this->assertEquals(
            $unserializedData,
            $this->configManagement->getCurrentEntriesValueUnserialized()
        );
    }

    /**
     * @return void
     */
    public function testGetCurrentEntriesValueForListing()
    {
        $entriesValue = 'serialized entries';
        $this->scopeConfigMock->method('getValue')
            ->with(ConfigManagement::XML_PATH_TO_ENTRIES_LIST)
            ->willReturn($entriesValue);

        $this->assertEquals(
            $entriesValue,
            $this->configManagement->getCurrentEntriesValueForListing()
        );
    }

    /**
     * @return void
     */
    public function testSaveEntries()
    {
        $serializedData = 'serialized entries';

        $this->configWriterMock
            ->expects($this->once())
            ->method('save')
            ->with(
                ConfigManagement::XML_PATH_TO_ENTRIES_LIST,
                $serializedData
        );
        $this->cacheTypeListMock
            ->expects($this->once())
            ->method('cleanType')
            ->with('config');
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('clean');
        $this->configManagement
            ->saveEntries($serializedData);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testUpdatedEntriesSuccess()
    {
        $newEntry = 'key1,60';
        $serializedResult = '{"key1":{"timeout":60}}';

        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['entry', null, $newEntry], // 'entry' returns $newEntry
                ['entry_key', null, null], // 'entry_key' returns null
            ]);
        $this->scopeConfigMock->method('getValue')
            ->with(ConfigManagement::XML_PATH_TO_ENTRIES_LIST)
            ->willReturn(null);
        $this->serializerMock->method('serialize')
            ->willReturn($serializedResult);

        $this->assertEquals($serializedResult, $this->configManagement->updatedEntries());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testUpdatedEntriesThrowsException()
    {
        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['entry', null, ''], // 'entry' returns an empty string
                ['entry_key', null, null], // 'entry_key' returns null
            ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Entry value is empty.');

        $this->configManagement->updatedEntries();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testRemovedEntriesSuccess()
    {
        $entryKey = 'key1';
        $serializedResult = '{"key2":{"timeout":60}}';

        $currentEntries = [
            'key1' => ['timeout' => 30],
            'key2' => ['timeout' => 60],
        ];

        $this->requestMock
            ->method('getParam')
            ->with('entry_key')
            ->willReturn($entryKey);
        $this->scopeConfigMock
            ->method('getValue')
            ->with(ConfigManagement::XML_PATH_TO_ENTRIES_LIST)
            ->willReturn(json_encode($currentEntries));
        $this->serializerMock
            ->method('unserialize')
            ->willReturn($currentEntries);
        $this->serializerMock
            ->method('serialize')
            ->willReturn($serializedResult);

        $this->assertEquals($serializedResult, $this->configManagement->removedEntries());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testRemovedEntriesThrowsException()
    {
        $entryKey = 'non_existing_key';
        $currentEntries = ['key1' => ['timeout' => 30]];

        $this->requestMock
            ->method('getParam')
            ->with('entry_key')
            ->willReturn($entryKey);
        $this->scopeConfigMock
            ->method('getValue')
            ->with(ConfigManagement::XML_PATH_TO_ENTRIES_LIST)
            ->willReturn(json_encode($currentEntries));
        $this->serializerMock
            ->method('unserialize')
            ->willReturn($currentEntries);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Entry Key doest not exist.');

        $this->configManagement->removedEntries();
    }
}
