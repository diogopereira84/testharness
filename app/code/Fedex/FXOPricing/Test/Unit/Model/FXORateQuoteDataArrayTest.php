<?php

namespace Fedex\FXOPricing\Test\Unit\Model;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\FXOPricing\Model\FXORateQuoteDataArray;
use Fedex\GraphQl\Helper\Data as GraphqlHelper;
use Fedex\GraphQl\Model\RequestQueryValidator;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\DataObjectFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Address;
use Fedex\ComputerRental\Model\CRdataModel;
use Magento\Quote\Model\Quote;

class FXORateQuoteDataArrayTest extends TestCase
{
    protected $dataObjectFactory;
    protected $quote;
    /**
     * @var (\Fedex\GraphQl\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $graphqlHelper;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfigMock;
    /**
     * @var (\Fedex\ComputerRental\Model\CRdataModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $crDataModelMock;
    /**
     * @var (\Fedex\InStoreConfigurations\Api\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $instoreConfig;
    protected $cartIntegrationRepository;
    protected $requestQueryValidator;
    protected $cartIntegration;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $fxoRateQuoteArray;
    /** @var Address  */
    private Address $address;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return void
     */
    protected function setUp():void
    {
        $this->dataObjectFactory = $this->getMockBuilder(DataObjectFactory::class)
            ->addMethods([
                'getQuoteObject',
                'getFedExAccountNumber',
                'getProductsData',
                'getOrderNumber',
                'getWebhookUrl',
                'getRecipients',
                'getPromoCodeArray',
                'getSite',
                'getSiteName',
                'getIsGraphQlRequest',
                'getQuoteLocationId',
                'getValidateContent',
                'getOrderNotes',
                'getLteIdentifier'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->onlyMethods([
                'getData',
                'getId',
                'getShippingAddress'
            ])
            ->addMethods([
                'getCustomerFirstname',
                'getCustomerLastname',
                'getCustomerEmail',
                'getCustomerTelephone',
                'getExtNo'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->graphqlHelper = $this->getMockBuilder(GraphqlHelper::class)
            ->onlyMethods(['getJwtParamByKey'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->onlyMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->crDataModelMock = $this->getMockBuilder(CRdataModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->address = $this->createMock(Address::class);
        $this->instoreConfig = $this->createMock(InstoreConfig::class);
        $this->cartIntegrationRepository = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->requestQueryValidator = $this->createMock(RequestQueryValidator::class);
        $this->cartIntegration = $this->createMock(CartIntegrationInterface::class);
        $this->objectManager = new ObjectManager($this);
        $this->fxoRateQuoteArray = $this->objectManager->getObject(
            FXORateQuoteDataArray::class,
            [
                'data' => $this->graphqlHelper,
                'toggleConfig' => $this->toggleConfigMock,
                'instoreConfig' => $this->instoreConfig,
                'cartIntegrationRepository' => $this->cartIntegrationRepository,
                'requestQueryValidator' => $this->requestQueryValidator,
                'crDataModelMock' => $this->crDataModelMock
            ]
        );
    }

    /**
     * Test case for getRateQuoteRequest
     */
    public function testGetRateQuoteRequest()
    {
        $orderNotes = "{\"text\" => \"Test Note\", \"audit\" => [\"user\" => \"Test User\",
            \"creationTime\" => \"2023-12-01T00:00:00Z\", \"userReference\" =>
            [\"reference\" => \"Testing\", \"source\" => \"MAGENTO\"]]}";

        $this->dataObjectFactory->expects($this->any())->method('getQuoteObject')->willReturn($this->quote);
        $this->dataObjectFactory->expects($this->any())->method('getFedExAccountNumber')->willReturn($this);
        $this->dataObjectFactory->expects($this->any())->method('getProductsData')->willReturn($this);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn($this);
        $this->dataObjectFactory->expects($this->any())->method('getWebhookUrl')->willReturn($this);
        $this->dataObjectFactory->expects($this->any())->method('getRecipients')->willReturn($this);
        $this->dataObjectFactory->expects($this->any())->method('getPromoCodeArray')->willReturn(['code' => 'UAT001']);
        $this->dataObjectFactory->expects($this->any())->method('getSite')->willReturn('Epro');
        $this->dataObjectFactory->expects($this->any())->method('getSiteName')->willReturn('Commercial');
        $this->dataObjectFactory->expects($this->any())->method('getIsGraphQlRequest')->willReturn(true);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteLocationId')->willReturn('12345');
        $this->dataObjectFactory->expects($this->any())->method('getValidateContent')->willReturn('false');
        $this->dataObjectFactory->expects($this->any())->method('getLteIdentifier')->willReturn($this);

        $this->quote->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['fjmp_quote_id'],
                ['pickupPageLocation'],
                ['order_notes']
            ])
            ->willReturnOnConsecutiveCalls("0001", "true", $orderNotes);

        $this->assertNotNull($this->fxoRateQuoteArray->getRateQuoteRequest($this->dataObjectFactory));
    }

    /**
     * Test case for getRateQuoteRequestWithNullQuoteLocationId
     */
    public function testGetRateQuoteRequestWithNullQuoteLocationId()
    {
        $this->dataObjectFactory->expects($this->any())->method('getQuoteObject')->willReturn($this->quote);
        $this->dataObjectFactory->expects($this->any())->method('getFedExAccountNumber')->willReturn($this);
        $this->dataObjectFactory->expects($this->any())->method('getProductsData')->willReturn($this);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn($this);
        $this->dataObjectFactory->expects($this->any())->method('getRecipients')->willReturn($this);
        $this->dataObjectFactory->expects($this->any())->method('getPromoCodeArray')->willReturn(['code' => 'UAT001']);
        $this->dataObjectFactory->expects($this->any())->method('getSite')->willReturn('Epro');
        $this->dataObjectFactory->expects($this->any())->method('getSiteName')->willReturn('Commercial');
        $this->dataObjectFactory->expects($this->any())->method('getIsGraphQlRequest')->willReturn(false);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteLocationId')->willReturn(null);
        $this->dataObjectFactory->expects($this->any())->method('getValidateContent')->willReturn('false');
        $this->dataObjectFactory->expects($this->any())->method('getLteIdentifier')->willReturn($this);

        $this->assertNotNull($this->fxoRateQuoteArray->getRateQuoteRequest($this->dataObjectFactory));
    }

    /**
     * Test case for getOrderContact
     */
    public function testGetOrderContact(): void
    {
        $cartId = 1;
        $retailCustomerId = "1";
        $this->quote->expects($this->any())->method('getCustomerFirstname')->willReturn('Ayush');
        $this->quote->expects($this->any())->method('getCustomerLastname')->willReturn('Sood');
        $this->quote->expects($this->any())->method('getCustomerEmail')->willReturn('Ayush.sood@infogain.com');
        $this->quote->expects($this->any())->method('getCustomerTelephone')->willReturn('9876543212');
        $this->quote->expects($this->any())->method('getExtNo')->willReturn('111');
        $this->requestQueryValidator->expects($this->any())->method('isGraphQl')->willReturn(true);
        $this->quote->expects($this->any())->method('getId')->willReturn($cartId);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->with($cartId)
            ->willReturn($this->cartIntegration);

        $this->quote->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->address);

        $this->address->expects($this->any())
            ->method('getCompany')
            ->willReturn('test123');

        $this->cartIntegration->expects($this->any())
            ->method('getRetailCustomerId')
            ->willReturn($retailCustomerId);

        $this->assertNotNull($this->fxoRateQuoteArray->getOrderContact($this->quote));
    }

    /**
     * Test case for getOrderContact
     */
    public function testGetOrderContactuWithNoSuchEntityException(): void
    {
        $cartId = 1;
        $retailCustomerId = "1";
        $this->quote->expects($this->any())->method('getCustomerFirstname')->willReturn('Ayush');
        $this->quote->expects($this->any())->method('getCustomerLastname')->willReturn('Sood');
        $this->quote->expects($this->any())->method('getCustomerEmail')->willReturn('Ayush.sood@infogain.com');
        $this->quote->expects($this->any())->method('getCustomerTelephone')->willReturn('9876543212');
        $this->quote->expects($this->any())->method('getExtNo')->willReturn('111');
        $this->requestQueryValidator->expects($this->any())->method('isGraphQl')->willReturn(true);
        $this->quote->expects($this->any())->method('getId')->willReturn($cartId);
        $exception = new NoSuchEntityException(__("Some message"));
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->with($cartId)
            ->willThrowException($exception);

        $this->quote->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->address);

        $this->address->expects($this->any())
            ->method('getCompany')
            ->willReturn('test123');

        $this->cartIntegration->expects($this->any())
            ->method('getRetailCustomerId')
            ->willReturn($retailCustomerId);

        $this->assertNotNull($this->fxoRateQuoteArray->getOrderContact($this->quote));
    }

    /**
     * Test case for getRecipientDetail
     */
    public function testgetRecipientDetail()
    {
        $this->quote->expects($this->any())->method('getCustomerFirstname')->willReturn("Ayush");
        $this->quote->expects($this->any())->method('getCustomerLastname')->willReturn('Sood');
        $this->quote->expects($this->any())->method('getCustomerEmail')->willReturn('Ayush.sood@infogain.com');
        $this->quote->expects($this->any())->method('getCustomerTelephone')->willReturn('9876543212');
        $this->quote->expects($this->any())->method('getExtNo')->willReturn('111');


        $this->quote->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->address);

        $this->address->expects($this->any())
            ->method('getCompany')
            ->willReturn('test123');

        $this->assertNotNull($this->fxoRateQuoteArray->getRecipientDetail($this->quote, ['Ayush' => 'test']));
    }

    /**
     * Test case for getRecipientDetailWithEmptyRecepient
     */
    public function testgetRecipientDetailWithEmptyRecepient()
    {
        $this->quote->expects($this->any())->method('getCustomerFirstname')->willReturn("Ayush");
        $this->quote->expects($this->any())->method('getCustomerLastname')->willReturn('Sood');
        $this->quote->expects($this->any())->method('getCustomerEmail')->willReturn('Ayush.sood@infogain.com');
        $this->quote->expects($this->any())->method('getCustomerTelephone')->willReturn('9876543212');
        $this->quote->expects($this->any())->method('getExtNo')->willReturn('111');
        $this->assertNull($this->fxoRateQuoteArray->getRecipientDetail($this->quote, []));
    }
}
