<?php

namespace Fedex\FXOCMConfigurator\Test\Unit\Helper;

use Fedex\FXOCMConfigurator\Helper\Batchupload;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\State;
use Fedex\FXOCMConfigurator\Model\UserworkspaceFactory;
use Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace\CollectionFactory as UserworkspaceCollectionFactory;
use Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace\Collection as UserworkspaceCollection;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Session;
use Fedex\FXOCMConfigurator\ViewModel\FXOCMHelper as FXOCMViewModelHelper;
use Fedex\FXOCMConfigurator\Model\Userworkspace as ModelUserworkspace;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Psr\Log\LoggerInterface;
use Fedex\Base\Helper\Auth;

class BatchuploadTest extends TestCase
{
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfigMock;
    protected $scopeConfigMock;
    /**
     * @var (\Magento\Framework\Encryption\EncryptorInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $encryptorMock;
    /**
     * @var (\Magento\Framework\App\State & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $stateMock;
    protected $userworkspaceMock;
    protected $sessionFactoryMock;
    protected $sessionMock;
    protected $userworkspaceCollectionFactoryMock;
    protected $userworkspaceCollectionMock;
    protected $fxoviewModelMock;
    protected $ssoconfigMock;
    protected $sdeHelper;
    protected $selfRegHelperMock;
    protected $deliveryHelperMock;
    protected $customeSessionMock;
    protected $modelUserworkspaceMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $dataHelper;
    protected $context;
    protected $toggleConfig;
    protected $scopeConfig;
    protected $encryptorInterface;
    protected $state;
    protected $userworkspace;
    protected $customerSessionFactory;
    protected $userworkspaceCollectionFactory;
    protected $fxocmViewModelHelper;
    protected $customerSession;
    protected Auth|MockObject $baseAuthMock;

    public const WORKSPACEDATA = '{
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
  }';

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->encryptorMock = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['decrypt'])
            ->getMockForAbstractClass();

        $this->stateMock = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAreaCode'])
            ->getMock();

        $this->userworkspaceMock = $this->getMockBuilder(UserworkspaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','load','delete' ,'getSize', 'getFirstItem', 'getData', 'setCustomerId' ,'setWorkspaceData', 'setApplicationType', 'setOldUploadDate', 'save','getUserworkspaceId'])
            ->getMockForAbstractClass();

        $this->sessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn','getId'])
            ->getMockForAbstractClass();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->userworkspaceCollectionFactoryMock = $this->getMockBuilder(UserworkspaceCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setCustomerId' ,'setWorkspaceData', 'setApplicationType', 'setOldUploadDate', 'save'])
            ->getMockForAbstractClass();

        $this->userworkspaceCollectionMock = $this->getMockBuilder(UserworkspaceCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['load','addFieldToFilter','getSize', 'getFirstItem', 'getData', 'setCustomerId' ,'setWorkspaceData', 'setApplicationType', 'setOldUploadDate', 'save'])
            ->getMockForAbstractClass();

        $this->fxoviewModelMock = $this->getMockBuilder(FXOCMViewModelHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getApplicationType'])
            ->getMock();

        $this->ssoconfigMock = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(['isFclCustomer', 'isRetail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfRegHelperMock = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['isSelfRegCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods(['isEproCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customeSessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(['setUserworkspace', 'getUserworkspace'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->modelUserworkspaceMock = $this->createMock(ModelUserworkspace::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);


        $context = $objectManager->getObject(Context::class);

        $this->dataHelper = $objectManager->getObject(
            Batchupload::class,
            [
                'context' => $context,
                'toggleConfig' => $this->toggleConfigMock,
                'scopeConfig' => $this->scopeConfigMock,
            'encryptorInterface' => $this->encryptorMock,
                'state' => $this->stateMock,
                'userworkspace' => $this->userworkspaceMock,
                'customerSessionFactory' => $this->sessionFactoryMock,
                'userworkspaceCollectionFactory' => $this->userworkspaceCollectionFactoryMock,
                'fxocmViewModelHelper' => $this->fxoviewModelMock,
                'ssoConfiguration' => $this->ssoconfigMock,
                'sdeHelper' => $this->sdeHelper,
                'selfRegHelper' => $this->selfRegHelperMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'logger' => $this->loggerMock,
                'customerSession' => $this->customeSessionMock,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    /**
     *
     */
    public function testCustomerId()
    {
        $this->sessionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->sessionMock);
        $this->baseAuthMock
            ->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->sessionMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(12);
        $result = $this->dataHelper->customerId();
        $this->assertEquals(12, $result);
    }

    public function testGetOldUploadedDate() {
        $workSpaceData = '{"files": [{"uploadDateTime": "2023-01-01 12:00:00"}, {"uploadDateTime": "2023-01-02 12:00:00"}]}';
        $result = $this->dataHelper->getOldUploadedDate($workSpaceData);
        $this->assertEquals("2023-01-01 12:00:00", $result);
    }

    public function testdeleteBatchUploadRow() {
        $this->userworkspaceMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->modelUserworkspaceMock);
        $this->modelUserworkspaceMock->expects($this->any())->method('load')->willReturn(null);
        $result = $this->dataHelper->deleteBatchUploadRow(12);
        $this->assertEquals(null, $result);
    }

    public function testAddDataInSession() {
      $this->customeSessionMock
          ->expects($this->any())
          ->method('setUserworkspace')
          ->willReturnSelf();
        $result = $this->dataHelper->getUserWorkspaceSessionValue();
        $this->assertEquals(null,$result);
    }

    public function testCheckProjectsExists() {
        $workSpaceData = '{"projects": []}';
        $result = $this->dataHelper->checkProjectsExists($workSpaceData);
        $this->assertTrue($result);
    }

    public function testCheckProjectsExistsTrue() {
        $workSpaceData = '{"projects": [{"name": "Project A"}, {"name": "Project B"}]}';
        $result = $this->dataHelper->checkProjectsExists($workSpaceData);
        $this->assertTrue($result);
    }

    public function testCheckProjectsExistsFalse() {
        $workSpaceData = '{"projects": [{"name": "Project A", "product": [{"name": "Product X"}]}]}';
        $result = $this->dataHelper->checkProjectsExists($workSpaceData);
        $this->assertFalse($result);
    }

    public function testAddBatchUploadData() {

        $this->testCustomerId();
        $this->testGetOldUploadedDate();
        $this->fxoviewModelMock
            ->expects($this->any())
            ->method('getApplicationType')
            ->willReturn('test');
        $this->userworkspaceCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userworkspaceCollectionMock);
        $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->userworkspaceCollectionMock);

        $this->userworkspaceMock
            ->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('getSize')
            ->willReturn(2);
        $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('getFirstItem')
            ->willReturnSelf();
        $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('getData')
            ->willReturn(12);

        $this->testCheckProjectsExistsTrue();
        $this->testdeleteBatchUploadRow();

        $result = $this->dataHelper->addBatchUploadData(SELF::WORKSPACEDATA);
        $this->assertNull($result);
    }

    public function testAddBatchUploadDataFirstElse() {

        $this->testCustomerId();
        $this->testGetOldUploadedDate();
        $this->fxoviewModelMock
            ->expects($this->any())
            ->method('getApplicationType')
            ->willReturn('test');
        $this->userworkspaceCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userworkspaceCollectionMock);
        $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->userworkspaceCollectionMock);

        $this->userworkspaceMock
            ->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('getSize')
            ->willReturn(0);

        $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('setCustomerId')
            ->willReturnSelf();
        $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('setWorkspaceData')
            ->willReturnSelf();
        $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('setApplicationType')
            ->willReturnSelf();
        $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('setOldUploadDate')
            ->willReturnSelf();
        $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $result = $this->dataHelper->addBatchUploadData(SELF::WORKSPACEDATA);
        $this->assertNull($result);
    }

    public function testGetApplicationTypeRetail() {
      $this->ssoconfigMock
            ->expects($this->any())
            ->method('isRetail')
            ->willReturn(True);
      $result = $this->dataHelper->getApplicationType();
      $this->assertEquals('retail',$result);
    }

    public function testGetApplicationTypeIsSde() {
      $this->sdeHelper
            ->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(True);
      $result = $this->dataHelper->getApplicationType();
      $this->assertEquals('SDE',$result);
    }

    public function testGetApplicationTypeSelfReg() {
      $this->selfRegHelperMock
            ->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(True);
      $result = $this->dataHelper->getApplicationType();
      $this->assertEquals('selfreg',$result);
    }

    public function testGetApplicationTypeEpro() {
      $this->deliveryHelperMock
            ->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(True);
      $result = $this->dataHelper->getApplicationType();
      $this->assertEquals('epro',$result);
    }

    public function testGetUserworkSpaceFromCustomerId() {
      $this->userworkspaceCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userworkspaceCollectionMock);
      $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->userworkspaceCollectionMock);
      $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->userworkspaceCollectionMock);
      $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('getData')
            ->willReturn('test');
      $result = $this->dataHelper->getUserworkSpaceFromCustomerId(12);
      $this->assertEquals('test',$result);
    }

    public function testGetUserWorkspaceSessionValue() {
        $this->customeSessionMock
          ->expects($this->any())
          ->method('getUserworkspace')
          ->willReturn('test');
        $result = $this->dataHelper->getUserWorkspaceSessionValue();
        $this->assertEquals('test',$result);
    }

      public function testgetWorkSpaceDeleteDays() {
        $this->scopeConfigMock->expects($this->any())
          ->method('getValue')
          ->willReturn(30);
      $result = $this->dataHelper->getWorkSpaceDeleteDays();
      $this->assertEquals(null,$result);
    }

    public function testgetRetailPrintUrl() {
        $this->scopeConfigMock->expects($this->any())
          ->method('getValue')
          ->willReturn('print-products-retail.html');
      $result = $this->dataHelper->getRetailPrintUrl();
      $this->assertEquals(null,$result);
    }

    public function testgetCommercialPrintUrl() {
        $this->scopeConfigMock->expects($this->any())
          ->method('getValue')
          ->willReturn('b2b-print-products.html');
      $result = $this->dataHelper->getCommercialPrintUrl();
      $this->assertEquals(null,$result);
    }

    public function testUpdateUserworkspaceDataAfterLogin() {

      $this->customeSessionMock
          ->expects($this->any())
          ->method('getUserworkspace')
          ->willReturn(self::WORKSPACEDATA);

      $this->userworkspaceCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userworkspaceCollectionMock);

      $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->userworkspaceCollectionMock);

      $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('getSize')
            ->willReturn(2);

      $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('getFirstItem')
            ->willReturnSelf();

      $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('getData')
            ->willReturn(self::WORKSPACEDATA);

      $this->testAddBatchUploadData();

      $this->dataHelper->updateUserworkspaceDataAfterLogin(12);
    }

    public function testUpdateUserworkspaceDataAfterLoginElse() {

      $this->customeSessionMock
          ->expects($this->any())
          ->method('getUserworkspace')
          ->willReturn(self::WORKSPACEDATA);

      $this->userworkspaceCollectionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->userworkspaceCollectionMock);

      $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->userworkspaceCollectionMock);

      $this->userworkspaceCollectionMock
            ->expects($this->any())
            ->method('getSize')
            ->willReturn(0);

      $this->testAddBatchUploadData();

      $this->dataHelper->updateUserworkspaceDataAfterLogin(12);
    }
}
