<?php
/**
 * @category  Fedex
 * @package   Fedex_AllPrintProducts
 * @copyright Copyright (c) 2024 Fedex.
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\AllPrintProducts\Block;

use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Magento\Cms\Block\Block;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Context;
use Magento\Store\Model\StoreManagerInterface;

class AllPrintProductsCmsHandler extends Block
{
    /**
     * @param Context $context
     * @param FilterProvider $filterProvider
     * @param StoreManagerInterface $storeManager
     * @param BlockFactory $blockFactory
     * @param AdditionalDataFactory $additionalDataFactory
     * @param CompanyHelper $companyHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context               $context,
        FilterProvider        $filterProvider,
        StoreManagerInterface $storeManager,
        BlockFactory          $blockFactory,
        protected AdditionalDataFactory $additionalDataFactory,
        protected CompanyHelper         $companyHelper,
        private AllPrintProducts      $allprintproductblockconfig,
        array                 $data = []
    ) {
        parent::__construct($context, $filterProvider, $storeManager, $blockFactory, $data);
    }

    /**
     * Prepare Content HTML
     *
     * @return string
     * @throws NoSuchEntityException
     */
    protected function _toHtml()
    {
        $blockId = $this->getAllPrintProductsCmsBlockIdentifierFromCompany();
        $html = '';
        if ($blockId) {
            $storeId = $this->_storeManager->getStore()->getId();
            $block = $this->_blockFactory->create();
            $block->setStoreId($storeId)->load($blockId);
            if ($block->isActive()) {
                $html = $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent());
            }
        }
        return $html;
    }

    /**
     * Retrieve All Print Products CMS Block Identifier from Company
     *
     * @return string
     */
    public function getAllPrintProductsCmsBlockIdentifierFromCompany()
    {
        $companyId = $this->companyHelper->getCompanyId();

        if ($companyId) {
            $additionalData = $this->additionalDataFactory->create()
                ->getCollection()
                ->addFieldToSelect('all_print_products_cms_block_identifier')
                ->addFieldToFilter(AdditionalData::COMPANY_ID, ['eq' => $companyId])
                ->getFirstItem();

            return $additionalData->getAllPrintProductsCmsBlockIdentifier();
        }

        return $this->allprintproductblockconfig->getRetailCMSBlockConfigForSpecialProduct();
    }

}
