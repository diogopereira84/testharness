<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Controller\Quote;

use Fedex\Recaptcha\Model\Validator;
use Psr\Log\LoggerInterface;
use Exception;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderBuilder;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\DataSourceComposite;
use Magento\Framework\Controller\ResultInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Checkout\Model\CartFactory;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;

/**
 * SubmitOrderOptimized Controller
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SubmitOrderOptimized implements ActionInterface
{
    /**
     * Unified Data Layer key
     */
    private const UNIFIED_DATA_LAYER = 'unified_data_layer';
    public const CHECKOUT_SUBMIT_ORDER_RECAPTCHA = 'checkout_order';

    /**
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param SubmitOrderBuilder $submitOrderBuilder
     * @param DataSourceComposite $dataSourceComposite
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     * @param CartFactory $cartFactory
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param Validator $recaptchaValidator
     */
    public function __construct(
        protected JsonFactory $resultJsonFactory,
        protected RequestInterface $request,
        protected SubmitOrderBuilder $submitOrderBuilder,
        private DataSourceComposite $dataSourceComposite,
        private LoggerInterface $logger,
        protected ToggleConfig $toggleConfig,
        protected CartFactory $cartFactory,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        protected Validator $recaptchaValidator
    )
    {
    }

    /**
     * Function to create Fujitsu Rate Quote request for delivery flow.
     * @return ResponseInterface|ResultInterface|void
     * @throws Exception
     */
    public function execute()
    {
        $requestData = $this->request->getPost('data');
        $this->logger->info(
            __METHOD__ . ':' . __LINE__ .
            ' Request data for  order submission SubmitOrderOptimized => '
            . $requestData
        );
        $recaptchaResult = $this->validateRecaptcha();
        if ($recaptchaResult !== null) {
            return $recaptchaResult;
        }

        $response = null;
        $pickupStore = $this->request->getParam('pickstore') ?? 0;
        $resultJson = $this->resultJsonFactory->create();

        try {
            $quoteDeactivationFixToggle = $this->toggleConfig
            ->getToggleConfigValue('mazegeek_team_utoq_quote_deactive_fix');
            if ($quoteDeactivationFixToggle) {
                $quote = $this->cartFactory->create()->getQuote();
            }

            $requestData = json_decode((string) $requestData);
            $response = $this->submitOrderBuilder->build(
                $requestData,
                $pickupStore
            );

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .
                ' Response after order submission SubmitOrderOptimized => '
                . json_encode($response)
            );

            if (is_array($response) && isset($response[0])) {
                if ($quoteDeactivationFixToggle) {
                    $quoteId = $quote->getId();
                    $this->uploadToQuoteViewModel->updateQuoteStatusByKey(
                        $quoteId,
                        NegotiableQuoteInterface::STATUS_ORDERED,
                        true
                    );
                }
                return $resultJson->setData([
                    $response,
                    self::UNIFIED_DATA_LAYER => $this->dataSourceComposite->compose($response)
                ]);
            }
        } catch (Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                ' Exception during checkout submission => '
                . $e->getMessage()
            );
        }

        return $resultJson->setData([$response]);
    }

    /**
     * Validate Recaptcha
     * @return ResultInterface|null
     */
    private function validateRecaptcha(): ?ResultInterface
    {
        if (!$this->toggleConfig->getToggleConfigValue('tiger_b2384493') ||
            !$this->recaptchaValidator->isRecaptchaEnabled(self::CHECKOUT_SUBMIT_ORDER_RECAPTCHA)) {
            return null;
        }

        $recaptchaValidation = $this->recaptchaValidator->validateRecaptcha(self::CHECKOUT_SUBMIT_ORDER_RECAPTCHA);
        if (is_array($recaptchaValidation)) {
            $result = $this->resultJsonFactory->create();
            $result->setData($recaptchaValidation);

            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Submit Order is not working: Recaptcha Error');
            return $result;
        }

        return null;
    }
}
