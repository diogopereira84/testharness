<?php

namespace Fedex\CSP\Test\Unit\Plugin;

use Fedex\CSP\Plugin\CspNonceMetaTag;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Csp\Helper\CspNonceProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use PHPUnit\Framework\TestCase;

class CspNonceMetaTagTest extends TestCase
{
    private $cspNonceProvider;
    private $request;
    private $toggleConfig;
    private $plugin;

    protected function setUp(): void
    {
        $this->cspNonceProvider = $this->createMock(CspNonceProvider::class);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getFullActionName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfig = $this->createMock(ToggleConfig::class);

        $this->plugin = new CspNonceMetaTag(
            $this->cspNonceProvider,
            $this->request,
            $this->toggleConfig
        );
    }

    public function testAfterGetIncludesAddsNonceMetaTag()
    {
        $pageConfig = $this->createMock(PageConfig::class);
        $originalResult = '<link rel="stylesheet" href="styles.css">';
        $generatedNonce = 'test-nonce';

        $this->toggleConfig->method('getToggleConfigValue')->with('tiger_d196844')->willReturn(true);
        $this->request->method('getFullActionName')->willReturn('checkout_index_index');
        $this->cspNonceProvider->method('generateNonce')->willReturn($generatedNonce);

        $result = $this->plugin->afterGetIncludes($pageConfig, $originalResult);

        $expectedResult = $originalResult . '<meta name="csp-nonce" content="' . $generatedNonce . '" />';
        $this->assertEquals($expectedResult, $result);
    }

    public function testAfterGetIncludesReturnsOriginalResultWhenToggleIsDisabled()
    {
        $pageConfig = $this->createMock(PageConfig::class);
        $originalResult = '<link rel="stylesheet" href="styles.css">';

        $this->toggleConfig->method('getToggleConfigValue')->with('tiger_d196844')->willReturn(false);

        $result = $this->plugin->afterGetIncludes($pageConfig, $originalResult);

        $this->assertEquals($originalResult, $result);
    }

    public function testAfterGetIncludesReturnsOriginalResultWhenActionNameDoesNotMatch()
    {
        $pageConfig = $this->createMock(PageConfig::class);
        $originalResult = '<link rel="stylesheet" href="styles.css">';

        $this->toggleConfig->method('getToggleConfigValue')->with('tiger_d196844')->willReturn(true);
        $this->request->method('getFullActionName')->willReturn('some_other_action');

        $result = $this->plugin->afterGetIncludes($pageConfig, $originalResult);

        $this->assertEquals($originalResult, $result);
    }

    public function testAfterGetIncludesReturnsOriginalResultWhenNonceIsEmpty()
    {
        $pageConfig = $this->createMock(PageConfig::class);
        $originalResult = '<link rel="stylesheet" href="styles.css">';

        $this->toggleConfig->method('getToggleConfigValue')->with('tiger_d196844')->willReturn(true);
        $this->request->method('getFullActionName')->willReturn('checkout_index_index');
        $this->cspNonceProvider->method('generateNonce')->willReturn('');

        $result = $this->plugin->afterGetIncludes($pageConfig, $originalResult);

        $this->assertEquals($originalResult, $result);
    }
}
