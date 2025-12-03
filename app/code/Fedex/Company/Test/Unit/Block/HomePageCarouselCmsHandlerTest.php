<?php
/**
 * @category  Fedex
 * @package   Fedex_AllPrintProducts
 * @copyright Copyright (c) 2023 Fedex.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Block;

use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\Company\Block\HomepageCarouselCmsHandler;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\ResourceModel\Block as ResourceBlock;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class HomePageCarouselCmsHandlerTest extends TestCase
{
    /**
     * @var (\Magento\Cms\Model\Template\FilterProvider & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $filterProviderMock;
    /**
     * @var (\Magento\Cms\Model\BlockFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $blockFactoryMock;
    protected $additionalDataFactoryMock;
    protected $companyHelperMock;
    protected $additionalDataMock;
    protected $additionalDataCollectionMock;
    protected $homepagebanner;
    private Context $contextMock;
    private CollectionFactory $productCollectionFactoryMock;
    private StoreManagerInterface $storeManagerMock;
    private ScopeConfigInterface $scopeConfigMock;
    private ConfigInterface $configInterface;

    protected ResourceBlock $blockResourceMock;

    /**
     * @return void
     * @group allPrintProducts
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->filterProviderMock = $this->createMock(FilterProvider::class);
        $this->blockFactoryMock = $this->createMock(BlockFactory::class);
        $this->blockResourceMock = $this->createMock(ResourceBlock::class);
        $this->additionalDataFactoryMock = $this->createMock(AdditionalDataFactory::class);
        $this->companyHelperMock = $this->createMock(CompanyHelper::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->configInterface = $this->getMockForAbstractClass(ConfigInterface::class);

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCollection',
                    'addFieldToSelect',
                    'addFieldToFilter',
                    'getSize',
                    'setStoreViewId',
                    'setStoreId',
                    'setCcToken',
                    'setCcData',
                    'save',
                    'getIterator',
                    'setOrderNotes',
                    'setTermsAndConditions'
                ]
            )
            ->getMock();
        $this->additionalDataCollectionMock = $this->getMockBuilder(AdditionalDataCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'addFieldToSelect',
                    'addFieldToFilter',
                    'getFirstItem'
                ]
            )
            ->getMock();

        $this->homepagebanner = new HomepageCarouselCmsHandler(
            $this->contextMock,
            $this->filterProviderMock,
            $this->storeManagerMock,
            $this->blockFactoryMock,
            $this->additionalDataFactoryMock,
            $this->companyHelperMock,
            $this->configInterface,
            $this->blockResourceMock
        );
    }

    /**
     * @return void
     */
    public function testGetHomepageCmsBlockIdentifierFromCompany()
    {
        $this->companyHelperMock->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(89);
        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->additionalDataMock);

        $this->assertEquals(NULL, $this->homepagebanner->getHomepageCmsBlockIdentifierFromCompany());
    }

    /**
     * @return void
     */
    public function testGetHomepageCmsBannerToggle()
    {
        $this->companyHelperMock->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(89);
        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->additionalDataMock);

        $this->assertEquals(false, $this->homepagebanner->getHomepageCmsBannerToggle());
    }
}

