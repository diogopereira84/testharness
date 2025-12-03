<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2024.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Plugin\Frontend;

use Fedex\WebAnalytics\Api\Data\NewRelicInterface;
use Fedex\WebAnalytics\Plugin\Frontend\AddScriptToHeaderNewRelic;
use Magento\Framework\DataObject;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Framework\View\Page\Config as PageConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddScriptToHeaderNewRelicTest extends TestCase
{

    protected $pageConfig;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $_objectManager;
    /**
     * @var AddScriptToHeaderNewRelic
     */
    protected $_addScriptToHeaderNewRelic;

    /**
     * @var NewRelicInterface|MockObject
     */
    protected $configInterface;

    /**
     * @var SecureHtmlRenderer|MockObject
     */
    private SecureHtmlRenderer|MockObject $secureHtmlRendererMock;

    /**
     * Test setUp
     */
    public function setUp() : void
    {
        $this->configInterface = $this->getMockBuilder(NewRelicInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->secureHtmlRendererMock = $this
            ->getMockBuilder(SecureHtmlRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageConfig = $this->createMock(PageConfig::class);
        $this->secureHtmlRendererMock->method('renderTag')
            ->willReturnCallback(
                function (string $tag, array $attributes, string $content): string {
                    $attributes = new DataObject($attributes);

                    return "<$tag {$attributes->serialize()}>$content</$tag>";
                }
            );

        $this->_objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_addScriptToHeaderNewRelic = $this->_objectManager->getObject(
            AddScriptToHeaderNewRelic::class,
            [
                'config' => $this->configInterface,
                'secureHtmlRenderer' => $this->secureHtmlRendererMock,
            ]
        );
    }

    /**
     * Test afterGetIncludes plugin function
     */
    public function testAfterGetIncludes()
    {
        $this->configInterface->expects($this->any())->method('isActive')
            ->willReturn(true);
        $this->configInterface->expects($this->any())->method('getScriptCode')
            ->willReturn(<<<script
<script type="text/javascript">console.log(1);</script>
script);
        $result = '<script type="text/javascript">console.log(1);</script>'.PHP_EOL;
        $this->assertEquals($result, $this->_addScriptToHeaderNewRelic->afterGetIncludes($this->pageConfig, ''));
    }

    /**
     * Test afterGetIncludes plugin function
     */
    public function testAfterGetIncludesToggleOff()
    {
        $this->configInterface->expects($this->any())->method('isActive')
            ->willReturn(false);
        $this->assertEquals('', $this->_addScriptToHeaderNewRelic->afterGetIncludes($this->pageConfig, ''));
    }
}
