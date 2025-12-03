<?php
namespace Fedex\CSP\Api;

interface CspManagementInterface
{
    /**
     * Return CSP Whitelist Enabled or Not
     *
     * @return bool
     */
    public function isCspWhitelistEnabled(): bool;

    /**
     * Return Unserialized data from Entries
     *
     * @return array
     */
    public function getCurrentEntriesValueUnserialized(): array;

    /**
     * Return Array data from Entries
     *
     * @return string
     */
    public function getCurrentEntriesValueForListing(): string;

    /**
     * Saves Data to core_config_data
     *
     * @param $serializedUpdatedValue
     * @return void
     */
    public function saveEntries($serializedUpdatedValue): void;

    /**
     * Return Data for create|update of Entries
     *
     * @return bool|mixed|string
     */
    public function updatedEntries(): mixed;

    /**
     * Return Data for removal of Entries
     *
     * @return mixed
     * @throws \Exception
     */
    public function removedEntries(): mixed;
}
