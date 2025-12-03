<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CmsImportExport\Test\Unit\Block\Adminhtml;

use \Magento\Framework\View\Element\Template\Context;
use \Magento\Cms\Api\PageRepositoryInterface;
use \Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Cms\Api\Data\PageSearchResultsInterface;
use \Magento\Cms\Api\Data\BlockSearchResultsInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Fedex\CmsImportExport\Block\Adminhtml\CmsData;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test class for Fedex\Shipment\Helper\ShipmentEmail
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CmsDataTest extends TestCase
{

    protected $contextMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $cmsData;
    /**
     * @var PageRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $pageRepositoryInterface;

    /**
     * @var BlockRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $blockRepositoryInterface;

    /**
     * @var \Magento\PageBuilder\Model\ResourceModel\Template\Grid\CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $pagebuilderCollectionFactory;

    /**
     * @var \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $widgetCollectionFactory;

    /**
     * @var SearchCriteriaBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $managerInterface;

    /**
     * @var UrlInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $urlBuilder;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageRepositoryInterface = $this->getMockBuilder(PageRepositoryInterface::class)
        ->setMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->blockRepositoryInterface = $this->getMockBuilder(BlockRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pagebuilderCollectionFactory =
            $this->getMockBuilder(\Magento\PageBuilder\Model\ResourceModel\Template\Grid\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->widgetCollectionFactory =
            $this->getMockBuilder(\Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['addFilter', 'create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->managerInterface = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
			->getMockForAbstractClass();

        $this->contextMock->expects($this->once())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilder);

        $this->objectManager = new ObjectManager($this);

        $this->cmsData = $this->objectManager->getObject(
            CmsData::class,
            [
                'context' => $this->contextMock,
                'pageRepositoryInterface' => $this->pageRepositoryInterface,
                'blockRepositoryInterface' => $this->blockRepositoryInterface,
                'pagebuilderCollectionFactory' => $this->pagebuilderCollectionFactory,
                'widgetCollectionFactory' => $this->widgetCollectionFactory,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'managerInterface' => $this->managerInterface,
                'urlBuilder' => $this->urlBuilder,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test getPages function
     */
    public function testGetPages()
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $itemsCollection = $this->getMockBuilder(PageSearchResultsInterface::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $itemsCollection->expects($this->once())->method('getItems')->willReturnSelf();
        $this->pageRepositoryInterface->expects($this->once())->method('getList')->willReturn($itemsCollection);

        $this->assertNotEmpty($this->cmsData->getPages());
    }

    /**
     * Test getBlocks function
     */
    public function testGetBlocks()
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $itemsCollection = $this->getMockBuilder(BlockSearchResultsInterface::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $itemsCollection->expects($this->once())->method('getItems')->willReturnSelf();
        $this->blockRepositoryInterface->expects($this->once())->method('getList')->willReturn($itemsCollection);

        $this->assertNotEmpty($this->cmsData->getBlocks());
    }

    /**
     * Test getPageTemplate function
     */
    public function testGetTemplate()
    {
        $this->pagebuilderCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->assertEquals(null, $this->cmsData->getPageTemplate());
    }

    /**
     * Test getInstanceWidget function
     */
    public function testGetInstanceWidget()
    {
        $this->widgetCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();
    
        $this->assertEquals(null, $this->cmsData->getInstanceWidget());
    }

    /**
     * Test exportSuccessMessage function
     */
    public function testExportSuccessMessage()
    {
        $this->managerInterface->expects($this->any())
            ->method('addSuccess')
            ->willReturn(" are export successfully");

        $this->assertEquals(null, $this->cmsData->exportSuccessMessage());
    }

    /**
     * Test exportErrorMessage function
     */
    public function testExportErrorMessage()
    {
        $this->managerInterface->expects($this->any())
            ->method('addError')
            ->willReturn(" export was unsuccessful.");
    
        $this->assertEquals(null, $this->cmsData->exportErrorMessage());
    }

    /**
     * Test getSaveUrl function
     */
    public function testGetSaveUrl()
    {
        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with('importexportcms/cmsexport/export/')
            ->willReturn("importexportcms/cmsexport/export/");

        $this->assertEquals("importexportcms/cmsexport/export/", $this->cmsData->getSaveUrl());
    }
}
