<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\TransactionApi;

use Fedex\GraphQl\Helper\Data;
use Magento\Quote\Model\Quote;
use Fedex\SubmitOrderSidebar\Model\TransactionApi\InStoreRequestBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InStoreRequestBuilderTest extends TestCase
{
    protected $quote;
    /**
     * @var Data|MockObject
     */
    private Data $graphQlHelperMock;

    /**
     * @var InStoreRequestBuilder
     */
    private InStoreRequestBuilder $inStoreRequestBuilder;

    protected $getTransactionResponse = '{
        "transactionId": "9c6bef2a-7f4a-4e25-9112-d0844ccbfd23",
        "output": {
            "transaction": {
                "transactionHeader": {
                    "guid": "e34b1e0e-fb72-4870-892b-48a5fe64ed62",
                    "type": "SALE",
                    "transactionDateTime": "2023-03-01T03:29:33.6148628-06:00",
                    "retailTransactionId": "ADSKDC8C63FF1AFD04X",
                    "virtualTill": false,
                    "transactionReferenceId": "e9f849fb-4b77-4aba-84b8-00cb1dc78ebc",
                    "sellingDeviceID": "ADSKD-C8C-04",
                    "sellingLocationID": "3088",
                    "terminalNumber": "0"
                },
                "returnQuantities": [
                    {
                        "unitQuantityRemaining": 1,
                        "unitQuantityReturned": 0,
                        "instanceId": "7"
                    },
                    {
                        "unitQuantityRemaining": 50,
                        "unitQuantityReturned": 0,
                        "instanceId": "11"
                    },
                    {
                        "unitQuantityRemaining": 1,
                        "unitQuantityReturned": 0,
                        "instanceId": "8"
                    },
                    {
                        "unitQuantityRemaining": 1,
                        "unitQuantityReturned": 0,
                        "instanceId": "9"
                    },
                    {
                        "unitQuantityRemaining": 1,
                        "unitQuantityReturned": 0,
                        "instanceId": "10"
                    },
                    {
                        "unitQuantityRemaining": 1,
                        "unitQuantityReturned": 0,
                        "instanceId": "12"
                    }
                ],
                "grossAmount": "79.19",
                "totalDiscountAmount": "0.0",
                "netAmount": "79.19",
                "taxableAmount": "79.19",
                "taxAmount": "0.26",
                "totalAmount": "79.45",
                "productLines": [
                    {
                        "instanceId": "7",
                        "productId": "1456773326927",
                        "unitQuantity": 1,
                        "unitOfMeasurement": "EACH",
                        "productRetailPrice": "3.19",
                        "productDiscountAmount": "0.0",
                        "productLinePrice": "3.19",
                        "productLineDetails": [
                            {
                                "instanceId": "8",
                                "detailCode": "0337",
                                "description": "8.5x11 Lamination",
                                "priceRequired": false,
                                "priceOverridable": false,
                                "unitQuantity": 1,
                                "quantity": 1,
                                "detailPrice": "1.99",
                                "detailDiscountPrice": "0.0",
                                "detailUnitPrice": "1.990000",
                                "detailDiscountedUnitPrice": "1.990000",
                                "detailRetailPrice": "1.99"
                            },
                            {
                                "instanceId": "9",
                                "detailCode": "40075",
                                "hasTermsAndConditions": true,
                                "description": "FS Color SS Resume",
                                "priceRequired": false,
                                "priceOverridable": false,
                                "unitQuantity": 1,
                                "quantity": 1,
                                "detailPrice": "0.70",
                                "detailDiscountPrice": "0.0",
                                "detailUnitPrice": "0.700000",
                                "detailDiscountedUnitPrice": "0.700000",
                                "detailRetailPrice": "0.70"
                            },
                            {
                                "instanceId": "10",
                                "detailCode": "0335",
                                "description": "Pouch Lam Trim",
                                "priceRequired": false,
                                "priceOverridable": false,
                                "unitQuantity": 1,
                                "quantity": 1,
                                "detailPrice": "0.50",
                                "detailDiscountPrice": "0.0",
                                "detailUnitPrice": "0.500000",
                                "detailDiscountedUnitPrice": "0.500000",
                                "detailRetailPrice": "0.50"
                            }
                        ],
                        "name": "Custom Multi Sheet",
                        "userProductName": "B-1491628-Code-Assertion-Report",
                        "type": "PRINT_PRODUCT",
                        "priceable": true,
                        "orderAssociationRefId": "2",
                        "productUnitPrice": "3.1900",
                        "productDiscountedUnitPrice": "3.1900"
                    },
                    {
                        "instanceId": "11",
                        "productId": "1463680545590",
                        "unitQuantity": 50,
                        "unitOfMeasurement": "EACH",
                        "productRetailPrice": "76.00",
                        "productDiscountAmount": "0.0",
                        "productLinePrice": "76.00",
                        "productLineDetails": [
                            {
                                "instanceId": "12",
                                "detailCode": "40005",
                                "hasTermsAndConditions": true,
                                "description": "Full Pg Clr Flyr 50",
                                "priceRequired": false,
                                "priceOverridable": false,
                                "unitQuantity": 1,
                                "quantity": 1,
                                "detailPrice": "76.00",
                                "detailDiscountPrice": "0.0",
                                "detailUnitPrice": "76.000000",
                                "detailDiscountedUnitPrice": "76.000000",
                                "detailRetailPrice": "76.00"
                            }
                        ],
                        "name": "Fast Order Flyer",
                        "userProductName": "Screenshot from 2023-01-22 13-57-12",
                        "type": "PRINT_PRODUCT",
                        "priceable": true,
                        "orderAssociationRefId": "2",
                        "productUnitPrice": "1.5200",
                        "productDiscountedUnitPrice": "1.5200"
                    }
                ],
                "deliveryLines": [
                    {
                        "deliveryLineId": "3397",
                        "recipientReference": "3397",
                        "estimatedDeliveryLocalTime": "2023-03-01T17:00:00",
                        "deliveryLineType": "PICKUP",
                        "pickupDetails": {
                            "locationName": "5747",
                            "requestedPickupLocalTime": "2023-03-01T17:00:00"
                        },
                        "productAssociation": [
                            {
                                "productRef": "7",
                                "quantity": "1.0"
                            },
                            {
                                "productRef": "11",
                                "quantity": "50.0"
                            }
                        ],
                        "orderAssociationRefId": "2"
                    }
                ],
                "tenders": [
                    {
                        "id": "1",
                        "paymentType": "CREDIT_CARD",
                        "tenderedAmount": "79.45",
                        "creditCard": {
                            "type": "VISA",
                            "maskedAccountNumber": "411111xxxxxx1111",
                            "accountLast4Digits": "xxxxxxxxxxxx1111"
                        },
                        "currency": "USD"
                    }
                ],
                "orderLineDetails": [
                    {
                        "orderTotalDiscountAmount": "0.0",
                        "orderGrossAmount": "79.19",
                        "orderNonTaxableAmount": "0.00",
                        "orderTaxExemptableAmount": "76.00",
                        "orderNetAmount": "79.19",
                        "orderTaxableAmount": "79.19",
                        "orderTaxAmount": "0.26",
                        "orderTotalAmount": "79.45",
                        "origin": {
                            "orderNumber": "2010159131541756",
                            "orderClient": "MAGENTO",
                            "apiCustomer": "l7e4acbdd6b7d341b0b59234bbdbd4e82e"
                        },
                        "productAssociations": [
                            {
                                "instanceId": "7"
                            },
                            {
                                "instanceId": "11"
                            }
                        ],
                        "printOrderRefId": "2"
                    }
                ],
                "totalRefundedAmount": "0.00",
                "refundedAmount": "0.00",
                "refundedTaxAmount": "0.00",
                "store": {
                    "code": "ADSKD",
                    "address": {
                        "streetLines": [
                            "5901 Winthrop Street",
                            "Ste K-140"
                        ],
                        "city": "Plano",
                        "stateOrProvinceCode": "TX",
                        "postalCode": "75024-0101",
                        "countryCode": "US"
                    },
                    "phone": "972.324.3017"
                },
                "currency": "USD"
            }
        }
    }';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->graphQlHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getJwtParamByKey'])
            ->getMockForAbstractClass();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inStoreRequestBuilder = new InStoreRequestBuilder($this->graphQlHelperMock);
    }

    public function testBuild()
    {
        $fjmpRateQuoteId = 125484545;
        $this->graphQlHelperMock->expects($this->once())->method('getJwtParamByKey')->willReturn('515998');
        $result = $this->inStoreRequestBuilder->build($fjmpRateQuoteId);
        $expectedResult = [
            'checkoutRequest' => [
                'transactionHeader' => [
                    'requestDateTime' => date('Y-m-d H:i:s'),
                    'rateQuoteId' => $fjmpRateQuoteId,
                    'teamMemberId' => '515998',
                    'type' => 'ORDER'
                ],
                'orderClient' => 'FUSE'
            ],
        ];
        $this->assertEquals($result, $expectedResult);
    }

    /**
     * Test case for prepareGetTransactionResponse
     */
    public function testPrepareGetTransactionResponse()
    {
        $transactionResponseData = json_decode($this->getTransactionResponse, true);
        $this->assertNotNull($this->inStoreRequestBuilder->prepareGetTransactionResponse(
            $this->quote,
            $transactionResponseData
        ));
    }
}
