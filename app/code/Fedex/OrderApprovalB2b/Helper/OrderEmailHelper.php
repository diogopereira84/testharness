<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Helper;

use Fedex\MarketplaceProduct\Model\ShopManagement;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\CIDPSG\Helper\Email;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;
use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\SelfReg\Model\EnhanceUserRoles;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\Stdlib\DateTime\Timezone\LocalizedDateToUtcConverterInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * OrderApprovalB2b OrderEmailHelper class
 */
class OrderEmailHelper extends AbstractHelper
{
    /**
     * Email template subject
     */
    public const FEDEX_OFFICE_PPRINT_ON_DEMAND = 'FedEx Office® Print On Demand';

    /**
     * @var String $declineMessage
     */
    public $declineMessage = '';

    public $status;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param AdminConfigHelper $adminConfigHelper
     * @param Email $email
     * @param StoreManagerInterface $storeManager
     * @param OrderRepositoryInterface $orderRepository
     * @param TimezoneInterface $timezoneInterface
     * @param ProductInfoHandler $productInfoHandler
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param ShopManagement $shopManagement
     * @param CountryFactory $countryFactory
     * @param CompanyManagementInterface $companyRepository
     * @param EnhanceUserRoles $enhanceUserRoles
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param SelfReg $selfRegHelper
     * @param LocalizedDateToUtcConverterInterface $utcConverter
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        protected LoggerInterface $logger,
        protected AdminConfigHelper $adminConfigHelper,
        protected Email $email,
        protected StoreManagerInterface $storeManager,
        protected OrderRepositoryInterface $orderRepository,
        private TimezoneInterface $timezoneInterface,
        protected ProductInfoHandler $productInfoHandler,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        protected ShopManagement $shopManagement,
        protected CountryFactory $countryFactory,
        protected CompanyManagementInterface $companyRepository,
        protected EnhanceUserRoles $enhanceUserRoles,
        protected CustomerRepositoryInterface $customerRepositoryInterface,
        protected SelfReg $selfRegHelper,
        protected LocalizedDateToUtcConverterInterface $utcConverter,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Send order email
     *
     * @param array $orderData
     * @return mixed|void
     */
    public function sendOrderGenericEmail($orderData)
    {
        try {
            $order = $this->orderRepository->get($orderData['order_id']);
            if (!empty($orderData['decline_message'])) {
                $this->declineMessage = $orderData['decline_message'];
            }
            switch ($orderData['status']) {
                case AdminConfigHelper::CONFIRMED:
                    if ($this->adminConfigHelper->isB2bOrderEmailEnabled(
                        AdminConfigHelper::XML_PATH_B2B_ORDER_REQUEST_CONFIRMATION_EMAIL_ENABLE
                    )) {
                         $this->prepareGenericEmailData(
                             $order,
                             AdminConfigHelper::CONFIRMED,
                             false
                         );
                    }
                    if ($this->adminConfigHelper->isB2bOrderEmailEnabled(
                        AdminConfigHelper::XML_PATH_B2B_ORDER_ADMIN_REVIEW_EMAIL_ENABLE
                    )) {
                        $this->prepareGenericEmailData(
                            $order,
                            AdminConfigHelper::REVIEW,
                            true
                        );
                    }
                    break;
                case AdminConfigHelper::DECLINE:
                    if ($this->adminConfigHelper->isB2bOrderEmailEnabled(
                        AdminConfigHelper::XML_PATH_B2B_ORDER_REQUEST_DECLINE_EMAIL_ENABLE
                    )) {
                         $this->prepareGenericEmailData(
                             $order,
                             AdminConfigHelper::DECLINE,
                             false
                         );
                    }
                    break;
                case AdminConfigHelper::EXPIRED:
                    if ($this->adminConfigHelper->isB2bOrderEmailEnabledForExpireCronEmail(
                        AdminConfigHelper::XML_PATH_B2B_ORDER_EXPIRED_EMAIL_ENABLE,
                    )) {
                        $this->prepareGenericEmailData(
                            $order,
                            AdminConfigHelper::EXPIRED,
                            true
                        );
                    }
                    break;
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' B2b order email send failure for order id '. $orderData['order_id'].' : '
            . $e->getMessage());
        }
    }

    /**
     * Prepare generic email request data
     *
     * @param object $order
     * @param string $status
     * @param bool $adminReviewFlag
     * @return void
     */
    public function prepareGenericEmailData($order, $status, $adminReviewFlag)
    {
        $this->logger->info(
            __METHOD__ . ':' . __LINE__ . ' call send email function for order '.
            $order->getIncrementId()
        );
        if ($adminReviewFlag) {
            $adminReviewUsers = $this->getCompanyAdminUserDetail($order->getCustomerId());
            if (count($adminReviewUsers) > 0) {
                foreach ($adminReviewUsers as $user => $companyUser) {
                    $genericEmailData = $this->prepareGenericEmailRequest(
                        $order,
                        $status,
                        $companyUser['to_email'],
                        $companyUser['user_name']
                    );
                    if ($this->email->callGenericEmailApi($genericEmailData)) {
                        $this->logger->info(
                            ' B2b order Admin review email send successfully to '.
                            $companyUser['to_email'].' for GTN Number => ' . $order->getIncrementId()
                        );
                    }
                }
            }
        } else {
            $userName = $order->getCustomerFirstname();
            $toEmail = $order->getCustomerEmail();
            $genericEmailData = $this->prepareGenericEmailRequest(
                $order,
                $status,
                $toEmail,
                $userName
            );
            if ($this->email->callGenericEmailApi($genericEmailData)) {
                $this->logger->info(
                    ' B2b '.$status.' order customer email send successfully to '.
                    $toEmail.' for GTN Number => ' . $order->getIncrementId()
                );
            }
        }
    }

    /**
     *  Prepare generic email request
     *
     * @param object $order
     * @param string $status
     * @param string $toEmail
     * @param string $userName
     * @return false|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareGenericEmailRequest($order, $status, $toEmail, $userName)
    {
        $items = $this->formatOrderItems($order);
        $emailOrderData= $this->formatOrderData($order, $userName);
        $emailData = $this->buildEmailData($emailOrderData, $items, $order);
        $fromEmail = 'no-reply@fedex.com';
        $storeId = $this->storeManager->getStore()->getId();
        $templateId = $this->getTemplateId($status);
        $subject = $this->getOrderEmailSubject($status).' #'.$order->getIncrementId();
        $emailTemplateContent = $this->email->loadEmailTemplate($templateId, $storeId, $emailData);

        return json_encode($this->buildEmailPayload($emailTemplateContent, $subject, $toEmail, $fromEmail));
    }

    /**
     * Get quote email subject
     *
     * @param string $status
     * @return string
     */
    public function getOrderEmailSubject($status)
    {
        switch ($status) {
            case AdminConfigHelper::CONFIRMED:
                return self::FEDEX_OFFICE_PPRINT_ON_DEMAND.' — Order Request Confirmation';
            case AdminConfigHelper::DECLINE:
                return self::FEDEX_OFFICE_PPRINT_ON_DEMAND.' — Order Request Decline';
            case AdminConfigHelper::REVIEW:
                return self::FEDEX_OFFICE_PPRINT_ON_DEMAND.' — Order Admin Review';
            case AdminConfigHelper::EXPIRED:
                return self::FEDEX_OFFICE_PPRINT_ON_DEMAND.' — Order About to expire';
        }
    }

    /**
     * Format Order Items
     *
     * @param object $order
     * @return array
     */
    public function formatOrderItems($order)
    {
        $orderItems = $order->getAllVisibleItems();
        $formattedItems = [];
        $rowTotal = 0;
        foreach ($orderItems as $item) {
            $rowTotal = $rowTotal + $item->getRowTotal();
            $productJson = $this->productInfoHandler->getItemExternalProd($item);
            $userProductName = $productJson['userProductName'] ?? '';
            $productJson = $this->productInfoHandler->getItemExternalProd($item);
            $specialInstruction = $this->uploadToQuoteViewModel->isProductLineItems($productJson, true);
            $formattedItems[] = [
                'item' => $userProductName,
                'qty' => (int) $item->getQtyOrdered(),
                'price' => $this->adminConfigHelper->convertPrice($item->getRowTotal()),
                'AdditionalPrintInstruction' => $specialInstruction,
                'itemName' =>  $item->getName()
            ];
        }
        $shippingMethod = $order->getShippingMethod() ?? '';
        $shippingLabel = 'In-Store Pickup';
        $shippingTotal = 0;
        $IsShipping = 0;
        $IsDiscount = 0;
        $discountTotal = 0;
        if ($shippingMethod !== 'fedexshipping_PICKUP') {
            $shippingLabel = explode(
                ' - ',
                $order->getShippingDescription() ?? ''
            )[0];
            $shippingTotal = $order->getShippingAmount();
            $IsShipping = 1;
        }
        if ($order->getDiscountAmount() !== null) {
            $discountTotal = $order->getDiscountAmount();
            $IsDiscount = 1;
        }
        $formattedItems['count'] = $order->getTotalItemCount();
        $formattedItems['subTotal'] = $this->adminConfigHelper->convertPrice($rowTotal);
        $formattedItems['total'] = $this->adminConfigHelper->convertPrice($order->getGrandTotal());
        $formattedItems['tax'] = $this->adminConfigHelper->convertPrice($order->getTaxAmount());
        $formattedItems['shipping_total'] = $this->adminConfigHelper->convertPrice($shippingTotal);
        $formattedItems['shipping_label'] = $shippingLabel;
        $formattedItems['is_shipping'] = $IsShipping;
        $formattedItems['discount'] = $this->adminConfigHelper->convertPrice($discountTotal);
        $formattedItems['is_discount'] = $IsDiscount;

        return $formattedItems;
    }

    /**
     * Format Order Data
     *
     * @param object $order
     * @param string $userName
     * @return array
     */
    private function formatOrderData($order, $userName)
    {
        $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        $orderUrl = '/sales/order/view/order_id/'.$order->getId();
        if ($order->getCustomerId()) {
            $companyExtensionUrl = $this->getCompanyExtensionUrl(
                $order->getCustomerId(),
                $orderUrl,
                $storeUrl
            );
        } else {
            $companyExtensionUrl = $storeUrl.$orderUrl;
        }
        $createdAt = $order->getCreatedAt();
        $orderCreationDate = $this->formatDate($createdAt);

        return  [
            'user_name' => $userName,
            'order_id' => $order->getId(),
            'increment_id' => $order->getIncrementId(),
            'order_placed_date' => $orderCreationDate,
            'company_url_extension' => $companyExtensionUrl,
            'declineMessage' => $this->declineMessage
        ];
    }

    /**
     * Build Email Payload
     *
     * @param string $templateContent
     * @param string $subject
     * @param string $toEmail
     * @param string $fromEmail
     * @return array
     */
    private function buildEmailPayload($templateContent, $subject, $toEmail, $fromEmail)
    {
        return [
            'templateData' => $templateContent,
            'templateSubject' => $subject,
            'toEmailId' => $toEmail,
            'fromEmailId' => $fromEmail,
            'retryCount' => 0,
            'errorSupportEmailId' => '',
            'attachment' => ''
        ];
    }

    /**
     * Format date
     *
     * @param string $dateString
     * @return mixed
     */
    private function formatDate($dateString)
    {
        if ($this->toggleConfig->getToggleConfigValue('mazegeeks_D192133_fix')) {
            $dateString = $this->utcConverter->convertLocalizedDateToUtc($dateString);
        }

        return $this->timezoneInterface->date($dateString)->setTimezone(
            new \DateTimeZone('CST')
        )->format('M d, Y \a\t h:i A \C\S\T');
    }

    /**
     * Get Template Id
     *
     * @param string $status
     * @return string
     */
    public function getTemplateId($status)
    {
        switch ($status) {
            case AdminConfigHelper::CONFIRMED:
                return $this->adminConfigHelper->getB2bOrderEmailTemplate(
                    AdminConfigHelper::XML_PATH_B2B_ORDER_REQUEST_CONFIRMATION_EMAIL_TEMPLATE
                );
            case AdminConfigHelper::DECLINE:
                return $this->adminConfigHelper->getB2bOrderEmailTemplate(
                    AdminConfigHelper::XML_PATH_B2B_ORDER_REQUEST_DECLINE_EMAIL_TEMPLATE
                );
            case AdminConfigHelper::REVIEW:
                return $this->adminConfigHelper->getB2bOrderEmailTemplate(
                    AdminConfigHelper::XML_PATH_B2B_ORDER_ADMIN_REVIEW_EMAIL_TEMPLATE
                );
            case AdminConfigHelper::EXPIRED:
                return $this->adminConfigHelper->getB2bOrderEmailTemplate(
                    AdminConfigHelper::XML_PATH_B2B_ORDER_EXPIRED_EMAIL_TEMPLATE
                );
        }
    }

    /**
     * Build email data
     *
     * @param array $orderData
     * @param array $items
     * @param object $order
     * @return array
     */
    private function buildEmailData($orderData, $items, $order)
    {
        return [
            'declineMessage' => $orderData['declineMessage'],
            'company_url_extension' => $orderData['company_url_extension'],
            'user_name'         => $orderData['user_name'],
            'order_id'          => $orderData['order_id'],
            'order_placed_date' => $orderData['order_placed_date'],
            'increment_id'      => $orderData['increment_id'],
            'items'             => $items,
            'orderdetail'       => $this->orderDetailsHtml($order)
        ];
    }

    /**
     * Order detail data
     *
     * @param array $orderData
     * @return mixed
     */
    public function orderDetailsHtml($orderData)
    {
        return $this->getOrderInformationData($orderData);
    }

    /**
     * Has Alternate contact
     *
     * @param array|object $orderData
     * @return bool
     */
    public function hasAlternateContact($orderData): bool
    {
        $shippingAddress = $orderData->getShippingAddress();
        $billingAddress = $orderData->getBillingAddress();

        return strtolower($billingAddress->getFirstName() ?? '')
            != strtolower($shippingAddress->getFirstName() ?? '') ||
            strtolower($billingAddress->getLastName() ?? '')
            != strtolower($shippingAddress->getLastName() ?? '') ||
            strtolower($billingAddress->getEmail() ?? '')
            != strtolower($shippingAddress->getEmail() ?? '') ||
            strtolower($billingAddress->getTelephone() ?? '')
            != strtolower($shippingAddress->getTelephone() ?? '');
    }

    /**
     * Get Formatted Addresses Array
     *
     * @param object $orderData
     * @param bool $hasAlternateContact
     * @return array
     */
    public function getFormattedAddressArray($orderData, bool $hasAlternateContact): array
    {
        $isShipping = $isPickup = $has3p = false;
        $formattedShippingAddress = $formattedPickupAddress = "NA";
        $formattedAlternateContact = null;
        if ($orderData->getShippingMethod() == "fedexshipping_PICKUP") {
            $isPickup = true;
            $formattedPickupAddress = $this->getFormattedBillingShipping($orderData);
        } else {
            $isShipping = true;
            $formattedShippingAddress = $this->getFormattedBillingShipping($orderData);
        }
        foreach ($orderData->getItems() as $item) {
            if ($item->getMiraklOfferId()) {
                $has3p = true;
                break;
            }
        }

        if ($has3p && $orderData->getShippingMethod() == "fedexshipping_PICKUP") {
            $isShipping = true;
            $formattedShippingAddress = $hasAlternateContact ?
                $this->getFormattedBillingShipping($orderData, true) :
                $this->getFormattedPickup($orderData);
            $formattedAlternateContact = $hasAlternateContact ?
                $this->getFormattedBillingShipping($orderData) : null;
        }

        return [
            'formattedAlternateContact' => $formattedAlternateContact,
            'formattedShippingAddress' => $formattedShippingAddress,
            'formattedPickupAddress' => $formattedPickupAddress,
            'isShipping' => $isShipping,
            'isPickup' => $isPickup
        ];
    }

    /**
     * Formatted Billing Shipping
     *
     * @param object $orderData
     * @param bool $alternateContact
     * @return array
     */
    public function getFormattedBillingShipping($orderData, bool $alternateContact = false): array
    {
        $address = $alternateContact ? $orderData->getBillingAddress() : $orderData->getShippingAddress();
        $firstName = $address->getData("firstname");
        $lastName = $address->getData("lastname");
        $company = $address->getData("company");
        $street = $address->getData("street");
        $city = $address->getData("city");
        $region = $address->getData("region");
        $postcode = $address->getData("postcode");

        return [
            'company' => $company,
            'name' => $firstName . " " . $lastName,
            'address' => $street . ", " . $city . ", " . $region . " " . $postcode
        ];
    }

    /**
     * Formatted pickup
     *
     * @param object $orderData
     * @return array
     */
    public function getFormattedPickup($orderData): array
    {
        $miraklAddress = [];
        if (!empty($orderData->getAllVisibleItems())) {
            foreach ($orderData->getAllVisibleItems() as $orderItem) {
                $additionalDataAsObject = json_decode($orderItem->getAdditionalData() ?? '{}');
                if (!property_exists($additionalDataAsObject, 'mirakl_shipping_data')) {
                    continue;
                }
                try {
                    if (isset($additionalDataAsObject->mirakl_shipping_data) &&
                        isset($additionalDataAsObject->mirakl_shipping_data->address)) {
                        $miraklShippingAddress = $additionalDataAsObject->mirakl_shipping_data->address;
                        foreach ($miraklShippingAddress as $key => $value) {
                            $miraklAddress[$key] = $value;
                        }
                        break;
                    }
                } catch (Exception) {
                    continue;
                }
            }
        }
        if (count($miraklAddress)) {
            $hasAlternateContact = isset($miraklAddress['altFirstName']) && $miraklAddress['altFirstName'] !== '';
            $miraklAddress['street'] = join(',', $miraklAddress['street']);
            $firstName = $hasAlternateContact ? $miraklAddress['altFirstName'] : $miraklAddress['firstname'];
            $lastName = $hasAlternateContact ? $miraklAddress['altLastName'] : $miraklAddress['lastname'];
            $telephone = $hasAlternateContact ? $miraklAddress['altPhoneNumber'] : $miraklAddress['telephone'];
            $email = $miraklAddress['altEmail'];
            if ($email === '') {
                foreach ($miraklAddress['customAttributes'] as $attribute) {
                    if ($attribute->attribute_code === 'email_id' && $attribute->value !== '') {
                        $email = $attribute->value;
                    }
                }
            }

            return [
                'company' => null,
                'address' => $miraklAddress['street'] . ", " . $miraklAddress['city'] . ", " .
                    $miraklAddress['region'] . ", " . $miraklAddress['postcode'] . " " . $miraklAddress['countryId'],
                'email' => $email,
                'name' => $firstName . ' ' . $lastName,
                'phone' => $telephone
            ];
        }

        return ['address' => 'NA'];
    }

    /**
     * Get Formatted Shipping Address Array
     *
     * @param object $orderData
     * @return array
     */
    public function getFormattedShippingAddressArray($orderData): array
    {
        $street = $orderData->getShippingAddress()->getData("street");
        $city = $orderData->getShippingAddress()->getData("city");
        $region = $orderData->getShippingAddress()->getData("region");
        $countryId = $orderData->getShippingAddress()->getData("country_id");
        $postcode = $orderData->getShippingAddress()->getData("postcode");
        if ($countryId) {
            $country = $this->countryFactory->create()->loadByCode($countryId);
            $countryName = $country->getName();
        }
        $isShipping = $isPickup = false;
        $formattedShippingAddress = "NA";
        if ($orderData->getShippingMethod() != "fedexshipping_PICKUP") {
            $isShipping = true;
        } else {
            $isPickup = true;
            if ($orderData->getMiraklIsOfferInclTax() !== null) {
                foreach ($orderData->getItems() as $item) {
                    if ($item->getMiraklOfferId()) {
                        $isShipping = true;
                        break;
                    }
                }
            }
        }
        if ($isShipping) {
            $formattedShippingAddress = $street . ", " . $city . ", " .
                $region . ", " . $countryName . ", " . $postcode;
        }

        return [
            'formattedShippingAddress' => $formattedShippingAddress,
            'isShipping' => $isShipping,
            'isPickup' => $isPickup
        ];
    }

    /**
     * Remove escape character from string
     *
     * @param string|null $value
     * @return string
     */
    public function escapeCharacter($value = null)
    {
        return preg_replace("/[^a-zA-Z0-9,]+/", " ", trim($value));
    }

    /**
     * Get Order Information Data
     *
     * @param object $orderData
     * @return array
     */
    public function getOrderInformationData($orderData)
    {
        $orderTemplateData = [];
        try {
            if (!empty($orderData)) {
                $formattedShippingAddress = "";
                $recipientFirstName = "";
                $recipientLastName = "";
                $recipientEmail = '';
                $recipientPhone = '';
                if (!empty($orderData->getShippingAddress())) {
                    $formattedAddressArray = $this->getFormattedShippingAddressArray($orderData);
                    $formattedShippingAddress = $formattedAddressArray['formattedShippingAddress'];
                    $recipientFirstName = $orderData->getShippingAddress()->getData("firstname");
                    $recipientLastName = $orderData->getShippingAddress()->getData("lastname");
                    $recipientEmail = $orderData->getShippingAddress()->getData("email");
                    $recipientPhone = $orderData->getShippingAddress()->getData("telephone");
                }
                $orderTemplateData["retail_transaction_id"] = $orderData->getPayment()
                    ->getRetailTransactionId();
                $paymentName = "Credit Card";
                if (!empty($orderData->getPayment()) &&
                    $orderData->getPayment()->getMethod() == "fedexaccount") {
                    $paymentName = "FedEx Account";
                }
                $orderTemplateData['payment_type'] = $paymentName;
                $orderTemplateData["customer_first_name"] =
                    $this->escapeCharacter($orderData->getCustomerFirstName());
                $orderTemplateData["customer_last_name"] =
                    $this->escapeCharacter($orderData->getCustomerLastName());
                $orderTemplateData["recipient_first_name"] = $this->escapeCharacter($recipientFirstName);
                $orderTemplateData["recipient_last_name"] = $this->escapeCharacter($recipientLastName);
                $orderTemplateData["shipping_address"] = $this->escapeCharacter($formattedShippingAddress);
                $orderTemplateData["customer_email"] = $orderData->getCustomerEmail();
                $orderTemplateData["delivery_method"] = ltrim($orderData->getShippingDescription(), "FedEx");
                $hasAlternateContact = $this->hasAlternateContact($orderData);
                $formattedAddressArray = $this->getFormattedAddressArray($orderData, $hasAlternateContact);
                $formattedAlternateContact = $formattedAddressArray['formattedAlternateContact'];
                $formattedShippingAddress = $formattedAddressArray['formattedShippingAddress'];
                $formattedPickupAddress = $formattedAddressArray['formattedPickupAddress'];
                $isShipping = $formattedAddressArray['isShipping'];
                $isPickup = $formattedAddressArray['isPickup'];
                $orderTemplateData["alternate_contact"] = $formattedPickupAddress;
                $orderTemplateData["shipping_address"] = [];
                $orderTemplateData["shipping_address"]["address"] = isset($formattedShippingAddress["address"]) ?
                    $this->escapeCharacter($formattedShippingAddress["address"]) : null;
                $orderTemplateData["shipping_address"]["phone"] = isset($formattedShippingAddress["phone"]) ?
                    $this->escapeCharacter($formattedShippingAddress["phone"]) : null;
                $orderTemplateData["shipping_address"]["name"] = isset($formattedShippingAddress["name"]) ?
                    $this->escapeCharacter($formattedShippingAddress["name"]) : null;
                $orderTemplateData["shipping_address"]["company"] = isset($formattedShippingAddress["company"]) ?
                    $this->escapeCharacter($formattedShippingAddress["company"]) : null;
                $orderTemplateData["shipping_address"]["email"] = $formattedShippingAddress["email"] ?? null;
                $orderTemplateData["pickup_address"] = is_array($formattedPickupAddress) ?
                    $this->escapeCharacter($formattedPickupAddress["address"]) : null;
                $orderTemplateData["has_alternate_contact"] = $hasAlternateContact;
                $orderTemplateData["isPickup"] = $isPickup;
                $orderTemplateData["isShipping"] = $isShipping;
                $orderTemplateData["showCustomerInfo"] = !$isShipping || !$isPickup;
                $orderTemplateData['discount'] = (float)$orderData->getDiscountAmount();
                $orderTemplateData['cc_last_4'] = $orderData->getPayment()->getCcLast4();
                $orderTemplateData['account_number'] = $orderData->getPayment()->getFedexAccountNumber() ?
                    substr($orderData->getPayment()->getFedexAccountNumber(), -4) : '';
                if (!empty($orderData->getBillingAddress())) {
                    $customerBillingPhone = $orderData->getBillingAddress()->getData('telephone');
                } else {
                    $customerBillingPhone = '';
                }
                $orderTemplateData['customer_phone'] = $customerBillingPhone;
                $orderTemplateData['recipient_email'] = $recipientEmail;
                $orderTemplateData['recipient_phone'] = $recipientPhone;
                $street = $orderData->getBillingAddress()->getData("street");
                $city = $orderData->getBillingAddress()->getData("city");
                $region = $orderData->getBillingAddress()->getData("region");
                $countryId = $orderData->getBillingAddress()->getData("country_id");
                $postcode = $orderData->getBillingAddress()->getData("postcode");
                if ($countryId) {
                    $country = $this->countryFactory->create()->loadByCode($countryId);
                    $countryName = $country->getName();
                }
                $orderTemplateData["billing_address"] = $street . ", " . $city . ", " .
                    $region . ", " . $countryName . ", " . $postcode;
                $orderTemplateData["billing_name"] = $orderData->getPayment()->getCcOwner() ??
                    $orderData->getBillingAddress()->getData("firstname") . ' ' .
                    $orderData->getBillingAddress()->getData("lastname");

                return $orderTemplateData;
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());

            return ['code' => '400', 'message' => $e->getMessage()];
        }

        return  $orderTemplateData;
    }

    /**
     * Get company url extension
     *
     * @param int $customerId
     * @param String $orderUrl
     * @param String $storeUrl
     * @return string
     */
    public function getCompanyExtensionUrl($customerId, $orderUrl, $storeUrl)
    {
        if ($this->storeManager->getStore()->getCode() == "ondemand" &&
            $company = $this->companyRepository->getByCustomerId($customerId)) {
            if ($companyUrl = $company->getCompanyUrlExtention()) {
                return $storeUrl.$companyUrl.$orderUrl;
            }
        }

        return $storeUrl.$orderUrl;
    }

    /**
     * Get Company user detail
     *
     * @param int $customerId
     * @return array
     */
    public function getCompanyAdminUserDetail($customerId)
    {
        $adminUserDetails = [];
        $company = $this->companyRepository->getByCustomerId($customerId);
        if ($company->getId()) {
            $customerIds = $this->selfRegHelper->getCompanyUserPermission(
                $company->getId(),
                ['review_orders']
            );
            $customerIds[] = $company->getSuperUserId();
            if (count($customerIds)) {
                foreach ($customerIds as $id) {
                    $customer = $this->customerRepositoryInterface->getById($id);
                    $adminUserDetails[] =  [
                        'user_name' => $customer->getFirstname(),
                        'to_email' =>  $customer->getCustomAttribute('secondary_email')->getValue()
                    ];
                }
            }
        }

        return $adminUserDetails;
    }
}
