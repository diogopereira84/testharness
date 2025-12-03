<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\InBranch\Controller\Cart;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\InBranch\Model\InBranchValidation;

/**
 * @codeCoverageIgnore
 */
class Add extends \Magento\Checkout\Controller\Cart\Add
{

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var RequestQuantityProcessor
     */
    private $quantityProcessor;


    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerCart $cart
     * @param InBranchValidation $inbranchvalidation
     * @param ToggleConfig $toggleConfig
     * @param RequestQuantityProcessor|null $quantityProcessor
     * @codeCoverageIgnore
     */
    public function __construct(
        Context                    $context,
        ScopeConfigInterface       $scopeConfig,
        Session                    $checkoutSession,
        StoreManagerInterface      $storeManager,
        Validator                  $formKeyValidator,
        ProductRepositoryInterface $productRepository,
        CustomerCart               $cart,
        private InBranchValidation         $inbranchvalidation,
        protected ToggleConfig               $toggleConfig,
        ?RequestQuantityProcessor  $quantityProcessor = null
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository,
            $quantityProcessor
        );
        $this->quantityProcessor = $quantityProcessor
            ?? ObjectManager::getInstance()->get(RequestQuantityProcessor::class);
    }

    /**
     * Goback function overriden
     *
     * @param string $backUrl
     * @param object $product
     * @return ResponseInterface|Redirect|ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function goBack($backUrl = null, $product = null)
    {
        $result = [];
        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $backUrl;

            //Inbranch Implementation
                $isEproStore = $this->inbranchvalidation->isInBranchUser();
            if ($isEproStore) {
                $inBranchProductExist = !empty($this->getRequest()->getParam('inBranchProductExist'))
                    ? $this->getRequest()->getParam('inBranchProductExist') : false;
                $result['inBranchProductExist'] = $inBranchProductExist;
            }
            //Inbranch Implementation

        } else {
            if ($product && !$product->getIsSalable()) {
                $result['product'] = [
                    'statusText' => __('Out of stock')
                ];
            }
        }
        if (!$this->getRequest()->isAjax()) {
            return parent::_goBack($backUrl);
        }

        if($this->cart->getQuote()->getFxoRateError()) {
            $result['fxo_rate_error'] = true;
            $this->cart->getQuote()->setFxoRateError(false);
        }

        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );

        return $this->getResponse();
    }
}
