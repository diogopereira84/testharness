<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Ui\Component\Fieldset;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Form\Fieldset;

class PaymentOption extends Fieldset
{
    /**
     * Constructor function
     *
     * @param ContextInterface $context
     * @param ToggleConfig $toggleConfig
     * @param array $components
     * @param array $data
     * @return void
     */
    public function __construct(
        ContextInterface $context,
        private ToggleConfig $toggleConfig,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
    }

    /**
     * Visible Payment tab based on toggle value
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        $config = parent::getConfiguration();
        $visible = true;
        if ($this->getName() == 'company_payment_methods') {
            $config['visible'] = (bool) $visible;
            $config['disabled'] = (bool) !$visible;
        } else {
            $config['visible'] = (bool) !$visible;
            $config['disabled'] = (bool) $visible;
        }

        return $config;
    }
}
