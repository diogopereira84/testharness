<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
*/

namespace Fedex\EnhancedProfile\Controller\Account;

use Exception;
use Magento\Framework\App\ActionInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * RemoveAccount Controller class
 */
class RemoveAccount implements ActionInterface
{
 
    /**
     * Initialize dependencies.
     *
     * @param RequestInterface $request
     * @param CompanyRepositoryInterface $companyRepository
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly LoggerInterface $logger,
        private readonly JsonFactory $resultJsonFactory,
        private readonly Json $json
    ) {
    }
        
    /**
     * Preferences information
     *
     * @return void
     */
    public function execute()
    {
        $requestParams = $this->request->getParams();
        $companyId = $requestParams['companyId'] ? $requestParams['companyId'] : null;
        try {
            if ($companyId !== null) {
                $company = $this->companyRepository->get((int) $companyId);
            }

            if ($requestParams['accountType'] == 'Print') {
                $company->setData('fedex_account_number', '');
                $company->setData('fxo_account_number_editable', 0);
            } else {
                $company->setData('shipping_account_number', '');
                $company->setData('shipping_account_number_editable', 0);
            }
            
            $company->save();
            
         } catch (LocalizedException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $result = ['error' => true, 'msg' => $e->getMessage()];

            return $this->resultJsonFactory->create()->setData($result);
         } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $result = ['error' => true, 'msg' => $e->getMessage()];

            return $this->resultJsonFactory->create()->setData($result);
         }
         $result = ['error'=>false, 'msg'=>'Account Removed Successfully'];

         return $this->resultJsonFactory->create()->setData($result);

    }

}
