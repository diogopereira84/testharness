<?php

namespace Fedex\Company\Api;

/**
 * @codeCoverageIgnore
 */
interface CreateCompanyEntitiesMessageInterface
{
    /**
     * Set message
     *
     * @param string $message
     * @return void
     */
    public function setMessage($message);

    /**
     * Get Message
     *
     * @return string
     */
    public function getMessage();
}
