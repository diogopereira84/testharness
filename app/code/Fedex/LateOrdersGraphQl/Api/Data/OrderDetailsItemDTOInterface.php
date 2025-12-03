<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Api\Data;

interface OrderDetailsItemDTOInterface
{
    public function getProductId(): string;
    public function getDocumentId(): array;
    public function getProductConfiguration(): array;
    public function getProductionInstructions(): array;
    public function getDownloadLinks(): array;
}

