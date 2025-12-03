<?php
/**
 * @category    Fedex
 * @package     Fedex_ShippingEstimator
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ShippingEstimator\Test\Unit\Controller\Index;

use Fedex\ShippingEstimator\Controller\Index\Index;
use Magento\Framework\App\Request\Http;
use Fedex\ShippingEstimator\Model\Service\Delivery;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class IndexTest extends TestCase
{
    protected $deliveryApiMock;
    protected $jsonMock;
    protected $index;
    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactoryMock;
    /**
     * @var Http
     */
    private Http $requestMock;

    /**
     * @inheritDoc
     *
     */
    protected function setUp(): void
    {
        $this->deliveryApiMock = $this->getMockBuilder(Delivery::class)
            ->setMethods(['getDeliveryInfo'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->setMethods(['getParams'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->index = $objectManagerHelper->getObject(
            Index::class,
            [
                'response' => $this->requestMock,
                'deliveryApi' => $this->deliveryApiMock,
                'jsonFactory' => $this->jsonFactoryMock
            ]
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {
        $data = ['products'=>'','postalCode'=>'','stateOrProvinceCode'=>'','validateContent'=>''];
        $response['response'] = ['data' => '',
            'hasError' => false,
            'message' => ''
        ];
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        $this->deliveryApiMock->expects($this->any())->method('getDeliveryInfo')->willReturn($response);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->index->execute());
    }
}
