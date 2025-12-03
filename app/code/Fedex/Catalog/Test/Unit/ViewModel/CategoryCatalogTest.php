<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Catalog\Test\Unit\ViewModel;

use Fedex\Catalog\ViewModel\CategoryCatalog;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\UrlInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class CategoryCatalogTest extends TestCase
{
    protected $urlInterface;
    protected $viewModel;
    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    /**
     * Setup mock objects
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->setMethods(['getCurrentUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->viewModel = (new ObjectManager($this))->getObject(
            CategoryCatalog::class,
            [
                'toggleConfig' => $this->toggleConfigMock,
                'urlInterface' => $this->urlInterface
            ]
        );
    }

    /**
     * @test testGetAdminUpdateToggleValue
     */
    public function testGetAdminUpdateToggleValue()
    {
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);

        $this->assertTrue(
            $this->viewModel->getAdminUpdateToggleValue('explorers_enable_disable_catalog_creation_ctc_admin_update')
        );
    }

    /**
     * @test testGetCurrentUrl
     */
    public function testGetCurrentUrl()
    {
        $url = 'https://test.office.fedex.com/test/catalog/product/new/set/12/type/simple/key/1465d1/';

        $this->urlInterface->expects($this->once())->method('getCurrentUrl')->willReturn($url);

        $this->assertNotNull($this->viewModel->getCurrentUrl());
    }

    /**
     * @test testGetToggleValueForCategorySorting
     */
    public function testGetToggleValueForCategorySorting()
    {
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);

        $this->assertTrue(
            $this->viewModel->getToggleValueForCategorySorting('techtitans_B_2428867_category_sorting')
        );
    }
}
