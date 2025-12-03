<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2024 FedEx
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Controller\Index;

use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplacePunchout\Model\FclLogin;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class Ajax extends Action implements CsrfAwareActionInterface
{
    const REQUEST_URL = 'marketplacepunchout/index/punchout';

    public function __construct(
        private Context          $context,
        private NonCustomizableProduct $nonCustomizableProductModel,
        private Session          $customerSession,
        private PageFactory      $resultPageFactory,
        private FclLogin         $fclLogin,
        private SsoConfiguration $ssoConfiguration,
        private FormKey          $formKey,
        private LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $data = [
            'error_url' => $this->ssoConfiguration->getHomeUrl(),
            'success' => false
        ];

        try {
            if ($this->nonCustomizableProductModel->isMktCbbEnabled() && $this->customerSession->isLoggedIn()) {
                $configurationData = $this->customerSession->getSellerConfigurationData();
                $productConfigData = $this->customerSession->getProductConfigData();

                $data = [
                    'product_config_data' => $productConfigData,
                    'configuration_data' => $configurationData,
                    'request_url' => $this->ssoConfiguration->getHomeUrl() . SELF::REQUEST_URL,
                    'error_url' => $this->ssoConfiguration->getHomeUrl(),
                    'form_key' => $this->formKey->getFormKey(),
                    'success' => true
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                'Exception during Login/Register process from Marketplace Configurator' .
                $e->getMessage()
            );
        }

        $this->fclLogin->setData($data);
        return $this->resultPageFactory->create();
    }

    /**
     * By pass CSRF Exception
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * By pass CSRF validation
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}

