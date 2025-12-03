<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2023.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Plugin\Frontend;

use Fedex\Company\Helper\Data;
use Fedex\WebAnalytics\Model\ContentSquare;
use Fedex\WebAnalytics\Plugin\Frontend\AddScriptToHeaderContentSquare;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\DataObject;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Framework\View\Page\Config as PageConfig;
use PHPUnit\Framework\TestCase;

class AddScriptToHeaderContentSquareTest extends TestCase
{
    /**
     * Test afterGetIncludes plugin function
     *
     * @return void
     */
    public function testAfterGetIncludesToggleOn(): void
    {
        $result = '<script type="text/javascript">console.log(1);</script>'.PHP_EOL;
        $pageConfig = $this->createMock(PageConfig::class);
        $contentSquare = $this->createMock(ContentSquare::class);
        $companyHelper = $this->createMock(Data::class);
        $secureHtmlRendererMock = $this->getMockBuilder(SecureHtmlRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $secureHtmlRendererMock->method('renderTag')
            ->willReturnCallback(
                function (string $tag, array $attributes, string $content): string {
                    $attributes = new DataObject($attributes);

                    return "<$tag {$attributes->serialize()}>$content</$tag>";
                }
            );
        $scriptToHeader = new AddScriptToHeaderContentSquare($contentSquare, $companyHelper, $secureHtmlRendererMock);
        $contentSquare->expects($this->once())->method('isActive')->willReturn(true);
        $contentSquare->expects($this->atMost(2))->method('getScriptCode')->willReturn($result);
        $this->assertEquals($result, $scriptToHeader->afterGetIncludes($pageConfig, ''));
    }

    /**
     * Test afterGetIncludes plugin function when inactive
     *
     * @return void
     */
    public function testAfterGetIncludesToggleOffCompanyOn(): void
    {
        $result = '<script type="text/javascript">console.log(1);</script>'.PHP_EOL;
        $pageConfig = $this->createMock(PageConfig::class);
        $contentSquare = $this->createMock(ContentSquare::class);
        $companyHelper = $this->createMock(Data::class);
        $company = $this->getMockBuilder(CompanyInterface::class)->setMethods(['getContentSquare'])
        ->disableOriginalConstructor()->getMockForAbstractClass();
        $secureHtmlRendererMock = $this->getMockBuilder(SecureHtmlRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $secureHtmlRendererMock->method('renderTag')
            ->willReturnCallback(
                function (string $tag, array $attributes, string $content): string {
                    $attributes = new DataObject($attributes);

                    return "<$tag {$attributes->serialize()}>$content</$tag>";
                }
            );
        $scriptToHeader = new AddScriptToHeaderContentSquare($contentSquare, $companyHelper, $secureHtmlRendererMock);
        $contentSquare->expects($this->once())->method('isActive')->willReturn(false);
        $company->expects($this->once())->method('getContentSquare')->willReturn(true);
        $companyHelper->expects($this->once())->method('getCustomerCompany')->willReturn($company);
        $contentSquare->expects($this->atMost(2))->method('getScriptCode')->willReturn($result);
        $this->assertEquals($result, $scriptToHeader->afterGetIncludes($pageConfig, ''));
    }

    /**
     * Test afterGetIncludes plugin function when inactive
     *
     * @return void
     */
    public function testAfterGetIncludesToggleOffCompanyOff(): void
    {
        $result = '<html></html>';
        $script = '<script type="text/javascript">
                    console.log(1);
                </script>';
        $pageConfig = $this->createMock(PageConfig::class);
        $contentSquare = $this->createMock(ContentSquare::class);
        $companyHelper = $this->createMock(Data::class);
        $company = $this->getMockBuilder(CompanyInterface::class)->setMethods(['getContentSquare'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $secureHtmlRendererMock = $this->getMockBuilder(SecureHtmlRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scriptToHeader = new AddScriptToHeaderContentSquare($contentSquare, $companyHelper, $secureHtmlRendererMock);
        $contentSquare->expects($this->once())->method('isActive')->willReturn(false);
        $company->expects($this->once())->method('getContentSquare')->willReturn(false);
        $companyHelper->expects($this->once())->method('getCustomerCompany')->willReturn($company);
        $contentSquare->expects($this->atMost(2))->method('getScriptCode')->willReturn($script);
        $this->assertEquals($result, $scriptToHeader->afterGetIncludes($pageConfig, $result));
    }
}
