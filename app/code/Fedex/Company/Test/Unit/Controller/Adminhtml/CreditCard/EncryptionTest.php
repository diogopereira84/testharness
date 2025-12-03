<?php
namespace Fedex\Company\Test\Unit\Controller\Adminhtml\CreditCard;

use Fedex\Company\Controller\Adminhtml\CreditCard\Encryption;
use Fedex\Delivery\Model\CreditCard\EncryptionHandler;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;

class EncryptionTest extends TestCase
{
    protected $jsonFactoryMock;
    protected $encryptionHandlerMock;
    protected $jsonMock;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $encryptionKey;
    /**
     * Mock encryption key
     */
    const ENCRYPTION_KEY = [
        'encryption' => [
            'key' => '-----BEGIN PUBLIC KEY-----↵MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkhupQI0fxg3qxXZK5iBK↵0FN3L6nfbNfsybjEL3qCB2eTgVa6VBcSqJKsl07wYOnG/H0Pd+9AKsIkffA5zdzz↵TKSewfEliSLCTgffGPTBHxkiM+flYyEjaFRbz9PdAfKICtFWLhlGodc9aceLJUYp↵JrAK/zZMnoGZhKxQzz+UeYHB9qTDKuFmxSpeeFnF85C3GTp0lCw+K90/DXhquP3I↵zPH4hqnTsixhKNkF9c5X/zoNv5TkJB6XZsdUbt7RlMrO32ppidOHtROn5v0Nw1Dm↵/ERv1GkMvemCbgaxBwcNaeqq3i6krWcJLst4ZEouVZ83DNZZHZyYLwRhwbW/VvvM↵lQIDAQAB↵-----END PUBLIC KEY-----',
        ],
    ];

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->jsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->encryptionHandlerMock = $this->createMock(EncryptionHandler::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->encryptionKey = $this->objectManagerHelper->getObject(
            Encryption::class,
            [
                'resultJsonFactory' => $this->jsonFactoryMock,
                'encryptionHandler' => $this->encryptionHandlerMock,
            ]
        );
    }

    /**
     * @test testExecute
     */
    public function testExecute()
    {
        $this->encryptionHandlerMock->expects($this->any())
            ->method('getEncryptionKey')
            ->willReturn(self::ENCRYPTION_KEY);

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonMock);

        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();

        $response = $this->encryptionKey->execute();

        $this->assertInstanceOf(Json::class, $response);
    }
}
