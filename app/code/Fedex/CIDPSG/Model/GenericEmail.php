<?php
/**
 * Copyright FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Model;

use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\CIDPSG\Api\MessageInterface;

class GenericEmail
{
    /**
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param PublisherInterface $publisher
     * @param MessageInterface $message
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected RequestInterface $request,
        protected PublisherInterface $publisher,
        protected MessageInterface $message
    )
    {
    }

    /**
     * Publish email string in queue
     *
     * @return void
     */
    public function publishGenericEmail()
    {
        try {
            $requestContent = $this->request->getContent();
            $validateRequestJson = $this->validateRequestJson($requestContent);

            $sampleRequestSchema = [
                "templateData" => "Email content added here",
                "templateSubject" => "Email Subject",
                "toEmailId" => "xyz@gmail.com",
                "fromEmailId" => "abc@gmail.com",
                "retryCount" => 0,
                "errorSupportEmailId" => "",
                "attachment" => "",
                "customerCsv" => ""
            ];

            if (!$validateRequestJson) {
                $this->logger->info(
                    __METHOD__.':'.__LINE__. "Request Schema is not valid"
                );
                return [
                    "code" => 500,
                    "message" => "Request schema is not valid. It should be".
                    json_encode($sampleRequestSchema)
                ];
            }

            $this->message->setMessage($requestContent);
            $this->publisher->publish("genericEmailQueue", $this->message);
            $this->logger->info(
                __METHOD__.':'.__LINE__. "Publish email data in generic email queue" .
                $this->message->getMessage()
            );

            return [
                "code" => 200,
                "message" => "success"
            ];
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__.':'.__LINE__.':Error message in publish data
                in generic email queue',
                ['exception' => $e->getMessage()]
            );
            return ["code" => 401, "message" => "failure"];
        }
    }

    /**
     * Validate request JSON
     *
     * @param string $requestContent
     * @return void
     */
    public function validateRequestJson($requestContent)
    {
        $requestContent = json_decode($requestContent, true);

        $emptyValidation = is_array($requestContent) && !empty($requestContent["templateData"])
        && !empty($requestContent["templateSubject"]) && !empty($requestContent["toEmailId"]) &&
        !empty($requestContent["fromEmailId"]);

        if ($emptyValidation) {
            return true;
        }
    }
}
