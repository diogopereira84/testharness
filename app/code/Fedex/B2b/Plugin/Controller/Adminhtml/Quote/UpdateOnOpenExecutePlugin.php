<?php

namespace Fedex\B2b\Plugin\Controller\Adminhtml\Quote;

use Magento\NegotiableQuote\Controller\Adminhtml\Quote\UpdateOnOpen;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Model\Quote\Currency;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Model\QuoteUpdatesInfo;

class UpdateOnOpenExecutePlugin {

    private   $quoteData;

    public function __construct(
        protected RequestInterface $request,
        protected ToggleConfig $toggleConfig,
        protected LoggerInterface $logger,
        protected CartRepositoryInterface $quoteRepository,
        protected Currency $quoteCurrency,
        protected NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        protected QuoteUpdatesInfo $quoteUpdatesInfo
    )
    {
    }
//@codeCoverageIgnoreStart
    public function aroundExecute(UpdateOnOpen $subject, callable $proceed)
    {
        $data = [];
        $quoteId = $this->request->getParam('quote_id');

        $this->quoteData = $subject->prepareQuoteData();

        try {
            $quote = $this->quoteRepository->get($quoteId, ['*']);
            $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();

            if (!$this->toggleConfig->getToggleConfigValue('xmen_upload_to_quote') &&
                $negotiableQuote->getStatus() != NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER) {

                $oldIsPriceChanged = $negotiableQuote->getIsCustomerPriceChanged();
                $this->quoteCurrency->updateQuoteCurrency($quoteId);
                $this->negotiableQuoteManagement->openByMerchant($quoteId);

                if ($negotiableQuote->getNegotiatedPriceValue() === null) {
                    $negotiableQuote->setIsCustomerPriceChanged($oldIsPriceChanged);
                }

                $this->quoteData = [];
                $data = $proceed();
                $quote = $this->quoteRepository->get($quoteId);
                $data['messages'] = $this->quoteUpdatesInfo->getMessages($quote);
                $this->quoteRepository->save($quote);

                return $data;
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                                    ' Requested quote was not found. Quote Id = ' . $quoteId);
            $data['messages'] = [
                ['type' => 'error', 'text' => __('Requested quote was not found.')]
            ];
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ' An error occurred on the server. Quote Id = ' . $quoteId . '. Error Message: ' . $e->getMessage());
            $data['messages'] = [
                [
                    'type' => 'error',
                    'text' => __('An error occurred on the server. %1', $e->getMessage())
                ]
            ];
        }
    }
     //@codeCoverageIgnoreEnd
}
