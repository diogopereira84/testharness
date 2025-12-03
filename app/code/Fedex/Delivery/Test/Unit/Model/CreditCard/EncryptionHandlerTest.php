<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Test\Unit\Model\CreditCard;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Delivery\Model\CreditCard\EncryptionHandler;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EncryptionHandlerTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Model\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    protected $scopeConfigInterfaceMock;
    protected $deliveryHelperMock;
    protected $punchoutHelperMock;
    protected $curlMock;
    /**
     * @var (\Magento\Framework\Model\ResourceModel\AbstractResource & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resourceMock;
    /**
     * @var (\Magento\Framework\Data\Collection\AbstractDb & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resourceCollectionMock;
    /**
     * @var (\Magento\Framework\Controller\Result\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonMock;
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    /**
     * Mock encryption API url
     */
    const ENCRYPTION_API_URL = 'https://api.test.office.fedex.com/payment/fedexoffice/v2/encryptionkey';

    /**
     * Mock token key
     */
    public const TOKEN_KEY = 't@k@n123';

    /**
     * Initialize mock objects
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->deliveryHelperMock = $this->createMock(DeliveryHelper::class);
        $this->punchoutHelperMock = $this->createMock(PunchoutHelper::class);
        $this->curlMock = $this->createMock(Curl::class);
        $this->resourceMock = $this->createMock(AbstractResource::class);
        $this->resourceCollectionMock = $this->createMock(AbstractDb::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->objectManager = new ObjectManager($this);

        $this->model = $this->objectManager->getObject(
            EncryptionHandler::class,
            [
                'context' => $this->contextMock,
                'registry' => $this->registryMock,
                'configInterface' => $this->scopeConfigInterfaceMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'punchoutHelper' => $this->punchoutHelperMock,
                'curl' => $this->curlMock,
                'resource' => $this->resourceMock,
                'resourceCollection' => $this->resourceCollectionMock,
                'logger' => $this->loggerMock,
                'data' => [],
            ]
        );
    }

    /**
     * @test testGetEncryptionKeyWithNotCommercialCustomer
     *
     * @return void
     */
    public function testGetEncryptionKeyWithNotCommercialCustomer()
    {
        $key = [
            "-----BEGIN PUBLIC KEY-----↵MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkhup",
            "QI0fxg3qxXZK5iBK↵0FN3L6nfbNfsybjEL3qCB2eTgVa6VBcSqJKsl07wYOnG/H0Pd+",
            "9AKsIkffA5zdzz↵TKSewfEliSLCTgffGPTBHxkiM+flYyEjaFRbz9PdAfKICtFWLhlGodc9aceLJUYp↵JrAK/zZMnoGZhKxQzz+Ue",
            "YHB9qTDKuFmxSpeeFnF85C3GTp0lCw+K90/DXhquP3I↵zPH4hqnTsixhKNkF9c5X/zoNv5TkJB6XZsdUbt7RlMr",
            "O32ppidOHtROn5v0Nw1Dm↵/ERv1GkMvemCbgaxBwcNaeqq3i6krWcJLst4ZEouVZ83DNZZHZyY",
            "LwRhwbW/VvvM↵lQIDAQAB↵-----END PUBLIC KEY-----"
        ];
        $encriyptionResponse  = '{
            "output": {
                "encryption": {
                    "key": "'.$key[0].$key[1].$key[2].$key[3].$key[4].$key[5].'"
                }
            }
        }';

        $this->scopeConfigInterfaceMock->expects($this->any())
            ->method('getValue')
            ->with(EncryptionHandler::XML_PATH_ENCRYPTION_API_URL)
            ->willReturn(self::ENCRYPTION_API_URL);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(false);
        $this->punchoutHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(static::TOKEN_KEY);
        $this->curlMock->expects($this->any())
            ->method('setOptions')->willReturnSelf();
        $this->curlMock->expects($this->any())
            ->method('getBody')->willreturn($encriyptionResponse);

        $this->assertEquals(json_decode($encriyptionResponse, true)['output'], $this->model->getEncryptionKey());
    }

    /**
     * @test testGetEncryptionKeyWithCommercialCustomer
     * @return void
     */
    public function testGetEncryptionKeyWithCommercialCustomer()
    {
        $key = [
            "-----BEGIN PUBLIC KEY-----↵MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkhup",
            "QI0fxg3qxXZK5iBK↵0FN3L6nfbNfsybjEL3qCB2eTgVa6VBcSqJKsl07wYOnG/H0Pd+",
            "9AKsIkffA5zdzz↵TKSewfEliSLCTgffGPTBHxkiM+flYyEjaFRbz9PdAfKICtFWLhlGodc9aceLJUYp↵JrAK/zZMnoGZhKxQzz+Ue",
            "YHB9qTDKuFmxSpeeFnF85C3GTp0lCw+K90/DXhquP3I↵zPH4hqnTsixhKNkF9c5X/zoNv5TkJB6XZsdUbt7RlMr",
            "O32ppidOHtROn5v0Nw1Dm↵/ERv1GkMvemCbgaxBwcNaeqq3i6krWcJLst4ZEouVZ83DNZZHZyY",
            "LwRhwbW/VvvM↵lQIDAQAB↵-----END PUBLIC KEY-----"
        ];
        $encriyptionResponse  = '{
            "output": {
                "encryption": {
                    "key": "'.$key[0].$key[1].$key[2].$key[3].$key[4].$key[5].'"
                }
            }
        }';

        $this->scopeConfigInterfaceMock->expects($this->any())
            ->method('getValue')
            ->with(EncryptionHandler::XML_PATH_ENCRYPTION_API_URL)
            ->willReturn(self::ENCRYPTION_API_URL);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(static::TOKEN_KEY);
        $this->curlMock->expects($this->any())
            ->method('setOptions')->willReturnSelf();
        $this->curlMock->expects($this->any())
            ->method('getBody')->willreturn($encriyptionResponse);

        $this->assertEquals(json_decode($encriyptionResponse, true)['output'], $this->model->getEncryptionKey());
    }

    /**
     * @test testGetEncryptionKeyWithApiError
     * @return void
     */
    public function testGetEncryptionKeyWithApiError()
    {
        $this->scopeConfigInterfaceMock->expects($this->any())
            ->method('getValue')
            ->with(EncryptionHandler::XML_PATH_ENCRYPTION_API_URL)
            ->willReturn(self::ENCRYPTION_API_URL);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(static::TOKEN_KEY);
        $this->curlMock->expects($this->any())
            ->method('setOptions')->willReturnSelf();
        $this->curlMock->expects($this->any())
            ->method('getBody')->willreturn(json_encode(['errors' => true]));
        $this->assertEquals(['errors' => true], $this->model->getEncryptionKey());
    }
}
