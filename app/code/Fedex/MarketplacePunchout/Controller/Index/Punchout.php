<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Controller\Index;

use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplacePunchout\Model\Context as MarketplaceContext;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Context;

class Punchout extends Action implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param MarketplaceContext $marketplaceContext
     * @param NonCustomizableProduct $nonCustomizableProductModel
     * @param Session $customerSession
     */
    public function __construct(
        private Context                $context,
        private JsonFactory            $resultJsonFactory,
        private RequestInterface       $request,
        private LoggerInterface        $logger,
        private MarketplaceContext     $marketplaceContext,
        private NonCustomizableProduct $nonCustomizableProductModel,
        private Session                $customerSession
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $response = [];
        $resultJson = $this->resultJsonFactory->create();

        try {
            if (!$this->nonCustomizableProductModel->isMktCbbEnabled() || !$this->customerSession->isLoggedIn()) {
                return $resultJson->setData($response);
            }

            if (!$this->request->isPost() || !$this->validateRequest($this->request->getPost())) {
                return $resultJson->setData($response);
            }

            $configurationData = $this->request->getPostValue('configuration_data');
            $productConfigData = $this->request->getPostValue('product_config_data');
            $codeChallenge = $this->request->getPostValue('code_challenge');

            if (!empty($productConfigData) && !empty($configurationData)) {
                $productConfigData = json_decode($productConfigData, true);
                $this->request->setParam('sku', $productConfigData['sku']);
                $this->request->setParam('offer_id', $productConfigData['offer_id']);
                $this->request->setParam('seller_sku', $productConfigData['seller_sku']);

                $response = $this->marketplaceContext->getMarketplace()->punchout(
                    $productConfigData['sku'],
                    false,
                    [
                        'code_challenge' => $codeChallenge
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                ' Stack trace: ' . $e->getTraceAsString() .
                ' Exception during Login/Register process from Marketplace Configurator' .
                $e->getMessage()
            );
        }

        return $resultJson->setData($response);
    }

    /**
     * @param mixed[] $post
     * @return bool
     */
    private function validateRequest(mixed $post): bool
    {
        try {
            $fields = [
                'code_challenge',
                'product_config_data',
                'configuration_data',
                'form_key'
            ];

            foreach ($fields as $field) {
                if (empty($post[$field])) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                ' Stack trace: ' . $e->getTraceAsString() .
                ' Invalid request during Punchout process from PDP or Cart' .
                $e->getMessage()
            );
        }

        return true;
    }
}
