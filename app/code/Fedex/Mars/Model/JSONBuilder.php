<?php

namespace Fedex\Mars\Model;

use Fedex\Mars\Helper\SendToMars;

use Fedex\Mars\Model\OrderProcess;
use Fedex\Mars\Model\OrderProcessFactory;
use Fedex\Mars\Model\QuoteProcessFactory;

class JSONBuilder
{
    private SendToMars $sendToMars;

    /**
     * @param OrderProcessFactory $orderProcessFactory
     * @param QuoteProcessFactory $quoteProcessFactory
     */
    public function __construct(
        private OrderProcessFactory $orderProcessFactory,
        private QuoteProcessFactory $quoteProcessFactory
    )
    {
    }

    /**
     * Prepare json
     *
     * @param $id
     * @param $type
     * @return array
     */
    public function prepareJson($id, $type): array
    {
        switch ($type) {
            case 'order':
                $orderProcess = $this->orderProcessFactory->create();
                $dataJson = $orderProcess->getOrderJson((int)$id);
                break;
            case 'negotiableQuote':
                $quoteProcess = $this->quoteProcessFactory->create();
                $dataJson = $quoteProcess->getQuoteJson((int)$id);
                break;
            default:
                $dataJson = [];
        }
        return $dataJson;
    }
}
