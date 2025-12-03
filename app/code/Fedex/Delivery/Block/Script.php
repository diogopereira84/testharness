<?php
namespace Fedex\Delivery\Block;

/**
 * Block class to get google api script url
 */
class Script extends \Magento\Framework\View\Element\Template
{
    /**
     * Constructor
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $configInterface
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        public \Magento\Framework\App\Config\ScopeConfigInterface $configInterface,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getApiKey()
    {
        return $this->configInterface->getValue('fedex/general/google_maps_api_url');
    }
}
