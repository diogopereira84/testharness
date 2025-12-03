<?php

declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class FclLogin
{
    /**
     * @var array
     */
    protected $data = [];

    public function __construct(
        private TimezoneInterface $timezone
    ) {
    }

    /**
     * Set data
     *
     * @param array $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getTimeStamp()
    {
        return strtotime($this->timezone->formatDateTime(
            $this->timezone->date(),
            null,
            null,
            null,
            null,
            'yyyy-MM-dd\'T\'HH:mm:ss')
        );
    }
}