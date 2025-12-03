<?php
namespace Fedex\CustomerGroup\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Fedex\CatalogMvp\Helper\CatalogMvp;

/**
 * Block class to get category label for B2B
 */
class DynamicCategoryLabel extends \Magento\Backend\Block\Template
{
    public const B2B_ROOT_CATEGORY = 'B2B Root Category';
    public const ONDEMAND = 'ondemand';

    public function __construct(
        Context $context,
        private CatalogMvp $catalogMvpHelper,
        array $data = []
    ) {
            parent::__construct($context, $data);
    }

    /**
     * Set Constant For B2B Category
     *
     * @return string
     */
    public function categoryB2B()
    {
        $rooatCategoryDeatail = $this->catalogMvpHelper->getRootCategoryDetailFromStore(self::ONDEMAND);
        return $rooatCategoryDeatail['name'] ?? self::B2B_ROOT_CATEGORY;
    }
}
