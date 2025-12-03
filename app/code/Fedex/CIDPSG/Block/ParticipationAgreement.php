<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Fedex\CIDPSG\Helper\PsgHelper;
use Magento\Framework\App\Request\Http;

/**
 * ParticipationAgreement Block class
 */
class ParticipationAgreement extends Template
{

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param PsgHelper $psgHelper
     * @param Http $request
     */
    public function __construct(
        Context $context,
        protected PsgHelper $psgHelper,
        protected Http $request
    ) {
        parent::__construct($context);
    }

    /**
     * Get the current url params
     *
     * @return array
     */
    public function getUrlParams()
    {
        return $this->request->getParams();
    }

    /**
     * Get the Psg customer Information
     *
     * @param int $clientId
     * @return array
     */
    public function getCustomerInfo($clientId)
    {
        return $this->psgHelper->getPSGCustomerInfo($clientId);
    }

    /**
     * Get the get field validation type
     *
     * @param string $fieldValidationType
     * @return array
     */
    public function getFieldValidationType($fieldValidationType)
    {
        $validationArray = [
            'email' => 'v-validate validate-email validate-length',
            'telephone' => 'v-validate validate-user-phone pa_phone',
            'text' => 'v-validate',
            'fax' => 'v-validate validate-user-fax pa_phone',
            'zipcode' => 'v-validate validate-zip-code',
            'fedex_account' => 'v-validate pa_allow_number_only validate-fedex-account-no',
            'fedex_shipping_account' => 'v-validate pa_allow_number_only',
        ];

        return $validationArray[$fieldValidationType];
    }

    /**
     * Mask Participation code last 4 characters.
     *
     * @param string $participationCode
     * @return string
     */
    public function getParticipationCodeMaskedLast4($participationCode)
    {
        if (!empty($participationCode) && strlen($participationCode = trim($participationCode)) >= 4) {
            $participationCode = str_repeat('*', strlen($participationCode) - 4) .
            substr((string) $participationCode, -4);
        }

        return $participationCode;
    }
}
