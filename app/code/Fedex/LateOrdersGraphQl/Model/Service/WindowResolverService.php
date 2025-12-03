<?php

namespace Fedex\LateOrdersGraphQl\Model\Service;

use Fedex\LateOrdersGraphQl\Model\Config;
use Fedex\LateOrdersGraphQl\Model\Data\TimeWindowDTO;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\Timezone;

class WindowResolverService
{
    const ISO8601_REGEX = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/';

    /**
     * Constructor
     * @param Config $config
     * @param Timezone $timezone
     */
    public function __construct(
        private Config   $config,
        private Timezone $timezone
    )
    {
    }

    /**
     * Resolve and cap the time window based on "since" and "until" parameters.
     * @param string|null $since
     * @param string|null $until
     * @return TimeWindowDTO
     * @throws LocalizedException
     * @throws \DateInvalidOperationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function resolveAndCapWindow(?string $since, ?string $until): TimeWindowDTO
    {
        $now = $this->timezone->date();
        $maxHours = (int)($this->config->getLateOrderQueryWindowHours() ?: 1);
        $maxHours = max(1, $maxHours);
        $maxSeconds = $maxHours * 3600;

        $sinceDt = $this->parseDate($since, 'since');
        $untilDt = $this->parseDate($until, 'until');

        if ($sinceDt === null && $untilDt === null) {
            throw new LocalizedException(__('You must provide at least one of "since" or "until".'));
        }

        if ($untilDt && $untilDt > $now) {
            $untilDt = $now;
        }

        if ($sinceDt && $untilDt) {
            return $this->buildWindowWithSinceAndUntil($sinceDt, $untilDt, $maxSeconds, $maxHours);
        }

        if ($sinceDt) {
            return $this->buildWindowWithSinceOnly($sinceDt, $now, $maxSeconds, $maxHours);
        }

        return $this->buildWindowWithUntilOnly($untilDt, $maxHours, $maxSeconds);
    }

    /**
     * Build a time window when both "since" and "until" are provided.
     * @param \DateTimeImmutable $sinceDt
     * @param \DateTimeImmutable $untilDt
     * @param int $maxSeconds
     * @param int $maxHours
     * @return TimeWindowDTO
     * @throws LocalizedException
     */
    private function buildWindowWithSinceAndUntil(
        \DateTimeImmutable $sinceDt,
        \DateTimeImmutable $untilDt,
        int                $maxSeconds,
        int                $maxHours
    ): TimeWindowDTO
    {
        $this->validateWindow($sinceDt, $untilDt, $maxSeconds, $maxHours);
        return new TimeWindowDTO($sinceDt, $untilDt);
    }

    /**
     * Build a time window when only "since" is provided.
     * @param \DateTimeImmutable $sinceDt
     * @param \DateTimeImmutable $now
     * @param int $maxSeconds
     * @param int $maxHours
     * @return TimeWindowDTO
     * @throws LocalizedException
     */
    private function buildWindowWithSinceOnly(
        \DateTimeImmutable $sinceDt,
        \DateTimeImmutable $now,
        int                $maxSeconds,
        int                $maxHours
    ): TimeWindowDTO
    {
        $this->validateWindow($sinceDt, $now, $maxSeconds, $maxHours, true);
        return new TimeWindowDTO($sinceDt, $now);
    }

    /**
     * Build a time window when only "until" is provided.
     * @param \DateTimeImmutable $untilDt
     * @param int $maxHours
     * @param int $maxSeconds
     * @return TimeWindowDTO
     * @throws LocalizedException
     * @throws \DateInvalidOperationException
     */
    private function buildWindowWithUntilOnly(
        \DateTimeImmutable $untilDt,
        int                $maxHours,
        int                $maxSeconds
    ): TimeWindowDTO
    {
        $sinceDt = $untilDt->sub(new \DateInterval('PT' . $maxHours . 'H'));
        $this->validateWindow($sinceDt, $untilDt, $maxSeconds, $maxHours);
        return new TimeWindowDTO($sinceDt, $untilDt);
    }

    /**
     * Parse a date string in ISO-8601 format into a DateTimeImmutable object.
     * @param string|null $value
     * @param $fieldName
     * @return \DateTimeImmutable|null
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    private function parseDate(?string $value, $fieldName): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!preg_match(self::ISO8601_REGEX, $value)) {
            throw new \Magento\Framework\GraphQl\Exception\GraphQlInputException(
                __("%1 must be in ISO-8601 format (e.g. 2025-09-01T00:00:00Z). Value given: '%2'", $fieldName, $value)
            );
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new \Magento\Framework\GraphQl\Exception\GraphQlInputException(
                __("Invalid '%1' date format: '%2'. Please use ISO-8601 format.", $fieldName, $value)
            );
        }
    }

    /**
     * Validate that the time window between since and until does not exceed maxSeconds.
     * @param \DateTimeImmutable $since
     * @param \DateTimeImmutable $until
     * @param int $maxSeconds
     * @param int $maxHours
     * @param bool $sinceOnly
     * @return void
     * @throws LocalizedException
     */
    private function validateWindow(
        \DateTimeImmutable $since,
        \DateTimeImmutable $until,
        int                $maxSeconds,
        int                $maxHours,
        bool               $sinceOnly = false
    ): void
    {
        if ($until <= $since) {
            throw new LocalizedException(__('Parameter "until" must be after "since".'));
        }
        $diff = $until->getTimestamp() - $since->getTimestamp();
        if ($diff > $maxSeconds) {
            if ($sinceOnly) {
                throw new LocalizedException(__('"since" is too far in the past. Max window is %1 hour(s).', $maxHours));
            }
            throw new LocalizedException(__('Time window exceeds the maximum of %1 hour(s).', $maxHours));
        }
    }
}
