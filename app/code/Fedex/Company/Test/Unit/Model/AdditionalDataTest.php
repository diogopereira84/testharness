<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Company\Test\Unit\Model;

use Fedex\Company\Model\AdditionalData;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class AdditionalDataTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\Magento\Framework\Api\ExtensionAttributesInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $additionalExtension;
    /**
     * @var (\Magento\Framework\Model\AbstractExtensibleModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $additionalExtensible;
    protected $additionalExtensionInterface;
    /**
     * @var AdditionalData
     */
    protected $additionalDataMock;

    /**
     * {@inheritdoc}
     */

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalExtension = $this->getMockBuilder(\Magento\Framework\Api\ExtensionAttributesInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->additionalExtensible = $this->getMockBuilder(\Magento\Framework\Model\AbstractExtensibleModel::class)
            ->setMethods(['_getExtensionAttributes','_setExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->additionalExtensionInterface = $this
            ->getMockBuilder(\Fedex\Company\Api\Data\AdditionalDataExtensionInterface::class)
            ->setMethods(['_setExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->additionalDataMock = $this->objectManager->getObject(
            AdditionalData::class,
            [ ]
        );
    }

    /**
     * @test getId
     */
    public function testGetId()
    {
        $this->assertNull($this->additionalDataMock->getId());
    }

    /**
     * @test setId
     */
    public function testSetId()
    {
        $this->assertIsObject($this->additionalDataMock->setId(2));
    }

    /**
     * @test getCompanyId
     */
    public function testGetCompanyId()
    {
        $this->assertNull($this->additionalDataMock->getCompanyId());
    }

    /**
     * @test setCompanyId
     */
    public function testSetCompanyId()
    {
        $this->assertIsObject($this->additionalDataMock->setCompanyId(2));
    }

    /**
     * @test getStoreViewId
     */
    public function testGetStoreViewId()
    {
        $this->assertNull($this->additionalDataMock->getStoreViewId());
    }

    /**
     * @test setStoreViewId
     */
    public function testSetStoreViewId()
    {
        $this->assertIsObject($this->additionalDataMock->setStoreViewId(2));
    }

    /**
     * @test getStoreId
     */
    public function testGetStoreId()
    {
        $this->assertNull($this->additionalDataMock->getStoreId());
    }

    /**
     * @test setStoreId
     */
    public function testSetStoreId()
    {
        $this->assertIsObject($this->additionalDataMock->setStoreId(2));
    }

    /**
     * @test getNewStoreViewId
     */
    public function testNewStoreViewId()
    {
        $this->assertNull($this->additionalDataMock->getNewStoreViewId());
    }

    /**
     * @test setNewStoreViewId
     */
    public function testSetNewStoreViewId()
    {
        $this->assertIsObject($this->additionalDataMock->setNewStoreViewId(2));
    }

    /**
     * @test getNewStoreId
     */
    public function testGetNewStoreId()
    {
        $this->assertNull($this->additionalDataMock->getNewStoreId());
    }

    /**
     * @test setNewStoreId
     */
    public function testSetNewStoreId()
    {
        $this->assertIsObject($this->additionalDataMock->setNewStoreId(2));
    }

    /**
     * @test getCcToken
     */
    public function testGetCcToken()
    {
        $this->assertNull($this->additionalDataMock->getCcToken());
    }

    /**
     * @test setCcToken
     */
    public function testSetCcToken()
    {
        $this->assertIsObject($this->additionalDataMock->setCcToken(2));
    }

    /**
     * @test getCcData
     */
    public function testGetCcData()
    {
        $this->assertNull($this->additionalDataMock->getCcData());
    }

    /**
     * @test setCcData
     */
    public function testSetCcData()
    {
        $this->assertIsObject($this->additionalDataMock->setCcData(2));
    }

    /**
     * @test getCompanyPaymentOptions
     */
    public function testGetCompanyPaymentOptions()
    {
        $this->assertNull($this->additionalDataMock->getCompanyPaymentOptions());
    }

    /**
     * @test setCompanyPaymentOptions
     */
    public function testSetCompanyPaymentOptions()
    {
        $this->assertIsObject($this->additionalDataMock->setCompanyPaymentOptions(2));
    }

    /**
     * @test getCreditcardOptions
     */
    public function testGetCreditcardOptions()
    {
        $this->assertNull($this->additionalDataMock->getCreditcardOptions());
    }

    /**
     * @test setCreditcardOptions
     */
    public function testSetCreditcardOptions()
    {
        $this->assertIsObject($this->additionalDataMock->setCreditcardOptions(2));
    }

    /**
     * @test getFedexAccountOptions
     */
    public function testFedexAccountOptions()
    {
        $this->assertNull($this->additionalDataMock->getFedexAccountOptions());
    }

    /**
     * @test setFedexAccountOptions
     */
    public function testSetFedexAccountOptions()
    {
        $this->assertIsObject($this->additionalDataMock->setFedexAccountOptions(2));
    }

    /**
     * @test getCcTokenExpiryDateTime
     */
    public function testGetCcTokenExpiryDateTime()
    {
        $this->assertNull($this->additionalDataMock->getCcTokenExpiryDateTime());
    }

    /**
     * @test setCcTokenExpiryDateTime
     */
    public function testSetCcTokenExpiryDateTime()
    {
        $this->assertIsObject($this->additionalDataMock->setCcTokenExpiryDateTime(2));
    }

    /**
     * @test getDefaultPaymentMethod
     */
    public function testGetDefaultPaymentMethod()
    {
        $this->assertNull($this->additionalDataMock->getDefaultPaymentMethod());
    }

    /**
     * @test setDefaultPaymentMethod
     */
    public function testSetDefaultPaymentMethod()
    {
        $this->assertIsObject($this->additionalDataMock->setDefaultPaymentMethod(2));
    }

    /**
     * @test getIsPromoDiscountEnabled
     */
    public function testGetIsPromoDiscountEnabled()
    {
        $this->assertNull($this->additionalDataMock->getIsPromoDiscountEnabled());
    }

    /**
     * @test setIsPromoDiscountEnabled
     */
    public function testSetIsPromoDiscountEnabled()
    {
        $this->assertIsObject($this->additionalDataMock->setIsPromoDiscountEnabled(2));
    }

    /**
     * @test getIsAccountDiscountEnabled
     */
    public function testGetIsAccountDiscountEnabled()
    {
        $this->assertNull($this->additionalDataMock->getIsAccountDiscountEnabled());
    }

    /**
     * @test setIsAccountDiscountEnabled
     */
    public function testSetIsAccountDiscountEnabled()
    {
        $this->assertIsObject($this->additionalDataMock->setIsAccountDiscountEnabled(2));
    }

    /**
     * @test getIsReorderEnabled
     */
    public function testGetIsReorderEnabled()
    {
        $this->assertNull($this->additionalDataMock->getIsReorderEnabled());
    }

    /**
     * @test setIsReorderEnabled
     */
    public function testSetIsReorderEnabled()
    {
        $this->assertIsObject($this->additionalDataMock->setIsReorderEnabled(2));
    }

    /**
     * @test getTermsAndConditions
     */
    public function testGetTermsAndConditions()
    {
        $this->assertNull($this->additionalDataMock->getTermsAndConditions());
    }

    /**
     * @test setTermsAndConditions
     */
    public function testSetTermsAndConditions()
    {
        $this->assertIsObject($this->additionalDataMock->setTermsAndConditions(2));
    }

    /**
     * @test getOrderNotes
     */
    public function testGetOrderNotes()
    {
        $this->assertNull($this->additionalDataMock->getOrderNotes());
    }

    /**
     * @test setOrderNotes
     */
    public function testSetOrderNotes()
    {
        $this->assertIsObject($this->additionalDataMock->setOrderNotes(2));
    }

    /**
     * @test getIsBannerEnable
     */
    public function testGetIsBannerEnable()
    {
        $this->assertNull($this->additionalDataMock->getIsBannerEnable());
    }

    /**
     * @test setIsBannerEnable
     */
    public function testSetIsBannerEnable()
    {
        $this->assertIsObject($this->additionalDataMock->setIsBannerEnable(2));
    }

    /**
     * @test getBannerTitle
     */
    public function testGetBannerTitle()
    {
        $this->assertNull($this->additionalDataMock->getBannerTitle());
    }

    /**
     * @test setBannerTitle
     */
    public function testSetBannerTitle()
    {
        $this->assertIsObject($this->additionalDataMock->setBannerTitle(2));
    }

    /**
     * @test getIconography
     */
    public function testGetIconography()
    {
        $this->assertNull($this->additionalDataMock->getIconography());
    }

    /**
     * @test setIconography
     */
    public function testSetIconography()
    {
        $this->assertIsObject($this->additionalDataMock->setIconography(2));
    }

    /**
     * @test getCtaText
     */
    public function testGetCtaText()
    {
        $this->assertNull($this->additionalDataMock->getCtaText());
    }

    /**
     * @test setCtaText
     */
    public function testSetCtaText()
    {
        $this->assertIsObject($this->additionalDataMock->setCtaText(2));
    }

    /**
     * @test getCtaLink
     */
    public function testGetCtaLink()
    {
        $this->assertNull($this->additionalDataMock->getCtaLink());
    }

    /**
     * @test setCtaLink
     */
    public function testSetCtaLink()
    {
        $this->assertIsObject($this->additionalDataMock->setCtaLink(2));
    }

    /**
     * @test getLinkOpenInNewTab
     */
    public function testGetLinkOpenInNewTab()
    {
        $this->assertNull($this->additionalDataMock->getLinkOpenInNewTab());
    }

    /**
     * @test setLinkOpenInNewTab
     */
    public function testSetLinkOpenInNewTab()
    {
        $this->assertIsObject($this->additionalDataMock->setLinkOpenInNewTab(2));
    }

    /**
     * @test getDescription
     */
    public function testGetDescription()
    {
        $this->assertNull($this->additionalDataMock->getDescription());
    }

    /**
     * @test setDescription
     */
    public function testSetDescription()
    {
        $this->assertIsObject($this->additionalDataMock->setDescription(2));
    }

    /**
     * @test getIsNonEditableCcPaymentMethod
     */
    public function testGetIsNonEditableCcPaymentMethod()
    {
        $this->assertNull($this->additionalDataMock->getIsNonEditableCcPaymentMethod());
    }

    /**
     * @test setIsNonEditableCcPaymentMethod
     */
    public function testSetIsNonEditableCcPaymentMethod()
    {
        $this->assertIsObject($this->additionalDataMock->setIsNonEditableCcPaymentMethod(2));
    }

    /**
     * @test getIsNonEditableCcPaymentMethod
     */
    public function testGetAllPrintProductsCmsBlockIdentifier()
    {
        $this->assertNull($this->additionalDataMock->getAllPrintProductsCmsBlockIdentifier());
    }

    /**
     * @test setIsNonEditableCcPaymentMethod
     */
    public function testSetAllPrintProductsCmsBlockIdentifier()
    {
        $this->assertIsObject($this->additionalDataMock->setAllPrintProductsCmsBlockIdentifier('all_print_products_identifier'));
    }

    public function testSetExtensionAttributes()
    {
        $this->additionalExtensionInterface->expects($this->any())->method('_setExtensionAttributes')->willReturnSelf();
        $this->assertIsObject($this->additionalDataMock->setExtensionAttributes($this->additionalExtensionInterface));
    }

    /**
     * @test getIsApprovalWorkflowEnabled
     */
    public function testGetIsApprovalWorkflowEnabled()
    {
        $this->assertNull($this->additionalDataMock->getIsApprovalWorkflowEnabled());
    }

    /**
     * @test setIsApprovalWorkflowEnabled
     */
    public function testSetIsApprovalWorkflowEnabled()
    {
        $this->assertIsObject($this->additionalDataMock->setSetIsApprovalWorkflowEnabled(1));
    }
}
