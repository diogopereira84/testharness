<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\Product;

use PHPUnit\Framework\TestCase;
use Fedex\Cart\Model\Quote\Product\ContentAssociationsResolver;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ContentAssociationsResolverTest extends TestCase
{
    /**
     * @var \Fedex\EnvironmentManager\ViewModel\ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var ContentAssociationsResolver
     */
    protected $contentAssociationsResolver;

    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->contentAssociationsResolver = new ContentAssociationsResolver($this->toggleConfigMock);
    }

     /**
      * Tests that the getContentReference method returns the correct content reference
      * when the feature toggle is enabled.
      * @return void
      */
    public function testGetContentReferenceWithToggleEnabled(): void
    {
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('instore_nfw')
            ->willReturn(true);

        $productContentAssociations = [['contentReference' => 'reference']];

        $result = $this->contentAssociationsResolver->getContentReference($productContentAssociations);
        $this->assertEquals('reference', $result);
    }

    /**
     * Tests that the getContentReference method returns the correct content reference
     * when the feature toggle is disabled.
     * @return void
     */
    public function testGetContentReferenceWithToggleDisabled(): void
    {
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('instore_nfw')
            ->willReturn(false);

        $productContentAssociations = [['contentReference' => 'reference']];

        $result = $this->contentAssociationsResolver->getContentReference($productContentAssociations);
        $this->assertEquals('reference', $result);
    }

    /**
     * Tests that the getContentReference method returns null when the input is null.
     * @return void
     */
    public function testGetContentReferenceWithNullInput(): void
    {
        $result = $this->contentAssociationsResolver->getContentReference(null);
        $this->assertNull($result, 'Method should return null when input is null');
    }

    /**
     * Tests that the getContentReference method returns null when the content reference is missing.
     * @return void
     */
    public function testGetContentReferenceWithMissingContentReference(): void
    {
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('instore_nfw')
            ->willReturn(true);

        $productContentAssociations = [];

        $result = $this->contentAssociationsResolver->getContentReference($productContentAssociations);
        $this->assertNull($result);
    }
}
