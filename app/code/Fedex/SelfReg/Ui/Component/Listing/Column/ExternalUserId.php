<?php

namespace Fedex\SelfReg\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Fedex\Base\Helper\Auth;

/**
 * External User ID column
 */
class ExternalUserId extends Column {

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SelfReg $selfregHelper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Auth $authHelper,
        array $components = [],
        array $data = []
    ) {
        $isFclSelfreg = $authHelper->getCompanyAuthenticationMethod();
        if ($isFclSelfreg !== Auth::AUTH_FCL) {
            $data = [];
        }
        parent::__construct($context, $uiComponentFactory,$components, $data);
    }
}