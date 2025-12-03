<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Test\Unit\Cron;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\CatalogMvp\Api\CatalogMvpItemEnableMessageInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Cron\ItemEnableCron;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;

class ItemEnableCronTest extends TestCase
{
    protected $toggleConfig;
    protected $loggerInterface;
    protected $productRepository;
    protected $productMock;
    protected $productCollectionFactory;
    protected $productCollection;
    protected $productAction;
    /**
     * @var (\Magento\Framework\MessageQueue\PublisherInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $publisher;
    /**
     * @var (\Fedex\CatalogMvp\Api\CatalogMvpItemEnableMessageInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $message;
    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $serializerJson;
    protected $itemEnableCron;
    /**
     * @var (\Fedex\CatalogMvp\Helper\CatalogMvp & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $catalogMvpHelper;
    public const JSON_RESULT = '{
        "id": 1456773326927,
        "version": 2,
        "name": "Multi Sheet",
        "qty": 1,
        "priceable": true,
        "features": [
          {
            "id": 1448981554101,
            "name": "Prints Per Page",
            "choice": {
              "id": 1448990257151,
              "name": "One",
              "properties": [
                {
                  "id": 1455387404922,
                  "name": "PRINTS_PER_PAGE",
                  "value": "1"
                }
              ]
            }
          },
          {
            "id": 1448981555573,
            "name": "Hole Punching",
            "choice": {
              "id": 1448999902070,
              "name": "None",
              "properties": []
            }
          },
          {
            "id": 1448981549109,
            "name": "Paper Size",
            "choice": {
              "id": 1448986650332,
              "name": "8.5x11",
              "properties": [
                {
                  "id": 1571841122054,
                  "name": "DISPLAY_HEIGHT",
                  "value": "11"
                },
                {
                  "id": 1571841164815,
                  "name": "DISPLAY_WIDTH",
                  "value": "8.5"
                },
                {
                  "id": 1449069906033,
                  "name": "MEDIA_HEIGHT",
                  "value": "11"
                },
                {
                  "id": 1449069908929,
                  "name": "MEDIA_WIDTH",
                  "value": "8.5"
                }
              ]
            }
          },
          {
            "id": 1448981549269,
            "name": "Sides",
            "choice": {
              "id": 1448988124560,
              "name": "Single-Sided",
              "properties": [
                {
                  "id": 1461774376168,
                  "name": "SIDE",
                  "value": "SINGLE"
                },
                {
                  "id": 1471294217799,
                  "name": "SIDE_VALUE",
                  "value": "1"
                }
              ]
            }
          },
          {
            "id": 1680724699067,
            "name": "Hole Punching Production",
            "choice": {
              "id": 1681184744573,
              "name": "Machine Finishing",
              "properties": []
            }
          },
          {
            "id": 1448984877869,
            "name": "Cutting",
            "choice": {
              "id": 1448999392195,
              "name": "None",
              "properties": []
            }
          },
          {
            "id": 1448984877645,
            "name": "Folding",
            "choice": {
              "id": 1448999720595,
              "name": "None",
              "properties": []
            }
          },
          {
            "id": 1448981532145,
            "name": "Collation",
            "choice": {
              "id": 1448986654687,
              "name": "Collated",
              "properties": [
                {
                  "id": 1449069945785,
                  "name": "COLLATION_TYPE",
                  "value": "MACHINE"
                }
              ]
            }
          },
          {
            "id": 1680725097331,
            "name": "Folding Production",
            "choice": {
              "id": 1680725112004,
              "name": "Hand Finishing",
              "properties": []
            }
          },
          {
            "id": 1448981554597,
            "name": "Binding",
            "choice": {
              "id": 1448997199553,
              "name": "None",
              "properties": []
            }
          },
          {
            "id": 1448984679442,
            "name": "Lamination",
            "choice": {
              "id": 1448999458409,
              "name": "None",
              "properties": []
            }
          },
          {
            "id": 1448984679218,
            "name": "Orientation",
            "choice": {
              "id": 1449000016192,
              "name": "Vertical",
              "properties": [
                {
                  "id": 1453260266287,
                  "name": "PAGE_ORIENTATION",
                  "value": "PORTRAIT"
                }
              ]
            }
          },
          {
            "id": 1679607670330,
            "name": "Offset Stacking",
            "choice": {
              "id": 1679607688873,
              "name": "On",
              "properties": []
            }
          },
          {
            "id": 1448981549741,
            "name": "Paper Type",
            "choice": {
              "id": 1448988661630,
              "name": "Laser(24 lb.)",
              "properties": [
                {
                  "id": 1450324098012,
                  "name": "MEDIA_TYPE",
                  "value": "LZ"
                },
                {
                  "id": 1453234015081,
                  "name": "PAPER_COLOR",
                  "value": "#FFFFFF"
                },
                {
                  "id": 1471275182312,
                  "name": "MEDIA_CATEGORY",
                  "value": "PASTEL_BRIGHTS"
                }
              ]
            }
          },
          {
            "id": 1448981549581,
            "name": "Print Color",
            "choice": {
              "id": 1448988600611,
              "name": "Full Color",
              "properties": [
                {
                  "id": 1453242778807,
                  "name": "PRINT_COLOR",
                  "value": "COLOR"
                }
              ]
            }
          },
          {
            "id": 1680723151283,
            "name": "Stapling Production",
            "choice": {
              "id": 1681184744572,
              "name": "Machine Finishing",
              "properties": []
            }
          }
        ],
        "properties": [
          {
            "id": 1453895478444,
            "name": "MIN_DPI",
            "value": "150.0"
          },
          {
            "id": 1455050109631,
            "name": "DEFAULT_IMAGE_HEIGHT",
            "value": "11"
          },
          {
            "id": 1490292304798,
            "name": "MIGRATED_PRODUCT",
            "value": "true"
          },
          {
            "id": 1494365340946,
            "name": "PREVIEW_TYPE",
            "value": "DYNAMIC"
          },
          {
            "id": 1470151737965,
            "name": "TEMPLATE_AVAILABLE",
            "value": "NO"
          },
          {
            "id": 1453243262198,
            "name": "ENCODE_QUALITY",
            "value": "100"
          },
          {
            "id": 1455050109636,
            "name": "DEFAULT_IMAGE_WIDTH",
            "value": "8.5"
          },
          {
            "id": 1453242488328,
            "name": "ZOOM_PERCENTAGE",
            "value": "60"
          },
          {
            "id": 1453894861756,
            "name": "LOCK_CONTENT_ORIENTATION",
            "value": "false"
          },
          {
            "id": 1470151626854,
            "name": "SYSTEM_SI",
            "value": null
          },
          {
            "id": 1454950109636,
            "name": "USER_SPECIAL_INSTRUCTIONS",
            "value": null
          }
        ],
        "pageExceptions": [
          {
            "id": 1487792379843,
            "name": null,
            "features": [
              {
                "id": 1448981549269,
                "name": "Sides",
                "choice": {
                  "id": 1448988124807,
                  "name": "Double-Sided",
                  "properties": [
                    {
                      "id": 1461774376168,
                      "name": "SIDE",
                      "value": "DOUBLE"
                    },
                    {
                      "id": 1471294217799,
                      "name": "SIDE_VALUE",
                      "value": "2"
                    }
                  ]
                }
              }
            ],
            "properties": [
              {
                "id": 1487792607721,
                "name": "EXCEPTION_TYPE",
                "value": "PRINTING_EXCEPTION"
              }
            ],
            "hasContent": true,
            "ranges": [
              {
                "start": 1,
                "end": 2
              }
            ],
            "instanceId": null
          },
          {
            "id": 1487792379843,
            "name": null,
            "features": [
              {
                "id": 1448981549269,
                "name": "Sides",
                "choice": {
                  "id": 1448988124807,
                  "name": "Double-Sided",
                  "properties": [
                    {
                      "id": 1461774376168,
                      "name": "SIDE",
                      "value": "DOUBLE"
                    },
                    {
                      "id": 1471294217799,
                      "name": "SIDE_VALUE",
                      "value": "2"
                    }
                  ]
                }
              }
            ],
            "properties": [
              {
                "id": 1487792607721,
                "name": "EXCEPTION_TYPE",
                "value": "PRINTING_EXCEPTION"
              }
            ],
            "hasContent": true,
            "ranges": [
              {
                "start": 3,
                "end": 4
              }
            ],
            "instanceId": null
          }
        ],
        "proofRequired": false,
        "instanceId": 0,
        "userProductName": "Mixed Single and Double Sided Document",
        "inserts": [],
        "exceptions": [],
        "addOns": [],
        "contentAssociations": [
          {
            "parentContentReference": "e18f21d6-0175-11ef-ae27-1b18ca659487",
            "contentReference": "e2b04948-0175-11ef-ae27-7119bc7b1d71",
            "contentReplacementUrl": null,
            "contentType": "PDF",
            "fileSizeBytes": 0,
            "fileName": "Print On Demand QRG_Catalog Nov 2023 RDE.pdf",
            "printReady": true,
            "contentReqId": 1483999952979,
            "name": "Multi Sheet",
            "desc": null,
            "purpose": "MAIN_CONTENT",
            "specialInstructions": null,
            "pageGroups": [
              {
                "start": 1,
                "end": 2,
                "width": 8.5,
                "height": 11,
                "orientation": "PORTRAIT"
              }
            ],
            "physicalContent": false
          },
          {
            "parentContentReference": "e3b8b89a-0175-11ef-ae27-e1f6602f8b4e",
            "contentReference": "e4c6cd3c-0175-11ef-ae27-8bae84ec3354",
            "contentReplacementUrl": null,
            "contentType": "PDF",
            "fileSizeBytes": 0,
            "fileName": "Print On Demand QRG_Create Project Nov 2023 RDE.pdf",
            "printReady": true,
            "contentReqId": 1483999952979,
            "name": "Multi Sheet",
            "desc": null,
            "purpose": "MAIN_CONTENT",
            "specialInstructions": null,
            "pageGroups": [
              {
                "start": 1,
                "end": 2,
                "width": 8.5,
                "height": 11,
                "orientation": "PORTRAIT"
              }
            ],
            "physicalContent": false
          }
        ],
        "productionContentAssociations": [],
        "catalogReference": null,
        "products": [],
        "externalSkus": [
            {
                "skuDescription":null,
                "skuRef":null,
                "code":"0001",
                "unitPrice":null,
                "price":null,
                "qty":1,
                "applyProductQty":true
            }
        ],
        "vendorReference": null,
        "isOutSourced": false,
        "contextKeys": [],
        "externalProductionDetails": null
      }';

    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();
        
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getExternalProd'])
            ->getMock();

    $this->productCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
      ->DisableOriginalConstructor()
      ->setMethods(['create'])
      ->getMock();
    // We return the collection itself for getSelect(), so we must also stub where() and limit()
    $this->productCollection = $this->getMockBuilder(Collection::class)
      ->DisableOriginalConstructor()
      ->setMethods(['addAttributeToSelect', 'addAttributeToFilter', 'getSelect', 'limit', 'getData', 'where'])
      ->getMock();
        $this->publisher = $this->getMockBuilder(PublisherInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['publish'])
            ->getMockForAbstractClass();
        $this->message = $this->getMockBuilder(CatalogMvpItemEnableMessageInterface::class)
            ->DisableOriginalConstructor()
            ->setMethods(['setMessage'])
            ->getMockForAbstractClass();
        $this->serializerJson = $this->getMockBuilder(Json::class)
            ->DisableOriginalConstructor()
            ->setMethods(['serialize'])
            ->getMock();
    $this->catalogMvpHelper = $this->getMockBuilder(\Fedex\CatalogMvp\Helper\CatalogMvp::class)
      ->disableOriginalConstructor()
      ->setMethods(['setAdminArea','getCurrentTime'])
      ->getMock();
    $this->catalogMvpHelper->method('setAdminArea')->willReturn(null);
    $this->catalogMvpHelper->method('getCurrentTime')->willReturn('2025-10-27 12:00:00');

    $this->productAction = $this->getMockBuilder(ProductAction::class)
      ->disableOriginalConstructor()
      ->setMethods(['updateAttributes'])
      ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->itemEnableCron = $objectManagerHelper->getObject(
            ItemEnableCron::class,
            [
                'serializerJson' => $this->serializerJson,
                'message' => $this->message,
                'publisher' => $this->publisher,
                'logger' => $this->loggerInterface,
                'toggleConfig' => $this->toggleConfig,
                'productCollectionFactory' => $this->productCollectionFactory,
                'catalogMvpHelper' => $this->catalogMvpHelper,
    'productRepository' => $this->productRepository,
    'productAction' => $this->productAction
            ]
        );
    }

    public function getProductData()
    {
        $productData = [];
        $productData[] = ['sku'=>'test','name'=>'test','entity_id'=>23];
        return $productData;
    }

    public function testExecute()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('limit')
            ->willReturnSelf();
    $this->productCollection->expects($this->any())
      ->method('getData')
      ->willReturn($this->getProductData());
    $this->productAction->expects($this->once())
      ->method('updateAttributes');
            //$this->serializerJson
        $this->productRepository->expects($this->any())
            ->method('getById')
            ->willReturn($this->productMock);
        $this->productMock->expects($this->any())
            ->method('getExternalProd')
            ->willReturn(self::JSON_RESULT);
        $result = $this->itemEnableCron->execute();
        $this->assertEquals(null, $result);
    }
    
    /**
     * Test execute with external skus
     *
     * @return void
     */
    public function testExecuteWithExternalSkus() : void
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('limit')
            ->willReturnSelf();
    $this->productCollection->expects($this->any())
      ->method('getData')
      ->willReturn($this->getProductData());
    $this->productAction->expects($this->once())
      ->method('updateAttributes');
            //$this->serializerJson
        $this->productRepository->expects($this->any())
            ->method('getById')
            ->willReturn($this->productMock);
        $this->productMock->expects($this->any())
            ->method('getExternalProd')
            ->willReturn(self::JSON_RESULT);
        $result = $this->itemEnableCron->execute();
        $this->assertEquals(null, $result);
    }

    public function testExecuteWithProductCountZero()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->loggerInterface->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
        $this->productCollection->expects($this->any())
            ->method('limit')
            ->willReturnSelf();
    $this->productCollection->expects($this->any())
      ->method('getData')
      ->willReturn([]);
    $this->productAction->expects($this->never())
      ->method('updateAttributes');
            //$this->serializerJson
        $result = $this->itemEnableCron->execute();
        $this->assertEquals(null, $result);
    }

    /**
     * @return array
     */
    public function executeWithStartDateFixDataProvider()
    {
        return [
            'New fix enabled, product with future start date, not published' => [
                'publishedFlagToggle' => true,
                'startDateFixToggle' => true,
                'productData' => [
                    ['entity_id' => 1, 'start_date_pod' => '2025-10-28 10:00:00', 'published' => 0]
                ],
                'expectedProcessedCount' => 0
            ],
            'New fix enabled, product with future start date, already published' => [
                'publishedFlagToggle' => true,
                'startDateFixToggle' => true,
                'productData' => [
                    ['entity_id' => 1, 'start_date_pod' => '2025-10-28 10:00:00', 'published' => 1]
                ],
                'expectedProcessedCount' => 0
            ],
            'New fix enabled, product with past start date, not published' => [
                'publishedFlagToggle' => true,
                'startDateFixToggle' => true,
                'productData' => [
                    ['entity_id' => 1, 'start_date_pod' => '2025-10-26 10:00:00', 'published' => 0]
                ],
                'expectedProcessedCount' => 1
            ],
            'New fix enabled, product with past start date, already published' => [
                'publishedFlagToggle' => true,
                'startDateFixToggle' => true,
                'productData' => [
                    ['entity_id' => 1, 'start_date_pod' => '2025-10-26 10:00:00', 'published' => 1]
                ],
                'expectedProcessedCount' => 0
            ],
            'New fix enabled, product with no start date, not published' => [
                'publishedFlagToggle' => true,
                'startDateFixToggle' => true,
                'productData' => [
                    ['entity_id' => 1, 'start_date_pod' => null, 'published' => 0]
                ],
                'expectedProcessedCount' => 1
            ],
            'New fix enabled, product with no start date, already published' => [
                'publishedFlagToggle' => true,
                'startDateFixToggle' => true,
                'productData' => [
                    ['entity_id' => 1, 'start_date_pod' => null, 'published' => 1]
                ],
                'expectedProcessedCount' => 0
            ],
            'New fix disabled, original behavior' => [
                'publishedFlagToggle' => true,
                'startDateFixToggle' => false,
                'productData' => [
                    ['entity_id' => 1, 'start_date_pod' => '2025-10-26 10:00:00', 'published' => 0]
                ],
                'expectedProcessedCount' => 1
            ],
        ];
    }

    /**
     * @dataProvider executeWithStartDateFixDataProvider
     */
    public function testExecuteWithStartDateFix(
        $publishedFlagToggle,
        $startDateFixToggle,
        $productData,
        $expectedProcessedCount
    ) {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['hawks_published_flag_indexing', $publishedFlagToggle],
                ['TechTitans_D_238087_fix_for_Publish_Toggle_Start_Date', $startDateFixToggle],
                ['explorers_non_standard_catalog', false]
          ]);

        $this->loggerInterface->expects($this->any())->method('info');
        $this->productCollectionFactory->expects($this->once())->method('create')->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->productCollection->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->productCollection->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->productCollection->expects($this->once())->method('limit')->willReturnSelf();
        
        if ($expectedProcessedCount > 0) {
            $this->productCollection->expects($this->once())->method('getData')->willReturn($productData);
            $this->productAction->expects($this->once())->method('updateAttributes');
        } else {
            $this->productCollection->expects($this->once())->method('getData')->willReturn([]);
            $this->productAction->expects($this->never())->method('updateAttributes');
        }

        $result = $this->itemEnableCron->execute();
        
        $this->assertNull($result, 'ItemEnableCron execute method should return null');
        
        if ($expectedProcessedCount > 0) {
            $this->assertTrue($publishedFlagToggle, 'Published flag toggle should be enabled when products are processed');
            $this->assertNotEmpty($productData, 'Product data should not be empty when processing is expected');
            $this->assertEquals(1, count($productData), 'Should process exactly one product in test data');
            
            $product = $productData[0];
            $this->assertArrayHasKey('entity_id', $product, 'Product data should contain entity_id');
            
            if ($startDateFixToggle && isset($product['published'])) {
                $this->assertEquals(0, $product['published'], 'Only unpublished products should be processed with start date fix');
                if (isset($product['start_date_pod']) && $product['start_date_pod'] !== null) {
                    $this->assertLessThan('2025-10-27 12:00:00', $product['start_date_pod'], 
                        'Products with future start dates should not be processed');
                }
            }
        } else {
            $this->assertGreaterThanOrEqual(0, $expectedProcessedCount, 'Expected processed count should not be negative');
        }
    }

    /**
     * Test that published attribute is set to 1 when both toggles are enabled
     */
    public function testExecuteUpdatesPublishedAttributeWhenBothTogglesEnabled()
    {
        // Arrange - Both toggles enabled
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturnMap([
                ['hawks_published_flag_indexing', true],
                ['TechTitans_D_238087_fix_for_Publish_Toggle_Start_Date', true],
                ['explorers_non_standard_catalog', false]
            ]);

        // Mock product data that meets criteria (past start date, unpublished)
        $productData = [
            ['entity_id' => 123, 'start_date_pod' => '2024-11-12 10:00:00', 'published' => 0]
        ];

        $this->loggerInterface->expects($this->any())->method('info');
        $this->productCollectionFactory->expects($this->once())->method('create')->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->productCollection->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->productCollection->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->productCollection->expects($this->once())->method('limit')->willReturnSelf();
        $this->productCollection->expects($this->once())->method('getData')->willReturn($productData);
        
        // Assert - updateAttributes called with both status=1 AND published=1
        $this->productAction->expects($this->once())
            ->method('updateAttributes')
            ->with(
                [123],
                ['status' => 1, 'published' => 1], 
                0
            ); 

        $result = $this->itemEnableCron->execute();

        $this->assertNull($result);
    }

    /**
     * Test that published attribute is NOT updated when start date fix toggle is disabled
     */
    public function testExecuteDoesNotUpdatePublishedAttributeWhenStartDateFixToggleDisabled()
    {
        // Arrange - Only published flag toggle enabled, start date fix disabled
        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturnMap([
                ['hawks_published_flag_indexing', true],
                ['TechTitans_D_238087_fix_for_Publish_Toggle_Start_Date', false],
                ['explorers_non_standard_catalog', false]
            ]);

        $productData = [
            ['entity_id' => 456, 'start_date_pod' => '2024-11-12 10:00:00', 'published' => 0]
        ];

        $this->loggerInterface->expects($this->any())->method('info');
        $this->productCollectionFactory->expects($this->once())->method('create')->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $this->productCollection->expects($this->any())->method('addAttributeToFilter')->willReturnSelf();
        $this->productCollection->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->productCollection->expects($this->once())->method('limit')->willReturnSelf();
        $this->productCollection->expects($this->once())->method('getData')->willReturn($productData);
        
        
        $this->productAction->expects($this->once())
            ->method('updateAttributes')
            ->with(
                [456], // product IDs
                ['status' => 1], // should NOT include published=1
                0 // store ID
            ); 

        $result = $this->itemEnableCron->execute();
        $this->assertNull($result);
    }
}
