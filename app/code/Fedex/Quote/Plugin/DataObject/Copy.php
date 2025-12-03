<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Quote\Plugin\DataObject;

/**
 * Plugin copy class to prevent copy customer to quote
 */
class Copy
{

    /**
     * Prevent customer details copy to quote
     *
     * @param object $subject
     * @param string $fieldset
     * @param string $aspect
     * @param array|DataObject|ExtensibleDataInterface|AbstractSimpleObject $source
     * @param array|DataObject|ExtensibleDataInterface|AbstractSimpleObject $target
     * @param string $root
     * @return array
     */
    public function beforeCopyFieldsetToTarget($subject, $fieldset, $aspect, $source, $target, $root = 'global')
    {
        if ($fieldset == 'customer_account' && $aspect == 'to_quote') {
            $target = '';
        }

        return [$fieldset, $aspect, $source, $target, $root];
    }
}
