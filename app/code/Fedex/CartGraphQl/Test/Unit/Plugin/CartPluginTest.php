<?php
/**
 * @category    Fedex
 * @package     Fedex_CartGraphQl
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Olimjon Akhmedov <oakhmedov@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Unit\Plugin;

use Fedex\CartGraphQl\Model\Validation\Validate\ValidateModel;
use Fedex\GraphQl\Model\GraphQlRequestCommand as RequestCommand;
use Fedex\GraphQl\Model\GraphQlRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Context;
use Fedex\CartGraphQl\Plugin\CartPlugin;
use Fedex\FXOPricing\Helper\FXORate;
use Magento\QuoteGraphQl\Model\Resolver\Cart;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

class CartPluginTest extends TestCase
{
    /**
     * @var (\Fedex\FXOPricing\Model\FXORateQuote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fxoRatQuoteMock;
    protected $toggleConfig;
    /**
     * @var Cart|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartMock;
    /**
     * @var Field|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldMock;
    /**
     * @var Context|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;
    /**
     * @var ResolveInfo|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resolverInfoMock;
    /**
     * @var CartPlugin
     */
    private CartPlugin $cartPlugin;
    /**
     * @var FXORate|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fxoRateMock;
    /**
     * @var RequestCommandFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestCommandFactoryMock;
    /**
     * @var ValidationComposite|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validationCompositeMock;
    /**
     * @var ValidateModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validateModelMock;

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
        $this->cartMock = $this->getMockBuilder(Cart::class)
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

        $this->fxoRateMock = $this->getMockBuilder(FXORate::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fxoRatQuoteMock = $this->getMockBuilder(FXORateQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fxoRateMock->method('getFXORate')->willReturn([]);
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
        $this->validateModelMock = $this->getMockBuilder(ValidateModel::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerHelper = $this->getMockBuilder(LoggerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->newRelicHeaders = $this->getMockBuilder(NewRelicHeaders::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartPlugin = new CartPlugin(
            $this->fxoRateMock,
            $this->fxoRatQuoteMock,
            $this->requestCommandFactoryMock,
            $this->validationCompositeMock,
            $this->validateModelMock,
            $this->toggleConfig,
            $this->loggerHelper,
            $this->newRelicHeaders
        );
    }

    public function testCartPluginData()
    {
        $mutationName = 'cart';
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

        $this->validationCompositeMock->expects($this->once())->method('validate');
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $cartPluginResult = $this->cartPlugin->afterResolve(
            $this->cartMock,
            ['model' => $this->cartMock],
            $this->fieldMock,
            $this->contextMock,
            $this->resolverInfoMock,
            null,
            null
        );

        $this->assertEquals(['model' => $this->cartMock], $cartPluginResult);
    }

    public function testCartPluginDataWithToggleoff()
    {
        $this->validationCompositeMock->expects($this->once())->method('validate');
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $cartPluginResult = $this->cartPlugin->afterResolve(
            $this->cartMock,
            ['model' => $this->cartMock],
            $this->fieldMock,
            $this->contextMock,
            $this->resolverInfoMock,
            null,
            null
        );

        $this->assertEquals(['model' => $this->cartMock], $cartPluginResult);
    }
}
