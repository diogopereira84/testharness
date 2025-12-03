<?php

namespace Magedelight\Megamenu\Block;

use Magedelight\Megamenu\Helper\Data;

class Init extends \Magento\Backend\Block\AbstractBlock
{

    /**
     * @var \Magento\Framework\View\Page\Config
     */
    protected $pageConfig;

    /**
     * @var \Magedelight\Megamenu\Helper\Data
     */
    protected $helper;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\View\Page\Config $pageConfig,
        \Magedelight\Megamenu\Helper\Data $helper,
        array $data = []
    ) {
        $this->pageConfig = $pageConfig;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $page = $this->pageConfig;
        $page->addPageAsset('Magedelight_Megamenu::css/font-awesome/css/font-awesome.min.css');
        $page->addPageAsset('Magedelight_Megamenu::js/megamenu/megamenu.js');

        if ($this->helper->isHumbergerMenu()) {
            $page->addPageAsset('Magedelight_Megamenu::js/megamenu/burgermenu.js');
        }
    }
}
