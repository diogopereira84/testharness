<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\DownloadLinkDTOInterface;

class DownloadLinkDTO implements DownloadLinkDTOInterface
{
    public function __construct(
        public string $href
    ) {}

    public function getHref(): string { return $this->href; }
}
