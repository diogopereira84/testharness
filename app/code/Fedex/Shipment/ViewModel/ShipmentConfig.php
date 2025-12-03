<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Shipment\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/*
 * Use class to send OMS new status order email
*/
class ShipmentConfig implements ArgumentInterface
{
    /**
     * @var StateInterface $inlineTranslation
     */
    protected $inlineTranslation;

    /**
     * Shipment Config Constructor
     *
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param StateInterface $state
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfigInterface,
        protected TransportBuilder $transportBuilder,
        protected StoreManagerInterface $storeManager,
        StateInterface $state
    ) {
        $this->inlineTranslation = $state;
    }

    /**
     * Use to get store configuration value
     *
     * @param string $path
     * @param int $storeId
     * @return mixed
     */
    public function getConfigValue($path, $storeId)
    {
        return $this->scopeConfigInterface->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Use to generate template
     *
     * @param  Mixed $emailTemplateVariables
     * @param  Mixed $senderInfo
     * @param  Mixed $receiverInfo
     * @param  string $templateId
     * @return this
     */
    public function generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo, $templateId)
    {
        $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId()
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($receiverInfo['email'], $receiverInfo['name']);

        return $this;
    }

    /**
     * Use to send order status mail
     *
     * @param  Mixed $emailTemplateVariables
     * @param  Mixed $senderInfo
     * @param  Mixed $receiverInfo
     * @param  string $templateId
     * @return void
     */
    public function sendOrderStatusMail($emailTemplateVariables, $senderInfo, $receiverInfo, $templateId)
    {
        $this->inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo, $templateId);
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }
}
