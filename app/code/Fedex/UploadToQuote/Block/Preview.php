<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Preview Block class
 */
class Preview extends Template
{
    public $item;
    /**
     * Set Item
     *
     * @param obj $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

   /**
    * Get Item

    * @param $item
    * @return obj
    */
    public function getItem()
    {
        return $this->item;
    }
}
