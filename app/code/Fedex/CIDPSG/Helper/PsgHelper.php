<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Helper;

use Fedex\CIDPSG\Model\CustomerFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\UrlInterface;

/**
 *   CIDPSG PsgHelper class
 */
class PsgHelper extends AbstractHelper
{

    /**
     * PsgHelper Constructor
     *
     * @param Context $context
     * @param CustomerFactory $customerFactory
     * @param UrlInterface $urlInterface
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        Context $context,
        protected CustomerFactory $customerFactory,
        protected UrlInterface $urlInterface,
        protected ResponseFactory $responseFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Get the Psg customer Information
     *
     * @param int $clientId
     * @return object|void
     */
    public function getPSGCustomerInfo($clientId)
    {
        $items = $this->customerFactory->create()->getCollection();
        $items->getSelect()->joinLeft(
            ["pcf" => $items->getTable("psg_customer_fields")],
            'main_table.entity_id = pcf.psg_customer_entity_id',
        );
        $items->addFieldToFilter('main_table.client_id', ['eq' => $clientId])
        ->getSelect()->reset('order')
        ->order(['pcf.field_group asc', 'pcf.position asc']);

        if (!$items->getSize()) {
            $this->responseFactory->create()->setRedirect($this->urlInterface->getUrl('psg'))->sendResponse();
        }

        return $items;
    }

    /**
     * Get the Pa agreement Information
     *
     * @param int $clientId
     * @return String
     */
    public function getPSGPaAgreementInfoByClientId($clientId)
    {
        $customCollection = $this->customerFactory->create()->load($clientId, 'client_id');

        return [
            "pa_agreement" => $customCollection->getParticipationAgreement(),
            "participation_code" => $customCollection->getCompanyParticipationId(),
            "company_name" => $customCollection->getCompanyName()
        ];
    }
}
