<?php

namespace Fedex\CatalogMvp\Api;

use Fedex\CatalogMvp\Api\DocRefMessageInterface;

/**
 * @codeCoverageIgnore
 */
interface DocRefSubscriberInterface
{
    /**
     * Process message for RabbitMq
     *
     * @param DocRefMessageInterface $message
     * @return void
     */
    public function processMessageMetaData(DocRefMessageInterface $message);

    /**
     * Process message for RabbitMq
     *
     * @param DocRefMessageInterface $message
     * @return void
     */
    public function processMessageAddRef(DocRefMessageInterface $message);

    /**
     * Process message for RabbitMq
     *
     * @param DocRefMessageInterface $message
     * @return void
     */
    public function processMessageDeleteRef(DocRefMessageInterface $message);

    /**
     * Process message for RabbitMq
     *
     * @param DocRefMessageInterface $message
     * @return void
     */
    public function processMessageExtandExpire(DocRefMessageInterface $message);
    
}