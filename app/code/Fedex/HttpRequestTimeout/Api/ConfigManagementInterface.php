<?php
/**
 * @category Fedex
 * @package  Fedex_HttpRequestTimeout
 * @copyright   Copyright (c) 2024 FedEx
 */
declare(strict_types=1);

namespace Fedex\HttpRequestTimeout\Api;

use Exception;

interface ConfigManagementInterface
{
    /** @var string  */
    const TIMEOUT_PARAMETER = 'timeout';

    /** @var string  */
    public const XML_PATH_TO_ENABLED = 'fedex_http_request_timeout/timeouts/enabled';

    /** @var string  */
    public const XML_PATH_TO_DEFAULT_TIMEOUT_ENABLED = 'fedex_http_request_timeout/timeouts/default_enabled';


    /** @var string  */
    public const XML_PATH_TO_ENTRIES_LIST = 'fedex_http_request_timeout/timeouts/entries_list';

    /** @var string  */
    public const XML_DEFAULT_TIMEOUT = 'fedex_http_request_timeout/timeouts/default_timeout';

    /** @var int  */
    public const DEFAULT_TIMEOUT = 60;

    /**
     * @return bool
     */
    public function isFeatureEnabled(): bool;

    /**
     * @return bool
     */
    public function isDefaultTimeoutEnabled(): bool;

    /**
     * @return int
     */
    public function getDefaultTimeout(): int;

    /**
     * @return array
     */
    public function getCurrentEntriesValueUnserialized(): array;

    /**
     * @return string
     */
    public function getCurrentEntriesValueForListing(): string;

    /**
     * @param $serializedUpdatedValue
     * @return void
     */
    public function saveEntries($serializedUpdatedValue): void;

    /**
     * @return mixed
     * @throws Exception
     */
    public function updatedEntries(): mixed;

    /**
     * @return string|bool
     * @throws Exception
     */
    public function removedEntries(): string|bool;
}
