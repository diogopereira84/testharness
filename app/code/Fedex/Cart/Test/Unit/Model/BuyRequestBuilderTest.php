<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Model;

use Fedex\GraphQl\Model\RequestQueryValidator;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Cart\Model\BuyRequestBuilder;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\FXOCMConfigurator\Helper\Data as FxocmHelperData;
use Fedex\Cart\Model\Quote\Product\ContentAssociationsResolver;

/*
 * BuyRequestBuilder class
 */

class BuyRequestBuilderTest extends TestCase
{
    /**
     * @var RequestQueryValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestQueryValidator;
    
    /**
     * @var (\Fedex\Cart\Model\Quote\Product\ContentAssociationsResolver & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contentAssociationsResolver;

    /**
     * @var BuyRequestBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $buyRequestBuilderMock;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * @var UploadToQuoteViewModel $uploadToQuoteViewModel
     */
    protected $uploadToQuoteViewModel;

    /**
     * @var FxocmHelperData $fxocmhelperdata
     */
    protected $fxocmhelperdata;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->onlyMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->uploadToQuoteViewModel = $this->getMockBuilder(UploadToQuoteViewModel::class)
            ->onlyMethods(['isUploadToQuoteEnable'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fxocmhelperdata = $this->getMockBuilder(FxocmHelperData::class)
            ->onlyMethods(['getFxoCMToggle'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestQueryValidator = $this->getMockBuilder(RequestQueryValidator::class)
            ->onlyMethods(['isGraphQl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contentAssociationsResolver = $this->createMock(ContentAssociationsResolver::class);

        $objectManagerHelper = new ObjectManager($this);

        $this->buyRequestBuilderMock = $objectManagerHelper->getObject(
            BuyRequestBuilder::class,
            [
                'toggleConfig' =>  $this->toggleConfig,
                'uploadToQuoteViewModel' =>  $this->uploadToQuoteViewModel,
                'fxocmhelperdata' => $this->fxocmhelperdata,
                'requestQueryValidator' => $this->requestQueryValidator,
                'contentAssociationsResolver' => $this->contentAssociationsResolver
            ]
        );
    }

    /**
     * Test isDbEnhancementToggleEnabled
     *
     * @return array
     */
    public function testBuild()
    {
        $this->fxocmhelperdata->expects($this->any())->method('getFxoCMToggle')->willReturn(true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->requestQueryValidator->expects($this->once())->method('isGraphQl')->willReturn(false);
        $this->uploadToQuoteViewModel->expects($this->any())->method('isUploadToQuoteEnable')->willReturn(true);
        $this->contentAssociationsResolver->expects($this->any())
            ->method('getContentReference')
            ->willReturn('13169008874182887338515073287980402046350');
        $requestData = '{
            "fxoMenuId":"1614105200640-4",
            "productType" : "COMMERCIAL_PRODUCT",
            "integratorProductReference": "1614105200640-4",
            "isEditable":true,
            "isEdited":false,
            "product": {
                "contentAssociations": [
                    {
                        "contentReference": "1integratorProductReference3169008874182887338515073287980402046350"
                    }
                ],
                 "designId": [
                            {
                                "designId": "DAF0yfv8Qpg"
                            }
                ],
                "partnerProductId": [
                            {
                                "partnerProductId": "CVAFLY1021"
                            }
                ]
            },
            "fxoProductInstance": {
                "productConfig": {
                    "product": {
                        "contentAssociations": [
                            {
                                "contentReference": "13169008874182887338515073287980402046350"
                            }
                        ]
                    }
                },
                "productRateTotal": {
                    "currency": "USD",
                    "quantity": 50,
                    "price": "$50.82",
                    "priceAfterDiscount": "$50.82",
                    "unitOfMeasure": "EACH",
                    "totalDiscount": "$0.00",
                    "productLineDetails": [
                    ]
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
                "fileManagementState": []
            },
            "integratorProductReference":"1614105200640-4"
        }';

        $requestData = json_decode($requestData, true);

        $expectedData = '{
            "external_prod":[{
                "contentAssociations":[{
                    "contentReference":"13169008874182887338515073287980402046350"
                }],
                "preview_url":"13169008874182887338515073287980402046350",
                "isEditable":true,
                "isEdited":false,
                "fxoMenuId":"1614105200640-4"
            }],
            "productConfig":[],
            "productRateTotal":{
                "currency":"USD",
                "quantity":50,
                "price":"$50.82",
                "priceAfterDiscount":"$50.82",
                "unitOfMeasure":"EACH",
                "totalDiscount":"$0.00",
                "productLineDetails":[]
            },
            "quantityChoices":["50","100","250","500","1000"],
            "fileManagementState":[],
            "fxoMenuId":"1614105200640-4"
        }';

        $expectedData = json_decode($expectedData, true);
        $this->assertIsArray($this->buyRequestBuilderMock->build($requestData));
    }

    /**
     * Test isDbEnhancementToggleEnabled
     *
     * @return array
     */
    public function testBuildElse()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $consecutive = $this->onConsecutiveCalls(false, true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn($consecutive);
        $this->requestQueryValidator->expects($this->once())->method('isGraphQl')->willReturn(true);
        $this->uploadToQuoteViewModel->expects($this->any())->method('isUploadToQuoteEnable')->willReturn(true);
        $requestData = '{
            "fxoMenuId":"1614105200640-4",
            "productType" : "COMMERCIAL_PRODUCT",
            "fxoProductInstance": {
                "productConfig": {
                    "product": {
                        "contentAssociations": [
                            {
                                "contentReference": "13169008874182887338515073287980402046350"
                            }
                        ]
                    }
                },
                "productRateTotal": {
                    "currency": "USD",
                    "quantity": 50,
                    "price": "$50.82",
                    "priceAfterDiscount": "$50.82",
                    "unitOfMeasure": "EACH",
                    "totalDiscount": "$0.00",
                    "productLineDetails": [
                    ]
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
                "fileManagementState": []
            }
        }';

        $requestData = json_decode($requestData, true);

        $expectedData = '{
            "external_prod":[{
                "contentAssociations":[{
                    "contentReference":"13169008874182887338515073287980402046350"
                }],
                "preview_url":"13169008874182887338515073287980402046350",
                "isEditable":true,
                "isEdited":false,
                "fxoMenuId":"1614105200640-4"
            }],
            "productConfig":[],
            "productRateTotal":{
                "currency":"USD",
                "quantity":50,
                "price":"$50.82",
                "priceAfterDiscount":"$50.82",
                "unitOfMeasure":"EACH",
                "totalDiscount":"$0.00",
                "productLineDetails":[]
            },
            "quantityChoices":["50","100","250","500","1000"],
            "fileManagementState":[],
            "fxoMenuId":"1614105200640-4"
        }';

        $expectedData = json_decode($expectedData, true);
        $this->assertIsArray($this->buyRequestBuilderMock->build($requestData));
    }

    /**
     * Test that preview_url is correctly set from content reference
     *
     * @return void
     */
    public function testPreviewUrlSetFromContentReference(): void
    {
        $this->fxocmhelperdata->expects($this->once())
            ->method('getFxoCMToggle')
            ->willReturn(false);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->requestQueryValidator->expects($this->once())
            ->method('isGraphQl')
            ->willReturn(true);

        $this->uploadToQuoteViewModel->expects($this->any())
            ->method('isUploadToQuoteEnable')
            ->willReturn(true);

        $expectedContentReference = 'test-content-reference-12345';
        $this->contentAssociationsResolver->expects($this->once())
            ->method('getContentReference')
            ->willReturn($expectedContentReference);

        $requestData = [
            'fxoMenuId' => 'test-menu-id',
            'fxoProductInstance' => [
                'productConfig' => [
                    'product' => [
                        'contentAssociations' => [
                            [
                                'contentReference' => 'original-content-reference'
                            ]
                        ]
                    ]
                ],
                'isEditable' => true,
                'isEdited' => false,
                'productRateTotal' => [],
                'quantityChoices' => [],
                'fileManagementState' => []
            ]
        ];

        $result = $this->buyRequestBuilderMock->build($requestData);

        $this->assertArrayHasKey('external_prod', $result);
        $this->assertIsArray($result['external_prod']);
        $this->assertNotEmpty($result['external_prod']);
        $this->assertArrayHasKey('preview_url', $result['external_prod'][0]);

        $this->assertEquals(
            $expectedContentReference,
            $result['external_prod'][0]['preview_url'],
            'The preview_url should be set to the content reference value'
        );
    }
}
