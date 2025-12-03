<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Data;

use Fedex\LateOrdersGraphQl\Model\Data\OrderDetailsItemDTO;
use PHPUnit\Framework\TestCase;

class OrderDetailsItemDTOTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $productId = 'PID123';
        $documentId = ['DOC1', 'DOC2'];
        $productConfiguration = ['config1' => 'value1'];
        $productionInstructions = ['instruction1', 'instruction2'];
        $downloadLinks = ['link1', 'link2'];
        $item = new OrderDetailsItemDTO(
            $productId,
            $documentId,
            $productConfiguration,
            $productionInstructions,
            $downloadLinks
        );
        $this->assertEquals($productId, $item->getProductId());
        $this->assertEquals($documentId, $item->getDocumentId());
        $this->assertEquals($productConfiguration, $item->getProductConfiguration());
        $this->assertEquals($productionInstructions, $item->getProductionInstructions());
        $this->assertEquals($downloadLinks, $item->getDownloadLinks());
    }
}
