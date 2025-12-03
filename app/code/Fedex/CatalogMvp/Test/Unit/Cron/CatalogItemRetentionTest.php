<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Test\Unit\Cron;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Cron\CatalogItemRetention;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Framework\DB\Select;
use Fedex\CatalogMvp\Model\ProductActivityFactory;
use Fedex\CatalogMvp\Model\ProductActivity;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\AttributeRepository;
use \Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Api\ProductRepositoryInterface;

class CatalogItemRetentionTest extends TestCase
{
    protected $toggleConfigMock;
    protected $loggerInterfaceMock;
    protected $productCollectionFactoryMock;
    protected $productCollectionMock;
    protected $productMock;
    /**
     * @var (\Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $catalogDocumentRefranceApiMock;
    protected $scopeConfigInterfaceMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    protected $dbSelectMock;
    protected $productActivityFactoryMock;
    protected $productActivityMock;
    protected $catalogMvpHelper;
    protected $catalogItemRetentionCron;
    public const JSON_RESULT='{
        "id" : 1456773326927,
        "version" : 2,
        "name" : "Multi Sheet",
        "qty" : 1,
        "priceable" : true,
        "features" : [ {
          "id" : 1448981554101,
          "name" : "Prints Per Page",
          "choice" : {
            "id" : 1448990257151,
            "name" : "One",
            "properties" : [ {
              "id" : 1455387404922,
              "name" : "PRINTS_PER_PAGE",
              "value" : "1"
            } ]
          }
        }, {
          "id" : 1448981555573,
          "name" : "Hole Punching",
          "choice" : {
            "id" : 1448999902070,
            "name" : "None",
            "properties" : [ ]
          }
        }, {
          "id" : 1448981549109,
          "name" : "Paper Size",
          "choice" : {
            "id" : 1448986650332,
            "name" : "8.5x11",
            "properties" : [ {
              "id" : 1571841122054,
              "name" : "DISPLAY_HEIGHT",
              "value" : "11"
            }, {
              "id" : 1571841164815,
              "name" : "DISPLAY_WIDTH",
              "value" : "8.5"
            }, {
              "id" : 1449069906033,
              "name" : "MEDIA_HEIGHT",
              "value" : "11"
            }, {
              "id" : 1449069908929,
              "name" : "MEDIA_WIDTH",
              "value" : "8.5"
            } ]
          }
        }, {
          "id" : 1448981549269,
          "name" : "Sides",
          "choice" : {
            "id" : 1448988124560,
            "name" : "Single-Sided",
            "properties" : [ {
              "id" : 1461774376168,
              "name" : "SIDE",
              "value" : "SINGLE"
            }, {
              "id" : 1471294217799,
              "name" : "SIDE_VALUE",
              "value" : "1"
            } ]
          }
        }, {
          "id" : 1448981554597,
          "name" : "Binding",
          "choice" : {
            "id" : 1448997199553,
            "name" : "None",
            "properties" : [ ]
          }
        }, {
          "id" : 1448984679442,
          "name" : "Lamination",
          "choice" : {
            "id" : 1448999458409,
            "name" : "None",
            "properties" : [ ]
          }
        }, {
          "id" : 1448984679218,
          "name" : "Orientation",
          "choice" : {
            "id" : 1449000016192,
            "name" : "Vertical",
            "properties" : [ {
              "id" : 1453260266287,
              "name" : "PAGE_ORIENTATION",
              "value" : "PORTRAIT"
            } ]
          }
        }, {
          "id" : 1448984877869,
          "name" : "Cutting",
          "choice" : {
            "id" : 1448999392195,
            "name" : "None",
            "properties" : [ ]
          }
        }, {
          "id" : 1448984877645,
          "name" : "Folding",
          "choice" : {
            "id" : 1448999720595,
            "name" : "None",
            "properties" : [ ]
          }
        }, {
          "id" : 1448981532145,
          "name" : "Collation",
          "choice" : {
            "id" : 1448986654687,
            "name" : "Collated",
            "properties" : [ {
              "id" : 1449069945785,
              "name" : "COLLATION_TYPE",
              "value" : "MACHINE"
            } ]
          }
        }, {
          "id" : 1448981549741,
          "name" : "Paper Type",
          "choice" : {
            "id" : 1448988661630,
            "name" : "Laser(24 lb.)",
            "properties" : [ {
              "id" : 1450324098012,
              "name" : "MEDIA_TYPE",
              "value" : "LZ"
            }, {
              "id" : 1453234015081,
              "name" : "PAPER_COLOR",
              "value" : "#FFFFFF"
            }, {
              "id" : 1471275182312,
              "name" : "MEDIA_CATEGORY",
              "value" : "PASTEL_BRIGHTS"
            } ]
          }
        }, {
          "id" : 1448981549581,
          "name" : "Print Color",
          "choice" : {
            "id" : 1448988600611,
            "name" : "Full Color",
            "properties" : [ {
              "id" : 1453242778807,
              "name" : "PRINT_COLOR",
              "value" : "COLOR"
            } ]
          }
        } ],
        "properties" : [ {
          "id" : 1453895478444,
          "name" : "MIN_DPI",
          "value" : "150.0"
        }, {
          "id" : 1455050109631,
          "name" : "DEFAULT_IMAGE_HEIGHT",
          "value" : "11"
        }, {
          "id" : 1490292304798,
          "name" : "MIGRATED_PRODUCT",
          "value" : "true"
        }, {
          "id" : 1494365340946,
          "name" : "PREVIEW_TYPE",
          "value" : "DYNAMIC"
        }, {
          "id" : 1470151737965,
          "name" : "TEMPLATE_AVAILABLE",
          "value" : "NO"
        }, {
          "id" : 1453243262198,
          "name" : "ENCODE_QUALITY",
          "value" : "100"
        }, {
          "id" : 1455050109636,
          "name" : "DEFAULT_IMAGE_WIDTH",
          "value" : "8.5"
        }, {
          "id" : 1453242488328,
          "name" : "ZOOM_PERCENTAGE",
          "value" : "60"
        }, {
          "id" : 1453894861756,
          "name" : "LOCK_CONTENT_ORIENTATION",
          "value" : "false"
        }, {
          "id" : 1470151626854,
          "name" : "SYSTEM_SI",
          "value" : null
        }, {
          "id" : 1454950109636,
          "name" : "USER_SPECIAL_INSTRUCTIONS",
          "value" : null
        } ],
        "pageExceptions" : [ ],
        "proofRequired" : false,
        "instanceId" : 0,
        "userProductName" : "Custom Product",
        "inserts" : [ ],
        "exceptions" : [ ],
        "addOns" : [ ],
        "contentAssociations" : [ {
          "parentContentReference" : "d75b02e4-d079-11ee-a21b-f9e2530a59c5",
          "contentReference" : "d7adb6c6-d079-11ee-a21b-35b0fff86765",
          "contentReplacementUrl" : null,
          "contentType" : "PDF",
          "fileSizeBytes" : 0,
          "fileName" : "2_pg.pdf",
          "printReady" : true,
          "contentReqId" : 1483999952979,
          "name" : "Multi Sheet",
          "desc" : null,
          "purpose" : "MAIN_CONTENT",
          "specialInstructions" : null,
          "pageGroups" : [ {
            "start" : 1,
            "end" : 2,
            "width" : 8.5,
            "height" : 11.0,
            "orientation" : "PORTRAIT"
          } ],
          "physicalContent" : false
        } ],
        "productionContentAssociations" : [ ],
        "catalogReference" : null,
        "products" : [ ],
        "externalSkus" : [ ],
        "vendorReference" : null,
        "isOutSourced" : false,
        "contextKeys" : [ ],
        "externalProductionDetails" : null
    }';

    protected $productRepositoryInterfaceMock;
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();
        $this->productCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->productCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToFilter','getIterator','addAttributeToSelect','getSelect'])
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExternalProd','getEntityId','getName','load','delete'])
            ->getMock();
        $this->catalogDocumentRefranceApiMock = $this->getMockBuilder(CatalogDocumentRefranceApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteProductRef'])
            ->getMock();    
        $this->scopeConfigInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();
       $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['register'])
            ->getMock();
       $this->dbSelectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['where'])
            ->getMock();
       $this->productActivityFactoryMock = $this->getMockBuilder(ProductActivityFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
      $this->productActivityMock = $this->getMockBuilder(ProductActivity::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData','save'])
            ->getMock();   
      $this->catalogMvpHelper = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttrSetIdByName'])
            ->getMockForAbstractClass();
      $this->productRepositoryInterfaceMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();      
      $objectManagerHelper = new ObjectManager($this);
        $this->catalogItemRetentionCron = $objectManagerHelper->getObject(
            CatalogItemRetention::class,
            [
                'logger' => $this->loggerInterfaceMock,
                'toggleConfig' => $this->toggleConfigMock,
                'productCollectionFactory' => $this->productCollectionFactoryMock,
                'catalogDocumentRefranceApi'=>$this->catalogDocumentRefranceApiMock,
                'scopeConfigInterface'=> $this->scopeConfigInterfaceMock,
                'registry'=> $this->registryMock,
                'productActivityFactory'=>$this->productActivityFactoryMock,
                'product'=> $this->productMock,
                'catalogMvpHelper'=> $this->catalogMvpHelper,
                'productRepositoryInterface'=>$this->productRepositoryInterfaceMock
            ]
        );
    }

    /**
     * @test Execute
     */
    public function testExecute()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->loggerInterfaceMock->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $this->productCollectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->productCollectionMock);
        $this->productCollectionMock
            ->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturnSelf();
         $this->productCollectionMock
             ->expects($this->once())
             ->method('addAttributeToSelect')
             ->willReturnSelf();    
        $this->productCollectionMock->expects($this->any())
             ->method('getSelect')
             ->willReturn($this->dbSelectMock); 
        $this->dbSelectMock->expects($this->once())
             ->method('where')
             ->willReturn($this->productCollectionMock);               
        $this->productCollectionMock->expects($this->once())
            ->method('getIterator') ->willReturn(new \ArrayIterator([$this->productMock]));
        $this->productMock
            ->expects($this->any())
            ->method('getEntityId')
            ->willReturn(12);
        $this->productMock
            ->expects($this->any())
            ->method('getName')
            ->willReturn('testname');    
        $this->scopeConfigInterfaceMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn('13');     
        $this->productMock
            ->expects($this->any())
           ->method('load')
           ->willReturnSelf();      
        $this->productMock
             ->expects($this->any())
            ->method('getExternalProd')
            ->willReturn(self::JSON_RESULT);
        $this->productMock
            ->expects($this->any())
           ->method('delete')
           ->willReturnSelf();      
        $this->productActivityFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->productActivityMock);        
        $this->productActivityMock     
            ->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->productActivityMock     
            ->expects($this->any())
            ->method('save')
            ->willReturnSelf(); 
        $this->catalogMvpHelper     
            ->expects($this->any())
            ->method('getAttrSetIdByName')
            ->willReturn('PrintOnDemand');    
        $this->productRepositoryInterfaceMock
             ->expects($this->any())
             ->method('getById')
            ->willReturn($this->productMock);         
        $result = $this->catalogItemRetentionCron->execute();
        $this->assertNull($result);
    }

     /**
     * @test Execute
     */
    public function testExecuteWithException()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->loggerInterfaceMock->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $this->productCollectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->productCollectionMock);
        $this->productCollectionMock
            ->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturnSelf();
         $this->productCollectionMock
             ->expects($this->once())
             ->method('addAttributeToSelect')
             ->willReturnSelf();    
        $this->productCollectionMock->expects($this->any())
             ->method('getSelect')
             ->willReturn($this->dbSelectMock); 
        $this->dbSelectMock->expects($this->once())
             ->method('where')
             ->willReturn($this->productCollectionMock);               
        $this->productCollectionMock->expects($this->once())
            ->method('getIterator') ->willReturn(new \ArrayIterator([$this->productMock]));
        $this->productMock
            ->expects($this->any())
            ->method('getEntityId')
            ->willReturn(12);
        $this->productMock
            ->expects($this->any())
            ->method('getName')
            ->willReturn('testname');    
        $this->scopeConfigInterfaceMock
            ->expects($this->once())
            ->method('getValue')
            ->willReturn('13');     
        $this->productMock
            ->expects($this->any())
           ->method('load')
           ->willReturnSelf();      
        $this->productMock
             ->expects($this->any())
            ->method('getExternalProd')
            ->willReturn(self::JSON_RESULT);
        $this->productMock
            ->expects($this->any())
            ->method('delete')
            ->will($this->returnCallback(function () {
             throw new \Exception('could not delete exception');
       }));          
        $this->productActivityFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->productActivityMock);        
        $this->productActivityMock     
            ->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->productActivityMock     
            ->expects($this->any())
            ->method('save')
            ->willReturnSelf();   
        $this->catalogMvpHelper     
            ->expects($this->any())
            ->method('getAttrSetIdByName')
            ->willReturn('PrintOnDemand');    
       $this->productRepositoryInterfaceMock
            ->expects($this->any())
            ->method('getById')
            ->willReturn($this->productMock);                       
        $result = $this->catalogItemRetentionCron->execute();
        $this->assertNull($result);
    }
}
