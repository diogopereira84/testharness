<?php
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Model;

use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\WebAnalytics\Model\Nuance;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Company\Api\Data\CompanyInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class NuanceTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var SecureHtmlRenderer|MockObject
     */
    private $secureHtmlRenderer;

    /**
     * @var CompanyHelper|MockObject
     */
    private $companyHelper;

    /**
     * @var StoreInterface|MockObject
     */
    private $store;

    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * @var Nuance
     */
    private $nuance;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->secureHtmlRenderer = $this->createMock(SecureHtmlRenderer::class);
        $this->companyHelper = $this->createMock(CompanyHelper::class);
        $this->store = $this->getMockBuilder(StoreInterface::class)
            ->addMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->addMethods(['getNuance'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->nuance = new Nuance(
            $this->scopeConfig,
            $this->storeManager,
            $this->secureHtmlRenderer,
            $this->companyHelper
        );
    }

    public function testIsActive()
    {
        $storeId = 1;
        $this->scopeConfig->method('isSetFlag')
            ->with(Nuance::XML_PATH_FEDEX_NUANCE_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)
            ->willReturn(true);

        $this->assertTrue($this->nuance->isActive($storeId));
    }

    public function testIsActiveReturnsFalse()
    {
        $storeId = 1;
        $this->scopeConfig->method('isSetFlag')
            ->with(Nuance::XML_PATH_FEDEX_NUANCE_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)
            ->willReturn(false);

        $this->assertFalse($this->nuance->isActive($storeId));
    }

    public function testGetScriptCode()
    {
        $storeId = 1;
        $scriptCode = '<script>console.log("Nuance script");</script>';
        $this->scopeConfig->method('getValue')
            ->with(Nuance::XML_PATH_FEDEX_NUANCE_SCRIPT_CODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)
            ->willReturn($scriptCode);

        $this->assertEquals($scriptCode, $this->nuance->getScriptCode($storeId));
    }

    public function testGetScriptCodeReturnsNull()
    {
        $storeId = 1;
        $this->scopeConfig->method('getValue')
            ->with(Nuance::XML_PATH_FEDEX_NUANCE_SCRIPT_CODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)
            ->willReturn(null);

        $this->assertNull($this->nuance->getScriptCode($storeId));
    }

    public function testIsEnabledNuanceForCompany()
    {
        $storeId = 1;
        $this->company->method('getNuance')->willReturn(true);
        $this->companyHelper->method('getCustomerCompany')->willReturn($this->company);
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->store->method('getStoreId')->willReturn($storeId);
        $this->scopeConfig->method('isSetFlag')->with(Nuance::XML_PATH_FEDEX_NUANCE_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)->willReturn(false);

        $this->assertTrue($this->nuance->isEnabledNuanceForCompany());
    }

    public function testIsEnabledNuanceForCompanyReturnsFalse()
    {
        $storeId = 1;
        $this->company->method('getNuance')->willReturn(false);
        $this->companyHelper->method('getCustomerCompany')->willReturn($this->company);
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->store->method('getStoreId')->willReturn($storeId);
        $this->scopeConfig->method('isSetFlag')->with(Nuance::XML_PATH_FEDEX_NUANCE_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)->willReturn(false);

        $this->assertFalse($this->nuance->isEnabledNuanceForCompany());
    }

    public function testGetScriptCodeWithNonce()
    {
        $storeId = 1;
        $scriptCode = '<script src="test.js"></script>';
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->store->method('getStoreId')->willReturn($storeId);
        $this->scopeConfig->method('getValue')->with(Nuance::XML_PATH_FEDEX_NUANCE_SCRIPT_CODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)->willReturn($scriptCode);
        $this->secureHtmlRenderer->method('renderTag')->willReturn($scriptCode);

        $this->assertEquals($scriptCode, $this->nuance->getScriptCodeWithNonce());
    }

    public function testGetScriptCodeWithNonceReturnsFalse()
    {
        $storeId = 1;
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->store->method('getStoreId')->willReturn($storeId);
        $this->scopeConfig->method('getValue')->with(Nuance::XML_PATH_FEDEX_NUANCE_SCRIPT_CODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId)->willReturn(null);

        $this->assertFalse($this->nuance->getScriptCodeWithNonce());
    }

    public function testGetCurrentStoreId()
    {
        $storeId = 1;
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->store->method('getStoreId')->willReturn($storeId);

        $this->assertEquals($storeId, $this->nuance->getCurrentStoreId());
    }
}
