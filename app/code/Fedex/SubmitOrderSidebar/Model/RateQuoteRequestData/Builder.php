<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\RateQuoteRequestData;

use Fedex\SubmitOrderSidebar\Api\RateQuoteRequestDataInterface;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\ComputerRental\Model\CRdataModel;

class Builder
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param SubmitOrderHelper $submitOrderHelper
     * @param CRdataModel $crData
     */
    public function __construct(
        private StoreManagerInterface $storeManager,
        private SubmitOrderHelper $submitOrderHelper,
        private CRdataModel $crData
    )
    {
    }

    /**
     * @param RateQuoteRequestDataInterface $rateQuoteRequest
     * @param bool $isPickup
     * @return array|array[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFormmatedData(RateQuoteRequestDataInterface $rateQuoteRequest, bool $isPickup): array
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $webhookUrl = "{$baseUrl}rest/V1/fedexoffice/orders/{$rateQuoteRequest->getOrderNumber()}/status";

        $userReferences = null;
        $getUuid = $this->submitOrderHelper->getUuid();

        if (!empty($getUuid)) {
            $userReferences = [
                [
                    'reference' => $getUuid,
                    'source' => 'FCL'
                ],
            ];
        }
        $fedexLocationId = null;
        if($this->crData->isRetailCustomer()){
            $fedexLocationId = $this->crData->getLocationCode();
        }

        $data = [
            'rateQuoteRequest' => [
                'sourceRetailLocationId' => $rateQuoteRequest->getSourceRetailLocationId(),
                'previousQuoteId' => null,
                'action' => 'SAVE_COMMIT',
                'retailPrintOrder' => [
                    'fedExAccountNumber' => $rateQuoteRequest->getFedexAccountNumber(),
                    'origin' => [
                        'orderNumber' => $rateQuoteRequest->getOrderNumber(),
                        'orderClient' => 'MAGENTO',
                        'site' => $rateQuoteRequest->getCompanySite(),
                        'userReferences' => $userReferences,
                        'fedExLocationId'=>$fedexLocationId
                    ],
                    'orderContact' => [
                        'contact' => [
                            'contactId' => null,
                            'personName' => [
                                'firstName' => $rateQuoteRequest->getFirstname(),
                                'lastName' => $rateQuoteRequest->getLastname()
                            ],
                            'company' => [
                                'name' => 'FXO'
                            ],
                            'emailDetail' => [
                                'emailAddress' => $rateQuoteRequest->getEmail()
                            ],
                            'phoneNumberDetails' => [
                                0 => [
                                    'phoneNumber' => [
                                        'number' => $rateQuoteRequest->getTelephone(),
                                        'extension' => null
                                    ],
                                    'usage' => 'PRIMARY'
                                ]
                            ]
                        ]
                    ],
                    'customerNotificationEnabled' => false,
                    'notificationRegistration' => [
                        'webhook' => [
                            'url' => $webhookUrl,
                            'auth' => null
                        ]
                    ],
                    'profileAccountId' => null,
                    'expirationDays' => '30',
                    'products' => $rateQuoteRequest->getProducts(),
                    'recipients' => [
                        0 => [
                            'reference' => $rateQuoteRequest->getShipmentId(),
                            'contact' => [
                                'contactId' => null,
                                'personName' => [
                                    'firstName' => $rateQuoteRequest->getFirstname(),
                                    'lastName' => $rateQuoteRequest->getLastname()
                                ],
                                'company' => [
                                    'name' => 'FXO'
                                ],
                                'emailDetail' => [
                                    'emailAddress' => $rateQuoteRequest->getEmail()
                                ],
                                'phoneNumberDetails' => [
                                    0 => [
                                        'phoneNumber' => [
                                            'number' => $rateQuoteRequest->getTelephone(),
                                            'extension' => null
                                        ],
                                        'usage' => 'PRIMARY'
                                    ]
                                ]
                            ],
                            'productAssociations' => $rateQuoteRequest->getProductAssociations(),
                        ],
                    ]
                ],
                'coupons' => !empty($rateQuoteRequest->getPromoCode()['code']) ?
                    [$rateQuoteRequest->getPromoCode()] :
                    null,
                'teamMemberId' => null,
            ]
        ];

        $data['rateQuoteRequest']['retailPrintOrder']['recipients'][0] = array_merge(
            $data['rateQuoteRequest']['retailPrintOrder']['recipients'][0],
            $this->getShippingInformation($rateQuoteRequest, $isPickup)
        );

        return $data;
    }

    /**
     * @param RateQuoteRequestDataInterface $rateQuoteRequest
     * @param bool $isPickup
     * @return array[]
     */
    private function getShippingInformation(RateQuoteRequestDataInterface $rateQuoteRequest, bool $isPickup): array
    {
        if (!$isPickup) {
            return [
                'shipmentDelivery' => [
                    'address' => [
                        'streetLines' => $rateQuoteRequest->getStreetAddress(),
                        'city' => $rateQuoteRequest->getCity(),
                        'stateOrProvinceCode' => $rateQuoteRequest->getShipperRegion(),
                        'postalCode' => $rateQuoteRequest->getZipCode(),
                        'countryCode' => 'US',
                        'addressClassification' => $rateQuoteRequest->getAddressClassification()
                    ],
                    'holdUntilDate' => null,
                    'serviceType' => $rateQuoteRequest->getShipMethod(),
                    'fedExAccountNumber' => $rateQuoteRequest->getFedexShipAccountNumber(),
                    'deliveryInstructions' => null,
                    'poNumber' => $rateQuoteRequest->getPoNumber()
                ]
            ];
        }

        return [
            'pickUpDelivery' => [
                'location' => [
                    'id' => $rateQuoteRequest->getLocationId(),
                ],
                'requestedPickupLocalTime' => $rateQuoteRequest->getRequestedPickupLocalTime()
            ]
        ];
    }
}
