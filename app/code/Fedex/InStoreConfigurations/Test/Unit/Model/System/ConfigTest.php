<?php
/**
 * @category    Fedex
 * @package     Fedex_InStoreConfigurations
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\InStoreConfigurations\Test\Unit\Model\System;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\InStoreConfigurations\Model\System\Config;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Serialize\Serializer\Json;

class ConfigTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonMock;

    /**
     * @var Config
     */
    private Config $instance;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private ScopeConfigInterface|MockObject $scopeConfigMock;

    /**
     * @var RequestQueryValidator|MockObject
     */
    private RequestQueryValidator|MockObject $requestQueryValidatorMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->requestQueryValidatorMock = $this->createMock(RequestQueryValidator::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->instance = new Config(
            $this->scopeConfigMock,
            $this->requestQueryValidatorMock,
            $this->jsonMock
        );
    }

    public function testGetLivesearchCustomGroupId(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_LIVESEARCH_CUSTOM_SHARED_CATALOG_ID,
                ScopeInterface::SCOPE_STORE
            )->willReturn("5");

        $result = $this->instance->getLivesearchCustomSharedCatalogId();
        static::assertIsString($result);
        static::assertSame($result, "5");
    }


    public function testGetLivesearchCustomGroupIdReturnsNull(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_LIVESEARCH_CUSTOM_SHARED_CATALOG_ID,
                ScopeInterface::SCOPE_STORE
            )->willReturn(null);

        $result = $this->instance->getLivesearchCustomSharedCatalogId();
        static::assertNull($result);
    }

    public function testIsFixPlaceOrderRetry(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_FIX_PLACE_ORDER_RETRY,
                ScopeInterface::SCOPE_STORE
            )->willReturn(true);

        $result = $this->instance->isFixPlaceOrderRetry();
        static::assertIsBool($result);
        static::assertSame($result, true);
    }

    public function testIsCheckoutRetryImprovementEnabled(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_CHECKOUT_RETRY_IMPROVEMENT_ENABLED,
                ScopeInterface::SCOPE_STORE
            )->willReturn(true);

        $result = $this->instance->isCheckoutRetryImprovementEnabled();
        static::assertIsBool($result);
        static::assertSame($result, true);
    }

    public function testIsRateQuoteProductAssociationEnabled(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_RATE_QUOTE,
                ScopeInterface::SCOPE_STORE
            )->willReturn(true);

        $result = $this->instance->isRateQuoteProductAssociationEnabled();
        static::assertIsBool($result);
        static::assertSame($result, true);
    }

    public function testIsEnabledAddNotes(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_ADD_NOTES,
                ScopeInterface::SCOPE_STORE
            )->willReturn(true);

        $result = $this->instance->isEnabledAddNotes();
        static::assertIsBool($result);
        static::assertSame($result, true);
    }

    public function testIsSupportLteIdentifierEnabled(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_LTE_IDENTIFIER,
                ScopeInterface::SCOPE_STORE
            )->willReturn(true);

        $result = $this->instance->isSupportLteIdentifierEnabled();
        static::assertIsBool($result);
        static::assertSame($result, true);
    }

    public function testIsEnableEstimatedSubtotalFix(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_ESTIMATED_SUBTOTAL_FIX,
                ScopeInterface::SCOPE_STORE
            )->willReturn(true);

        $result = $this->instance->isEnableEstimatedSubtotalFix();
        static::assertIsBool($result);
        static::assertSame($result, true);
    }

    public function testIsEnabledUserCannotPerformCartOperationsFix(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_USER_CANNOT_PERFORM_CART_OPERATIONS_FIX,
                ScopeInterface::SCOPE_STORE
            )->willReturn(true);

        $result = $this->instance->isEnabledUserCannotPerformCartOperationsFix();
        static::assertIsBool($result);
        static::assertSame($result, true);
    }

    public function testIsUnableToPlaceOrderDueToRemovedPreferenceFixEnabled(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_UNABLE_TO_PLACE_ORDERS_DUE_TO_REMOVED_PREFERENCE,
                ScopeInterface::SCOPE_STORE
            )->willReturn(true);

        $result = $this->instance->isUnableToPlaceOrderDueToRemovedPreferenceFix();
        static::assertIsBool($result);
        static::assertTrue($result);
    }

    public function testIsUnableToPlaceOrderDueToRemovedPreferenceFixDisabled(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_UNABLE_TO_PLACE_ORDERS_DUE_TO_REMOVED_PREFERENCE,
                ScopeInterface::SCOPE_STORE
            )->willReturn(false);

        $result = $this->instance->isUnableToPlaceOrderDueToRemovedPreferenceFix();
        static::assertIsBool($result);
        static::assertFalse($result);
    }

    public function testIsEnablePoliticalDisclosureInOrderSearchEnabled(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_ADD_POLITICAL_DISCLOSURE_ORDER_SEARCH,
                ScopeInterface::SCOPE_STORE
            )->willReturn(true);

        $result = $this->instance->isEnablePoliticalDisclosureInOrderSearch();
        static::assertIsBool($result);
        static::assertTrue($result);
    }

    public function testIsEnablePoliticalDisclosureInOrderSearchDisabled(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with(
                ConfigInterface::XML_PATH_ADD_POLITICAL_DISCLOSURE_ORDER_SEARCH,
                ScopeInterface::SCOPE_STORE
            )->willReturn(false);

        $result = $this->instance->isEnablePoliticalDisclosureInOrderSearch();
        static::assertIsBool($result);
        static::assertFalse($result);
    }


}
