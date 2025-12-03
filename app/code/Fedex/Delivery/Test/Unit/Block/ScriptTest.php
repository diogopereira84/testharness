<?php
namespace Fedex\Delivery\Test\Unit\Block;

use Fedex\Delivery\Block\Script;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template\Context;

class ScriptTest extends TestCase
{
 
    protected $configInterface;
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $script;
    protected function setUp(): void
    {
        $this->configInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->script = $objectManagerHelper->getObject(
            Script::class,
            [
              'configInterface' => $this->configInterface,
              'context' => $this->context,
              'data'    => []
            ]
        );
    }

    /**
     * Test getApiKey.
     *
     * @return string
     */
    public function testGetApiKey()
    {
        $this->configInterface->expects($this->once())->method('getValue')->willReturn("Testvalue");
        $this->assertEquals("Testvalue", $this->script->getApiKey());
    }
}
