<?php
declare(strict_types=1);

/**
 * @category Fedex
 * @package  Fedex_UpSellIt
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Austin King <austin.king@fedex.com>
 */

namespace Fedex\UpSellIt\Test\Unit\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\UpSellIt\Block\Script;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\TestCase;

class MockScript extends Script {

    public function _toHtml()
    {
        if ($this->scopeConfigInterface->isSetFlag(static::XML_PATH_ACTIVE_UPSELLIT)) {
            return '<script type="text/javascript" src="https://www.testUpSellitScript.com/"></script>';
        }
    }
}

class ScriptTest extends TestCase
{
    protected $secureHtmlMock;
    protected $script;
    protected $mockScript;
    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var MockObject|ObjectManager
     */
    protected $objectManager;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->secureHtmlMock = $this->getMockBuilder(SecureHtmlRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->script = $this->objectManager->getObject(
            Script::class,
            [
                'scopeConfigInterface' => $this->scopeConfigMock,
                'secureHtmlRenderer' => $this->secureHtmlMock,
            ]
        );
        $this->mockScript = $this->objectManager->getObject(
            MockScript::class,
            [
                'scopeConfigInterface' => $this->scopeConfigMock,
                'secureHtmlRenderer' => $this->secureHtmlMock,
            ]
        );
    }

    /**
     * @param array $toHtmlValues
     * @dataProvider getToHtmlValuesDataProvider
     */
    public function testToHtml(array $toHtmlValues): void
    {
        $this->scopeConfigMock->expects($this->once())->method('isSetFlag')
            ->with(Script::XML_PATH_ACTIVE_UPSELLIT)
            ->willReturn($toHtmlValues[0]);

        $this->assertEquals($toHtmlValues[1], $this->mockScript->_toHtml());
    }

    /**
     * @param string $scriptCode
     * @dataProvider getGetScriptCodeDataProvider
     */
    public function testGetScriptCode(string $scriptCode): void
    {
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(Script::XML_PATH_UPSELLIT_SCRIPT)
            ->willReturn($scriptCode);

        $this->secureHtmlMock->expects($this->any())->method('renderTag')
            ->with('script', ['type' => 'text/javascript'], 'console.log("UpSellit Test!");', false)
            ->willReturn('<script type="text/javascript">console.log("UpSellit Test!");</script>');

        $this->assertEquals($scriptCode, $this->script->getScript());
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getToHtmlValuesDataProvider(): array
    {
        return [
            [
                [
                    true,
                    '<script type="text/javascript" src="https://www.testUpSellitScript.com/"></script>'
                ]
            ],
            [
                [
                    false,
                    null
                ]
            ]
        ];
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getGetScriptCodeDataProvider(): array
    {
        return [
            [
                '<script type="text/javascript">console.log("UpSellit Test!");</script>'
            ],
            ['']
        ];
    }
}
