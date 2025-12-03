<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);
namespace Fedex\AllPrintProducts\Model\Config\Source;

class InstoreOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    protected $_options;

    /**
     * getAllOptions
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '0', 'label' => __('Not Available')],
                ['value' => '1', 'label' => __('Available')]
            ];
        }
        return $this->_options;
    }
   }
