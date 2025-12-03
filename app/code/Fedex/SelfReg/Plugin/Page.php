<?php
/**
 * Copyright © fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Plugin;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use Fedex\SelfReg\Helper\SelfReg;

class Page
{
    /**
     * Data construct
     *
     * @param Session $customerSession
     * @param CompanyRepositoryInterface $companyRepository
     * @param Http $request
     * @param DeliveryHelper $deliveryHelper
     * @param SdeHelper $sdeHelper
     * @param SelfReg $selfReg
     */
    public function __construct(
        private Session $customerSession,
        private CompanyRepositoryInterface $companyRepository,
        private Http $request,
        private DeliveryHelper $deliveryHelper,
        private SdeHelper $sdeHelper,
        private SelfReg $selfReg
    )
    {
    }

    /**
     * Return configuration array
     * @param \Magento\Cms\Block\Page $subject
     * @param result
     * @return array|mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */

    public function afterGetPage($subject, $result)
    {
        $isEproCustomer = $this->deliveryHelper->isEproCustomer();
        $action = $this->request->getFullActionName();
        $isSdeStore = $this->sdeHelper->getIsSdeStore();
        $isSelfRegCustomer = $this->selfReg->isSelfRegCustomer();

        if (($isSelfRegCustomer && $action == "cms_index_index") ||
         ($isEproCustomer && $action == "cms_index_index" && !$isSdeStore)) {
            $result->setContent(
                '{{block class="Fedex\SelfReg\Block\EproHome"
                 name="epro_homepage_block" template="Fedex_SelfReg::eprohome.phtml"}}'
            );
        }
        // B-1515570
		if ($action == "cms_index_index"){
			$companyData = $this->customerSession->getOndemandCompanyInfo();

            if ($companyData && is_array($companyData) &&
                !empty($companyData['url_extension']) &&
                !empty($companyData['company_type']) &&
                $companyData['company_type'] == 'sde'
            ) {
                $result->setContent(
                    '{{block class="Fedex\SelfReg\Block\Home"
                                name="epro_homepage_block" template="Fedex_SelfReg::home.phtml"}}'
                );
            }

		}


        return $result;
    }
}
