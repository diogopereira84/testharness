<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Block\Product\View\AboutUs;

use Fedex\Catalog\Block\Product\View\AboutUs\Faq;
use Fedex\Catalog\Model\Config;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FaqTest extends TestCase
{
    /**
     * @var Faq
     */
    private $faq;

    /**
     * @var CatalogHelper|MockObject
     */
    private $catalogHelperMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var Template\Context|MockObject
     */
    private $context;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(Template\Context::class);
        $this->catalogHelperMock = $this->createMock(CatalogHelper::class);
        $this->configMock = $this->createMock(Config::class);

        $this->faq = new Faq(
            $this->context,
            $this->catalogHelperMock,
            $this->configMock
        );
    }

    /**
     * Test getProductFaqs method
     */
    public function testGetProductFaqs(): void
    {
        $productFaqs = 'sample_faqs';

        $this->catalogHelperMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->createMock(Product::class));

        $this->configMock->expects($this->once())
            ->method('formatAttribute')
            ->willReturn($productFaqs);

        $result = $this->faq->getProductFaqs();
        $this->assertEquals($productFaqs, $result);
    }

    /**
     * Test getProductFaqs method when product is not present
     */
    public function testGetProductFaqsWhenProductNotPresent(): void
    {
        $this->catalogHelperMock->expects($this->once())
            ->method('getProduct')
            ->willReturn(null);

        $this->configMock->expects($this->never())
            ->method('formatAttribute');

        $result = $this->faq->getProductFaqs();

        $this->assertEquals('', $result);
    }
}
