<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Company\Api\CompanyManagementInterface;
use Fedex\MarketplaceCheckout\Model\Email;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Mail\Template\Factory as TemplateFactory;
use Magento\Framework\App\Area;

/**
 * Class ShipmentEmail to send order status emails
 */
class OrderConfirmationTemplateProvider extends AbstractHelper
{

    /**
     * ShipmentEmail constructor
     *
     * @param Context $context
     * @param ToggleConfig $toggleConfig
     * @param CompanyManagementInterface $companyManager
     * @param StoreManagerInterface $storeManager
     * @param TemplateFactory $template
     */
    public function __construct(
        Context $context,
        protected ToggleConfig $toggleConfig,
        private CompanyManagementInterface $companyManager,
        private StoreManagerInterface $storeManager,
        private TemplateFactory $template
    ) {
        parent::__construct($context);
    }

    /**
     * Function to get bcc email
     *
     * @param string $shipmentStatus
     * @param int $customerid
     * @return string
     */
    public function getBccEmail($shipmentStatus, $customerid)
    {
        $company = null;
        $bccEmail = '';
        if(!empty($customerid)) {
            $company = $this->getCompanyByCustomerId($customerid);
        }
        if ($company != null && $shipmentStatus == 'confirmed') {
            $commaSeperatedEmail= $company->getBccCommaSeperatedEmail();
            if ($commaSeperatedEmail != ''){
                $bccEmailArrays = explode(',', $commaSeperatedEmail);
                $emailString = '';
                foreach ($bccEmailArrays as $bcc) {
                    $bcc = trim($bcc);
                    $emailJson =
                    '{
                        "address":"' . $bcc . '"
                    }';
                    if ($emailString=='') {
                        $emailString =
                        $emailString.$emailJson;
                    } else {
                        $emailString =
                        $emailString.',
                        '.$emailJson;
                    }
                }
                $bccEmail=
                '"bcc":[
                    '.$emailString.'
                ],';
            }
        }
        return $bccEmail;
    }

    /**
     * Check OrderConfirmation Enabled for Company
     *
     * @param string $shipmentStatus
     * @param int $customerid
     * @return bool
     */
    public function getConfirmationstatus($shipmentStatus, $customerid)
    {
        $company = null;
        $status = true;
        if($customerid != null) {
            $company = $this->getCompanyByCustomerId($customerid);
        }
        if ($company != null && $shipmentStatus == 'confirmed') {
            $status = $company->getIsSuccessEmailEnable();
        }
        return $status;
    }

    /**
     * Check OrderConfirmation Enabled for Company
     *
     * @param int $customerid
     * @return object
     */
    public function getCompanyByCustomerId($customerid)
    {
        return $this->companyManager->getByCustomerId($customerid);
    }

    /**
     * Check OrderConfirmation Enabled for Company
     *
     * @param int $customerid
     * @return object
     */
    public function getTemplateId($customerid)
    {
        $templateId = null;
        $company = $this->getCompanyByCustomerId($customerid);
        if ($company != null) {
            $templateId = $company->getOrderConfirmationEmailTemplate();
        }
        return $templateId;
    }

    /**
     * Check OrderConfirmation Enabled for Company
     *
     * @param array $orderData
     * @return Json
     */
    public function getEmailTemplateById($orderData)
    {
        $orderConfirmationTemplateId = $this->getTemplateId($orderData['customer_id']);
        $html = [];
        $template = $this->template->get($orderConfirmationTemplateId, null)
            ->setVars($orderData)
            ->setOptions([
                'area' => Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()->getId(),
            ]);
        $html['template'] = $template->processTemplate();
        $html['subject'] = html_entity_decode((string)$template->getSubject(), ENT_QUOTES);
        return $html;
    }
}
