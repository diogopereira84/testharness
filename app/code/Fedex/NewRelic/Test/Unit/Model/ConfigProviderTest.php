<?php
/**
 * @category  Fedex
 * @package   Fedex_NewRelic
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\NewRelic\Test\Unit\Model;

use Fedex\NewRelic\Model\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /**
     * NewRelic Deployment Marker API URL xml path key
     */
    private const API_URL = "https://api.newrelic.com/v2/applications/{{app_id}}/deployments.json";

    /**
     * NewRelic Deployment Marker API KEY xml path key
     */
    private const API_KEY_KEY = 'fedex/new_relic_deployment_marker/api_key';

    /**
     * NewRelic Deployment Marker API KEY xml path value
     */
    private const API_KEY_VALUE = 'api_key';

    /**
     * NewRelic Deployment Marker Identifier xml path key
     */
    private const APPLICATION_IDENTIFIER_KEY = 'fedex/new_relic_deployment_marker/application_identifier';

    /**
     * NewRelic Deployment Marker Identifier xml path value
     */
    private const APPLICATION_IDENTIFIER_VALUE = 'application_identifier';

    /**
     * NewRelic Deployment Marker Enabled xml path key
     */
    private const ENABLED_KEY = 'fedex/new_relic_deployment_marker/enabled';

    /**
     * NewRelic Deployment Marker Enabled xml path value
     */
    private const ENABLED_VALUE = true;

    /**
     * NewRelic Deployment Marker Changelog xml path key
     */
    private const CHANGELOG_KEY = 'fedex/new_relic_deployment_marker/next_changelog';

    /**
     * NewRelic Deployment Marker Changelog xml path value
     */
    private const CHANGELOG_VALUE = 'next_changelog';

    /**
     * NewRelic Deployment Marker description xml path key
     */
    private const DESCRIPTION_KEY = 'fedex/new_relic_deployment_marker/next_description';

    /**
     * NewRelic Deployment Marker description xml path value
     */
    private const DESCRIPTION_VALUE = 'next_description';

    /**
     * NewRelic Deployment Marker user xml path key
     */
    private const USER_KEY = 'fedex/new_relic_deployment_marker/next_user';

    /**
     * NewRelic Deployment Marker user xml path value
     */
    private const USER_VALUE = 'next_user';

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected ScopeConfigInterface|MockObject $scopeConfigMock;

    /**
     * @var WriterInterface|MockObject
     */
    protected WriterInterface|MockObject $writerMock;

    /**
     * @var ConfigProvider
     */
    protected ConfigProvider $configProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(
            ScopeConfigInterface::class
        )
            ->onlyMethods(['getValue', 'isSetFlag'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->writerMock = $this->getMockForAbstractClass(
            WriterInterface::class
        );
        $this->configProvider = new ConfigProvider(
            $this->scopeConfigMock,
            $this->writerMock
        );
    }

    /**
     * Test method getStatus
     *
     * @return void
     */
    public function testGetStatus(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('isSetFlag')
            ->with(
                self::ENABLED_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            )
            ->willReturn(self::ENABLED_VALUE);

        $this->assertTrue($this->configProvider->getStatus());
    }

    /**
     * Test method getApiUrl
     *
     * @return void
     */
    public function testGetApiUrl(): void
    {
        $this->assertEquals(
            self::API_URL,
            $this->configProvider->getApiUrl()
        );
    }

    /**
     * Test method getApiKey
     *
     * @return void
     */
    public function testGetApiKey(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with(
                self::API_KEY_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            )
            ->willReturn(self::API_KEY_VALUE);

        $this->assertEquals(
            self::API_KEY_VALUE,
            $this->configProvider->getApiKey()
        );
    }

    /**
     * Test method getAppIdentifier
     *
     * @return void
     */
    public function testGetAppIdentifier(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with(
                self::APPLICATION_IDENTIFIER_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            )
            ->willReturn(self::APPLICATION_IDENTIFIER_VALUE);

        $this->assertEquals(
            self::APPLICATION_IDENTIFIER_VALUE,
            $this->configProvider->getAppIdentifier()
        );
    }

    /**
     * Test method getDescription
     *
     * @return void
     */
    public function testGetDescription(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with(
                self::DESCRIPTION_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            )
            ->willReturn(self::DESCRIPTION_VALUE);

        $this->assertEquals(
            self::DESCRIPTION_VALUE,
            $this->configProvider->getDescription()
        );
    }

    /**
     * Test method getChangeLog
     *
     * @return void
     */
    public function testGetChangeLog(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with(
                self::CHANGELOG_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            )
            ->willReturn(self::CHANGELOG_VALUE);

        $this->assertEquals(
            self::CHANGELOG_VALUE,
            $this->configProvider->getChangeLog()
        );
    }

    /**
     * Test method getUser
     *
     * @return void
     */
    public function testGetUser(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with(
                self::USER_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            )
            ->willReturn(self::USER_VALUE);

        $this->assertEquals(
            self::USER_VALUE,
            $this->configProvider->getUser()
        );
    }

    /**
     * Test method canPerformDeploymentMarker when have information
     *
     * @return void
     */
    public function testCanPerformDeploymentMarkerTrue(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('isSetFlag')
            ->with(
                self::ENABLED_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            )
            ->willReturn(self::ENABLED_VALUE);
        $this->scopeConfigMock
            ->expects($this->atLeast(2))
            ->method('getValue')
            ->withConsecutive(
                [self::APPLICATION_IDENTIFIER_KEY],
                [self::API_KEY_KEY]
            )->willReturnOnConsecutiveCalls(
                self::APPLICATION_IDENTIFIER_VALUE,
                self::API_KEY_VALUE
            );

        $this->assertTrue(
            $this->configProvider->canPerformDeploymentMarker()
        );
    }

    /**
     * Test method canPerformDeploymentMarker when don't have information
     *
     * @return void
     */
    public function testCanPerformDeploymentMarkerFalse(): void
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('isSetFlag')
            ->with(
                self::ENABLED_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            )
            ->willReturn(self::ENABLED_VALUE);
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with(
                self::APPLICATION_IDENTIFIER_KEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            )
            ->willReturn(null);

        $this->assertFalse(
            $this->configProvider->canPerformDeploymentMarker()
        );
    }

    /**
     * Test method resetFields
     *
     * @return void
     */
    public function testResetFields(): void
    {
        $this->writerMock
            ->expects($this->atLeast(3))
            ->method('save')
            ->withConsecutive(
                [self::CHANGELOG_KEY],
                [self::DESCRIPTION_KEY],
                [self::USER_KEY]
            );

        $this->configProvider->resetFields();
    }
}
