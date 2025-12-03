<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Ui\Component\Listing\Column\Product;

use Magento\Store\Ui\Component\Listing\Column\Store\Options as StoreOptions;

/**
 * Ui Class Options
 */
class Options extends StoreOptions
{
    /**
     * All Store Views value
     */
    public const ALL_STORE_VIEWS = '0';

    /**
     * All Store View Label text
     */
    public const ALL_STORE_VIEW_LABEL_TEXT = 'All Store View';

    /**
     * Default Store View Label text
     */
    public const DEFAULT_STORE_VIEW_LABEL_TEXT = 'Default Store View';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }
        $this->currentOptions[self::ALL_STORE_VIEW_LABEL_TEXT] = __('All Store View');
        $this->currentOptions[self::ALL_STORE_VIEW_LABEL_TEXT] = '';
        $this->currentOptions[self::DEFAULT_STORE_VIEW_LABEL_TEXT]['label'] = __('Default Store View');
        $this->currentOptions[self::DEFAULT_STORE_VIEW_LABEL_TEXT]['value'] = self::ALL_STORE_VIEWS;

        $this->generateCurrentOptions();

        $this->options = array_values($this->currentOptions);

        return $this->options;
    }
}
