<?php
namespace Fedex\LateOrdersGraphQl\Model\Data;

class TimeWindowDTO
{
    public function __construct(
        public readonly \DateTimeImmutable $since,
        public readonly \DateTimeImmutable $until
    ) {}
}

