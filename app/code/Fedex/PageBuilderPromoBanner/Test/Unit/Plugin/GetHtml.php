<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PageBuilderPromoBanner\Test\Unit\Plugin;

/**
 * GetHtml class for Unit Testing
 *
 */
class GetHtml
{
    /**
     * @var $html
     */
    protected $html;

    /**
     * Define getAfterElementHtml for unit testing purpose
     *
     * @return string
     */
    public function getAfterElementHtml()
    {
        $this->html = '';

        return $this->html;
    }

    /**
     * Set data for unit testing purpose
     *
     * @return string
     */
    public function setData($afterElementHtml, $element)
    {
        return $afterElementHtml.''.$element;
    }
}

