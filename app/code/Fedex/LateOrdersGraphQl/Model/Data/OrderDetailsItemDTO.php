<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\OrderDetailsItemDTOInterface;

class OrderDetailsItemDTO implements OrderDetailsItemDTOInterface
{
    public function __construct(
        public string $productId,
        public array $documentId,
        public array $productConfiguration,
        public array $productionInstructions,
        public array $downloadLinks
    ) {}

    public function getProductId(): string { return $this->productId; }
    public function getDocumentId(): array { return $this->documentId; }
    public function getProductConfiguration(): array { return $this->productConfiguration; }
    public function getProductionInstructions(): array { return $this->productionInstructions; }
    public function getDownloadLinks(): array { return $this->downloadLinks; }
}
