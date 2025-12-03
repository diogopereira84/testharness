<?php
/**
 * @category     Fedex
 * @package      Fedex_FujitsuCore
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;

class RequestQueryValidator
{
    /**
     * RequestQueryValidator constructor
     *
     * @param SerializerInterface $serializer
     * @param RequestInterface $request
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     */
    public function __construct(
        private SerializerInterface $serializer,
        private RequestInterface $request,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel
    )
    {
    }

    /**
     * Check if is GraphQl request
     *
     * @param RequestInterface $request
     * @return boolean
     */
    public function isGraphQl(): bool
    {
        return $this->isGraphQlRequest($this->request);
    }

    /**
     * Check if is GraphQl request
     *
     * @param RequestInterface $request
     * @return boolean
     */
    public function isGraphQlRequest(RequestInterface $request): bool
    {
        /** @var Http $request */
        if ((true === $request->isPost())
          || (true === $request->isGet())) {
            $requestContent = $request->getContent();
            if ($this->isValidJson($requestContent)) {
                $body = $this->serializer->unserialize($requestContent);
                if ((isset($body['query'])) && (!empty($body['query']))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if string is a valid json
     *
     * @param string $rawJson
     * @return boolean
     */
    private function isValidJson(string $rawJson): bool
    {
        return (json_decode($rawJson, true) === null) ? false : true;
    }

     /**
      * Check if is U2Q request
      *
      * @param RequestInterface $request
      * @param bool $isGraphQlRequest
      * @return boolean
      */
    public function isNegotiableQuoteGraphQlRequest(RequestInterface $request, $isGraphQlRequest): bool
    {
        if ($isGraphQlRequest && $this->uploadToQuoteViewModel->isUploadToQuoteGloballyEnabled()) {
                    /** @var Http $request */
            if ((true === $request->isPost())
            || (true === $request->isGet())) {
                $requestContent = $request->getContent();
                if ($this->isValidJson($requestContent)) {
                    $body = $this->serializer->unserialize($requestContent);
                    if (isset($body['query']) &&
                    (
                        str_contains($body['query'], 'updateNegotiableQuote')
                        || str_contains($body['query'], 'getQuoteDetails')
                    )
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
