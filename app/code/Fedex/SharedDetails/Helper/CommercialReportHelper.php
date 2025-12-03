<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedDetails\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Helper\Context;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Fedex\CIDPSG\Api\MessageInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Data Helper class
 */
class CommercialReportHelper extends AbstractHelper
{
    /**
     * CommercialReportHelper constructor
     *
     * @param Context $context
     * @param ToggleConfig $toggleConfig
     * @param ProducingAddressFactory $producingAddressFactory
     * @param Product $product
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param DirectoryList $directoryList
     * @param MessageInterface $message
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Context $context,
        protected ToggleConfig $toggleConfig,
        protected ProducingAddressFactory $producingAddressFactory,
        private Product $product,
        private AttributeSetRepositoryInterface $attributeSetRepository,
        private DirectoryList $directoryList,
        private MessageInterface $message,
        private PublisherInterface $publisher
    ) {
        parent::__construct($context);
    }

    /**
     * Get Responsible center id
     * @param Int $orderId
     * @return Int|null
     */
    public function getBranchId($orderId)
    {
        $branchId = null;

        $producingAddressModel = $this->producingAddressFactory->create()->load($orderId, 'order_id');
        $addtionalData = $producingAddressModel->getData('additional_data');
        if (!empty($addtionalData)) {
            $addtionalDetails = json_decode($addtionalData, true);
            $branchId = $addtionalDetails['responsible_location_id'] ?? null;
        }

        return $branchId;
    }

    /**
     * Get Attribute Set Name
     * @param Int $productId
     * @return string
     */
    public function getAttributeSet($productId = null)
    {
        $product = $this->product->load($productId);

        $attributeSetRepository = $this->attributeSetRepository->get($product->getAttributeSetId());

        return $attributeSetRepository->getAttributeSetName();
    }

    /**
     * Send Email
     * @param string $fileName
     * @param string $emailData
     */
    public function sendEmail($fileName, $toEmails)
    {
        $filePath = $this->directoryList->getPath(DirectoryList::VAR_DIR) .'/' . $fileName;
        $fromEmail = $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );

        $genericEmailData["toEmailId"] = $toEmails;
        $genericEmailData["fromEmailId"] = $fromEmail;
        $genericEmailData["templateSubject"] = 'Order Data Report';
        $genericEmailData["templateData"] = 'Orders Data';
        $genericEmailData["attachment"] = $filePath;
        $genericEmailData["commercial_report"] = true;

        $this->message->setMessage(json_encode($genericEmailData));
        $this->publisher->publish("genericEmailQueue", $this->message);
        $this->_logger->info(
            __METHOD__.':'.__LINE__. "Publish email data in generic email queue" .
            $this->message->getMessage()
        );
    }

    /**
     * Send User Report Email
     *
     * @param string $fileName
     * @param string $emailData
     */
    public function sendUserReportEmail($fileName, $toEmails)
    {
        $filePath = $this->directoryList->getPath(DirectoryList::VAR_DIR) .'/' . $fileName;
        $fromEmail = $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );

        $genericEmailData["toEmailId"] = $toEmails;
        $genericEmailData["fromEmailId"] = $fromEmail;
        $genericEmailData["templateSubject"] = 'Users Data Report';
        $genericEmailData["templateData"] = 'Users Data';
        $genericEmailData["attachment"] = $filePath;
        $genericEmailData["commercial_report"] = true;

        $this->message->setMessage(json_encode($genericEmailData));
        $this->publisher->publish("genericEmailQueue", $this->message);
        $this->_logger->info(
            __METHOD__.':'.__LINE__. "Publish email data in generic email queue" .
            $this->message->getMessage()
        );
    }
}
