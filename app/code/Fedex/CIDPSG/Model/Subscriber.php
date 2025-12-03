<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Model;

use Fedex\CIDPSG\Api\MessageInterface;
use Fedex\CIDPSG\Api\SubscriberInterface;
use Psr\Log\LoggerInterface;
use Fedex\CIDPSG\Helper\GenerateCsvHelper;
use Fedex\CIDPSG\Helper\Email as EmailHelper;

class Subscriber implements SubscriberInterface
{
    /**
     * Subscriber constructor.
     * @param LoggerInterface $logger
     * @param EmailHelper $emailHelper
     * @param GenerateCsvHelper $generateCsvHelper
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected EmailHelper $emailHelper,
        protected GenerateCsvHelper $generateCsvHelper
    )
    {
    }

    /**
     * Process generic mail data from queue
     *
     * @param MessageInterface $message
     * @return void
     */
    public function processGenericEmail(MessageInterface $message)
    {
        try {
            $requestData = $message->getMessage();
            $genericEmailData = json_decode((string)$requestData, true);

            $this->logger->info(
                __METHOD__.':'.__LINE__. "Email data read from generic
                email queue " . $requestData
            );

            if (isset($genericEmailData["commercial_report"])) {
                $this->emailHelper->sendEmail($genericEmailData);
                unlink($genericEmailData["attachment"]);
            } else {
                if (!empty($genericEmailData) && is_array($genericEmailData)) {
                    if (array_key_exists("attachment", $genericEmailData) && $genericEmailData["attachment"]) {
                        $attachmentContent = json_decode(str_replace('\"', '"', $genericEmailData["attachment"]), true);

                        $csvFilePath = $this->generateCsvHelper->generateExcelForAuthrizedUser($attachmentContent);

                        $genericEmailData["attachment"] = $csvFilePath;
                    }

                    if (array_key_exists("customerCsv", $genericEmailData) && $genericEmailData["customerCsv"] != '') {
                        $genericEmailData["attachment"] = $genericEmailData["customerCsv"];
                    }
                    $this->emailHelper->sendEmail($genericEmailData);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__.':'.__LINE__.': Error message in read data from
                generic email queue ',
                ['exception' => $e->getMessage()]
            );
        }
    }
}
