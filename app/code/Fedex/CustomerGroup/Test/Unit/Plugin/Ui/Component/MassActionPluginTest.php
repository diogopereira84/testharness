<?php
namespace Fedex\CustomerGroup\Plugin\Ui\Component;

use Magento\Ui\Component\AbstractComponent;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\CustomerGroup\Plugin\Ui\Component\MassActionPlugin;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Ui\Component\MassAction;
use PHPUnit\Framework\TestCase;

class MassActionPluginTest extends TestCase
{
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    protected $subject;
    protected $component;
    protected $massActionPlugin;
    const NAME = 'massaction';
    /**
     * Test setUp
     */
    protected function setUp(): void
    {

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
                                ->setMethods(['getToggleConfigValue'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->subject = $this->getMockBuilder(MassAction::class)
                                ->setMethods(['getConfiguration','setData'])
                                ->disableOriginalConstructor()
                                ->getMock();
        $this->component = $this->getMockBuilder(AbstractComponent::class)
                                ->setMethods(['getComponentName'])
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->massActionPlugin = $this->getMockForAbstractClass(
            MassActionPlugin::class,
            [
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }
    public function testAfterPrepare(){
        $result = ['some', 'result', 'array'];

        // Mock the configuration
        $config = [
            'actions' => [
                ['type' => 'some_type'],
                ['type' => 'assign_to_group'],
                ['type' => 'another_type'],
            ],
        ];
        $this->subject->expects($this->any())
        ->method('getConfiguration')
        ->willReturn($config);
        $this->subject->expects($this->any())
        ->method('setData')
        ->with('config', ['actions' => [['type' => 'some_type'], ['type' => 'another_type']]]);
        $this->assertNotNull($this->massActionPlugin->afterPrepare($this->subject, $result));
    }
    public function testGetComponentName(){
        $this->component->expects($this->any())->method('getComponentName')->willReturn(static::NAME);
        $this->assertEquals(MassActionPlugin::NAME,$this->massActionPlugin->getComponentName());
    }
}
