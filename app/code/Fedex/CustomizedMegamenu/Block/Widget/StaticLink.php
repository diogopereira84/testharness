<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomizedMegamenu\Block\Widget;

use Magento\Widget\Block\BlockInterface;
use Magento\Framework\View\Element\Template;

class StaticLink extends Template implements BlockInterface
{

    /**
     * default template variable
     *
     * @var Template $_templete
     */
    protected $_template = "widget/staticlink.phtml";
}
