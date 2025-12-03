<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Catalog\Test\Unit\ViewModel;

use Fedex\CatalogDocumentUserSettings\Helper\Data as CatalogDocumentUserSettingsHelper;
use Fedex\Catalog\ViewModel\ProductList;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Helper\CatalogMvp as CatalogMvpHelper;
use Fedex\FXOCMConfigurator\Helper\Data as FXOCMHelper;

class ProductListTest extends TestCase
{
    protected $viewModel;
    /**
     * @var PunchoutHelper|MockObject
     */
    protected $punchoutHelperMock;

    /**
     * @var DeliveryDataHelper|MockObject
     */
    protected $deliveryDataHelperMock;

    /**
     * @var CatalogDocumentUserSettingsHelper|MockObject
     */
    protected $catalogDocumentUserSettingsHelperMock;

    /**
     * @var SdeHelper|MockObject
     */
    protected $sdeHelperMock;

    /**
     * @var CatalogMvpHelper|MockObject
     */
    protected $catalogMvpHelperMock;

    /**
     * @var FXOCMHelper
     */
    protected $fxoCMHelper;

    /**
     * Setup mock objects
     */
    protected function setUp(): void
    {
        $this->punchoutHelperMock = $this->createMock(PunchoutHelper::class);

        $this->deliveryDataHelperMock = $this->createMock(DeliveryDataHelper::class);

        $this->catalogDocumentUserSettingsHelperMock = $this->createMock(CatalogDocumentUserSettingsHelper::class);

        $this->sdeHelperMock = $this->createMock(SdeHelper::class);

        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvpHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

       $this->fxoCMHelper = $this->getMockBuilder(FXOCMHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCharLimitToggle',
                'isCatalogEllipsisControlEnabled',
                'getCatalogEllipsisControlTotalCharacters',
                'getCatalogEllipsisControlStartCharacters',
                'getCatalogEllipsisControlEndCharacters',
                'getFixedQtyHandlerToggle',
            ])
            ->getMockForAbstractClass();     

        $this->viewModel = (new ObjectManager($this))->getObject(
            ProductList::class,
            [
                'punchoutHelper' => $this->punchoutHelperMock,
                'deliveryDataHelper' => $this->deliveryDataHelperMock,
                'catalogDocumentUserSettingsHelper' => $this->catalogDocumentUserSettingsHelperMock,
                'sdeHelper' => $this->sdeHelperMock,
                'CatalogMvpHelper' => $this->catalogMvpHelperMock,
                'fxoCMHelper' => $this->fxoCMHelper
            ]
        );
    }

    /**
     * @test testGetTazToken
     */
    public function testGetTazToken()
    {
        $tazToken = 'taz-token';

        $this->deliveryDataHelperMock->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);

        $this->punchoutHelperMock->expects($this->any())
            ->method('getTazToken')
            ->willReturn($tazToken);

        $this->assertEquals($tazToken, $this->viewModel->getTazToken());
    }

    /**
     * @test testGetTazTokenForNoneProCustomer
     */
    public function testGetTazTokenForNoneProCustomer()
    {
        $tazToken = '';

        $this->deliveryDataHelperMock->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(false);

        $this->assertEquals($tazToken, $this->viewModel->getTazToken());
    }

    /**
     * @test testGetSiteNameForEproCustomer
     */
    public function testGetSiteNameForEproCustomer()
    {
        $siteName = 'site-name';

        $this->deliveryDataHelperMock->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);

        $this->deliveryDataHelperMock->expects($this->any())
            ->method('getCompanySite')
            ->willReturn($siteName);

        $this->assertEquals($siteName, $this->viewModel->getSiteName());
    }

    /**
     * @test testGetSiteNameForNonEproCustomer
     */
    public function testGetSiteNameForNonEproCustomer()
    {
        $siteName = '';

        $this->deliveryDataHelperMock->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(false);

        $this->deliveryDataHelperMock->expects($this->any())
            ->method('getCompanySite')
            ->willReturn($siteName);

        $this->assertEquals($siteName, $this->viewModel->getSiteName());
    }

    /**
     * @test testGetWrapperClassForCommercialCustomer
     */
    public function testGetWrapperClassForCommercialCustomer()
    {
        $this->deliveryDataHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $this->assertEquals('ero-session', $this->viewModel->getWrapperClass());
    }

    /**
     * @test testGetWrapperClassForNonCommercialCustomer
     */
    public function testGetWrapperClassForNonCommercialCustomer()
    {
        $this->deliveryDataHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(false);

        $this->assertEquals('retail-session', $this->viewModel->getWrapperClass());
    }

    /**
     * @test testGetCatalogDocumentUserSettingsHelper
     */
    public function testGetCatalogDocumentUserSettingsHelper()
    {
        $this->assertEquals($this->catalogDocumentUserSettingsHelperMock, $this->viewModel->getCatalogDocumentUserSettingsHelper());
    }

    /**
     * @test testGetDeliveryDataHelper
     */
    public function testGetDeliveryDataHelper()
    {
        $this->assertEquals($this->deliveryDataHelperMock, $this->viewModel->getDeliveryDataHelper());
    }

    /**
     * @test testIsCommercialCustomer
     */
    public function testIsCommercialCustomer()
    {
        $this->deliveryDataHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $this->assertEquals(true, $this->viewModel->isCommercialCustomer());
    }

    /**
     * @test testGetIsSdeStore
     */
    public function testGetIsSdeStore()
    {
        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);

        $this->assertEquals(true, $this->viewModel->getIsSdeStore());
    }

    /**
     * @test testGetCatalogMvpHelper
     */
    public function testGetCatalogMvpHelper()
    {
        $this->viewModel->getCatalogMvpHelper();
    }

    /**
     * Test getCharLimitToggle
     *
     * @return Int
     */
    public function testGetCharLimitToggle()
    {
        $this->fxoCMHelper->expects($this->any())->method('getCharLimitToggle')->willReturn(1);
        $this->assertNotNull($this->viewModel->getCharLimitToggle());
    }

    /**
     * TestisCatalogEllipsisControlEnabled
     *
     * @return Int
     */
    public function testisCatalogEllipsisControlEnabled()
    {
        $this->fxoCMHelper->expects($this->any())->method('isCatalogEllipsisControlEnabled')->willReturn(1);
        $this->assertNotNull($this->viewModel->isCatalogEllipsisControlEnabled());
    }

    /**
     * TestgetCatalogEllipsisControlTotalCharacters
     *
     * @return Int
     */
    public function testgetCatalogEllipsisControlTotalCharacters()
    {
        $this->fxoCMHelper->expects($this->any())->method('getCatalogEllipsisControlTotalCharacters')->willReturn(20);
        $this->assertNotNull($this->viewModel->getCatalogEllipsisControlTotalCharacters());
    }

    /**
     * TestgetCatalogEllipsisControlStartCharacters
     *
     * @return Int
     */
    public function testgetCatalogEllipsisControlStartCharacters()
    {
        $this->fxoCMHelper->expects($this->any())->method('getCatalogEllipsisControlStartCharacters')->willReturn(10);
        $this->assertNotNull($this->viewModel->getCatalogEllipsisControlStartCharacters());
    }

    /**
     * TestgetCatalogEllipsisControlEndCharacters
     *
     * @return Int
     */
    public function testgetCatalogEllipsisControlEndCharacters()
    {
        $this->fxoCMHelper->expects($this->any())->method('getCatalogEllipsisControlEndCharacters')->willReturn(10);
        $this->assertNotNull($this->viewModel->getCatalogEllipsisControlEndCharacters());
    }

    /**
     * TestgetCatalogEllipsisControlEndCharacters
     *
     * @return Int
     */
    public function testgetFixedQtyHandlerToggle()
    {
        $this->fxoCMHelper->expects($this->any())->method('getFixedQtyHandlerToggle')->willReturn(1);
        $this->assertNotNull($this->viewModel->getFixedQtyHandlerToggle());
    }
}
