<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\Ui\Component\Listing\Columns;

class Column
{
    /**
     * @inheritDoc
     */
    public function __construct(
        private \Fedex\Orderhistory\Helper\Data $helper,
        private \Magento\Framework\App\Request\Http $request
    )
    {
    }

    /**
     * @inheritDoc
     *
     * B-1027092
     */
    public function beforePrepare(\Magento\Ui\Component\Listing\Columns\Column $subject)
    {
        $fullaction = $this->request->getFullActionName();
        $actions = "negotiable_quote_quote_index";
        if ($this->helper->isModuleEnabled() === true && $fullaction == $actions) {
            $hideColumns = ['updated_at','created_by'];

            $newData = $subject->getData();
            if (in_array($newData['name'], $hideColumns)) {
                $newData['config']['visible'] = false;

            }
            if ($newData['name'] == "quote_name") {
                $newData['config']['component'] = 'Fedex_Orderhistory/js/quote/grid/columns/text_with_title';
            }
            $className = 'epro_quote_list_column_'.$newData['name'];
            $newData['config']['fieldClass'][$className] = 1;
            $subject->setData($newData);
        }
    }
}
