<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api\Data;

interface DownloadLinkDTOInterface
{
    public function getHref(): string;
}

