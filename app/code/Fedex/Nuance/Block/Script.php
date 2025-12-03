<?php
declare(strict_types=1);
namespace Fedex\Nuance\Block;

use Fedex\WebAnalytics\Api\Data\GDLConfigInterface;
use Fedex\WebAnalytics\Api\Data\NuanceInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class Script extends Template implements IdentityInterface
{

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param NuanceInterface $nuanceInterface
     * @param GDLConfigInterface $GDLConfig
     * @param array $data
     */
    public function __construct(
        private readonly Context $context,
        private readonly StoreManagerInterface $storeManager,
        private readonly NuanceInterface $nuanceInterface,
        private readonly GDLConfigInterface $GDLConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Retrieve base content for Nuance Script
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->nuanceInterface->isEnabledNuanceForCompany() && $this->nuanceInterface->getScriptCodeWithNonce()) {
            return parent::_toHtml();
        }

        return false;
    }

    /**
     * @return string[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getIdentities()
    {
        return [
            'nuance_' . $this->storeManager->getStore()->getId(),
        ];
    }

    /**
     * @return false|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getNuanceScript()
    {
        return $this->nuanceInterface->getScriptCode();
    }

    /**
     * @return false|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getGdlScript()
    {
        return $this->GDLConfig->getScriptFullyRendered();
    }
}
