<?php

namespace Fedex\Catalog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Breadcrumbs extends AbstractHelper
{
    const XML_PATH_PAGE_BREADCRUMB_CONTROL = 'web/pages_breadcrumb/control';

    /**
     *
     * @param Context $context
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param ManagerInterface $messageManager
     * @param State $state
     */
    public function __construct(
        Context $context,
        private WriterInterface $configWriter,
        private TypeListInterface $cacheTypeList,
        private ManagerInterface $messageManager,
        private State $state
    ) {
        parent::__construct($context);
    }

    /**
     * Gets Json structure for Page Templates allowed in PDP
     * @return string
     */
    public function getControlJson(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PAGE_BREADCRUMB_CONTROL,
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * Sets Json structure for Page Templates allowed in PDP
     * @param string $value
     * @return void
     */
    public function setControlJson(string $value): void
    {
        $this->configWriter->save(
            self::XML_PATH_PAGE_BREADCRUMB_CONTROL,
            $value
        );
    }
}
