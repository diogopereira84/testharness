<?php

namespace Fedex\FXOCMConfigurator\Test\Unit\Controller\Batchupload;

use Magento\Framework\App\Action\Context;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\FXOCMConfigurator\Controller\Batchupload\Index;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Fedex\FXOCMConfigurator\Helper\Batchupload as BatchuploadHelper;
use Magento\Framework\Controller\Result\JsonFactory;

class IndexTest extends TestCase
{

    /**
     * @var (\Fedex\FXOCMConfigurator\Helper\Batchupload & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $batchuploadhelperMock;
    protected $request;
    protected $resultJsonFactoryMock;
    protected $indexController;
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->batchuploadhelperMock = $this->getMockBuilder(BatchuploadHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['addBatchUploadData'])
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

       $this->resultJsonFactoryMock = $this
            ->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData','create'])
            ->getMock();        


        $context = $objectManager->getObject(Context::class);

        $this->indexController = $objectManager->getObject(
            Index::class,
            [
                'batchuploadhelper' => $this->batchuploadhelperMock,
                '_request' => $this->request,
                'resultJsonFactory'=> $this->resultJsonFactoryMock
            ]
        );
    }

    /**
     * 
     */
    public function testExecute()
    {
        $data = '{
  "configuratorStateId": "01ee9808-bc37-1d50-8540-0919ea8e7062",
  "expirationDateTime": "2023-12-11T09:13:58.495Z",
  "isEditable": true,
  "product": {
    "id": 1456773326927,
    "version": 2,
    "name": "Multi Sheet",
    "qty": 1,
    "priceable": true,
    "features": [
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
        "id": 1448984679218,
        "name": "Orientation",
        "choice": {
          "id": 1449000016327,
          "name": "Horizontal",
          "properties": [
            {
              "id": 1453260266287,
              "name": "PAGE_ORIENTATION",
              "value": "LANDSCAPE"
            }
          ]
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
          "id": 1448988664295,
          "name": "Laser (32 lb.)",
          "properties": [
            {
              "id": 1450324098012,
              "name": "MEDIA_TYPE",
              "value": "E32"
            },
            {
              "id": 1453234015081,
              "name": "PAPER_COLOR",
              "value": "#FFFFFF"
            },
            {
              "id": 1471275182312,
              "name": "MEDIA_CATEGORY",
              "value": "RESUME"
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
        "value": "50"
      },
      {
        "id": 1453894861756,
        "name": "LOCK_CONTENT_ORIENTATION",
        "value": "false"
      },
      {
        "id": 1470151626854,
        "name": "SYSTEM_SI"
      },
      {
        "id": 1454950109636,
        "name": "USER_SPECIAL_INSTRUCTIONS"
      }
    ],
    "pageExceptions": [],
    "proofRequired": false,
    "instanceId": 1702282609870,
    "userProductName": "bird",
    "inserts": [],
    "exceptions": [],
    "addOns": [],
    "contentAssociations": [
      {
        "parentContentReference": "15717557991150010790309055202901730128837",
        "contentReference": "15718023127046998870408383343790822772384",
        "contentType": "IMAGE",
        "fileSizeBytes": 0,
        "fileName": "bird.jpeg",
        "printReady": true,
        "contentReqId": 1483999952979,
        "name": "Multi Sheet",
        "purpose": "MAIN_CONTENT",
        "specialInstructions": "",
        "pageGroups": [
          {
            "start": 1,
            "end": 1,
            "width": 11,
            "height": 8.5,
            "orientation": "LANDSCAPE"
          }
        ]
      }
    ],
    "productionContentAssociations": [],
    "products": [],
    "isOutSourced": false
  },
  "integratorProductReference": "1534436209752-4-4",
  "configuratorSessionId": "01ee9809-c575-1718-b7dd-5254e7e2df12",
  "expressCheckoutButtonSelected": false,
  "errors": [],
  "userWorkspace": {
    "files": [
      {
        "fileName": "bird.jpeg",
        "documentId": "15717557991150010790309055202901730128837",
        "fileSize": 6109,
        "uploadDateTime": "2023-12-11T08:16:55.287Z"
      },
      {
        "fileName": "camera.jpeg",
        "documentId": "15718023126186712058300523700961427908047",
        "fileSize": 8879,
        "uploadDateTime": "2023-12-11T08:16:55.211Z"
      }
    ],
    "projects": [
      {
        "products": [
          {
            "id": 1456773326927,
            "version": 2,
            "name": "Multi Sheet",
            "qty": 1,
            "priceable": true,
            "features": [
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
                "id": 1448984679218,
                "name": "Orientation",
                "choice": {
                  "id": 1449000016327,
                  "name": "Horizontal",
                  "properties": [
                    {
                      "id": 1453260266287,
                      "name": "PAGE_ORIENTATION",
                      "value": "LANDSCAPE"
                    }
                  ]
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
                  "id": 1448988664295,
                  "name": "Laser (32 lb.)",
                  "properties": [
                    {
                      "id": 1450324098012,
                      "name": "MEDIA_TYPE",
                      "value": "E32"
                    },
                    {
                      "id": 1453234015081,
                      "name": "PAPER_COLOR",
                      "value": "#FFFFFF"
                    },
                    {
                      "id": 1471275182312,
                      "name": "MEDIA_CATEGORY",
                      "value": "RESUME"
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
                "value": "50"
              },
              {
                "id": 1453894861756,
                "name": "LOCK_CONTENT_ORIENTATION",
                "value": "false"
              },
              {
                "id": 1470151626854,
                "name": "SYSTEM_SI"
              },
              {
                "id": 1454950109636,
                "name": "USER_SPECIAL_INSTRUCTIONS"
              }
            ],
            "pageExceptions": [],
            "proofRequired": false,
            "instanceId": 1702282609870,
            "userProductName": "bird",
            "inserts": [],
            "exceptions": [],
            "addOns": [],
            "contentAssociations": [
              {
                "parentContentReference": "15717557991150010790309055202901730128837",
                "contentReference": "15718023127046998870408383343790822772384",
                "contentType": "IMAGE",
                "fileSizeBytes": 0,
                "fileName": "bird.jpeg",
                "printReady": true,
                "contentReqId": 1483999952979,
                "name": "Multi Sheet",
                "purpose": "MAIN_CONTENT",
                "specialInstructions": "",
                "pageGroups": [
                  {
                    "start": 1,
                    "end": 1,
                    "width": 11,
                    "height": 8.5,
                    "orientation": "LANDSCAPE"
                  }
                ]
              }
            ],
            "productionContentAssociations": [],
            "products": [],
            "isOutSourced": false
          }
        ]
      },
      {
        "products": [
          {
            "id": 1456773326927,
            "version": 2,
            "name": "Multi Sheet",
            "qty": 1,
            "priceable": true,
            "features": [
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
                "id": 1448984679442,
                "name": "Lamination",
                "choice": {
                  "id": 1448999458409,
                  "name": "None",
                  "properties": []
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
                  "id": 1448988664295,
                  "name": "Laser (32 lb.)",
                  "properties": [
                    {
                      "id": 1450324098012,
                      "name": "MEDIA_TYPE",
                      "value": "E32"
                    },
                    {
                      "id": 1453234015081,
                      "name": "PAPER_COLOR",
                      "value": "#FFFFFF"
                    },
                    {
                      "id": 1471275182312,
                      "name": "MEDIA_CATEGORY",
                      "value": "RESUME"
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
                "name": "ZOOM_PERCENTAGE"
              },
              {
                "id": 1453894861756,
                "name": "LOCK_CONTENT_ORIENTATION",
                "value": "false"
              },
              {
                "id": 1470151626854,
                "name": "SYSTEM_SI"
              },
              {
                "id": 1454950109636,
                "name": "USER_SPECIAL_INSTRUCTIONS"
              }
            ],
            "pageExceptions": [],
            "proofRequired": false,
            "instanceId": 1702282610284,
            "inserts": [],
            "exceptions": [],
            "addOns": [],
            "contentAssociations": [],
            "productionContentAssociations": [],
            "products": [],
            "isOutSourced": false
          }
        ]
      }
    ]
  }
}';
        $output = ['output'=>'sessionset'];
        $jsonOutput = json_encode($output);

       $this->resultJsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->request->expects($this->any())->method('getParam')->willReturn($data);
         $this->resultJsonFactoryMock->expects($this->any())
            ->method('setData')
            ->willReturn($jsonOutput);
        $this->assertEquals($jsonOutput, $this->indexController->execute());
    }

    
}
