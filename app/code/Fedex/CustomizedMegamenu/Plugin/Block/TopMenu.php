<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomizedMegamenu\Plugin\Block;

/**
 * TopMenu Plugin
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class TopMenu
{
    /**
     * TopMEnu construct
     *
     * @param \Magento\Framework\View\Element\Context $context
     */
    public function __construct(
        private \Magento\Framework\View\Element\Context $context
    )
    {
    }
    
    /**
     * Before Render Result
     *
     * @param $object $subject
     * @param $object $result
     * @param $childrens
     *
     * @return  string
     */
    public function afterSetVerticalRightParentItem(\Magedelight\Megamenu\Block\Topmenu $subject, $html, $childrens)
    {
        $html = '';
        $columnCountForVerticalMenu = count($childrens['childrens']) >= 3 ? 3 : count($childrens['childrens']);
        $html .= '<div id="subcat-tab-' . $childrens['id'] . '" class="vertical-subcate-content">';
        $html .= '<ul class="menu-vertical-child child-level-3 column' . $columnCountForVerticalMenu . '">';
        foreach ($childrens['childrens'] as $key=>$child) {
            $verticalclass = '';
            if ($key < 5) {
                $verticalclass = $child['id'] == $subject->getCurrentCat() ? 'active' : '';
                $html .= '<li class="' . $verticalclass . '">';
                $html .= '<h4 class="level-3-cat ">';
                $html .= '<a href="' . $child['url'] . '">' . $child['label'] . '</a>';
                $html .= '</h4>';
                $html .= $subject->setVerticalRightChildItem($child);
                $html .= '</li>';
            }else {

                $parentCatUrl = explode('/', $child['url']);
                $viewAllUrl = $parentCatUrl[0].'/'.$parentCatUrl[1].'/'.
                $parentCatUrl[2].'/'.$parentCatUrl[3].'/'.$parentCatUrl[4].'.html';
                $html .= '<li class="' . $verticalclass . ' view-all-link">';
                $html .= '<h4 class="level-3-cat ">';
                $html .= '<a href="' .$viewAllUrl. '">View All</a>';
                $html .= '</h4>';
                $html .= '</li>';
                break;
            }

        }
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}
