<?php
declare(strict_types=1);

namespace Fedex\Nuance\Test\Unit\Block;

use Fedex\Nuance\Block\Script;
use Fedex\WebAnalytics\Api\Data\GDLConfigInterface;
use Fedex\WebAnalytics\Api\Data\NuanceInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ScriptTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var NuanceInterface|MockObject
     */
    private $nuanceInterface;

    /**
     * @var GDLConfigInterface|MockObject
     */
    private $GDLConfigInterface;

    /**
     * @var ManagerInterface|MockObject
     */
    private $eventManager;

    /**
     * @var Script
     */
    private $block;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->nuanceInterface = $this->createMock(NuanceInterface::class);
        $this->GDLConfigInterface = $this->createMock(GDLConfigInterface::class);
        $this->eventManager = $this->createMock(ManagerInterface::class);

        $this->context->method('getEventManager')->willReturn($this->eventManager);

        $this->block = new Script(
            $this->context,
            $this->storeManager,
            $this->nuanceInterface,
            $this->GDLConfigInterface
        );
    }

    public function testToHtmlWithEnabledNuanceAndScriptCode()
    {
        $testMethod = new \ReflectionMethod(
            Script::class,
            '_toHtml',
        );

        $this->nuanceInterface->method('isEnabledNuanceForCompany')->willReturn(true);
        $this->nuanceInterface->method('getScriptCodeWithNonce')->willReturn('<script>console.log("Nuance script");</script>');

        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->block);
        $this->assertIsString($expectedResult);
    }

    public function testToHtmlWithDisabledNuance()
    {
        $testMethod = new \ReflectionMethod(
            Script::class,
            '_toHtml',
        );

        $this->nuanceInterface->method('isEnabledNuanceForCompany')->willReturn(false);

        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->block);
        $this->assertFalse($expectedResult);
    }

    public function testToHtmlWithNoScriptCode()
    {

        $testMethod = new \ReflectionMethod(
            Script::class,
            '_toHtml',
        );

        $this->nuanceInterface->method('isEnabledNuanceForCompany')->willReturn(true);
        $this->nuanceInterface->method('getScriptCodeWithNonce')->willReturn(false);

        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->block);
        $this->assertFalse($expectedResult);
    }

    public function testGetIdentities()
    {
        $storeId = 1;
        $this->storeManager->method('getStore')->willReturn($this->createConfiguredMock(\Magento\Store\Api\Data\StoreInterface::class, ['getId' => $storeId]));

        $expectedIdentities = ['nuance_' . $storeId];
        $this->assertEquals($expectedIdentities, $this->block->getIdentities());
    }
    public function testGetNuanceScript()
    {
        $scriptCode = '<script>console.log("Nuance script");</script>';
        $this->nuanceInterface->method('getScriptCode')->willReturn($scriptCode);

        $this->assertEquals($scriptCode, $this->block->getNuanceScript());
    }

    public function testGetGdlScript()
    {
        $scriptCode = '<script>console.log("GDL script");</script>';
        $this->GDLConfigInterface->method('getScriptFullyRendered')->willReturn($scriptCode);

        $this->assertEquals($scriptCode, $this->block->getGdlScript());
    }
}
