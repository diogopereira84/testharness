<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @copyright Copyright (c) 2024 Fedex.
 * @author    Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Company\Block;

use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Magento\Cms\Block\Block;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Cms\Model\ResourceModel\Block as ResourceBlock;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Context;
use Magento\Store\Model\StoreManagerInterface;

class HomepageCarouselCmsHandler extends Block
{

    /**
     * @param Context $context
     * @param FilterProvider $filterProvider
     * @param StoreManagerInterface $storeManager
     * @param BlockFactory $blockFactory
     * @param AdditionalDataFactory $additionalDataFactory
     * @param CompanyHelper $companyHelper
     * @param ConfigInterface $configInterface
     * @param ResourceBlock $blockResource
     * @param array $data
     */
    public function __construct(
        protected Context                 $context,
        protected FilterProvider          $filterProvider,
        protected StoreManagerInterface   $storeManager,
        BlockFactory            $blockFactory,
        protected AdditionalDataFactory   $additionalDataFactory,
        protected CompanyHelper           $companyHelper,
        protected ConfigInterface         $configInterface,
        protected ResourceBlock $blockResource,
        array                   $data = []
    )
    {
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
        $html = '';
        if ($this->configInterface->getE414712HeroBannerCarouselForCommercial()) {
            $blockId = $this->getHomepageCmsBlockIdentifierFromCompany();

            if ($blockId) {
                $storeId = $this->_storeManager->getStore()->getId();
                $block = $this->_blockFactory->create();
                $this->blockResource->load($block, $blockId);
                if ($block->isActive()) {
                    $html = $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent());
                }
            }
        }
        return $html;
    }

    /**
     * Retrieve Homepage CMS Block Identifier from Company
     *
     * @return string
     */
    public function getHomepageCmsBlockIdentifierFromCompany()
    {
        $companyId = $this->companyHelper->getCompanyId();

        if ($companyId) {
            $additionalData = $this->additionalDataFactory->create()
                ->getCollection()
                ->addFieldToSelect('homepage_cms_block_identifier')
                ->addFieldToFilter(AdditionalData::COMPANY_ID, ['eq' => $companyId])
                ->getFirstItem();

            return $additionalData->getHomepageCmsBlockIdentifier();
        }

        return '';
    }

}
