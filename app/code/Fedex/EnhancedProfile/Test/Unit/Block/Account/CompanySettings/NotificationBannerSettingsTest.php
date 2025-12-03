<?php
namespace Fedex\EnhancedProfile\Test\Unit\Block\Account\CompanySettings;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\EnhancedProfile\Block\Account\CompanySettings\NotificationBannerSettings;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Company\Model\Config\Source\IconographyOptions;
use PHPUnit\Framework\TestCase;

class NotificationBannerSettingsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var NotificationBannerSettings
     */
    protected $notificationBannerSettings;


    /**
     * @var IconographyOptions
     */
    protected $iconographyOptionsMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->iconographyOptionsMock = $this->getMockBuilder(IconographyOptions::class)
            ->disableOriginalConstructor()
            ->setMethods(['toOptionArray'])
            ->getMock();

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->notificationBannerSettings = $this->objectManager->getObject(
            NotificationBannerSettings::class,
            [
                'context' => $context,
                'iconographyOptions' => $this->iconographyOptionsMock,
            ]
        );
    }

    /**
     * Test getIconographyOptions
     */
    public function testGetIconographyOptions()
    {
        $optionsArray = [
            [
                'value' => '',
                'label' => __('Please select banner icon...')
            ],
            [
                'value' => 'warning',
                'label' => __('Warning')
            ],
            [
                'value' => 'information',
                'label' => __('Information')
            ]
        ];

        $this->iconographyOptionsMock->expects($this->any())
            ->method('toOptionArray')
            ->willReturn($optionsArray);

        $this->assertEquals($optionsArray, $this->notificationBannerSettings->getIconographyOptions());
    }
}
