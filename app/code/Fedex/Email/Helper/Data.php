<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Email\Helper;

use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Fedex\MarketplaceCheckout\Model\Email;

/**
 * Data Helper
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const FEDEX_OFFICE = 'Fedex Office';
    public const CHANNEL = 'Print On Demand';
    public const CUSTOMER_RELATIONS_PHONE = '1.800.GoFedEx 1.800.463.3339';
    public const ERR_MSG = ' Could not retrieve customer name or email.';
    public const MAIL_TO = '[mailto: ';

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param LayoutFactory $layoutFactory
     * @param SendEmail $mail
     * @param \Fedex\Punchout\Helper\Data $punchoutHelper
     * @param CompanyRepositoryInterface $companyRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param ToggleConfig $toggleConfig
     * @param LoggerInterface $logger
     * @param Email $emailHelper
     * @param ConfigInterface $productBundleConfig
     */
    public function __construct(
        protected Context                     $context,
        protected Session                     $customerSession,
        protected CustomerRepositoryInterface $customerRepository,
        protected LayoutFactory               $layoutFactory,
        protected SendEmail                   $mail,
        protected \Fedex\Punchout\Helper\Data $punchoutHelper,
        protected CompanyRepositoryInterface  $companyRepository,
        protected CartRepositoryInterface     $quoteRepository,
        protected ToggleConfig                $toggleConfig,
        protected LoggerInterface             $logger,
        protected Email                       $emailHelper,
        protected ConfigInterface             $productBundleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Get Current Customer
     *
     * @return object|boolean $customer|false
     */
    public function getCustomer()
    {
        $id = $this->customerSession->getCustomer()->getId(); //Get Current customer ID
        if ($id) {
            return $this->customerRepository->getById($id);
        } else {
            return false;
        }

    }
    /**
     * Get Assigned Company
     *
     * @param object $customer
     * @return object $company
     */
    public function getAssignedCompany($customer = null)
    {
        $companyId = $this->customerSession->getCustomerCompany();
        if ((int) $companyId == 0) {
            $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
            $companyId = $companyAttributes->getCompanyId();
        }
        return $this->companyRepository->get((int) $companyId);
    }

    /**
     * Send  email on Quote creation
     *
     * @param int $quoteId
     * @return boolean|string $result
     */
    public function sendEmailNotification($quoteId)
    {
            $quote = $this->quoteRepository->get($quoteId);
                $subtotalAmountWithDiscount = $quote->getSubtotal() - $quote->getDiscount();
                $amount = (($quote->getSubtotal() > 0) ? (string) $subtotalAmountWithDiscount : '');

        $customer = $quote->getCustomer();

        if (!empty($customer) && $customer->getId() && $this->getIsQuoteNotificationEnable($customer)) {
            if ($this->getIsQuoteNotificationEnable($customer)) {
                $name = trim($customer->getFirstName() . ' ' . $customer->getLastName());
                // D-184291 ePro-Updated email id not received Quote confirmation
                if ($this->toggleConfig->getToggleConfigValue('explorers_d_184291_fix')) {
                    $email = $quote->getCustomerEmail();
                } else {
                    $email = $customer->getEmail();
                }
                /** enable updated epro email template */
                $subject = null;
                if($this->toggleConfig->getToggleConfigValue('enable_epro_email')) {
                    $templateId = 'generic_template';
                    $subject = self::FEDEX_OFFICE.' '.self::CHANNEL.' - Quote Confirmation (Order '. $quote->getGtn().')';
                    $emailData = $this->getQuoteTemplateData($quoteId, 'ePro_quote_confirmation_template');
                    $templateD = [
                        "messages" => [
                            "statement" => addslashes($this->emailHelper->minifyHtml($emailData["template"])),
                            "url" => $this->emailHelper->getEmailLogoUrl(),
                            "disclaimer" => ''
                        ],
                        "order" => [
                            "contact" => [
                                "email" => $email
                            ]
                        ]
                    ];

                    $tokenData = $this->getTokenData();
                } else {
                    $templateId = 'ePro_quote_confirmation';
                    $templateD = [
                        'order' => [
                            'primaryContact' => [
                                'firstLastName' => $name, // Customer name
                            ],
                            'gtn' => $quote->getGtn(), // order no  for quote.
                            'productionCostAmount' => $amount, //quote sub total price excluding shipping & taxes.
                        ],
                        'producingCompany' => [
                            'name' => self::FEDEX_OFFICE,
                            'customerRelationsPhone' => self::CUSTOMER_RELATIONS_PHONE,
                        ],
                        'user' => [
                            'emailaddr' => $email,
                        ],
                        'channel' => self::CHANNEL,
                    ];

                    $tokenData['access_token'] = '';
                    $tokenData['auth_token'] = '';
                    $tokenData['token_type'] = '';

                    if ($this->customerSession->getApiAccessToken() && $this->customerSession->getApiAccessType()
                    && $this->punchoutHelper->getAuthGatewayToken()) {
                        $tokenData['access_token'] = $this->customerSession->getApiAccessToken();
                        $tokenData['token_type'] = $this->customerSession->getApiAccessType();
                        $tokenData['auth_token'] = $this->punchoutHelper->getAuthGatewayToken();
                    } else {
                        $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error retrieving token data.');
                    }
                }

                $templateData = json_encode($templateD, JSON_UNESCAPED_SLASHES);

                $customerData = ['name' => $name, 'email' => $email];

                return $this->mail->sendMail($customerData, $templateId, $templateData, $tokenData, null, $subject);
            }
        }
    }

    /**
     * Identify whether quote notification mail enable/disable
     *
     * @param object $customer
     * @return boolean
    */
    public function getIsQuoteNotificationEnable($customer)
    {
        $company = $this->getAssignedCompany($customer);
        return $company->getIsQuoteRequest();
    }

    /**
     * Get Taz Token And Token Type
     *
     * @return array
     */
    public function getApiToken()
    {
        $accessToken = '';
        $tokenType = '';
        if ($this->customerSession->getApiAccessToken() && $this->customerSession->getApiAccessType()) {
            $accessToken = $this->customerSession->getApiAccessToken();
            $tokenType = $this->customerSession->getApiAccessType();
        }
        return ['token' => $accessToken, 'type' => $tokenType];
    }

    /**
     * Get Gateway Token For API Communication
     *
     * @return string $token
     */
    public function getGateToken()
    {
        return $this->punchoutHelper->getGatewayToken();
    }

    /**
     * Send  email on Quote Rejection
     *
     * @param int $quoteId
     * @param int $ponumber
     * @return boolean|string $mailStatus
     */
    public function orderRejectEmail($quoteId, $ponumber)
    {
        $tokenData = $this->getTokenData();

        $quoteDetails = $this->quoteRepository->get($quoteId);
        $customer = $this->customerRepository->getById($quoteDetails->getCustomerId());

        if ($this->getIsOrderRejectNotificationEnable($customer)) {
            $name = trim($quoteDetails->getCustomerFirstname() . ' ' . $quoteDetails->getCustomerLastname());
            $email = $quoteDetails->getCustomerEmail();

            /** enable updated epro email template */
            $subject = null;
            if($toggle = $this->toggleConfig->getToggleConfigValue('enable_epro_email')) {
                $templateId = 'generic_template';
                $subject = self::FEDEX_OFFICE.' '.self::CHANNEL.' - Order Rejected (Order '. $quoteDetails->getGtn().')';
                $emailData = $this->getQuoteTemplateData($quoteId, 'ePro_order_rejection_template', array('po_number'=>$ponumber));
                $templateD = [
                    "messages" => [
                        "statement" => addslashes($this->emailHelper->minifyHtml($emailData["template"])),
                        "url" => $this->emailHelper->getEmailLogoUrl(),
                        "disclaimer" => ''
                    ],
                    "order" => [
                        "contact" => [
                            "email" => $email
                        ]
                    ]
                ];

            } else {
                $templateId = 'ePro_order_rejected';
            $templateD = [
                'order' => [
                    'primaryContact' => [
                        'firstLastName' => $name ? $name : '', // Customer name
                    ],
                    'gtn' => $quoteDetails->getGtn(), // order no.  for quote.
                    'productionCostAmount' => $quoteDetails->getGrandTotal() ?
                    $quoteDetails->getGrandTotal() : 0, //quote sub total price excluding shipping & taxes.
                    'rejectionReason' => 'Purchase Order # ' . $ponumber . ' does not match Order '
                     . $quoteId . '. Please resubmit a corrected order',
                ],
                'producingCompany' => [
                    'name' => self::FEDEX_OFFICE,
                    'customerRelationsPhone' => self::CUSTOMER_RELATIONS_PHONE,
                ],
                'user' => [
                    'emailaddr' => self::MAIL_TO . $email . ' ] ' . $email,
                ],
                'channel' => self::CHANNEL,
            ];
        }

            $templateData = json_encode($templateD, JSON_UNESCAPED_SLASHES);
            if (!empty($name && $email)) {
                $customerData = ['name' => $name, 'email' => $email];
                return $this->mail->sendMail($customerData, $templateId, $templateData, $tokenData, null, $subject);
                //return $mailStatus;
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . self::ERR_MSG);
            }
        }

    }
    /**
     * Identify whether quote rejection notification mail enable/disable
     *
     * @param object $customer
     * @return boolean
     */
    public function getIsOrderRejectNotificationEnable($customer)
    {
        $company = $this->getAssignedCompany($customer);
        return $company->getIsOrderReject();
    }

    /**
     * Send  email when quote expired
     *
     * @param int $quoteId
     * @return $result
     */
    public function orderExpiredEmail($quoteId)
    {
        $tokenData = $this->getTokenData();

        $quoteDetails = $this->quoteRepository->get($quoteId);
        $customer = $this->customerRepository->getById($quoteDetails->getCustomerId());
        if ($this->getIsOrderExpiredNotificationEnable($customer)) {
            $name = trim($quoteDetails->getCustomerFirstname() . ' ' . $quoteDetails->getCustomerLastname());
            $email = $quoteDetails->getCustomerEmail();
            /** enable updated epro email template */
            $subject = null;
            if($this->toggleConfig->getToggleConfigValue('enable_epro_email')) {
                $templateId = 'generic_template';
                $subject = self::FEDEX_OFFICE.' '.self::CHANNEL.' - Order Expired Confirmation (Order '. $quoteDetails->getGtn().')';
                $emailData = $this->getQuoteTemplateData($quoteId, 'ePro_expired_quote_template');
                $templateArr = [
                    "messages" => [
                        "statement" => addslashes($this->emailHelper->minifyHtml($emailData["template"])),
                        "url" => $this->emailHelper->getEmailLogoUrl(),
                        "disclaimer" => ''
                    ],
                    "order" => [
                        "contact" => [
                            "email" => $email
                        ]
                    ]
                ];
            } else {
                $templateId = 'ePro_quote_expire_confirmation';
            $templateArr = [
                'order' => [
                    'primaryContact' => [
                        'firstLastName' => $name ? $name : '',
                    ],
                    'gtn' => $quoteDetails->getGtn(),
                    'productionCostAmount' => $quoteDetails->getGrandTotal() ? $quoteDetails->getGrandTotal() : 0,

                ],
                'producingCompany' => [
                    'name' => self::FEDEX_OFFICE,
                    'customerRelationsPhone' => self::CUSTOMER_RELATIONS_PHONE,
                ],
                'user' => [
                    'emailaddr' => self::MAIL_TO . $email . ' ] ' . $email,
                ],
                'channel' => self::CHANNEL,
            ];
        }

            $templateData = json_encode($templateArr, JSON_UNESCAPED_SLASHES);
            if (!empty($name && $email)) {
                $customerData = ['name' => $name, 'email' => $email];
                return $this->mail->sendMail($customerData, $templateId, $templateData, $tokenData, null, $subject);
                //return $mailStatus;
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . self::ERR_MSG);
            }
        }
    }

    /**
     * Identify whether quote expired notification mail enable/disable
     *
     * @param object $customer
     * @return boolean
     */
    public function getIsOrderExpiredNotificationEnable($customer)
    {
        $company = $this->getAssignedCompany($customer);
        return $company->getIsExpiredOrder();
    }

    /**
     * Send  email on Quote creation
     *
     * @param int $quoteId
     * @param date $expiringDate
     * @return boolean|string $mailstatus
     */
    public function orderExpiringEmail($quoteId, $expiringDate)
    {
        $tokenData = $this->getTokenData();

        $quoteDetails = $this->quoteRepository->get($quoteId);
        $customer = $this->customerRepository->getById($quoteDetails->getCustomerId());
        if ($this->getIsOrderExpiringOrderNotificationEnable($customer)) {
            $name = trim($quoteDetails->getCustomerFirstname() . ' ' . $quoteDetails->getCustomerLastname());
            $email = $quoteDetails->getCustomerEmail();

            /** enable updated epro email template */
            $subject = null;
            if($this->toggleConfig->getToggleConfigValue('enable_epro_email')) {
                $templateId = 'generic_template';
                $subject = self::FEDEX_OFFICE.' '.self::CHANNEL.' - Expiring Quote Notification (Order '. $quoteDetails->getGtn().')';
                $expireDate = [
                    'expire_date' => $expiringDate ? date("F d, Y", strtotime($expiringDate)) : ''
                ];
                $emailData = $this->getQuoteTemplateData($quoteId, 'ePro_expiring_quote_template', $expireDate);
                $templateD = [
                    "messages" => [
                        "statement" => addslashes($this->emailHelper->minifyHtml($emailData["template"])),
                        "url" => $this->emailHelper->getEmailLogoUrl(),
                        "disclaimer" => ''
                    ],
                    "order" => [
                        "contact" => [
                            "email" => $email
                        ]
                    ]
                ];

            } else {
                $templateId = 'ePro_pending_quote_about_to_expire';

            $templateD = [
                'order' => [
                    'primaryContact' => [
                        'firstLastName' => $name ? $name : '',
                    ],
                    'gtn' => $quoteDetails->getGtn(),
                    'productionCostAmount' => $quoteDetails->getGrandTotal(),
                    'orderCreateDate' => $quoteDetails->getCreatedAt() ?
                     date("m/d/Y", strtotime($quoteDetails->getCreatedAt())) : '',
                    'expireDate' => $expiringDate ? date("m/d/Y", strtotime($expiringDate)) : '',
                ],
                'producingCompany' => [
                    'name' => self::FEDEX_OFFICE,
                    'customerRelationsPhone' => self::CUSTOMER_RELATIONS_PHONE,
                ],
                'user' => [
                    'emailaddr' => self::MAIL_TO . $email . ' ] ' . $email,
                ],
                'channel' => self::CHANNEL,
            ];
        }

            $templateData = json_encode($templateD, JSON_UNESCAPED_SLASHES);
            if (!empty($name && $email)) {
                $customerData = ['name' => $name, 'email' => $email];
                return $this->mail->sendMail($customerData, $templateId, $templateData, $tokenData, null, $subject);
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . self::ERR_MSG);
            }
        }
    }

    /**
     * Identify whether quote expiring notification mail enable/disable
     *
     * @param object $customer
     * @return boolean
     */
    public function getIsOrderExpiringOrderNotificationEnable($customer)
    {
        $company = $this->getAssignedCompany($customer);
        return $company->getIsExpiringOrder();
    }

    /**
     * Returns the token data array
     *
     * @return array
     */
    private function getTokenData()
    {
        $tokenData['access_token'] = $this->punchoutHelper->getTazToken() ?? "";
        $tokenData['auth_token'] = $this->punchoutHelper->getAuthGatewayToken() ?? "";
        return $tokenData;
    }

    /**
     * @param $shipmentItems
     * @param $totalDetails
     * @return mixed
     */
    public function quoteItemHtml($shipmentItems, $totalDetails)
    {
        $quoteLayout = $this->layoutFactory->create();
        return $quoteLayout->createBlock(\Fedex\Email\Block\Order\Email\Items::class)
        ->setName('fedex_quote_items')
        ->setArea('frontend')
        ->setData('shipment_items', $shipmentItems)
        ->setData('total_details', $totalDetails)
        ->toHtml();
    }

    /**
     * @param $quoteId
     * @param $templatId
     * @param array $otherDetails
     * @return array
     */
    public function getQuoteTemplateData($quoteId, $templateId, $otherDetails=array())
    {
        $quoteDetails = $this->quoteRepository->get($quoteId);
        $shippingMethod = $quoteDetails->getShippingAddress()->getShippingMethod() ?? '';
        $quoteShipmentData = $quoteDetails->getShippingAddress()->getData();
        $shipmentItems=[];

        foreach ($quoteDetails->getItemsCollection() as $item) {
            $shipmentItems[$item->getItemId()]["name"] = $item->getName();
            $shipmentItems[$item->getItemId()]["qty"] = $item->getQty();
            $shipmentItems[$item->getItemId()]["row_total"] = $item->getRowTotal();

            if ($shippingMethod !== 'fedexshipping_PICKUP') {
                $isExpectedDeliveryDateEnabled =
                    (bool) $this->toggleConfig->getToggleConfigValue('sgc_enable_expected_delivery_date');

                if ($isExpectedDeliveryDateEnabled) {
                    $shippingDescription = explode(' - ', $quoteShipmentData['shipping_description'] ?? '');
                    if (isset($shippingDescription[0])) {
                        $shipmentItems[$item->getItemId()]["shipping_label"] = $shippingDescription[0];
                    }
                    if (isset($shippingDescription[1])) {
                        $shipmentItems[$item->getItemId()]["shipping_expected_delivery_date"] = $shippingDescription[1];
                    }
                } else {
                    $shipmentItems[$item->getItemId()]["shipping_label"] =
                        explode(' - ', $quoteShipmentData['shipping_description'] ?? '')[0];
                }
                $shipmentItems[$item->getItemId()]["shipping_total"] = $quoteShipmentData['shipping_amount'];
            }
        }

        $totalDetails=[];
        $quoteTemplateData=[];
        $totalDetails['sub_total'] = $quoteDetails->getSubtotal();
        $totalDetails['shipping']= $quoteShipmentData['shipping_amount'];
        $totalDetails['shipping_estimate'] = $quoteShipmentData['shipping_amount'];
        $totalDetails['discount']=$quoteDetails->getDiscount();
        $totalDetails['discounts']=['price'=>'10.0'];
        $totalDetails['tax'] = $quoteDetails->getCustomTaxAmount();
        $totalDetails['total'] = (float)$quoteDetails->getGrandTotal();
        if ($this->productBundleConfig->isTigerE468338ToggleEnabled()) {
            $totalDetails['discounts'] = [
                ['label' => 'Account Discount', 'price' => (float)$quoteDetails->getAccountDiscount()],
                ['label' => 'Bundle Discount', 'price' => (float)$quoteDetails->getBundleDiscount()],
                ['label' => 'Volume Discount', 'price' => (float)$quoteDetails->getVolumeDiscount()],
                ['label' => 'Promo Discount', 'price' => (float)$quoteDetails->getPromoDiscount()],
                ['label' => 'Shipping Discount', 'price' => (float)$quoteDetails->getShippingDiscount()]
            ];
        } else {
            $totalDetails['discounts'] = [
                ['label' => 'Account Discount', 'price' => (float)$quoteDetails->getAccountDiscount()],
                ['label' => 'Volume Discount', 'price' => (float)$quoteDetails->getVolumeDiscount()],
                ['label' => 'Promo Discount', 'price' => (float)$quoteDetails->getPromoDiscount()],
                ['label' => 'Shipping Discount', 'price' => (float)$quoteDetails->getShippingDiscount()]
            ];
        }

        $quoteTemplateData["customer_first_name"] = $quoteDetails->getCustomerFirstname();
        $quoteTemplateData["quote_id"] = $quoteId;
        $quoteTemplateData['quote_date'] = $this->emailHelper->getFormattedCstDate($quoteDetails->getCreatedAt());
        $quoteTemplateData["customer_last_name"] = $quoteDetails->getCustomerLastname();

        if(!empty($otherDetails['expire_date'])) {
            $quoteTemplateData['quote_expiring_date'] = $otherDetails['expire_date'];
            $quoteTemplateData['quote_placed_date'] = date("F d, Y", strtotime($quoteDetails->getCreatedAt()));
        }
        if(!empty($otherDetails['po_number'])) {
            $quoteTemplateData['po_number'] = $otherDetails['po_number'];
        }

        $quoteTemplateData["item_html"] = $this->quoteItemHtml($shipmentItems, $totalDetails);
        $emailData = $this->emailHelper->getEmailHtml($templateId, $quoteTemplateData);
        return $emailData;
    }
}
