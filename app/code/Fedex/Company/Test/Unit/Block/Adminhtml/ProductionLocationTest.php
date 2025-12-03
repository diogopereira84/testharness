<?php

namespace Fedex\Company\Test\Unit\Block;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\Company\Block\Adminhtml\ProductionLocation;
use PHPUnit\Framework\TestCase;
use Fedex\Company\Model\AuthDynamicRowsFactory;
use Fedex\Company\Model\AuthDynamicRows;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Request\Http;
use Fedex\Company\Model\ResourceModel\AuthDynamicRows\Collection;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\Shipto\Model\ProductionLocation as ProductionLocationModel;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ProductionLocationTest extends TestCase
{


    protected $companyRepositoryMock;
    protected $productionLocationFactoryMock;
    protected $productionLocationMock;
    protected $productionLocationCollectionMock;
    protected $toggleConfig;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $blockProductionLocation;
    /**
     * Sample ID
     * @var int
     */
    const ID = 2;
    private const RADIUS = '15';

    /**
     * @var
     */

     protected $authDynamicRowsCollection;

    /**
     * @var
     */
    private $contextMock;

     /**
      * @var
      */
    private $authDynamicRowsFactoryMock;

    /**
     * @var
     */
    private $companyRepository;
    
    /**
     * @var
     */
    private $company;
     /**
      * @var
      */
    protected $AuthenticationRule;
    
     /**
      * @var Http|MockObject
      */
    private $requestMock;

    /**
     * @var
     */
    private $actioncontext;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productionLocationFactoryMock = $this->createMock(ProductionLocationFactory::class);//B-1326233
                                       
    
        $this->productionLocationMock = $this->getMockBuilder(ProductionLocationModel::class)
                                        ->setMethods(['getCollection', 'getData'])
                                            ->disableOriginalConstructor()
                                            ->getMock();
                                            
        $this->productionLocationCollectionMock = $this->getMockBuilder(\Fedex\Shipto\Model\ResourceModel\ProductionLocation\Collection::class)
                                        ->setMethods(['addFieldToFilter','getData'])
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->actioncontext = $this->getMockBuilder(Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this
            ->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
       
        $this->contextMock = $this->objectManager->getObject(
            Context::class,
            [
                'request' => $this->requestMock,
            ]
        );

        $this->blockProductionLocation = $this->objectManager->getObject(
            ProductionLocation::class,
            [
                'context' => $this->contextMock,
                'companyRepository' => $this->companyRepositoryMock,
                'productionlocationFactory' => $this->productionLocationFactoryMock,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * Test for displayBlock method.
     *
     * @return void
     */
    public function testDisplayBlock()
    {
        $this->requestMock->expects($this->any())->method('getParam')->with('id')->willReturn(8);
        $expected =  true;
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllowProductionLocation'])
            ->getMockForAbstractClass();
        $this->companyRepositoryMock->expects($this->any())->method('get')->with(8)->willReturn($company);
        $company->expects($this->once())->method('getAllowProductionLocation')->willReturn(1);
        $this->assertEquals($expected, $this->blockProductionLocation->displayBlock());
    }

    /**
     * Test for getCompany Data method.
     *
     * @return void
     */
    public function testGetCompanyData()
    {
        $this->requestMock->expects($this->any())->method('getParam')->with('id')->willReturn(8);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepositoryMock->expects($this->any())->method('get')->with(8)->willReturn($company);
        $this->assertEquals($company, $this->blockProductionLocation->getCompanyData());
    }

    /**
     * Test isRestrictedProductionToggleEnabled
     *
     * @return void
     */
    public function testIsRestrictedProductionToggleEnabled()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->assertEquals(true,$this->blockProductionLocation->isRestrictedProductionToggleEnabled());
    }

    /**
     * 
     * @return void
     */
    public function testGetProductionLocationCollectionForRecommended()
    {   $isRecommended = 0;
        $this->productionLocationFactoryMock->expects($this->any())->method('create')->willReturn($this->productionLocationMock);
        $this->productionLocationMock->expects($this->any())->method('getCollection')->willReturn($this->productionLocationCollectionMock);
        $this->productionLocationCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->productionLocationCollectionMock->expects($this->any())->method('getData')->willReturn(['test']);
        $this->blockProductionLocation->getProductionLocationsData(3,$isRecommended);
    }

    public function testGetRadius()
    {
        $result = $this->blockProductionLocation->getRadius();
        $this->assertEquals(self::RADIUS, $result);
    }

    /**
     * Test isLocationUiFixToggle
     *
     * @return void
     */
    public function testIsLocationUiFixToogle()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->assertEquals(true,$this->blockProductionLocation->isLocationUiFixToggle());
    }
}
