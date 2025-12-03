<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomizedMegamenu\Plugin\Block;

/**
 * TopMenuBlock Plugin
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class TopMenuBlock
{
    /**
     * TopMenuBlock construct
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magedelight\Megamenu\Model\MegamenuManagement $megamenuManagement
     */
    public function __construct(
        private \Magento\Framework\View\Element\Context $context,
        protected \Magedelight\Megamenu\Model\MegamenuManagement $megamenuManagement
    )
    {
    }

    /**
     * @param $subject \Magedelight\Megamenu\Block\Topmenu
     * @param $html
     * @param $item \Magedelight\Megamenu\Model\MenuItems|\Magedelight\Megamenu\Api\Data\MenuItemsInterface
     * @param $childrenWrapClass
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     */
    public function afterSetMegamenu(\Magedelight\Megamenu\Block\Topmenu $subject, $html, $item, $childrenWrapClass)
    {
        $html = '';
        $megaMenuItemData = [
            'menu_block' => $subject,
            'menu_item' => $item,
            'menu_management' => $this->megamenuManagement,
        ];
        $megaMenuItemBlock = $subject->getLayout()->createBlock('Magento\Framework\View\Element\Template');
        /** @var $megaMenuItemBlock \Magento\Framework\View\Element\Template */
        $megaMenuItemBlock->setData($megaMenuItemData);
        if ($item->getItemType() == 'megamenu') {
            $megaMenuItemBlock->setTemplate('Fedex_CustomizedMegamenu::menu/items/megaMenuItemBlock.phtml');
            $html .= trim(preg_replace('/\s\s+/', ' ', (string)$megaMenuItemBlock->toHtml()));
        } else {
            $megaMenuItemBlock->setTemplate('Fedex_CustomizedMegamenu::menu/items/menuItemBlock.phtml');
            $html .= trim(preg_replace('/\s\s+/', ' ', (string)$megaMenuItemBlock->toHtml()));
        }
        return $html;
    }
}
