<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\CustomerData;

use Fedex\Canva\Api\Data\ConfigInterface as ModuleConfig;
use Fedex\Canva\Model\CanvaCredentials;
use Fedex\Canva\ViewModel\PodConfiguration;
use Magento\Customer\Model\Session;
use Magento\Customer\CustomerData\SectionSourceInterface;

class CanvaSection implements SectionSourceInterface
{
    /**
     * @var Session
     */
    private Session $session;

    public function __construct(
        private ModuleConfig $moduleConfig,
        private PodConfiguration $pod,
        private CanvaCredentials $canvaCredentials,
        Session $customerSession
    ) {
        $this->session = $customerSession;
    }
    /**
     * @inheritDoc
     */
    public function getSectionData(): array
    {
        $this->canvaCredentials->fetchSectionData();
        return [
            'partnerId' => $this->moduleConfig->getPartnerId() ?? '',
            'partnershipSdkUrl' => $this->moduleConfig->getPartnershipSdkUrl() ?? '',
            'userToken' => $this->session->getCanvaAccessToken(),
            'clientId' => $this->session->getClientId(),
            'designId' => $this->pod->getDesignId()
        ];
    }
}
