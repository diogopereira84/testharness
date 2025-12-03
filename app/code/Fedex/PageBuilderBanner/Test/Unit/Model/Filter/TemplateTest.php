<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PageBuilderBanner\Test\Unit\Model\Filter;

use Fedex\Company\Api\Data\ConfigInterface as CompanyConfigInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\ConfigInterface;
use Fedex\PageBuilderBanner\Model\Filter\Template;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Escaper;
use Magento\Framework\Config\View;
use Magento\Framework\View\Config;
use Fedex\PageBuilderBanner\Helper\Data;
use Fedex\Commercial\Helper\CommercialHelper As CommercialHelper;
class TemplateTest extends TestCase
{
    /**
     * @var (\Fedex\PageBuilderBanner\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $pagebulderHelper;
    /**
     * @var (\Fedex\Commercial\Helper\CommercialHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $commercialHelper;
    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigInterface|MockObject
     */
    private $viewConfig;

    /**
     * @var Template
     */
    private $model;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var View
     */
    private $configView;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var CompanyConfigInterface
     */
    private $companyConfigInterface;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->viewConfig = $this->createMock(ConfigInterface::class);
        $this->objectManager = new ObjectManager($this);
        $this->escaper = $this->objectManager->getObject(Escaper::class);
        $this->configView = $this->createMock(View::class);
        $this->pagebulderHelper = $this->createMock(Data::class);
        $this->commercialHelper = $this->createMock(CommercialHelper::class);
        $this->viewConfig = $this->createConfiguredMock(
            Config::class,
            [
                'getViewConfig' => $this->configView
            ]
        );

        $this->context = $this->createConfiguredMock(
            Context::class,
            [
                'getEscaper' => $this->escaper,
                'getViewConfig' => $this->viewConfig
            ]
        );

        $this->companyConfigInterface = $this->getMockForAbstractClass(CompanyConfigInterface::class);

        $this->model = new Template(
            $this->logger,
            $this->viewConfig,
            new Random(),
            new Json(),
            $this->companyConfigInterface,
            $this->pagebulderHelper,
            $this->commercialHelper
        );
    }

    public function testFilter(): void
    {
        $configMap = ['Magento_Catalog', 'gallery/nav', 'thumbs'];

        $data = $this->filterProvider();

        foreach ($data[1] as $value) {
            $this->configView->expects($this->any())
                ->method('getVarValue')
                ->willReturn($configMap);

            $this->assertNotNull($this->model->filter($value));
        }
        foreach ($data[3] as $value) {
            $this->configView->expects($this->any())
                ->method('getVarValue')
                ->willReturn($configMap);

            $this->assertNotNull($this->model->filter($value));
        }
    }

    /**
     * Test filterProvider
     * @return array
     */
    public function filterProvider(): array
    {
        return [
            [
                '',
                ''
            ],
            [
                '<div class="messages" data-background-images=\'{"desktop_image":"https://fedex.com/media/img.jpg",
                    "mobile_image":"https://fedex.com/media/img.jpg",
                    "desktop_medium_image":"https://fedex.com/media/img.jpg",
                    "mobile_medium_image":"https://fedex.com/media/img.jpg"}\'>' .
                '<div class="messages">' .
                '<span class="message alert-success">success</span>' .
                '</div>' .
                '</div>',
                '<div data-background-images=\'{"result":[{}]}\'>' .
                '<div class="messages">' .
                '<span class="message alert-success">success</span>' .
                '</div>' .
                '</div>'
            ],
            [
                '<div class="messages">' .
                '<span class="message alert-success">success</span>' .
                '</div>',
                '<div class="messages">' .
                '<span class="message alert-success">success</span>' .
                '</div>'
            ],
            [
                '<div data-content-type="html">' .
                '<div class="messages">' .
                '<span class="message alert-success">success</span>' .
                '</div>' .
                '</div>',
                '<div data-content-type="html" data-decoded="true">' .
                '<div class="messages">' .
                '<span class="message alert-success">success</span>' .
                '</div>' .
                '</div>'
            ],
            [
                '<div data-content-type="html">' .
                '<div class="messages">' .
                '<span class="message alert-success">success</span>' .
                '</div>' .
                '</div>',
                '<div data-content-type="html" data-decoded="true">' .
                '<div class="messages">' .
                '<span class="message alert-success">success</span>' .
                '</div>' .
                '</div>'
            ],
            [
                '<div class="widget">' .
                '<div>smart widget</div>' .
                '<script type="text/x-magento-template">' .
                '<span>smart template</span>' .
                '</script>' .
                '</div>',
                '<div class="widget">' .
                '<div>smart widget</div>' .
                '<script type="text/x-magento-template">' .
                '<span>smart template</span>' .
                '</script>' .
                '</div>'
            ],
            [
                '<div data-content-type="html">' .
                '<div class="widget">' .
                '<div>smart widget</div>' .
                '<script type="text/x-magento-template">' .
                '<span>smart template</span>' .
                '</script>' .
                '</div>' .
                '</div>',
                '<div data-content-type="html" data-decoded="true">' .
                '<div class="widget">' .
                '<div>smart widget</div>' .
                '<script type="text/x-magento-template">' .
                '<span>smart template</span>' .
                '</script>' .
                '</div>' .
                '</div>',
            ],
        ];
    }

    public function testFilterWithEmptyBreakpoints(): void
    {
        $configMap = [];
        $data = $this->filterProvider();
        foreach ($data[1] as $value) {
            $this->configView->expects($this->any())
                ->method('getVarValue')
                ->willReturn($configMap);

            $this->assertNotNull($this->model->filter($value));
        }
        foreach ($data[6] as $value) {
            $this->configView->expects($this->any())
                ->method('getVarValue')
                ->willReturn($configMap);

            $this->assertNotNull($this->model->filter($value));
        }
    }
}
