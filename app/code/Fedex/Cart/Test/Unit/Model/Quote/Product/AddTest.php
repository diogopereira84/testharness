<?php

/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Eduardo Diogo Dias <edias@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\Product;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\Cart\Model\Quote\Product\ContentAssociationsResolver;
use Fedex\ProductBundle\ViewModel\BundleProductHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\FXOPricing\Helper\FXORate;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Fedex\Cart\Model\BuyRequestBuilder;
use Magento\Quote\Model\Quote\Item\Option;
use Fedex\Cart\Model\Quote\Product\Add;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Quote\Api\Data\CartItemExtensionInterface;
use Fedex\Cart\Api\Data\CartIntegrationItemInterface;
use Fedex\FXOCMConfigurator\Helper\Data;
use \Psr\Log\LoggerInterface;

class AddTest extends TestCase
{
   /**
    * @var Add
    */
    protected $itemMock;

   /**
    * @var MockObject|SerializerInterface
    */
    protected $quoteMock;

   /**
    * @var MockObject|ProductRepositoryInterface
    */
    protected $itemOptionMock;

   /**
    * @var MockObject|BuyRequestBuilder
    */
    protected $productMock;

   /**
    * @var MockObject|Option
    */
    protected $toggleConfig;

   /**
    * @var MockObject|FXORate
    */
    protected $fxocmhelper;

   /**
    * @var MockObject|RequestInterface
    */
    protected $requestQueryValidator;

   /**
    * @var (\Fedex\Cart\Model\Quote\Product\ContentAssociationsResolver & \PHPUnit\Framework\MockObject\MockObject)
    */
    protected $contentAssociationsResolver;

   /**
    * @var MockObject|Data
    */
    protected $addMock;

   /**
    * @var MockObject|CartItemExtensionInterface
    */
    protected $cartItemExtensionMock;

   /**
    * @var MockObject|CartIntegrationItemInterface
    */
    protected $cartIntegrationItemMock;

   /**
    * @var MockObject|ToggleConfig
    */
    protected $thirdParty;

   /**
    * @var MockObject|LoggerInterface
    */
    protected $loggerInterface;

    public const ITEM_DATA = '{
        "fxoMenuId": "1614105200640-4",
        "fxoProductInstance": {
           "id": "1641146269419",
           "name": "Flyers",
           "productConfig": {
              "product": {
                 "productionContentAssociations": [],
                 "userProductName": "Flyers",
                 "id": "1463680545590",
                 "version": 1,
                 "name": "Flyer",
                 "qty": 50,
                 "priceable": true,
                 "instanceId": 1641146269419,
                 "proofRequired": false,
                 "isOutSourced": false,
                 "features": [
                    {
                       "id": "1448981549109",
                       "name": "Paper Size",
                       "choice": {
                          "id": "1448986650332",
                          "name": "8.5x11",
                          "properties": [
                             {
                                "id": "1449069906033",
                                "name": "MEDIA_HEIGHT",
                                "value": "11"
                             },
                             {
                                "id": "1449069908929",
                                "name": "MEDIA_WIDTH",
                                "value": "8.5"
                             },
                             {
                                "id": "1571841122054",
                                "name": "DISPLAY_HEIGHT",
                                "value": "11"
                             },
                             {
                                "id": "1571841164815",
                                "name": "DISPLAY_WIDTH",
                                "value": "8.5"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448981549581",
                       "name": "Print Color",
                       "choice": {
                          "id": "1448988600611",
                          "name": "Full Color",
                          "properties": [
                             {
                                "id": "1453242778807",
                                "name": "PRINT_COLOR",
                                "value": "COLOR"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448981549269",
                       "name": "Sides",
                       "choice": {
                          "id": "1448988124560",
                          "name": "Single-Sided",
                          "properties": [
                             {
                                "id": "1470166759236",
                                "name": "SIDE_NAME",
                                "value": "Single Sided"
                             },
                             {
                                "id": "1461774376168",
                                "name": "SIDE",
                                "value": "SINGLE"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448984679218",
                       "name": "Orientation",
                       "choice": {
                          "id": "1449000016327",
                          "name": "Horizontal",
                          "properties": [
                             {
                                "id": "1453260266287",
                                "name": "PAGE_ORIENTATION",
                                "value": "LANDSCAPE"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448981549741",
                       "name": "Paper Type",
                       "choice": {
                          "id": "1448988664295",
                          "name": "Laser(32 lb.)",
                          "properties": [
                             {
                                "id": "1450324098012",
                                "name": "MEDIA_TYPE",
                                "value": "E32"
                             },
                             {
                                "id": "1453234015081",
                                "name": "PAPER_COLOR",
                                "value": "#FFFFFF"
                             },
                             {
                                "id": "1470166630346",
                                "name": "MEDIA_NAME",
                                "value": "32lb"
                             },
                             {
                                "id": "1471275182312",
                                "name": "MEDIA_CATEGORY",
                                "value": "RESUME"
                             }
                          ]
                       }
                    }
                 ],
                 "pageExceptions": [],
                 "contentAssociations": [
                    {
                       "parentContentReference": "12902125413169047140007771472401978681858",
                       "contentReference": "12901703829109282057207386197891193197015",
                       "contentType": "IMAGE",
                       "fileName": "nature1.jpeg",
                       "contentReqId": "1455709847200",
                       "name": "Front_Side",
                       "desc": null,
                       "purpose": "SINGLE_SHEET_FRONT",
                       "specialInstructions": "",
                       "printReady": true,
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
                 "properties": [
                    {
                       "id": "1453242488328",
                       "name": "ZOOM_PERCENTAGE",
                       "value": "50"
                    },
                    {
                       "id": "1453243262198",
                       "name": "ENCODE_QUALITY",
                       "value": "100"
                    },
                    {
                       "id": "1453894861756",
                       "name": "LOCK_CONTENT_ORIENTATION",
                       "value": false
                    },
                    {
                       "id": "1453895478444",
                       "name": "MIN_DPI",
                       "value": "150.0"
                    },
                    {
                       "id": "1454950109636",
                       "name": "USER_SPECIAL_INSTRUCTIONS",
                       "value": null
                    },
                    {
                       "id": "1455050109636",
                       "name": "DEFAULT_IMAGE_WIDTH",
                       "value": "8.5"
                    },
                    {
                       "id": "1455050109631",
                       "name": "DEFAULT_IMAGE_HEIGHT",
                       "value": "11"
                    },
                    {
                       "id": "1464709502522",
                       "name": "PRODUCT_QTY_SET",
                       "value": "50"
                    },
                    {
                       "id": "1459784717507",
                       "name": "SKU",
                       "value": "40005"
                    },
                    {
                       "id": "1470151626854",
                       "name": "SYSTEM_SI",
                       "value": "40005"
                    },
                    {
                       "id": "1494365340946",
                       "name": "PREVIEW_TYPE",
                       "value": "DYNAMIC"
                    },
                    {
                       "id": "1470151737965",
                       "name": "TEMPLATE_AVAILABLE",
                       "value": "YES"
                    },
                    {
                       "id": "1459784776049",
                       "name": "PRICE",
                       "value": null
                    },
                    {
                       "id": "1490292304798",
                       "name": "MIGRATED_PRODUCT",
                       "value": "true"
                    },
                    {
                       "id": "1558382273340",
                       "name": "PNI_TEMPLATE",
                       "value": "NO"
                    },
                    {
                       "id": "1602530744589",
                       "name": "CONTROL_ID",
                       "value": "4"
                    }
                 ]
              },
              "productPresetId": "1602518818916",
              "fileCreated": "2022-01-02T17:58:49.452Z"
           },
           "productRateTotal": {
              "unitPrice": null,
              "currency": "USD",
              "quantity": 50,
              "price": "$34.99",
              "priceAfterDiscount": "$34.99",
              "unitOfMeasure": "EACH",
              "totalDiscount": "$0.00",
              "productLineDetails": [
                 {
                    "detailCode": "40005",
                    "description": "Full Pg Clr Flyr 50",
                    "detailCategory": "PRINTING",
                    "unitQuantity": 1,
                    "detailPrice": "$34.99",
                    "detailDiscountPrice": "$0.00",
                    "detailUnitPrice": "$34.9900",
                    "detailDiscountedUnitPrice": "$0.00"
                 }
              ]
           },
           "isUpdateButtonVisible": false,
           "link": {
              "href": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHYAAABbCAYAgg=="
           },
           "quantityChoices": [
              "50",
              "100",
              "250",
              "500",
              "1000"
           ],
           "isEditable": true,
           "isEdited": false,
           "fileManagementState": {
              "availableFileItems": [
                 {
                    "file": {},
                    "fileItem": {
                       "fileId": "12902125413169047140007771472401978681858",
                       "fileName": "nature1.jpeg",
                       "fileExtension": "jpeg",
                       "fileSize": 543986,
                       "createdTimestamp": "2022-01-02T17:58:55.914Z"
                    },
                    "uploadStatus": "Success",
                    "errorMsg": "",
                    "uploadProgressPercentage": 100,
                    "uploadProgressBytesLoaded": 544177,
                    "selected": false,
                    "httpRsp": {
                       "successful": true,
                       "output": {
                          "document": {
                             "documentId": "12902125413169047140007771472401978681858",
                             "documentName": "nature1.jpeg",
                             "documentSize": 543984,
                             "printReady": false
                          }
                       }
                    }
                 }
              ],
              "projects": [
                 {
                    "fileItems": [
                       {
                          "uploadStatus": "Success",
                          "errorMsg": "",
                          "selected": false,
                          "originalFileItem": {
                             "fileId": "12902125413169047140007771472401978681858",
                             "fileName": "nature1.jpeg",
                             "fileExtension": "jpeg",
                             "fileSize": 543986,
                             "createdTimestamp": "2022-01-02T17:58:55.914Z"
                          },
                          "convertStatus": "Success",
                          "convertedFileItem": {
                             "fileId": "12901703829109282057207386197891193197015",
                             "fileName": "nature1.jpeg",
                             "fileExtension": "pdf",
                             "fileSize": 546132,
                             "createdTimestamp": "2022-01-02T17:58:58.708Z",
                             "numPages": 1
                          },
                          "orientation": "LANDSCAPE",
                          "conversionResult": {
                             "parentDocumentId": "12902125413169047140007771472401978681858",
                             "originalDocumentName": "nature1.jpeg",
                             "printReadyFlag": true,
                             "previewURI": "preview",
                             "documentSize": 546132,
                             "documentType": "IMAGE",
                             "lowResImage": true,
                             "documentId": "12901703829109282057207386197891193197015",
                             "metrics": {
                                "pageCount": 1,
                                "pageGroups": [
                                   {
                                      "startPageNum": 1,
                                      "endPageNum": 1,
                                      "pageWidthInches": 11,
                                      "pageHeightInches": 8.5
                                   }
                                ]
                             }
                          },
                          "contentAssociation": {
                             "parentContentReference": "12902125413169047140007771472401978681858",
                             "contentReference": "12901703829109282057207386197891193197015",
                             "contentType": "IMAGE",
                             "fileSizeBytes": "546132",
                             "fileName": "nature1.jpeg",
                             "printReady": true,
                             "pageGroups": [
                                {
                                   "start": 1,
                                   "end": 1,
                                   "width": 11,
                                   "height": 8.5,
                                   "orientation": "LANDSCAPE"
                                }
                             ],
                             "contentReqId": "1455709847200",
                             "name": "Front_Side",
                             "desc": null,
                             "purpose": "SINGLE_SHEET_FRONT",
                             "specialInstructions": ""
                          }
                       }
                    ],
                    "projectName": "Flyers",
                    "productId": "1463680545590",
                    "productPresetId": "1602518818916",
                    "productVersion": null,
                    "controlId": "4",
                    "maxFiles": 2,
                    "productType": "Flyers",
                    "availableSizes": "8.5\"x11\"",
                    "convertStatus": "Success",
                    "showInList": true,
                    "firstInList": false,
                    "accordionOpen": true,
                    "needsToBeConverted": false,
                    "selected": false,
                    "mayContainUserSelections": false,
                    "supportedProductSizes": {
                       "featureId": "1448981549109",
                       "featureName": "Size",
                       "choices": [
                          {
                             "choiceId": "1448986650332",
                             "choiceName": "8.5\"x11\"",
                             "properties": [
                                {
                                   "name": "MEDIA_HEIGHT",
                                   "value": "11"
                                },
                                {
                                   "name": "MEDIA_WIDTH",
                                   "value": "8.5"
                                },
                                {
                                   "name": "DISPLAY_HEIGHT",
                                   "value": "11"
                                },
                                {
                                   "name": "DISPLAY_WIDTH",
                                   "value": "8.5"
                                }
                             ]
                          }
                       ]
                    },
                    "productConfig": {
                       "product": {
                          "productionContentAssociations": [],
                          "userProductName": "Flyers",
                          "id": "1463680545590",
                          "version": 1,
                          "name": "Flyer",
                          "qty": 50,
                          "priceable": true,
                          "instanceId": 1641146269419,
                          "proofRequired": false,
                          "isOutSourced": false,
                          "features": [
                             {
                                "id": "1448981549109",
                                "name": "Paper Size",
                                "choice": {
                                   "id": "1448986650332",
                                   "name": "8.5x11",
                                   "properties": [
                                      {
                                         "id": "1449069906033",
                                         "name": "MEDIA_HEIGHT",
                                         "value": "11"
                                      },
                                      {
                                         "id": "1449069908929",
                                         "name": "MEDIA_WIDTH",
                                         "value": "8.5"
                                      },
                                      {
                                         "id": "1571841122054",
                                         "name": "DISPLAY_HEIGHT",
                                         "value": "11"
                                      },
                                      {
                                         "id": "1571841164815",
                                         "name": "DISPLAY_WIDTH",
                                         "value": "8.5"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448981549581",
                                "name": "Print Color",
                                "choice": {
                                   "id": "1448988600611",
                                   "name": "Full Color",
                                   "properties": [
                                      {
                                         "id": "1453242778807",
                                         "name": "PRINT_COLOR",
                                         "value": "COLOR"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448981549269",
                                "name": "Sides",
                                "choice": {
                                   "id": "1448988124560",
                                   "name": "Single-Sided",
                                   "properties": [
                                      {
                                         "id": "1470166759236",
                                         "name": "SIDE_NAME",
                                         "value": "Single Sided"
                                      },
                                      {
                                         "id": "1461774376168",
                                         "name": "SIDE",
                                         "value": "SINGLE"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448984679218",
                                "name": "Orientation",
                                "choice": {
                                   "id": "1449000016327",
                                   "name": "Horizontal",
                                   "properties": [
                                      {
                                         "id": "1453260266287",
                                         "name": "PAGE_ORIENTATION",
                                         "value": "LANDSCAPE"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448981549741",
                                "name": "Paper Type",
                                "choice": {
                                   "id": "1448988664295",
                                   "name": "Laser(32 lb.)",
                                   "properties": [
                                      {
                                         "id": "1450324098012",
                                         "name": "MEDIA_TYPE",
                                         "value": "E32"
                                      },
                                      {
                                         "id": "1453234015081",
                                         "name": "PAPER_COLOR",
                                         "value": "#FFFFFF"
                                      },
                                      {
                                         "id": "1470166630346",
                                         "name": "MEDIA_NAME",
                                         "value": "32lb"
                                      },
                                      {
                                         "id": "1471275182312",
                                         "name": "MEDIA_CATEGORY",
                                         "value": "RESUME"
                                      }
                                   ]
                                }
                             }
                          ],
                          "pageExceptions": [],
                          "contentAssociations": [
                             {
                                "parentContentReference": "12902125413169047140007771472401978681858",
                                "contentReference": "12901703829109282057207386197891193197015",
                                "contentType": "IMAGE",
                                "fileName": "nature1.jpeg",
                                "contentReqId": "1455709847200",
                                "name": "Front_Side",
                                "desc": null,
                                "purpose": "SINGLE_SHEET_FRONT",
                                "specialInstructions": "",
                                "printReady": true,
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
                          "properties": [
                             {
                                "id": "1453242488328",
                                "name": "ZOOM_PERCENTAGE",
                                "value": "50"
                             },
                             {
                                "id": "1453243262198",
                                "name": "ENCODE_QUALITY",
                                "value": "100"
                             },
                             {
                                "id": "1453894861756",
                                "name": "LOCK_CONTENT_ORIENTATION",
                                "value": false
                             },
                             {
                                "id": "1453895478444",
                                "name": "MIN_DPI",
                                "value": "150.0"
                             },
                             {
                                "id": "1454950109636",
                                "name": "USER_SPECIAL_INSTRUCTIONS",
                                "value": null
                             },
                             {
                                "id": "1455050109636",
                                "name": "DEFAULT_IMAGE_WIDTH",
                                "value": "8.5"
                             },
                             {
                                "id": "1455050109631",
                                "name": "DEFAULT_IMAGE_HEIGHT",
                                "value": "11"
                             },
                             {
                                "id": "1464709502522",
                                "name": "PRODUCT_QTY_SET",
                                "value": "50"
                             },
                             {
                                "id": "1459784717507",
                                "name": "SKU",
                                "value": "40005"
                             },
                             {
                                "id": "1470151626854",
                                "name": "SYSTEM_SI",
                                "value": "40005"
                             },
                             {
                                "id": "1494365340946",
                                "name": "PREVIEW_TYPE",
                                "value": "DYNAMIC"
                             },
                             {
                                "id": "1470151737965",
                                "name": "TEMPLATE_AVAILABLE",
                                "value": "YES"
                             },
                             {
                                "id": "1459784776049",
                                "name": "PRICE",
                                "value": null
                             },
                             {
                                "id": "1490292304798",
                                "name": "MIGRATED_PRODUCT",
                                "value": "true"
                             },
                             {
                                "id": "1558382273340",
                                "name": "PNI_TEMPLATE",
                                "value": "NO"
                             },
                             {
                                "id": "1602530744589",
                                "name": "CONTROL_ID",
                                "value": "4"
                             }
                          ]
                       },
                       "productPresetId": "1602518818916",
                       "fileCreated": "2022-01-02T17:58:49.452Z"
                    }
                 }
              ],
              "catalogManageFilesToggle": true
           }
        },
        "productType": "PRINT_PRODUCT",
        "instanceId": null,
        "integratorProductReference":"1614105200640-4"
    }';

    public const ITEM_DATA_NEW_CONFIGURATOR = '{
      "fxoMenuId": "1614105200640-4",
      "configuratorStateId": "01ee7308-636b-1b60-a3ed-596ad079071e",
       "expirationDateTime": "2023-10-25T07:03:46.000Z",
       "integratorProductReference":"1614105200640-4",
      "customDocumentDetails": [
      {
         "documentId": "43ff7cf3-c40d-11ee-8d95-ed85716fe55f",
         "formFields": [
            {
               "fieldName": "Date",
               "fieldType": "TEXT",
               "pageNumber": 1,
               "label": "Date",
               "description": "",
               "hintText": ""
            },
            {
               "fieldName": "Recipient",
               "fieldType": "TEXT",
               "pageNumber": 1,
               "label": "Recipient",
               "description": "",
               "hintText": ""
            },
            {
               "fieldName": "Numeric_Amount",
               "fieldType": "TEXT",
               "pageNumber": 1,
               "label": "Test",
               "description": "",
               "hintText": ""
            },
            {
               "fieldName": "Written_Amount_in_words",
               "fieldType": "TEXT",
               "pageNumber": 1,
               "label": "Test2",
               "description": "",
               "hintText": ""
            },
            {
               "fieldName": "Memo_if_needed",
               "fieldType": "TEXT",
               "pageNumber": 1,
               "label": "Test 4",
               "description": "",
               "hintText": ""
            }
         ]
      }
   ],
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
                 "name": "SYSTEM_SI",
                 "value": null
               },
               {
                 "id": 1454950109636,
                 "name": "USER_SPECIAL_INSTRUCTIONS",
                 "value": null
               }
             ],
             "pageExceptions": [],
             "proofRequired": false,
             "instanceId": 1698213766324,
             "userProductName": "test_images",
             "inserts": [],
             "exceptions": [],
             "addOns": [],
             "contentAssociations": [
               {
                 "parentContentReference": "14915435772043081474214284988421662449320",
                 "contentReference": "14915435774045907879012010604621605967521",
                 "contentReplacementUrl": null,
                 "contentType": "IMAGE",
                 "fileSizeBytes": 0,
                 "fileName": "test_images.jpeg",
                 "printReady": true,
                 "contentReqId": 1483999952979,
                 "name": "Multi Sheet",
                 "desc": null,
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
             "catalogReference": null,
             "products": [],
             "externalSkus": null,
             "vendorReference": null,
             "isOutSourced": false,
             "contextKeys": null
         },
            "configuratorSessionId": "01ee7301-847c-1d88-9757-3b45878392e1",
            "expressCheckoutButtonSelected": false,
            "errors": []
   }';

    public const ITEM_DATA_IS_EDITED_FALSE = '{
        "fxoMenuId": "1614105200640-4",
        "fxoProductInstance": {
           "id": "1641146269419",
           "name": "Flyers",
           "productConfig": {
              "product": {
                 "productionContentAssociations": [],
                 "userProductName": "Flyers",
                 "id": "1463680545590",
                 "version": 1,
                 "name": "Flyer",
                 "qty": 50,
                 "priceable": true,
                 "instanceId": 1641146269419,
                 "proofRequired": false,
                 "isOutSourced": false,
                 "features": [
                    {
                       "id": "1448981549109",
                       "name": "Paper Size",
                       "choice": {
                          "id": "1448986650332",
                          "name": "8.5x11",
                          "properties": [
                             {
                                "id": "1449069906033",
                                "name": "MEDIA_HEIGHT",
                                "value": "11"
                             },
                             {
                                "id": "1449069908929",
                                "name": "MEDIA_WIDTH",
                                "value": "8.5"
                             },
                             {
                                "id": "1571841122054",
                                "name": "DISPLAY_HEIGHT",
                                "value": "11"
                             },
                             {
                                "id": "1571841164815",
                                "name": "DISPLAY_WIDTH",
                                "value": "8.5"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448981549581",
                       "name": "Print Color",
                       "choice": {
                          "id": "1448988600611",
                          "name": "Full Color",
                          "properties": [
                             {
                                "id": "1453242778807",
                                "name": "PRINT_COLOR",
                                "value": "COLOR"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448981549269",
                       "name": "Sides",
                       "choice": {
                          "id": "1448988124560",
                          "name": "Single-Sided",
                          "properties": [
                             {
                                "id": "1470166759236",
                                "name": "SIDE_NAME",
                                "value": "Single Sided"
                             },
                             {
                                "id": "1461774376168",
                                "name": "SIDE",
                                "value": "SINGLE"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448984679218",
                       "name": "Orientation",
                       "choice": {
                          "id": "1449000016327",
                          "name": "Horizontal",
                          "properties": [
                             {
                                "id": "1453260266287",
                                "name": "PAGE_ORIENTATION",
                                "value": "LANDSCAPE"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448981549741",
                       "name": "Paper Type",
                       "choice": {
                          "id": "1448988664295",
                          "name": "Laser(32 lb.)",
                          "properties": [
                             {
                                "id": "1450324098012",
                                "name": "MEDIA_TYPE",
                                "value": "E32"
                             },
                             {
                                "id": "1453234015081",
                                "name": "PAPER_COLOR",
                                "value": "#FFFFFF"
                             },
                             {
                                "id": "1470166630346",
                                "name": "MEDIA_NAME",
                                "value": "32lb"
                             },
                             {
                                "id": "1471275182312",
                                "name": "MEDIA_CATEGORY",
                                "value": "RESUME"
                             }
                          ]
                       }
                    }
                 ],
                 "pageExceptions": [],
                 "contentAssociations": [
                    {
                       "parentContentReference": "12902125413169047140007771472401978681858",
                       "contentReference": "12901703829109282057207386197891193197015",
                       "contentType": "IMAGE",
                       "fileName": "nature1.jpeg",
                       "contentReqId": "1455709847200",
                       "name": "Front_Side",
                       "desc": null,
                       "purpose": "SINGLE_SHEET_FRONT",
                       "specialInstructions": "",
                       "printReady": true,
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
                 "properties": [
                    {
                       "id": "1453242488328",
                       "name": "ZOOM_PERCENTAGE",
                       "value": "50"
                    },
                    {
                       "id": "1453243262198",
                       "name": "ENCODE_QUALITY",
                       "value": "100"
                    },
                    {
                       "id": "1453894861756",
                       "name": "LOCK_CONTENT_ORIENTATION",
                       "value": false
                    },
                    {
                       "id": "1453895478444",
                       "name": "MIN_DPI",
                       "value": "150.0"
                    },
                    {
                       "id": "1454950109636",
                       "name": "USER_SPECIAL_INSTRUCTIONS",
                       "value": null
                    },
                    {
                       "id": "1455050109636",
                       "name": "DEFAULT_IMAGE_WIDTH",
                       "value": "8.5"
                    },
                    {
                       "id": "1455050109631",
                       "name": "DEFAULT_IMAGE_HEIGHT",
                       "value": "11"
                    },
                    {
                       "id": "1464709502522",
                       "name": "PRODUCT_QTY_SET",
                       "value": "50"
                    },
                    {
                       "id": "1459784717507",
                       "name": "SKU",
                       "value": "40005"
                    },
                    {
                       "id": "1470151626854",
                       "name": "SYSTEM_SI",
                       "value": "40005"
                    },
                    {
                       "id": "1494365340946",
                       "name": "PREVIEW_TYPE",
                       "value": "DYNAMIC"
                    },
                    {
                       "id": "1470151737965",
                       "name": "TEMPLATE_AVAILABLE",
                       "value": "YES"
                    },
                    {
                       "id": "1459784776049",
                       "name": "PRICE",
                       "value": null
                    },
                    {
                       "id": "1490292304798",
                       "name": "MIGRATED_PRODUCT",
                       "value": "true"
                    },
                    {
                       "id": "1558382273340",
                       "name": "PNI_TEMPLATE",
                       "value": "NO"
                    },
                    {
                       "id": "1602530744589",
                       "name": "CONTROL_ID",
                       "value": "4"
                    }
                 ]
              },
              "productPresetId": "1602518818916",
              "fileCreated": "2022-01-02T17:58:49.452Z"
           },
           "productRateTotal": {
              "unitPrice": null,
              "currency": "USD",
              "quantity": 50,
              "price": "$34.99",
              "priceAfterDiscount": "$34.99",
              "unitOfMeasure": "EACH",
              "totalDiscount": "$0.00",
              "productLineDetails": [
                 {
                    "detailCode": "40005",
                    "description": "Full Pg Clr Flyr 50",
                    "detailCategory": "PRINTING",
                    "unitQuantity": 1,
                    "detailPrice": "$34.99",
                    "detailDiscountPrice": "$0.00",
                    "detailUnitPrice": "$34.9900",
                    "detailDiscountedUnitPrice": "$0.00"
                 }
              ]
           },
           "isUpdateButtonVisible": false,
           "link": {
              "href": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHYAAABbCAYAgg=="
           },
           "quantityChoices": [
              "50",
              "100",
              "250",
              "500",
              "1000"
           ],
           "isEditable": true,
           "isEdited": true,
           "fileManagementState": {
              "availableFileItems": [
                 {
                    "file": {},
                    "fileItem": {
                       "fileId": "12902125413169047140007771472401978681858",
                       "fileName": "nature1.jpeg",
                       "fileExtension": "jpeg",
                       "fileSize": 543986,
                       "createdTimestamp": "2022-01-02T17:58:55.914Z"
                    },
                    "uploadStatus": "Success",
                    "errorMsg": "",
                    "uploadProgressPercentage": 100,
                    "uploadProgressBytesLoaded": 544177,
                    "selected": false,
                    "httpRsp": {
                       "successful": true,
                       "output": {
                          "document": {
                             "documentId": "12902125413169047140007771472401978681858",
                             "documentName": "nature1.jpeg",
                             "documentSize": 543984,
                             "printReady": false
                          }
                       }
                    }
                 }
              ],
              "projects": [
                 {
                    "fileItems": [
                       {
                          "uploadStatus": "Success",
                          "errorMsg": "",
                          "selected": false,
                          "originalFileItem": {
                             "fileId": "12902125413169047140007771472401978681858",
                             "fileName": "nature1.jpeg",
                             "fileExtension": "jpeg",
                             "fileSize": 543986,
                             "createdTimestamp": "2022-01-02T17:58:55.914Z"
                          },
                          "convertStatus": "Success",
                          "convertedFileItem": {
                             "fileId": "12901703829109282057207386197891193197015",
                             "fileName": "nature1.jpeg",
                             "fileExtension": "pdf",
                             "fileSize": 546132,
                             "createdTimestamp": "2022-01-02T17:58:58.708Z",
                             "numPages": 1
                          },
                          "orientation": "LANDSCAPE",
                          "conversionResult": {
                             "parentDocumentId": "12902125413169047140007771472401978681858",
                             "originalDocumentName": "nature1.jpeg",
                             "printReadyFlag": true,
                             "previewURI": "preview",
                             "documentSize": 546132,
                             "documentType": "IMAGE",
                             "lowResImage": true,
                             "documentId": "12901703829109282057207386197891193197015",
                             "metrics": {
                                "pageCount": 1,
                                "pageGroups": [
                                   {
                                      "startPageNum": 1,
                                      "endPageNum": 1,
                                      "pageWidthInches": 11,
                                      "pageHeightInches": 8.5
                                   }
                                ]
                             }
                          },
                          "contentAssociation": {
                             "parentContentReference": "12902125413169047140007771472401978681858",
                             "contentReference": "12901703829109282057207386197891193197015",
                             "contentType": "IMAGE",
                             "fileSizeBytes": "546132",
                             "fileName": "nature1.jpeg",
                             "printReady": true,
                             "pageGroups": [
                                {
                                   "start": 1,
                                   "end": 1,
                                   "width": 11,
                                   "height": 8.5,
                                   "orientation": "LANDSCAPE"
                                }
                             ],
                             "contentReqId": "1455709847200",
                             "name": "Front_Side",
                             "desc": null,
                             "purpose": "SINGLE_SHEET_FRONT",
                             "specialInstructions": ""
                          }
                       }
                    ],
                    "projectName": "Flyers",
                    "productId": "1463680545590",
                    "productPresetId": "1602518818916",
                    "productVersion": null,
                    "controlId": "4",
                    "maxFiles": 2,
                    "productType": "Flyers",
                    "availableSizes": "8.5\"x11\"",
                    "convertStatus": "Success",
                    "showInList": true,
                    "firstInList": false,
                    "accordionOpen": true,
                    "needsToBeConverted": false,
                    "selected": false,
                    "mayContainUserSelections": false,
                    "supportedProductSizes": {
                       "featureId": "1448981549109",
                       "featureName": "Size",
                       "choices": [
                          {
                             "choiceId": "1448986650332",
                             "choiceName": "8.5\"x11\"",
                             "properties": [
                                {
                                   "name": "MEDIA_HEIGHT",
                                   "value": "11"
                                },
                                {
                                   "name": "MEDIA_WIDTH",
                                   "value": "8.5"
                                },
                                {
                                   "name": "DISPLAY_HEIGHT",
                                   "value": "11"
                                },
                                {
                                   "name": "DISPLAY_WIDTH",
                                   "value": "8.5"
                                }
                             ]
                          }
                       ]
                    },
                    "productConfig": {
                       "product": {
                          "productionContentAssociations": [],
                          "userProductName": "Flyers",
                          "id": "1463680545590",
                          "version": 1,
                          "name": "Flyer",
                          "qty": 50,
                          "priceable": true,
                          "instanceId": 1641146269419,
                          "proofRequired": false,
                          "isOutSourced": false,
                          "features": [
                             {
                                "id": "1448981549109",
                                "name": "Paper Size",
                                "choice": {
                                   "id": "1448986650332",
                                   "name": "8.5x11",
                                   "properties": [
                                      {
                                         "id": "1449069906033",
                                         "name": "MEDIA_HEIGHT",
                                         "value": "11"
                                      },
                                      {
                                         "id": "1449069908929",
                                         "name": "MEDIA_WIDTH",
                                         "value": "8.5"
                                      },
                                      {
                                         "id": "1571841122054",
                                         "name": "DISPLAY_HEIGHT",
                                         "value": "11"
                                      },
                                      {
                                         "id": "1571841164815",
                                         "name": "DISPLAY_WIDTH",
                                         "value": "8.5"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448981549581",
                                "name": "Print Color",
                                "choice": {
                                   "id": "1448988600611",
                                   "name": "Full Color",
                                   "properties": [
                                      {
                                         "id": "1453242778807",
                                         "name": "PRINT_COLOR",
                                         "value": "COLOR"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448981549269",
                                "name": "Sides",
                                "choice": {
                                   "id": "1448988124560",
                                   "name": "Single-Sided",
                                   "properties": [
                                      {
                                         "id": "1470166759236",
                                         "name": "SIDE_NAME",
                                         "value": "Single Sided"
                                      },
                                      {
                                         "id": "1461774376168",
                                         "name": "SIDE",
                                         "value": "SINGLE"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448984679218",
                                "name": "Orientation",
                                "choice": {
                                   "id": "1449000016327",
                                   "name": "Horizontal",
                                   "properties": [
                                      {
                                         "id": "1453260266287",
                                         "name": "PAGE_ORIENTATION",
                                         "value": "LANDSCAPE"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448981549741",
                                "name": "Paper Type",
                                "choice": {
                                   "id": "1448988664295",
                                   "name": "Laser(32 lb.)",
                                   "properties": [
                                      {
                                         "id": "1450324098012",
                                         "name": "MEDIA_TYPE",
                                         "value": "E32"
                                      },
                                      {
                                         "id": "1453234015081",
                                         "name": "PAPER_COLOR",
                                         "value": "#FFFFFF"
                                      },
                                      {
                                         "id": "1470166630346",
                                         "name": "MEDIA_NAME",
                                         "value": "32lb"
                                      },
                                      {
                                         "id": "1471275182312",
                                         "name": "MEDIA_CATEGORY",
                                         "value": "RESUME"
                                      }
                                   ]
                                }
                             }
                          ],
                          "pageExceptions": [],
                          "contentAssociations": [
                             {
                                "parentContentReference": "12902125413169047140007771472401978681858",
                                "contentReference": "12901703829109282057207386197891193197015",
                                "contentType": "IMAGE",
                                "fileName": "nature1.jpeg",
                                "contentReqId": "1455709847200",
                                "name": "Front_Side",
                                "desc": null,
                                "purpose": "SINGLE_SHEET_FRONT",
                                "specialInstructions": "",
                                "printReady": true,
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
                          "properties": [
                             {
                                "id": "1453242488328",
                                "name": "ZOOM_PERCENTAGE",
                                "value": "50"
                             },
                             {
                                "id": "1453243262198",
                                "name": "ENCODE_QUALITY",
                                "value": "100"
                             },
                             {
                                "id": "1453894861756",
                                "name": "LOCK_CONTENT_ORIENTATION",
                                "value": false
                             },
                             {
                                "id": "1453895478444",
                                "name": "MIN_DPI",
                                "value": "150.0"
                             },
                             {
                                "id": "1454950109636",
                                "name": "USER_SPECIAL_INSTRUCTIONS",
                                "value": null
                             },
                             {
                                "id": "1455050109636",
                                "name": "DEFAULT_IMAGE_WIDTH",
                                "value": "8.5"
                             },
                             {
                                "id": "1455050109631",
                                "name": "DEFAULT_IMAGE_HEIGHT",
                                "value": "11"
                             },
                             {
                                "id": "1464709502522",
                                "name": "PRODUCT_QTY_SET",
                                "value": "50"
                             },
                             {
                                "id": "1459784717507",
                                "name": "SKU",
                                "value": "40005"
                             },
                             {
                                "id": "1470151626854",
                                "name": "SYSTEM_SI",
                                "value": "40005"
                             },
                             {
                                "id": "1494365340946",
                                "name": "PREVIEW_TYPE",
                                "value": "DYNAMIC"
                             },
                             {
                                "id": "1470151737965",
                                "name": "TEMPLATE_AVAILABLE",
                                "value": "YES"
                             },
                             {
                                "id": "1459784776049",
                                "name": "PRICE",
                                "value": null
                             },
                             {
                                "id": "1490292304798",
                                "name": "MIGRATED_PRODUCT",
                                "value": "true"
                             },
                             {
                                "id": "1558382273340",
                                "name": "PNI_TEMPLATE",
                                "value": "NO"
                             },
                             {
                                "id": "1602530744589",
                                "name": "CONTROL_ID",
                                "value": "4"
                             }
                          ]
                       },
                       "productPresetId": "1602518818916",
                       "fileCreated": "2022-01-02T17:58:49.452Z"
                    }
                 }
              ],
              "catalogManageFilesToggle": true
           }
        },
        "productType": "PRINT_PRODUCT",
        "instanceId": null
    }';

    public const EDIT_ITEM_DATA = '{
        "fxoMenuId": "1614105200640-4",
        "fxoProductInstance": {
           "id": "1641146269419",
           "name": "Flyers",
           "productConfig": {
              "product": {
                 "productionContentAssociations": [],
                 "userProductName": "Flyers",
                 "id": "1463680545590",
                 "version": 1,
                 "name": "Flyer",
                 "qty": 50,
                 "priceable": true,
                 "instanceId": 1641146269419,
                 "proofRequired": false,
                 "isOutSourced": false,
                 "features": [
                    {
                       "id": "1448981549109",
                       "name": "Paper Size",
                       "choice": {
                          "id": "1448986650332",
                          "name": "8.5x11",
                          "properties": [
                             {
                                "id": "1449069906033",
                                "name": "MEDIA_HEIGHT",
                                "value": "11"
                             },
                             {
                                "id": "1449069908929",
                                "name": "MEDIA_WIDTH",
                                "value": "8.5"
                             },
                             {
                                "id": "1571841122054",
                                "name": "DISPLAY_HEIGHT",
                                "value": "11"
                             },
                             {
                                "id": "1571841164815",
                                "name": "DISPLAY_WIDTH",
                                "value": "8.5"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448981549581",
                       "name": "Print Color",
                       "choice": {
                          "id": "1448988600611",
                          "name": "Full Color",
                          "properties": [
                             {
                                "id": "1453242778807",
                                "name": "PRINT_COLOR",
                                "value": "COLOR"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448981549269",
                       "name": "Sides",
                       "choice": {
                          "id": "1448988124560",
                          "name": "Single-Sided",
                          "properties": [
                             {
                                "id": "1470166759236",
                                "name": "SIDE_NAME",
                                "value": "Single Sided"
                             },
                             {
                                "id": "1461774376168",
                                "name": "SIDE",
                                "value": "SINGLE"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448984679218",
                       "name": "Orientation",
                       "choice": {
                          "id": "1449000016327",
                          "name": "Horizontal",
                          "properties": [
                             {
                                "id": "1453260266287",
                                "name": "PAGE_ORIENTATION",
                                "value": "LANDSCAPE"
                             }
                          ]
                       }
                    },
                    {
                       "id": "1448981549741",
                       "name": "Paper Type",
                       "choice": {
                          "id": "1448988664295",
                          "name": "Laser(32 lb.)",
                          "properties": [
                             {
                                "id": "1450324098012",
                                "name": "MEDIA_TYPE",
                                "value": "E32"
                             },
                             {
                                "id": "1453234015081",
                                "name": "PAPER_COLOR",
                                "value": "#FFFFFF"
                             },
                             {
                                "id": "1470166630346",
                                "name": "MEDIA_NAME",
                                "value": "32lb"
                             },
                             {
                                "id": "1471275182312",
                                "name": "MEDIA_CATEGORY",
                                "value": "RESUME"
                             }
                          ]
                       }
                    }
                 ],
                 "pageExceptions": [],
                 "contentAssociations": [
                    {
                       "parentContentReference": "12902125413169047140007771472401978681858",
                       "contentReference": "12901703829109282057207386197891193197015",
                       "contentType": "IMAGE",
                       "fileName": "nature1.jpeg",
                       "contentReqId": "1455709847200",
                       "name": "Front_Side",
                       "desc": null,
                       "purpose": "SINGLE_SHEET_FRONT",
                       "specialInstructions": "",
                       "printReady": true,
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
                 "properties": [
                    {
                       "id": "1453242488328",
                       "name": "ZOOM_PERCENTAGE",
                       "value": "50"
                    },
                    {
                       "id": "1453243262198",
                       "name": "ENCODE_QUALITY",
                       "value": "100"
                    },
                    {
                       "id": "1453894861756",
                       "name": "LOCK_CONTENT_ORIENTATION",
                       "value": false
                    },
                    {
                       "id": "1453895478444",
                       "name": "MIN_DPI",
                       "value": "150.0"
                    },
                    {
                       "id": "1454950109636",
                       "name": "USER_SPECIAL_INSTRUCTIONS",
                       "value": null
                    },
                    {
                       "id": "1455050109636",
                       "name": "DEFAULT_IMAGE_WIDTH",
                       "value": "8.5"
                    },
                    {
                       "id": "1455050109631",
                       "name": "DEFAULT_IMAGE_HEIGHT",
                       "value": "11"
                    },
                    {
                       "id": "1464709502522",
                       "name": "PRODUCT_QTY_SET",
                       "value": "50"
                    },
                    {
                       "id": "1459784717507",
                       "name": "SKU",
                       "value": "40005"
                    },
                    {
                       "id": "1470151626854",
                       "name": "SYSTEM_SI",
                       "value": "40005"
                    },
                    {
                       "id": "1494365340946",
                       "name": "PREVIEW_TYPE",
                       "value": "DYNAMIC"
                    },
                    {
                       "id": "1470151737965",
                       "name": "TEMPLATE_AVAILABLE",
                       "value": "YES"
                    },
                    {
                       "id": "1459784776049",
                       "name": "PRICE",
                       "value": null
                    },
                    {
                       "id": "1490292304798",
                       "name": "MIGRATED_PRODUCT",
                       "value": "true"
                    },
                    {
                       "id": "1558382273340",
                       "name": "PNI_TEMPLATE",
                       "value": "NO"
                    },
                    {
                       "id": "1602530744589",
                       "name": "CONTROL_ID",
                       "value": "4"
                    }
                 ]
              },
              "productPresetId": "1602518818916",
              "fileCreated": "2022-01-02T17:58:49.452Z"
           },
           "productRateTotal": {
              "unitPrice": null,
              "currency": "USD",
              "quantity": 50,
              "price": "$34.99",
              "priceAfterDiscount": "$34.99",
              "unitOfMeasure": "EACH",
              "totalDiscount": "$0.00",
              "productLineDetails": [
                 {
                    "detailCode": "40005",
                    "description": "Full Pg Clr Flyr 50",
                    "detailCategory": "PRINTING",
                    "unitQuantity": 1,
                    "detailPrice": "$34.99",
                    "detailDiscountPrice": "$0.00",
                    "detailUnitPrice": "$34.9900",
                    "detailDiscountedUnitPrice": "$0.00"
                 }
              ]
           },
           "isUpdateButtonVisible": false,
           "link": {
              "href": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHYAAABbCAYAgg=="
           },
           "quantityChoices": [
              "50",
              "100",
              "250",
              "500",
              "1000"
           ],
           "isEditable": true,
           "isEdited": false,
           "fileManagementState": {
              "availableFileItems": [
                 {
                    "file": {},
                    "fileItem": {
                       "fileId": "12902125413169047140007771472401978681858",
                       "fileName": "nature1.jpeg",
                       "fileExtension": "jpeg",
                       "fileSize": 543986,
                       "createdTimestamp": "2022-01-02T17:58:55.914Z"
                    },
                    "uploadStatus": "Success",
                    "errorMsg": "",
                    "uploadProgressPercentage": 100,
                    "uploadProgressBytesLoaded": 544177,
                    "selected": false,
                    "httpRsp": {
                       "successful": true,
                       "output": {
                          "document": {
                             "documentId": "12902125413169047140007771472401978681858",
                             "documentName": "nature1.jpeg",
                             "documentSize": 543984,
                             "printReady": false
                          }
                       }
                    }
                 }
              ],
              "projects": [
                 {
                    "fileItems": [
                       {
                          "uploadStatus": "Success",
                          "errorMsg": "",
                          "selected": false,
                          "originalFileItem": {
                             "fileId": "12902125413169047140007771472401978681858",
                             "fileName": "nature1.jpeg",
                             "fileExtension": "jpeg",
                             "fileSize": 543986,
                             "createdTimestamp": "2022-01-02T17:58:55.914Z"
                          },
                          "convertStatus": "Success",
                          "convertedFileItem": {
                             "fileId": "12901703829109282057207386197891193197015",
                             "fileName": "nature1.jpeg",
                             "fileExtension": "pdf",
                             "fileSize": 546132,
                             "createdTimestamp": "2022-01-02T17:58:58.708Z",
                             "numPages": 1
                          },
                          "orientation": "LANDSCAPE",
                          "conversionResult": {
                             "parentDocumentId": "12902125413169047140007771472401978681858",
                             "originalDocumentName": "nature1.jpeg",
                             "printReadyFlag": true,
                             "previewURI": "preview",
                             "documentSize": 546132,
                             "documentType": "IMAGE",
                             "lowResImage": true,
                             "documentId": "12901703829109282057207386197891193197015",
                             "metrics": {
                                "pageCount": 1,
                                "pageGroups": [
                                   {
                                      "startPageNum": 1,
                                      "endPageNum": 1,
                                      "pageWidthInches": 11,
                                      "pageHeightInches": 8.5
                                   }
                                ]
                             }
                          },
                          "contentAssociation": {
                             "parentContentReference": "12902125413169047140007771472401978681858",
                             "contentReference": "12901703829109282057207386197891193197015",
                             "contentType": "IMAGE",
                             "fileSizeBytes": "546132",
                             "fileName": "nature1.jpeg",
                             "printReady": true,
                             "pageGroups": [
                                {
                                   "start": 1,
                                   "end": 1,
                                   "width": 11,
                                   "height": 8.5,
                                   "orientation": "LANDSCAPE"
                                }
                             ],
                             "contentReqId": "1455709847200",
                             "name": "Front_Side",
                             "desc": null,
                             "purpose": "SINGLE_SHEET_FRONT",
                             "specialInstructions": ""
                          }
                       }
                    ],
                    "projectName": "Flyers",
                    "productId": "1463680545590",
                    "productPresetId": "1602518818916",
                    "productVersion": null,
                    "controlId": "4",
                    "maxFiles": 2,
                    "productType": "Flyers",
                    "availableSizes": "8.5\"x11\"",
                    "convertStatus": "Success",
                    "showInList": true,
                    "firstInList": false,
                    "accordionOpen": true,
                    "needsToBeConverted": false,
                    "selected": false,
                    "mayContainUserSelections": false,
                    "supportedProductSizes": {
                       "featureId": "1448981549109",
                       "featureName": "Size",
                       "choices": [
                          {
                             "choiceId": "1448986650332",
                             "choiceName": "8.5\"x11\"",
                             "properties": [
                                {
                                   "name": "MEDIA_HEIGHT",
                                   "value": "11"
                                },
                                {
                                   "name": "MEDIA_WIDTH",
                                   "value": "8.5"
                                },
                                {
                                   "name": "DISPLAY_HEIGHT",
                                   "value": "11"
                                },
                                {
                                   "name": "DISPLAY_WIDTH",
                                   "value": "8.5"
                                }
                             ]
                          }
                       ]
                    },
                    "productConfig": {
                       "product": {
                          "productionContentAssociations": [],
                          "userProductName": "Flyers",
                          "id": "1463680545590",
                          "version": 1,
                          "name": "Flyer",
                          "qty": 50,
                          "priceable": true,
                          "instanceId": 1641146269419,
                          "proofRequired": false,
                          "isOutSourced": false,
                          "features": [
                             {
                                "id": "1448981549109",
                                "name": "Paper Size",
                                "choice": {
                                   "id": "1448986650332",
                                   "name": "8.5x11",
                                   "properties": [
                                      {
                                         "id": "1449069906033",
                                         "name": "MEDIA_HEIGHT",
                                         "value": "11"
                                      },
                                      {
                                         "id": "1449069908929",
                                         "name": "MEDIA_WIDTH",
                                         "value": "8.5"
                                      },
                                      {
                                         "id": "1571841122054",
                                         "name": "DISPLAY_HEIGHT",
                                         "value": "11"
                                      },
                                      {
                                         "id": "1571841164815",
                                         "name": "DISPLAY_WIDTH",
                                         "value": "8.5"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448981549581",
                                "name": "Print Color",
                                "choice": {
                                   "id": "1448988600611",
                                   "name": "Full Color",
                                   "properties": [
                                      {
                                         "id": "1453242778807",
                                         "name": "PRINT_COLOR",
                                         "value": "COLOR"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448981549269",
                                "name": "Sides",
                                "choice": {
                                   "id": "1448988124560",
                                   "name": "Single-Sided",
                                   "properties": [
                                      {
                                         "id": "1470166759236",
                                         "name": "SIDE_NAME",
                                         "value": "Single Sided"
                                      },
                                      {
                                         "id": "1461774376168",
                                         "name": "SIDE",
                                         "value": "SINGLE"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448984679218",
                                "name": "Orientation",
                                "choice": {
                                   "id": "1449000016327",
                                   "name": "Horizontal",
                                   "properties": [
                                      {
                                         "id": "1453260266287",
                                         "name": "PAGE_ORIENTATION",
                                         "value": "LANDSCAPE"
                                      }
                                   ]
                                }
                             },
                             {
                                "id": "1448981549741",
                                "name": "Paper Type",
                                "choice": {
                                   "id": "1448988664295",
                                   "name": "Laser(32 lb.)",
                                   "properties": [
                                      {
                                         "id": "1450324098012",
                                         "name": "MEDIA_TYPE",
                                         "value": "E32"
                                      },
                                      {
                                         "id": "1453234015081",
                                         "name": "PAPER_COLOR",
                                         "value": "#FFFFFF"
                                      },
                                      {
                                         "id": "1470166630346",
                                         "name": "MEDIA_NAME",
                                         "value": "32lb"
                                      },
                                      {
                                         "id": "1471275182312",
                                         "name": "MEDIA_CATEGORY",
                                         "value": "RESUME"
                                      }
                                   ]
                                }
                             }
                          ],
                          "pageExceptions": [],
                          "contentAssociations": [
                             {
                                "parentContentReference": "12902125413169047140007771472401978681858",
                                "contentReference": "12901703829109282057207386197891193197015",
                                "contentType": "IMAGE",
                                "fileName": "nature1.jpeg",
                                "contentReqId": "1455709847200",
                                "name": "Front_Side",
                                "desc": null,
                                "purpose": "SINGLE_SHEET_FRONT",
                                "specialInstructions": "",
                                "printReady": true,
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
                          "properties": [
                             {
                                "id": "1453242488328",
                                "name": "ZOOM_PERCENTAGE",
                                "value": "50"
                             },
                             {
                                "id": "1453243262198",
                                "name": "ENCODE_QUALITY",
                                "value": "100"
                             },
                             {
                                "id": "1453894861756",
                                "name": "LOCK_CONTENT_ORIENTATION",
                                "value": false
                             },
                             {
                                "id": "1453895478444",
                                "name": "MIN_DPI",
                                "value": "150.0"
                             },
                             {
                                "id": "1454950109636",
                                "name": "USER_SPECIAL_INSTRUCTIONS",
                                "value": null
                             },
                             {
                                "id": "1455050109636",
                                "name": "DEFAULT_IMAGE_WIDTH",
                                "value": "8.5"
                             },
                             {
                                "id": "1455050109631",
                                "name": "DEFAULT_IMAGE_HEIGHT",
                                "value": "11"
                             },
                             {
                                "id": "1464709502522",
                                "name": "PRODUCT_QTY_SET",
                                "value": "50"
                             },
                             {
                                "id": "1459784717507",
                                "name": "SKU",
                                "value": "40005"
                             },
                             {
                                "id": "1470151626854",
                                "name": "SYSTEM_SI",
                                "value": "40005"
                             },
                             {
                                "id": "1494365340946",
                                "name": "PREVIEW_TYPE",
                                "value": "DYNAMIC"
                             },
                             {
                                "id": "1470151737965",
                                "name": "TEMPLATE_AVAILABLE",
                                "value": "YES"
                             },
                             {
                                "id": "1459784776049",
                                "name": "PRICE",
                                "value": null
                             },
                             {
                                "id": "1490292304798",
                                "name": "MIGRATED_PRODUCT",
                                "value": "true"
                             },
                             {
                                "id": "1558382273340",
                                "name": "PNI_TEMPLATE",
                                "value": "NO"
                             },
                             {
                                "id": "1602530744589",
                                "name": "CONTROL_ID",
                                "value": "4"
                             }
                          ]
                       },
                       "productPresetId": "1602518818916",
                       "fileCreated": "2022-01-02T17:58:49.452Z"
                    }
                 }
              ],
              "catalogManageFilesToggle": true
           }
        },
        "productType": "PRINT_PRODUCT",
        "instanceId": 57817263004095960
    }';

    public const ITEM_DETAILS = '{
        "previewUrl":"12902494030139882304209366672470447774382",
        "itemName":"Flyers",
        "fxoProduct":""
    }';

   // @codingStandardsIgnoreStart
   private const EXTERNAL_PROD_DATA = '{"external_prod":[{"productionContentAssociations":[],"userProductName":"Untitled+Design","id":"1577117409977","version":1,"name":"Invitations-Canva","qty":50,"priceable":true,"instanceId":1672743971672,"proofRequired":false,"isOutSourced":false,"features":[{"id":"1531980104656","name":"Product+Type","choice":{"id":"1533112686585","name":"Quick+Invitations+&+Announcements","properties":[]}},{"id":"1448981549109","name":"Paper+Size","choice":{"id":"1533111699862","name":"5.24x7.24","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"7.24"},{"id":"1449069908929","name":"MEDIA_WIDTH","value":"5.24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"7"},{"id":"1571841164815","name":"DISPLAY_WIDTH","value":"5"}]}},{"id":"1448981549741","name":"Paper+Type","choice":{"id":"1448997301634","name":"Gloss+Cover+(87+lb.)","properties":[{"id":"1450324098012","name":"MEDIA_TYPE","value":"CC2"},{"id":"1453234015081","name":"PAPER_COLOR","value":"#FFFFFF"}]}},{"id":"1448981549581","name":"Print+Color","choice":{"id":"1448988600611","name":"Full+Color","properties":[{"id":"1453242778807","name":"PRINT_COLOR","value":"COLOR"}]}},{"id":"1448981549269","name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168","name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016192","name":"Vertical","properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"PORTRAIT"}]}},{"id":"1534920174638","name":"Envelope","choice":{"id":"1534920308259","name":"Standard+White+Envelope","properties":[]}}],"pageExceptions":[],"contentAssociations":[{"parentContentReference":"13245794301200481943513125136730449279156","contentReference":"13245787992183015624905926624251333314256","contentType":"PDF","fileName":"Untitled+Design","contentReqId":"1455709847200","name":"Front_Side","purpose":"SINGLE_SHEET_FRONT","printReady":true,"pageGroups":[{"start":1,"end":1,"width":5.24,"height":7.24,"orientation":"PORTRAIT"}]}],"properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"60"},{"id":"1453243262198","name":"ENCODE_QUALITY","value":"10"},{"id":"1453894861756","name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},{"id":"1470151626854","name":"SYSTEM_SI","value":"ATTENTION:Use+the+following+instructions+to+produce+this+Standard+Invitation+order.THIS+IS+A+FULL+BLEED+FILE.+Define+the+bleed+area+to+1\/8th+on+all+edges.+Use+Step+&+Repeat+Template+:+12x18+4up+(5x7+Landscape\/Portrait+Card)+with+crop+marks.+Print+on+12x18+10pt.+Gloss+(CCXX2)+in+Color.+Print+quantity+:+13.+Trim+to+bleed.+Final+size+:+5x7.+Yield+=+52+pieces.+Provide+50+-+5x7+white+envelopes.+Refer+to+the+Design+and+Content+Tool+Procedures+Guide+(located+on+FedEx+One)+for+step-by-step+set+up+instructions."},{"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"5.24"},{"id":"1455050109631","name":"DEFAULT_IMAGE_HEIGHT","value":"7.24"},{"id":"1464709502522","name":"PRODUCT_QTY_SET","value":"50"},{"id":"1568041487844","name":"VENDOR_TEMPLATE","value":"YES"},{"id":"1614715469176","name":"IMPOSE_TEMPLATE_ID","value":"12"}],"preview_url":"13245787992183015624905926624251333314256","fxo_product":"{\"fxoMenuId\":\"1593103993699-4\",\"fxoProductInstance\":{\"id\":\"1672743971672\",\"name\":\"Untitled+Design\",\"productConfig\":{\"product\":{\"productionContentAssociations\":[],\"userProductName\":\"Untitled+Design\",\"id\":\"1577117409977\",\"version\":1,\"name\":\"Invitations-Canva\",\"qty\":50,\"priceable\":true,\"instanceId\":1672743971672,\"proofRequired\":false,\"isOutSourced\":false,\"features\":[{\"id\":\"1531980104656\",\"name\":\"Product+Type\",\"choice\":{\"id\":\"1533112686585\",\"name\":\"Quick+Invitations+&+Announcements\",\"properties\":[]}},{\"id\":\"1448981549109\",\"name\":\"Paper+Size\",\"choice\":{\"id\":\"1533111699862\",\"name\":\"5.24x7.24\",\"properties\":[{\"id\":\"1449069906033\",\"name\":\"MEDIA_HEIGHT\",\"value\":\"7.24\"},{\"id\":\"1449069908929\",\"name\":\"MEDIA_WIDTH\",\"value\":\"5.24\"},{\"id\":\"1571841122054\",\"name\":\"DISPLAY_HEIGHT\",\"value\":\"7\"},{\"id\":\"1571841164815\",\"name\":\"DISPLAY_WIDTH\",\"value\":\"5\"}]}},{\"id\":\"1448981549741\",\"name\":\"Paper+Type\",\"choice\":{\"id\":\"1448997301634\",\"name\":\"Gloss+Cover+(87+lb.)\",\"properties\":[{\"id\":\"1450324098012\",\"name\":\"MEDIA_TYPE\",\"value\":\"CC2\"},{\"id\":\"1453234015081\",\"name\":\"PAPER_COLOR\",\"value\":\"#FFFFFF\"}]}},{\"id\":\"1448981549581\",\"name\":\"Print+Color\",\"choice\":{\"id\":\"1448988600611\",\"name\":\"Full+Color\",\"properties\":[{\"id\":\"1453242778807\",\"name\":\"PRINT_COLOR\",\"value\":\"COLOR\"}]}},{\"id\":\"1448981549269\",\"name\":\"Sides\",\"choice\":{\"id\":\"1448988124560\",\"name\":\"Single-Sided\",\"properties\":[{\"id\":\"1461774376168\",\"name\":\"SIDE\",\"value\":\"SINGLE\"},{\"id\":\"1471294217799\",\"name\":\"SIDE_VALUE\",\"value\":\"1\"}]}},{\"id\":\"1448984679218\",\"name\":\"Orientation\",\"choice\":{\"id\":\"1449000016192\",\"name\":\"Vertical\",\"properties\":[{\"id\":\"1453260266287\",\"name\":\"PAGE_ORIENTATION\",\"value\":\"PORTRAIT\"}]}},{\"id\":\"1534920174638\",\"name\":\"Envelope\",\"choice\":{\"id\":\"1534920308259\",\"name\":\"Standard+White+Envelope\",\"properties\":[]}}],\"pageExceptions\":[],\"contentAssociations\":[{\"parentContentReference\":\"13245794301200481943513125136730449279156\",\"contentReference\":\"13245787992183015624905926624251333314256\",\"contentType\":\"PDF\",\"fileName\":\"Untitled+Design\",\"contentReqId\":\"1455709847200\",\"name\":\"Front_Side\",\"purpose\":\"SINGLE_SHEET_FRONT\",\"printReady\":true,\"pageGroups\":[{\"start\":1,\"end\":1,\"width\":5.24,\"height\":7.24,\"orientation\":\"PORTRAIT\"}]}],\"properties\":[{\"id\":\"1453242488328\",\"name\":\"ZOOM_PERCENTAGE\",\"value\":\"60\"},{\"id\":\"1453243262198\",\"name\":\"ENCODE_QUALITY\",\"value\":\"10\"},{\"id\":\"1453894861756\",\"name\":\"LOCK_CONTENT_ORIENTATION\",\"value\":true},{\"id\":\"1453895478444\",\"name\":\"MIN_DPI\",\"value\":\"150.0\"},{\"id\":\"1470151626854\",\"name\":\"SYSTEM_SI\",\"value\":\"ATTENTION:Use+the+following+instructions+to+produce+this+Standard+Invitation+order.THIS+IS+A+FULL+BLEED+FILE.+Define+the+bleed+area+to+1\\\/8th+on+all+edges.+Use+Step+&+Repeat+Template+:+12x18+4up+(5x7+Landscape\\\/Portrait+Card)+with+crop+marks.+Print+on+12x18+10pt.+Gloss+(CCXX2)+in+Color.+Print+quantity+:+13.+Trim+to+bleed.+Final+size+:+5x7.+Yield+=+52+pieces.+Provide+50+-+5x7+white+envelopes.+Refer+to+the+Design+and+Content+Tool+Procedures+Guide+(located+on+FedEx+One)+for+step-by-step+set+up+instructions.\"},{\"id\":\"1455050109636\",\"name\":\"DEFAULT_IMAGE_WIDTH\",\"value\":\"5.24\"},{\"id\":\"1455050109631\",\"name\":\"DEFAULT_IMAGE_HEIGHT\",\"value\":\"7.24\"},{\"id\":\"1464709502522\",\"name\":\"PRODUCT_QTY_SET\",\"value\":\"50\"},{\"id\":\"1568041487844\",\"name\":\"VENDOR_TEMPLATE\",\"value\":\"YES\"},{\"id\":\"1614715469176\",\"name\":\"IMPOSE_TEMPLATE_ID\",\"value\":\"12\"}]},\"cartItemId\":null,\"designProduct\":{\"designId\":\"DAFWm0PMcxo\",\"partnerProductId\":\"CVAINV1044\"},\"fileCreated\":\"2023-01-03T11:06:11.689Z\"},\"productRateTotal\":{\"unitPrice\":null,\"currency\":\"USD\",\"quantity\":50,\"price\":\"$31.99\",\"priceAfterDiscount\":\"$31.99\",\"unitOfMeasure\":\"EACH\",\"totalDiscount\":\"$0.00\",\"productLineDetails\":[{\"detailCode\":\"51155\",\"priceRequired\":false,\"priceOverridable\":false,\"description\":\"Invt+SS+5x7+Std+50\",\"unitQuantity\":1,\"quantity\":1,\"detailPrice\":\"$31.99\",\"detailDiscountPrice\":\"$0.00\",\"detailUnitPrice\":\"$31.9900\",\"detailDiscountedUnitPrice\":\"$0.0000\",\"detailCategory\":\"PRINTING\"}]},\"isUpdateButtonVisible\":false,\"quantityChoices\":[\"25\",\"50\",\"100\",\"150\",\"200\",\"250\"],\"expressCheckout\":false,\"isEditable\":true,\"catalogDocumentMetadata\":null,\"isEdited\":false,\"fileManagementState\":{\"availableFileItems\":[],\"projects\":[],\"catalogManageFilesToggle\":true,\"displayErrorIds\":false}},\"productType\":\"PRINT_PRODUCT\",\"instanceId\":\"7259176819640068\"}"}]}';
   // @codingStandardsIgnoreEnd

   /**
    * @var MockObject
    */
    protected $cartMock;

    /**
     * @var MockObject
     */
    protected $cartFactoryMock;

   /**
    * @var MockObject
    */
    protected $productRepositoryInterfaceMock;

   /**
    * @var MockObject
    */
    protected $requestMock;

   /**
    * @var MockObject
    */
    protected $serializerMock;

   /**
    * @var MockObject
    */
    protected $fxoRateHelperMock;

   /**
    * @var Add
    */
    protected $fxoRateQuote;

   /**
    * @var BuyRequestBuilder|MockObject
    */
    protected $buyRequestBuilderMock;

    public const ITEM_SKU = '1614105200640-4';

   /**
    * @var \Fedex\Cart\Model\Quote\Product\CartItemFactory
    */
    protected $cartItemFactory;

   /**
    * @var MockObject
    */
    protected $quote;

   /**
    * @var MockObject
    */
    protected $searchCriteriaBuilder;

    /**
     * @var MockObject
     */
    protected $bundleProductHandler;

    protected function setUp(): void
    {
        $this->cartMock = $this->createMock(\Magento\Checkout\Model\Cart::class);
        $this->cartFactoryMock = $this->getMockBuilder(\Magento\Checkout\Model\CartFactory::class)
         ->disableOriginalConstructor()
         ->getMock();
        $this->cartFactoryMock->method('create')
            ->willReturn($this->cartMock);

        $this->itemMock = $this->getMockBuilder(CartItemInterface::class)
         ->disableOriginalConstructor()
         ->onlyMethods(['setQty', 'getExtensionAttributes'])
         ->addMethods([
            'getData',
            'getId',
            'getOptionByCode',
            'addOption',
            'getProductId',
            'saveItemOptions',
            'setInstanceId',
            'save'
         ])
         ->getMockForAbstractClass();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
         ->onlyMethods(['getItemById', 'getId'])
         ->addMethods(['setQuote'])
         ->disableOriginalConstructor()
         ->getMock();

        $this->itemOptionMock = $this->getMockBuilder(Option::class)
         ->setMethods(['getValue'])
         ->disableOriginalConstructor()
         ->getMock();

        $this->productMock = $this->getMockBuilder(ProductModel::class)
         ->disableOriginalConstructor()
         ->addMethods(['getCustomizable'])
         ->onlyMethods(['addCustomOption', 'getId'])
         ->getMock();

        $this->productMock->expects($this->any())
         ->method('addCustomOption')
         ->willReturnSelf();

        $this->productMock->expects($this->any())
         ->method('getId')
         ->willReturn(123);

        $optionValue = $this->getMockBuilder(\Magento\Catalog\Model\Product\Option\Value::class)
         ->disableOriginalConstructor()
         ->getMock();

        $optionFactory = $this->getMockBuilder(\Magento\Catalog\Model\Product\OptionFactory::class)
         ->disableOriginalConstructor()
         ->getMock();

        $optionFactory->expects($this->any())
         ->method('create')
         ->willReturn($optionValue);

        $this->loggerInterface = $this->createMock(LoggerInterface::class);

        $this->productRepositoryInterfaceMock = $this->getMockForAbstractClass(ProductRepositoryInterface::class);

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
         ->addMethods(['setPostValue'])
         ->getMockForAbstractClass();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->fxoRateHelperMock = $this->getMockBuilder(FXORate::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->buyRequestBuilderMock = $this->getMockBuilder(BuyRequestBuilder::class)
         ->disableOriginalConstructor()
         ->onlyMethods(['build'])
         ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
         ->disableOriginalConstructor()
         ->setMethods(['getToggleConfigValue'])
         ->getMock();

        $this->fxocmhelper = $this->getMockBuilder(Data::class)
         ->disableOriginalConstructor()
         ->setMethods([
            'getFxoCMToggle',
            'getEproLegacyLineItemsToggle',
            'getNewConfiguratorToggle',
            'getCommercialCartLineItemsToggle'
         ])
         ->getMock();

        $this->requestQueryValidator = $this->getMockBuilder(RequestQueryValidator::class)
         ->onlyMethods(['isGraphQl'])
         ->disableOriginalConstructor()
         ->getMockForAbstractClass();

        $this->fxoRateQuote = $this->getMockBuilder(\Fedex\FXOPricing\Model\FXORateQuote::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->cartItemFactory = $this->getMockBuilder(\Magento\Quote\Api\Data\CartItemInterfaceFactory::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->thirdParty = $this->getMockBuilder(\Fedex\Cart\Model\Quote\ThirdPartyProduct\Add::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->contentAssociationsResolver = $this->createMock(ContentAssociationsResolver::class);
        $this->cartItemFactory = $this->createMock(\Magento\Quote\Api\Data\CartItemInterfaceFactory::class);
        $this->searchCriteriaBuilder = $this->createMock(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->thirdParty = $this->createMock(\Fedex\Cart\Model\Quote\ThirdPartyProduct\Add::class);
        $this->bundleProductHandler = $this->createMock(BundleProductHandler::class);

        $objectManager = new ObjectManager($this);
        $this->addMock = $objectManager->getObject(
            Add::class,
            [
                'cart' => $this->cartFactoryMock,
                'productRepositoryInterface' => $this->productRepositoryInterfaceMock,
                'request' => $this->requestMock,
                'serializer' => $this->serializerMock,
                'fxoRateHelper' => $this->fxoRateHelperMock,
                'fxoRateQuote' => $this->fxoRateQuote,
                'buyRequestBuilder' => $this->buyRequestBuilderMock,
                'toggleConfig' => $this->toggleConfig,
                'cartItemFactory' => $this->cartItemFactory,
                'quote' => $this->quote,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'thirdParty' => $this->thirdParty,
                'fxocmhelper' => $this->fxocmhelper,
                'requestQueryValidator' => $this->requestQueryValidator,
                'contentAssociationsResolver' => $this->contentAssociationsResolver,
                'loggerInterface' => $this->loggerInterface,
                'bundleProductHandler' => $this->bundleProductHandler
            ]
        );

        $this->cartItemExtensionMock = $this->createMock(CartItemExtensionInterface::class);
        $this->cartIntegrationItemMock = $this->createMock(CartIntegrationItemInterface::class);
    }

   /**
    * Test method for add item to cart without update Item
    *
    * @return null
    */
    public function testAddItemToCartWithoutUpdateItem()
    {
        $this->fxocmhelper->expects($this->any())->method('getFxoCMToggle')->willReturn(false);
        $this->requestQueryValidator->expects($this->once())->method('isGraphQl')->willReturn(false);
        $this->productRepositoryInterfaceMock->expects($this->once())->method('get')->with(self::ITEM_SKU)
         ->willReturn($this->productMock);
        $this->buyRequestBuilderMock->expects($this->any())->method('build')->willReturn(['test']);
        $this->requestMock->expects($this->any())->method('setPostValue')->willReturn(true);
        $this->loggerInterface->expects($this->any())
         ->method('info')
         ->willReturnSelf();
        $result = $this->addMock->addItemToCart(self::ITEM_DATA);
        $this->assertEquals($result, ['updatedProductName' => null]);
    }

   /**
    * Test that addItemToCart correctly handles marketplace products
    *
    * @return void
    */
    public function testAddItemToCartWithMarketplaceProduct(): void
    {
        $thirdPartyMock = $this->getMockBuilder(\Fedex\Cart\Model\Quote\ThirdPartyProduct\Add::class)
         ->disableOriginalConstructor()
         ->getMock();

        $thirdPartyMock->expects($this->once())
         ->method('addItemToCart')
         ->with($this->arrayHasKey('isMarketplaceProduct'));

        $reflection = new \ReflectionClass($this->addMock);
        $property = $reflection->getProperty('thirdParty');
        $property->setAccessible(true);
        $property->setValue($this->addMock, $thirdPartyMock);

        $requestData = ['isMarketplaceProduct' => true, 'sku' => 'test-product'];

        $result = $this->addMock->addItemToCart($requestData);

        $this->assertEquals(['updatedProductName' => null], $result);
    }

   /**
    * Test method for add item to cart without update Item
    *
    * @return null
    */
    public function testAddItemToCartWithoutUpdateItemNewConfigurator()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->requestQueryValidator->expects($this->once())->method('isGraphQl')->willReturn(false);
        $this->fxocmhelper->expects($this->any())->method('getFxoCMToggle')->willReturn(true);
        $this->fxocmhelper->expects($this->any())->method('getEproLegacyLineItemsToggle')->willReturn(true);
        $this->productRepositoryInterfaceMock->expects($this->once())->method('get')->with(self::ITEM_SKU)
         ->willReturn($this->productMock);
        $this->buyRequestBuilderMock->expects($this->any())->method('build')->willReturn(['test']);
        $this->requestMock->expects($this->any())->method('setPostValue')->willReturn(true);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())
         ->method('getId')
         ->willReturn("12");
        ;
        $result = $this->addMock->addItemToCart(self::ITEM_DATA_NEW_CONFIGURATOR);
        $this->assertEquals($result, ['updatedProductName' => null]);
    }

   /**
    * Test method for add item to cart with update Item
    *
    * @return null
    * @throws \Exception
    */
    public function testAddItemToCartWithUpdateItem()
    {
        $this->cartMock->expects($this->any())->method('getItems')->willReturn([$this->itemMock]);
        $this->productRepositoryInterfaceMock->expects($this->once())->method('get')->with(self::ITEM_SKU)
         ->willReturn($this->productMock);
        $this->buyRequestBuilderMock->expects($this->any())->method('build')->willReturn(['test']);
        $this->requestMock->expects($this->any())->method('setPostValue')->willReturn(true);
        $this->testUpdateItem();

        $result = $this->addMock->addItemToCart(self::EDIT_ITEM_DATA);
        $this->assertEquals($result, ['updatedProductName' => 'Flyers']);
    }

   /**
    * Test method for add item to cart with update Item
    *
    * @return null
    * @throws \Exception
    */
    public function testAddItemToCartWithUpdateItem1()
    {
        $this->cartMock->expects($this->any())->method('getItems')->willReturn([$this->itemMock]);
        $this->productRepositoryInterfaceMock->expects($this->once())->method('get')->with(self::ITEM_SKU)
         ->willReturn($this->productMock);
        $this->buyRequestBuilderMock->expects($this->any())->method('build')->willReturn(['test']);
        $this->requestMock->expects($this->any())->method('setPostValue')->willReturn(true);
        $this->testUpdateItem();

        $result = $this->addMock->addItemToCart(self::ITEM_DATA_IS_EDITED_FALSE);
        $this->assertEquals($result, ['updatedProductName' => 'Flyers']);
    }

   /**
    * Test method for add item to cart with update Item
    *
    * @return null
    * @throws \Exception
    */
    public function testAddItemToCartWithUpdateItemException()
    {
        $this->expectExceptionMessage('No such item with instanceId = 57817263004095960');
        $this->expectException(NoSuchEntityException::class);

        $requestData = json_decode(self::EDIT_ITEM_DATA, true);
        $params = [
         'product' => 578,
         'qty' => 50,
        ];
        $itemId = 16004;

        $this->cartMock->expects($this->any())->method('getItems')->willReturn([$this->itemMock]);
        $this->itemMock->expects($this->any())->method('getData')->with('instance_id')->willReturn(57817263004095960);
        $this->itemMock->expects($this->any())->method('getId')->willReturn($itemId);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getItemById')->willReturn(null);
        $this->loggerInterface->expects($this->any())
         ->method('info')
         ->willReturnSelf();

        $this->addMock->updateItem($requestData, $params, self::ITEM_DETAILS, self::EDIT_ITEM_DATA);
    }

   /**
    * Test that product name is correctly used when userProductName is not set
    *
    * @return void
    */
    public function testAddItemToCartWithProductName(): void
    {
        $requestData = [
         'integratorProductReference' => '1614105200640-4',
         'fxoProductInstance' => [
            'id' => '1641146269419',
            'name' => 'Product Display Name',
            'productConfig' => [
               'product' => [
                  'name' => 'Product Display Name',
                  'instanceId' => 12345678,
                  'qty' => 1,
                  'contentAssociations' => [
                     [
                        'contentReference' => '12345',
                        'fileName' => 'test.jpg',
                        'contentType' => 'IMAGE'
                     ]
                  ]
               ]
            ]
         ],
         'product' => [
            'name' => 'Product Display Name',
            'instanceId' => 12345678,
            'qty' => 1,
            'contentAssociations' => [
               [
                  'contentReference' => '12345',
                  'fileName' => 'test.jpg',
                  'contentType' => 'IMAGE'
               ]
            ]
         ]
        ];

        $this->fxocmhelper->expects($this->once())
         ->method('getFxoCMToggle')
         ->willReturn(true);

        $this->fxocmhelper->expects($this->any())
         ->method('getEproLegacyLineItemsToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getNewConfiguratorToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getCommercialCartLineItemsToggle')
         ->willReturn(false);

        $this->requestQueryValidator->expects($this->once())
         ->method('isGraphQl')
         ->willReturn(false);

        $this->productRepositoryInterfaceMock->expects($this->once())
         ->method('get')
         ->with('1614105200640-4')
         ->willReturn($this->productMock);

        $this->buyRequestBuilderMock->expects($this->once())
         ->method('build')
         ->willReturn(['qty' => 1]);

        $this->requestMock->expects($this->exactly(2))
         ->method('setPostValue')
         ->willReturn(true);

        $this->cartMock->expects($this->once())
         ->method('addProduct')
         ->with($this->productMock, $this->anything())
         ->willReturnSelf();

        $this->cartMock->expects($this->once())
         ->method('save')
         ->willReturnSelf();

        $this->cartMock->expects($this->any())
         ->method('getQuote')
         ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->any())
         ->method('getId')
         ->willReturn(123);

        $result = $this->addMock->addItemToCart(json_encode($requestData));

        $this->assertEquals(['updatedProductName' => null], $result);
    }

   /**
    * Test method for testUpdateItem
    */
    public function testUpdateItem()
    {
        $requestData = json_decode(self::EDIT_ITEM_DATA, true);
        $params = [
         'product' => 578,
         'qty' => 50,
        ];
        $itemId = 16004;
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->cartMock->expects($this->any())->method('getItems')->willReturn([$this->itemMock]);
        $this->itemMock->expects($this->any())->method('getData')->with('instance_id')->willReturn(57817263004095960);
        $this->itemMock->expects($this->any())->method('getId')->willReturn($itemId);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getItemById')->willReturn($this->itemMock);

        $this->itemMock->expects($this->any())
         ->method('getExtensionAttributes')
         ->willReturn($this->cartItemExtensionMock);

        $this->cartItemExtensionMock->expects($this->any())
         ->method('getIntegrationItemData')
         ->willReturn($this->cartIntegrationItemMock);

        $this->assertNotNull(
            $this->addMock->updateItem($requestData, $params, self::ITEM_DETAILS, self::EDIT_ITEM_DATA)
        );
    }

   /**
    * Test method for testUpdateItemWithFXOKey
    */
    public function testUpdateItemWithFXOKey()
    {
        $requestData = json_decode(self::EDIT_ITEM_DATA, true);
        $params = [
         'product' => 578,
         'qty' => 50,
        ];
        $itemId = 16004;

        $this->cartMock->expects($this->any())->method('getItems')->willReturn([$this->itemMock]);
        $this->itemMock->expects($this->any())->method('getOptionByCode')->with('info_buyRequest')
         ->willReturn($this->itemOptionMock);
        $this->itemOptionMock->expects($this->any())->method('getValue')
         ->willReturn(self::EXTERNAL_PROD_DATA);

        $this->itemMock->expects($this->any())->method('getData')->with('instance_id')
         ->willReturn(57817263004095969);
        $this->itemMock->expects($this->any())->method('getId')->willReturn($itemId);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getItemById')->willReturn($this->itemMock);
        $this->itemMock->expects($this->any())
         ->method('getExtensionAttributes')
         ->willReturn($this->cartItemExtensionMock);

        $this->cartItemExtensionMock->expects($this->any())
         ->method('getIntegrationItemData')
         ->willReturn($this->cartIntegrationItemMock);
        $this->assertNotNull(
            $this->addMock->updateItem($requestData, $params, self::ITEM_DETAILS, self::EDIT_ITEM_DATA)
        );
    }

   /**
    * Test method for testUpdateItemWithoutFXOKey
    */
    public function testUpdateItemWithoutFXOKey()
    {
        $requestData = json_decode(self::EDIT_ITEM_DATA, true);
        $params = [
         'product' => 578,
         'qty' => 50,
        ];
        $itemId = 16004;

        $itemData = '{
            "external_prod": [{
                "instanceId": "57817263004095960"
            }]
        }';

        $this->cartMock->expects($this->any())->method('getItems')->willReturn([$this->itemMock]);
        $this->itemMock->expects($this->any())->method('getOptionByCode')->with('info_buyRequest')
         ->willReturn($this->itemOptionMock);
        $this->itemOptionMock->expects($this->any())->method('getValue')->willReturn($itemData);

        $this->itemMock->expects($this->any())->method('getData')->with('instance_id')->willReturn(57817263004095969);
        $this->itemMock->expects($this->any())->method('getId')->willReturn($itemId);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getItemById')->willReturn($this->itemMock);
        $this->itemMock->expects($this->any())
         ->method('getExtensionAttributes')
         ->willReturn($this->cartItemExtensionMock);

        $this->cartItemExtensionMock->expects($this->any())
         ->method('getIntegrationItemData')
         ->willReturn($this->cartIntegrationItemMock);

        $this->assertNotNull(
            $this->addMock->updateItem($requestData, $params, self::ITEM_DETAILS, self::EDIT_ITEM_DATA)
        );
    }

   /**
    * Test that addItemToCart calls updateItem when instanceId exists and customizeDocument is false
    *
    * @return void
    */
    public function testAddItemToCartWithInstanceIdAndNoCustomizeDocument(): void
    {
        $requestData = [
         'integratorProductReference' => '1614105200640-4',
         'instanceId' => 12345678,
         'fxoProductInstance' => [
            'id' => '1641146269419',
            'name' => 'Product Display Name',
            'productConfig' => [
               'product' => [
                  'name' => 'Product Display Name',
                  'instanceId' => 12345678,
                  'qty' => 1,
                  'contentAssociations' => [
                     [
                        'contentReference' => '12345',
                        'fileName' => 'test.jpg',
                        'contentType' => 'IMAGE'
                     ]
                  ]
               ]
            ]
         ],
         'product' => [
            'name' => 'Product Display Name',
            'instanceId' => 12345678,
            'qty' => 1,
            'contentAssociations' => [
               [
                  'contentReference' => '12345',
                  'fileName' => 'test.jpg',
                  'contentType' => 'IMAGE'
               ]
            ]
         ]
        ];

        $this->fxocmhelper->expects($this->once())
         ->method('getFxoCMToggle')
         ->willReturn(true);

        $this->fxocmhelper->expects($this->any())
         ->method('getEproLegacyLineItemsToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getNewConfiguratorToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getCommercialCartLineItemsToggle')
         ->willReturn(false);

        $this->requestQueryValidator->expects($this->once())
         ->method('isGraphQl')
         ->willReturn(false);

        $this->productRepositoryInterfaceMock->expects($this->once())
         ->method('get')
         ->with('1614105200640-4')
         ->willReturn($this->productMock);

        $this->cartItemFactory = $this->getMockBuilder(\Magento\Quote\Api\Data\CartItemInterfaceFactory::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->thirdParty = $this->getMockBuilder(\Fedex\Cart\Model\Quote\ThirdPartyProduct\Add::class)
         ->disableOriginalConstructor()
         ->getMock();

        $addMockPartial = $this->getMockBuilder(Add::class)
         ->setConstructorArgs([
            $this->cartFactoryMock,
            $this->productRepositoryInterfaceMock,
            $this->requestMock,
            $this->serializerMock,
            $this->fxoRateHelperMock,
            $this->fxoRateQuote,
            $this->buyRequestBuilderMock,
            $this->toggleConfig,
            $this->cartItemFactory,
            $this->quote,
            $this->searchCriteriaBuilder,
            $this->thirdParty,
            $this->fxocmhelper,
            $this->requestQueryValidator,
            $this->contentAssociationsResolver,
            $this->loggerInterface,
             $this->bundleProductHandler
         ])
         ->onlyMethods(['updateItem'])
         ->getMock();

        $addMockPartial->expects($this->once())
         ->method('updateItem')
         ->willReturn('Product Display Name');

        $result = $addMockPartial->addItemToCart(json_encode($requestData));

        $this->assertEquals(['updatedProductName' => 'Product Display Name'], $result);
    }

   /**
    * Test that instanceId is set in request data when product is edited
    *
    * @return void
    */
    public function testInstanceIdIsSetWhenProductIsEdited(): void
    {
        $requestData = [
         'integratorProductReference' => '1614105200640-4',
         'fxoProductInstance' => [
            'id' => '1641146269419',
            'name' => 'Product Display Name',
            'isEdited' => true,
            'productConfig' => [
               'product' => [
                  'name' => 'Product Display Name',
                  'instanceId' => 12345678,
                  'qty' => 1,
                  'contentAssociations' => [
                     [
                        'contentReference' => '12345',
                        'fileName' => 'test.jpg',
                        'contentType' => 'IMAGE'
                     ]
                  ]
               ]
            ]
         ],
         'product' => [
            'name' => 'Product Display Name',
            'instanceId' => 12345678,
            'qty' => 1,
            'contentAssociations' => [
               [
                  'contentReference' => '12345',
                  'fileName' => 'test.jpg',
                  'contentType' => 'IMAGE'
               ]
            ]
         ]
        ];

        $this->fxocmhelper->expects($this->once())
         ->method('getFxoCMToggle')
         ->willReturn(true);

        $this->fxocmhelper->expects($this->any())
         ->method('getEproLegacyLineItemsToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getNewConfiguratorToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getCommercialCartLineItemsToggle')
         ->willReturn(false);

        $this->requestQueryValidator->expects($this->once())
         ->method('isGraphQl')
         ->willReturn(false);

        $this->productRepositoryInterfaceMock->expects($this->once())
         ->method('get')
         ->with('1614105200640-4')
         ->willReturn($this->productMock);

        $this->cartItemFactory = $this->getMockBuilder(\Magento\Quote\Api\Data\CartItemInterfaceFactory::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->searchCriteriaBuilder = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaBuilder::class)
         ->disableOriginalConstructor()
         ->getMock();

        $this->thirdParty = $this->getMockBuilder(\Fedex\Cart\Model\Quote\ThirdPartyProduct\Add::class)
         ->disableOriginalConstructor()
         ->getMock();

        $addMockPartial = $this->getMockBuilder(Add::class)
         ->setConstructorArgs([
            $this->cartFactoryMock,
            $this->productRepositoryInterfaceMock,
            $this->requestMock,
            $this->serializerMock,
            $this->fxoRateHelperMock,
            $this->fxoRateQuote,
            $this->buyRequestBuilderMock,
            $this->toggleConfig,
            $this->cartItemFactory,
            $this->quote,
            $this->searchCriteriaBuilder,
            $this->thirdParty,
            $this->fxocmhelper,
            $this->requestQueryValidator,
            $this->contentAssociationsResolver,
            $this->loggerInterface,
             $this->bundleProductHandler
         ])
         ->onlyMethods(['updateItem'])
         ->getMock();

        $addMockPartial->expects($this->once())
         ->method('updateItem')
         ->with(
             $this->callback(function ($arg) {
                return isset($arg['instanceId']) && $arg['instanceId'] === 12345678;
             }),
             $this->anything(),
             $this->anything(),
             $this->anything()
         )
         ->willReturn('Product Display Name');

        $result = $addMockPartial->addItemToCart(json_encode($requestData));

        $this->assertEquals(['updatedProductName' => 'Product Display Name'], $result);
    }

   /**
    * Test method for testSetCart
    */
    public function testSetCart()
    {
        $this->quoteMock->expects($this->any())->method('setQuote')->willReturnSelf();
        $this->addMock->setCart($this->quoteMock);
    }

   /**
    * Test case for getItemIdForFxoProduct
    */
    public function testGetItemIdForFxoProduct()
    {
        $externalProdData = [
         'fxo_product' => '{"instanceId": 123}',
         'fxoProductInstance' => [
            'productConfig' => [
               'product' => [
                  'instanceId' => 123
               ]
            ]
         ]
        ];
        $this->assertNull($this->addMock->getItemIdForFxoProduct(
            $externalProdData,
            $this->itemMock,
            123
        ));
    }

   /**
    * Test that customizeDocument is set to true for ePro legacy products that are customizable
    *
    * @return void
    */
    public function testCustomizeDocumentSetForEproLegacyCustomizableProducts(): void
    {
        $requestData = [
         'integratorProductReference' => '1614105200640-4',
         'customDocumentDetails' => [],
         'product' => [
            'name' => 'Product Display Name',
            'instanceId' => 12345678,
            'qty' => 1,
            'contentAssociations' => [
               [
                  'contentReference' => '12345',
                  'fileName' => 'test.jpg',
                  'contentType' => 'IMAGE'
               ]
            ]
         ]
        ];

        $this->fxocmhelper->expects($this->once())
         ->method('getFxoCMToggle')
         ->willReturn(true);

        $this->fxocmhelper->expects($this->any())
         ->method('getEproLegacyLineItemsToggle')
         ->willReturn(true);
        $this->fxocmhelper->expects($this->any())
         ->method('getNewConfiguratorToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getCommercialCartLineItemsToggle')
         ->willReturn(false);

        $this->requestQueryValidator->expects($this->once())
         ->method('isGraphQl')
         ->willReturn(false);

        $this->productMock->expects($this->once())
         ->method('getCustomizable')
         ->willReturn(true);

        $this->cartMock->expects($this->any())
         ->method('getQuote')
         ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->any())
         ->method('getId')
         ->willReturn(123);

        $this->productRepositoryInterfaceMock->expects($this->once())
         ->method('get')
         ->with('1614105200640-4')
         ->willReturn($this->productMock);

        $this->productMock->expects($this->once())
         ->method('addCustomOption')
         ->with(
             'customize_fields',
             $this->anything()
         );

        $this->buyRequestBuilderMock->expects($this->once())
         ->method('build')
         ->willReturn(['qty' => 1]);

        $this->cartMock->expects($this->once())
         ->method('addProduct')
         ->with($this->productMock, $this->anything())
         ->willReturnSelf();

        $this->cartMock->expects($this->once())
         ->method('save')
         ->willReturnSelf();

        $result = $this->addMock->addItemToCart(json_encode($requestData));

        $this->assertEquals(['updatedProductName' => null], $result);
    }

   /**
    * Test case for getItemIdForFxoProduct
    */
    public function testGetItemIdForFxoProductWithoutInstanceId()
    {
        $externalProdData = [
         'fxo_product' => '{
               "instanceId": 12,
               "fxoProductInstance": {
                  "productConfig": {
                     "product": {
                        "instanceId": 123
                     }
                  }
               }
            }'
        ];
        $this->assertNull($this->addMock->getItemIdForFxoProduct(
            $externalProdData,
            $this->itemMock,
            123
        ));
    }

   /**
    * Test that commercial cart line items toggle adds custom options for non-UUID SKUs
    *
    * @return void
    */
    public function testCommercialCartLineItemsWithNonUuidSku(): void
    {
        $requestData = [
         'integratorProductReference' => 'simple-sku-123',
         'customDocumentDetails' => [],
         'product' => [
            'name' => 'Product Display Name',
            'instanceId' => 12345678,
            'qty' => 1,
            'contentAssociations' => [
               [
                  'contentReference' => '12345',
                  'fileName' => 'test.jpg',
                  'contentType' => 'IMAGE'
               ]
            ]
         ]
        ];

        $this->fxocmhelper->expects($this->once())
         ->method('getFxoCMToggle')
         ->willReturn(true);

        $this->fxocmhelper->expects($this->any())
         ->method('getEproLegacyLineItemsToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getNewConfiguratorToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getCommercialCartLineItemsToggle')
         ->willReturn(true);

        $this->requestQueryValidator->expects($this->once())
         ->method('isGraphQl')
         ->willReturn(false);

        $this->productRepositoryInterfaceMock->expects($this->once())
         ->method('get')
         ->with('simple-sku-123')
         ->willReturn($this->productMock);

        $expectedOptions = ['label' => 'fxoProductInstance', 'value' => 12345678];
        $serializedOptions = 'serialized_options_json';

        $this->serializerMock->expects($this->once())
         ->method('serialize')
         ->with($this->equalTo($expectedOptions))
         ->willReturn($serializedOptions);

        $this->productMock->expects($this->once())
         ->method('addCustomOption')
         ->with('custom_option', $serializedOptions)
         ->willReturnSelf();

        $this->cartMock->expects($this->any())
         ->method('getQuote')
         ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->any())
         ->method('getId')
         ->willReturn(123);

        $this->buyRequestBuilderMock->expects($this->once())
         ->method('build')
         ->willReturn(['qty' => 1]);

        $this->cartMock->expects($this->once())
         ->method('addProduct')
         ->with($this->productMock, $this->anything())
         ->willReturnSelf();

        $this->cartMock->expects($this->once())
         ->method('save')
         ->willReturnSelf();

        $result = $this->addMock->addItemToCart(json_encode($requestData));

        $this->assertEquals(['updatedProductName' => null], $result);
    }

   /**
    * Test that custom options are not added for UUID format SKUs when commercial cart toggle is on
    *
    * @return void
    */
    public function testCommercialCartLineItemsWithUuidSku(): void
    {
        $requestData = [
         'integratorProductReference' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
         'customDocumentDetails' => [],
         'product' => [
            'name' => 'Product Display Name',
            'instanceId' => 12345678,
            'qty' => 1,
            'contentAssociations' => [
               [
                  'contentReference' => '12345',
                  'fileName' => 'test.jpg',
                  'contentType' => 'IMAGE'
               ]
            ]
         ]
        ];

        $this->fxocmhelper->expects($this->once())
         ->method('getFxoCMToggle')
         ->willReturn(true);

        $this->fxocmhelper->expects($this->any())
         ->method('getEproLegacyLineItemsToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getNewConfiguratorToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getCommercialCartLineItemsToggle')
         ->willReturn(true);

        $this->requestQueryValidator->expects($this->once())
         ->method('isGraphQl')
         ->willReturn(false);

        $this->productRepositoryInterfaceMock->expects($this->once())
         ->method('get')
         ->with('a1b2c3d4-e5f6-7890-abcd-ef1234567890')
         ->willReturn($this->productMock);

        $this->productMock->expects($this->never())
         ->method('addCustomOption')
         ->with('custom_option', $this->anything());

        $this->buyRequestBuilderMock->expects($this->once())
         ->method('build')
         ->willReturn(['qty' => 1]);

        $this->cartMock->expects($this->any())
         ->method('getQuote')
         ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->any())
         ->method('getId')
         ->willReturn(123);

        $this->cartMock->expects($this->once())
         ->method('addProduct')
         ->with($this->productMock, $this->anything())
         ->willReturnSelf();

        $this->cartMock->expects($this->once())
         ->method('save')
         ->willReturnSelf();

        $result = $this->addMock->addItemToCart(json_encode($requestData));

        $this->assertEquals(['updatedProductName' => null], $result);
    }

   /**
    * Test that updateItem is called with itemId and customizeFields when both are present
    *
    * @return void
    */
    public function testUpdateItemWithItemIdAndCustomizeFields(): void
    {

        $itemId = 12345;
        $requestData = [
         'integratorProductReference' => '1614105200640-4',
         'customDocumentDetails' => [
            [
               'documentId' => '43ff7cf3-c40d-11ee-8d95-ed85716fe55f',
               'formFields' => [
                  [
                     'fieldName' => 'Date',
                     'fieldType' => 'TEXT',
                     'pageNumber' => 1,
                     'label' => 'Date',
                     'description' => '',
                     'hintText' => ''
                  ]
               ]
            ]
         ],
         'product' => [
            'name' => 'Product Display Name',
            'instanceId' => 57307904,
            'qty' => 1,
            'contentAssociations' => [
               [
                  'contentReference' => '12345',
                  'fileName' => 'test.jpg',
                  'contentType' => 'IMAGE'
               ]
            ]
         ]
        ];

        $this->fxocmhelper->expects($this->once())
         ->method('getFxoCMToggle')
         ->willReturn(true);

        $this->fxocmhelper->expects($this->any())
         ->method('getEproLegacyLineItemsToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getNewConfiguratorToggle')
         ->willReturn(false);

        $this->fxocmhelper->expects($this->any())
         ->method('getCommercialCartLineItemsToggle')
         ->willReturn(false);

        $this->requestQueryValidator->expects($this->once())
         ->method('isGraphQl')
         ->willReturn(false);

        $this->productRepositoryInterfaceMock->expects($this->once())
         ->method('get')
         ->with('1614105200640-4')
         ->willReturn($this->productMock);

        $this->cartMock->expects($this->any())
         ->method('getQuote')
         ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->any())
         ->method('getId')
         ->willReturn(123);

        $addMockPartial = $this->getMockBuilder(Add::class)
         ->setConstructorArgs([
            $this->cartFactoryMock,
            $this->productRepositoryInterfaceMock,
            $this->requestMock,
            $this->serializerMock,
            $this->fxoRateHelperMock,
            $this->fxoRateQuote,
            $this->buyRequestBuilderMock,
            $this->toggleConfig,
            $this->cartItemFactory,
            $this->quote,
            $this->searchCriteriaBuilder,
            $this->thirdParty,
            $this->fxocmhelper,
            $this->requestQueryValidator,
            $this->contentAssociationsResolver,
            $this->loggerInterface,
             $this->bundleProductHandler
         ])
         ->onlyMethods(['updateItem'])
         ->getMock();

        $addMockPartial->expects($this->once())
         ->method('updateItem')
         ->with(
             $this->callback(function ($arg) {
                return isset($arg['integratorProductReference'])
                  && $arg['integratorProductReference'] === '1614105200640-4'
                  && isset($arg['product']['name'])
                  && $arg['product']['name'] === 'Product Display Name'
                  && isset($arg['product']['instanceId'])
                  && is_numeric($arg['product']['instanceId']);
             }),
             $this->anything(),
             $this->anything(),
             $this->anything(),
             $this->equalTo($itemId),
             $this->callback(function ($customizeFields) {
                return $customizeFields['label'] === 'customizeFields'
                  && isset($customizeFields['value'])
                  && is_array($customizeFields['value']);
             })
         )
         ->willReturn('Updated Product Name');

        $this->buyRequestBuilderMock->expects($this->once())
         ->method('build')
         ->willReturn(['qty' => 1]);

        $this->serializerMock->expects($this->once())
         ->method('serialize')
         ->willReturn('serialized_customize_fields');

        $result = $addMockPartial->addItemToCart(json_encode($requestData), $itemId);

        $this->assertEquals(['updatedProductName' => 'Updated Product Name'], $result);
    }

   /**
    * Test that product name is set from userProductName when FXOCM toggle is enabled and not a GraphQL request
    *
    * @return void
    */
    public function testProductNameSetFromUserProductNameInUpdateItem(): void
    {
        $requestData = [
         'integratorProductReference' => '1614105200640-4',
         'product' => [
            'userProductName' => 'Custom Product Title',
            'name' => 'Standard Product Name',
            'instanceId' => 12345678,
            'qty' => 1,
            'contentAssociations' => [
               [
                  'contentReference' => '12345',
                  'fileName' => 'test.jpg',
                  'contentType' => 'IMAGE'
               ]
            ]
         ]
        ];

        $params = [
         'product' => 578,
         'qty' => 50,
        ];

        $itemId = 16004;
        $itemDetails = self::ITEM_DETAILS;

        $this->fxocmhelper->expects($this->once())
         ->method('getFxoCMToggle')
         ->willReturn(true);

        $this->requestQueryValidator->expects($this->once())
         ->method('isGraphQl')
         ->willReturn(false);

        $this->cartMock->expects($this->once())
         ->method('getQuote')
         ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->once())
         ->method('getItemById')
         ->with($itemId)
         ->willReturn($this->itemMock);

        $this->itemMock->expects($this->any())
         ->method('getExtensionAttributes')
         ->willReturn($this->cartItemExtensionMock);

        $this->cartItemExtensionMock->expects($this->any())
         ->method('getIntegrationItemData')
         ->willReturn($this->cartIntegrationItemMock);

        $this->fxoRateHelperMock->expects($this->once())
         ->method('isEproCustomer')
         ->willReturn(false);

        $this->fxoRateQuote->expects($this->any())
         ->method('getFXORateQuote')
         ->with($this->quoteMock);

        $result = $this->addMock->updateItem(
            $requestData,
            $params,
            $itemDetails,
            json_encode($requestData['product']),
            $itemId
        );

        $this->assertEquals(
            'Custom Product Title',
            $result,
            'The updateItem method should return userProductName when FXOCM toggle is enabled and not a GraphQL request'
        );
    }

   /**
    * Test that customize options are correctly added to cart item
    *
    * @return void
    */
    public function testAddingCustomizeOptionsToCartItem(): void
    {
        $errorReporting = error_reporting();
        error_reporting($errorReporting & ~E_WARNING & ~E_NOTICE);

        try {
            $requestData = [
            'integratorProductReference' => '1614105200640-4',
            'fxoProductInstance' => [
               'id' => '1641146269419',
               'name' => 'Product Display Name',
               'productConfig' => [
                  'product' => [
                     'name' => 'Product Display Name',
                     'instanceId' => 12345678,
                     'qty' => 1
                  ]
               ]
            ],
            ];

            $params = [
            'product' => 578,
            'qty' => 50,
            ];

            $itemId = 16004;
            $itemDetails = self::ITEM_DETAILS;

            $productData = [
            'name' => 'Product Display Name',
            'userProductName' => 'Product Display Name',
            'instanceId' => 12345678,
            'qty' => 1
            ];

            $customizeOption = [
            'label' => 'customizeFields',
            'value' => [
               [
                  'documentId' => '43ff7cf3-c40d-11ee-8d95-ed85716fe55f',
                  'formFields' => [
                     [
                        'fieldName' => 'Recipient',
                        'fieldType' => 'TEXT',
                        'fieldValue' => 'John Doe'
                     ]
                  ]
               ]
            ]
            ];

            $this->cartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);

            $this->quoteMock->expects($this->once())
            ->method('getItemById')
            ->with($itemId)
            ->willReturn($this->itemMock);

            $this->itemMock->expects($this->any())
            ->method('addOption');

            $this->itemMock->expects($this->any())
            ->method('saveItemOptions');

            $this->itemMock->expects($this->any())
            ->method('getProductId')
            ->willReturn(1001);

            $this->itemMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->cartItemExtensionMock);

            $this->cartItemExtensionMock->expects($this->any())
            ->method('getIntegrationItemData')
            ->willReturn($this->cartIntegrationItemMock);

            $this->fxoRateHelperMock->expects($this->once())
            ->method('isEproCustomer')
            ->willReturn(false);

            $this->fxoRateQuote->expects($this->any())
            ->method('getFXORateQuote')
            ->with($this->quoteMock);

            $this->serializerMock->expects($this->any())
            ->method('serialize')
            ->willReturn('serialized_data');

            $this->addMock->updateItem(
                $requestData,
                $params,
                $itemDetails,
                json_encode($productData),
                $itemId,
                $customizeOption
            );

            $this->assertTrue(true, 'Customize options were correctly added to cart item');
        } finally {
            error_reporting($errorReporting);
        }
    }

   /**
    * Test that getFXORate is called for ePro customers in updateItem method
    *
    * @return void
    */
    public function testUpdateItemWithEproCustomer(): void
    {
        $requestData = json_decode(self::EDIT_ITEM_DATA, true);
        $params = [
         'product' => 578,
         'qty' => 50,
        ];
        $itemId = 16004;

        $this->cartMock->expects($this->once())
         ->method('getQuote')
         ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->once())
         ->method('getItemById')
         ->with($itemId)
         ->willReturn($this->itemMock);

        $this->itemMock->expects($this->any())
         ->method('getExtensionAttributes')
         ->willReturn($this->cartItemExtensionMock);

        $this->cartItemExtensionMock->expects($this->any())
         ->method('getIntegrationItemData')
         ->willReturn($this->cartIntegrationItemMock);

        $this->fxoRateHelperMock->expects($this->once())
         ->method('isEproCustomer')
         ->willReturn(true);

        $this->fxoRateHelperMock->expects($this->once())
         ->method('getFXORate')
         ->with($this->quoteMock);

        $this->fxoRateQuote->expects($this->never())
         ->method('getFXORateQuote');

        $result = $this->addMock->updateItem(
            $requestData,
            $params,
            self::ITEM_DETAILS,
            self::EDIT_ITEM_DATA,
            $itemId
        );

        $this->assertEquals('Flyers', $result, 'The updateItem method should return the product name');
    }
}
