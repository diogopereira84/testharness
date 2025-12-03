<?php

namespace Fedex\Company\Test\Unit\Model;

use Fedex\Company\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{

    protected $toggleConfigMock;
    private const SHARED_CATALOGS_MAPPING_ISSUE_FIX = "shared_catalog_mapping_issue_fix";

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Config
     */
    private $config;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = new Config($this->scopeConfigMock, $this->toggleConfigMock);
    }

    public function testGetCompanyStoreRelation(): void
    {
        $expectedValue = 'value_from_config';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_COMPANY_STORE_RELATION, ScopeInterface::SCOPE_STORE)
            ->willReturn($expectedValue);

        $this->assertEquals($expectedValue, $this->config->getCompanyStoreRelation());
    }

    public function testGetCategoryEditD173846Toggle(): void
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(Config::XML_PATH_D_173846_CATEGORY_EDIT_ISSUE)
            ->willReturn(true);

        $this->assertTrue($this->config->getCategoryEditD173846Toggle());
    }

    public function getSharedCatalogsMapIssueFixToggle(): void
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(static::SHARED_CATALOGS_MAPPING_ISSUE_FIX)
            ->willReturn(true);

        $this->assertTrue($this->config->getSharedCatalogsMapIssueFixToggle());
    }
}
