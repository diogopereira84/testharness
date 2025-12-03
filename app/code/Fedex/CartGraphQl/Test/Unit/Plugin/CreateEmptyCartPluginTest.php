<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Plugin;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterfaceFactory;
use Fedex\CartGraphQl\Model\Validation\Validate\ValidateLocationId;
use Fedex\CartGraphQl\Model\Validation\Validate\ValidateStoreId;
use Fedex\CartGraphQl\Plugin\CreateEmptyCartPlugin;
use Fedex\GraphQl\Model\GraphQlRequestCommand as RequestCommand;
use Fedex\GraphQl\Model\GraphQlRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\Validate\ValidateInput;
use Fedex\GraphQl\Model\Validation\ValidationComposite;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\QuoteGraphQl\Model\Resolver\CreateEmptyCart;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

class CreateEmptyCartPluginTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $cartRepositoryMock;
    protected $getPunchoutHelperMock;
    protected $cartInterfaceMock;
    /**
     * @var CreateEmptyCart|MockObject
     */
    private $createEmptyCartMock;
    /**
     * @var Field|MockObject
     */
    private $fieldMock;
    /**
     * @var Context|MockObject
     */
    private $contextMock;
    /**
     * @var ResolveInfo|MockObject
     */
    private $resolverInfoMock;
    /**
     * @var CreateEmptyCartPlugin
     */
    private CreateEmptyCartPlugin $createEmptyCartPlugin;
    /**
     * @var CartIntegrationInterfaceFactory|MockObject
     */
    private $cartIntegrationInterfaceFactoryMock;
    /**
     * @var CartIntegrationRepositoryInterface|MockObject
     */
    private $cartIntegrationRepositoryMock;
    /**
     * @var RequestCommandFactory|MockObject
     */
    private $requestCommandFactoryMock;
    /**
     * @var ValidationComposite|MockObject
     */
    private $validationCompositeMock;
    /**
     * @var ValidateInput|MockObject
     */
    private $validateInputMock;
    /**
     * @var ValidateLocationId|MockObject
     */
    private $validateLocationIdMock;
    /**
     * @var ValidateStoreId|MockObject
     */
    private $validateStoreIdMock;

    /**
     * @var LoggerHelper|MockObject
     */
    protected $loggerHelper;

    /**
     * @var NewRelicHeaders|MockObject
     */
    protected $newRelicHeaders;

    protected function setUp(): void
    {
        $this->createEmptyCartMock = $this->getMockBuilder(CreateEmptyCart::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->fieldMock = $this->getMockBuilder(Field::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resolverInfoMock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartIntegrationInterfaceFactoryMock = $this->getMockBuilder(CartIntegrationInterfaceFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartIntegrationRepositoryMock = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->onlyMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $maskedQuoteIdToQuoteIdMock = $this->getMockBuilder(MaskedQuoteIdToQuoteIdInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestCommandFactoryMock = $this->getMockBuilder(RequestCommandFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requestCommandMock = $this->getMockBuilder(RequestCommand::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestCommandFactoryMock->method('create')->willReturn($requestCommandMock);
        $this->validationCompositeMock = $this->getMockBuilder(ValidationComposite::class)
            ->onlyMethods(['validate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validateInputMock = $this->getMockBuilder(ValidateInput::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validateLocationIdMock = $this->getMockBuilder(ValidateLocationId::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validateStoreIdMock = $this->getMockBuilder(ValidateStoreId::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getPunchoutHelperMock = $this->getMockBuilder(PunchoutHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartInterfaceMock = $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setGtn'])
            ->getMockForAbstractClass();
        $this->cartRepositoryMock->expects($this->once())->method('get')->willReturn($this->cartInterfaceMock);

        $this->loggerHelper = $this->getMockBuilder(LoggerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->newRelicHeaders = $this->getMockBuilder(NewRelicHeaders::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->createEmptyCartPlugin = new CreateEmptyCartPlugin(
            $this->requestCommandFactoryMock,
            $this->validationCompositeMock,
            $this->validateInputMock,
            $this->validateLocationIdMock,
            $this->validateStoreIdMock,
            $this->cartIntegrationInterfaceFactoryMock,
            $this->cartIntegrationRepositoryMock,
            $maskedQuoteIdToQuoteIdMock,
            $this->loggerMock,
            $this->cartRepositoryMock,
            $this->getPunchoutHelperMock,
            $this->loggerHelper,
            $this->newRelicHeaders
        );
    }

    public function testCreateEmptyCartData()
    {
        $mutationName = 'createEmptyCart';
        $headerArray = [];
        $this->fieldMock->expects($this->once())
            ->method('getName')
            ->willReturn($mutationName);

        $this->newRelicHeaders->expects($this->once())
            ->method('getHeadersForMutation')
            ->with($mutationName)
            ->willReturn($headerArray);

        $this->loggerHelper->expects($this->any())
            ->method('error')
            ->with(
                $this->stringContains('Magento graphQL start'),
                $headerArray
            );

        $args = [
            'store_id' => 'DNEK',
            'location_id' => '0798'
        ];

        $this->validationCompositeMock->expects($this->once())->method('validate');

        $this->getPunchoutHelperMock->expects($this->once())->method('getGTNNumber')
            ->willReturn('2020205626047905');

        $cartIntegrationInterfaceMock = $this->getMockBuilder(CartIntegrationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $cartIntegrationInterfaceMock->expects($this->once())->method('getStoreId')->willReturn('DNEK');
        $cartIntegrationInterfaceMock->expects($this->once())->method('getLocationId')->willReturn('0798');

        $this->cartIntegrationInterfaceFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($cartIntegrationInterfaceMock);


        $createEmptyCartPluginResult = $this->createEmptyCartPlugin->afterResolve(
            $this->createEmptyCartMock,
            'PqdqCrn8Zuw7pDWJrLsE5zVHFodsD0Iy',
            $this->fieldMock,
            $this->contextMock,
            $this->resolverInfoMock,
            null,
            ['input' => $args]
        );

        $args['cart_id'] = 'PqdqCrn8Zuw7pDWJrLsE5zVHFodsD0Iy';
        $args['gtn'] = '2020205626047905';

        $this->assertEquals($createEmptyCartPluginResult, $args);
    }

    public function testResultResolveSaveIntegrationDataException()
    {
        $this->validationCompositeMock->expects($this->once())->method('validate');

        $this->expectExceptionMessage('exception message');
        $this->expectException(GraphQlInputException::class);

        $cartIntegrationInterfaceMock = $this->getMockBuilder(CartIntegrationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartIntegrationInterfaceFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($cartIntegrationInterfaceMock);

        $this->cartIntegrationRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('exception message'));

        $this->createEmptyCartPlugin->afterResolve(
            $this->createEmptyCartMock,
            'PqdqCrn8Zuw7pDWJrLsE5zVHFodsD0Iy',
            $this->fieldMock,
            $this->contextMock,
            $this->resolverInfoMock,
            null,
            ['input' => ['mock' => true]]
        );
    }
}
