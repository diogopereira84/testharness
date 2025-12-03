<?php

/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\Factory as Template;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\Timezone\LocalizedDateToUtcConverterInterface;

class EmailTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var Template|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $templateMock;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeManagerMock;

    /**
     * @var ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scopeConfigInterface;

    /**
     * @var UrlInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $urlBuilder;

    /**
     * @var TimezoneInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $timezone;

    /**
     * @var LocalizedDateToUtcConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $utcConverter;

    /**
     * @var Email
     */
    protected $emailModel;
    
    /**
     * Set up the test environment for each unit test.
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->templateMock = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->timezone = $this->getMockBuilder(TimezoneInterface::class)
            ->onlyMethods(['date'])
            ->addMethods(['format', 'setTimezone'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->utcConverter = $this->getMockBuilder(LocalizedDateToUtcConverterInterface::class)
            ->onlyMethods(['convertLocalizedDateToUtc'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->emailModel = new Email(
            $this->templateMock,
            $this->storeManagerMock,
            $this->toggleConfigMock,
            $this->scopeConfigInterface,
            $this->urlBuilder,
            $this->timezone,
            $this->utcConverter
        );
    }

    /**
     * Test method for getEmailHtml with a specified template.
     * @return void
     */
    public function testGetEmailHtmlWithTemplate()
    {
        $templateName = 'test/template';
        $templateId = 5;
        $templateIdentifier = 5;
        $this->testGetEmailHtml($templateName, $templateId, $templateIdentifier);
    }

    /**
     * Test getEmailHtml method behavior when no email template is provided.
     * @return void
     */
    public function testGetEmailHtmlWithNoTemplate()
    {
        $templateName = 'test/template';
        $templateId = 0;
        $templateIdentifier = 'test_template';
        $this->testGetEmailHtml($templateName, $templateId, $templateIdentifier);
    }

    /**
     * Helper method to test getEmailHtml functionality.
     *
     * @param string $templateName
     * @param int $templateId
     * @param string $templateIdentifier
     * @return void
     */
    private function testGetEmailHtml($templateName, $templateId, $templateIdentifier)
    {
        $orderData = ['key' => 'value'];
        $templateSubject = 'Test Subject';
        $templateHtml = '<html>Test HTML</html>';

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->with($templateName)
            ->willReturn($templateId);
        $templateMock = $this->getMockBuilder(Template::class)
            ->addMethods(['setVars', 'setOptions', 'processTemplate', 'getSubject'])
            ->disableOriginalConstructor()
            ->getMock();
        $templateMock->expects($this->once())
            ->method('setVars')
            ->with($orderData)
            ->willReturnSelf();
        $templateMock->expects($this->once())
            ->method('setOptions')
            ->with([
                'area' => Area::AREA_FRONTEND,
                'store' => 1
            ])
            ->willReturnSelf();
        $templateMock->expects($this->once())
            ->method('processTemplate')
            ->willReturn($templateHtml);
        $templateMock->expects($this->once())
            ->method('getSubject')
            ->willReturn($templateSubject);
        $this->templateMock->expects($this->once())
            ->method('get')
            ->with($templateIdentifier, null)
            ->willReturn($templateMock);
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();
        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($storeMock);
        $emailModel = new Email(
            $this->templateMock,
            $this->storeManagerMock,
            $this->toggleConfigMock,
            $this->scopeConfigInterface,
            $this->urlBuilder,
            $this->timezone,
            $this->utcConverter
        );
        $result = $emailModel->getEmailHtml($templateName, $orderData);
        $this->assertEquals(['template' => $templateHtml, 'subject' => $templateSubject], $result);
    }

    /**
     * Test method for getEmailHtml when the template does not exist.
     * @return void
     */
    public function testGetEmailHtmlThrowsException()
    {
        $this->expectException(NoSuchEntityException::class);

        $templateName = 'test/template';
        $orderData = ['key' => 'value'];
        $templateId = 123;

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfig')
            ->with($templateName)
            ->willReturn($templateId);

        $this->templateMock->expects($this->once())
            ->method('get')
            ->willThrowException(new NoSuchEntityException(__('message')));

        $this->emailModel->getEmailHtml($templateName, $orderData);
    }

    /**
     * Test method for convertBase64.
     * @return void
     */
    public function testConvertBase64()
    {
        $html = '<p>some html</p>';
        $expectedResult = 'base64:PHA+c29tZSBodG1sPC9wPg==';
        $result = $this->emailModel->convertBase64($html);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test method for minifyHtml.
     * This test checks if the HTML is correctly minified by removing unnecessary whitespace and formatting.
     * @return void
     */
    public function testMinifyHtml()
    {
        $inputHtml = "<html>
                <head>
                    <title>Sample Page</title>
                    <style>
                        .someTag {
                            content: '\\2014 \\00A0'
                        }
                    </style>
                </head>
                <body>
                    <p style='font-family: \"Some Font\";'>\"Line\"</p>
                </body>
            </html>";

        $expectedOutput = "<html><head><title>Sample Page</title><style> .someTag { content: ' ' } </style></head><body><p style='font-family: Some Font;'>'Line'</p></body></html>"; // phpcs:ignore
        $result = $this->emailModel->minifyHtml($inputHtml);
        $this->assertEquals($expectedOutput, $result);
    }

    /**
     * Test method for getEmailLogoUrl.
     * This test checks if the email logo URL is correctly constructed based on the configuration and base URL.
     * @return void
     */
    public function testGetEmailLogoUrl()
    {
        $this->scopeConfigInterface->expects($this->once())->method('getValue')->willReturn('logo.png');
        $this->urlBuilder->expects($this->once())->method('getBaseUrl')->willReturn('some/path/folder/');
        $result = $this->emailModel->getEmailLogoUrl();
        $expectedResult = 'some/path/folder/email/logo/logo.png';
        $this->assertEquals($result, $expectedResult);
    }

    /**
     * Test method for getFormattedCstDate.
     * This test checks if the date is correctly formatted to CST timezone.
     * @return void
     */
    public function testGetFormattedCstDate()
    {
        $date = '2023-08-03 06:11:16';
        $expectedResult = 'Aug 01, 2023 at 07:11 AM CST';
        $this->timezone->expects($this->once())->method('date')->willReturnSelf();
        $this->timezone->expects($this->once())->method('format')->willReturn($expectedResult);
        $this->utcConverter->expects($this->any())
            ->method('convertLocalizedDateToUtc')
            ->willReturn($date);
        $this->timezone->expects($this->once())->method('setTimezone')->willReturnSelf();
        $result = $this->emailModel->getFormattedCstDate($date);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test method for getFormattedCstDate with fix enabled.
     * This test checks if the date is correctly converted to UTC and formatted to CST timezone when the fix is enabled.
     * @return void
     */
    public function testGetFormattedCstDateWithFixEnabled()
    {
        $date = '2023-08-03 06:11:16';
        $convertedDate = '2023-08-03 11:11:16';
        $expectedResult = 'Aug 03, 2023 at 07:11 AM CST';

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('mazegeeks_D192133_fix')
            ->willReturn(true);

        $this->utcConverter->expects($this->once())
            ->method('convertLocalizedDateToUtc')
            ->with($date)
            ->willReturn($convertedDate);

        $this->timezone->expects($this->once())
            ->method('date')
            ->with($convertedDate)
            ->willReturnSelf();

        $this->timezone->expects($this->once())
            ->method('setTimezone')
            ->willReturnSelf();

        $this->timezone->expects($this->once())
            ->method('format')
            ->willReturn($expectedResult);

        $result = $this->emailModel->getFormattedCstDate($date);

        $this->assertEquals($expectedResult, $result);
    }
}
