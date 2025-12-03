<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Controller\Index;

use Fedex\MarketplacePunchout\Model\Marketplace;
use \Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplacePunchout\Model\Redirect;
use Exception;
use Fedex\MarketplacePunchout\Model\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Fedex\MarketplacePunchout\Controller\Index\Index;
use Magento\Framework\Message\ManagerInterface;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;

class IndexTest extends TestCase
{
    /** @var Context  */
    private Context $context;

    /** @var Redirect  */
    private Redirect $redirect;

    /** @var ManagerInterface  */
    private ManagerInterface $messageManager;

    /** @var MarketplaceConfig  */
    private MarketplaceConfig $marketplaceConfig;

    /** @var Marketplace  */
    private Marketplace $marketplace;

    /** @var Logger  */
    private Logger $logger;

    /** @var RequestInterface  */
    private RequestInterface $request;

    /** @var Index  */
    private Index $index;

    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->redirect = $this->createMock(Redirect::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->marketplaceConfig = $this->createMock(MarketplaceConfig::class);
        $this->marketplace = $this->createMock(Marketplace::class);
        $this->logger = $this->createMock(Logger::class);
        $this->request = $this->createMock(RequestInterface::class);

        $this->context->method('getMessageManagerInterface')
            ->willReturn($this->messageManager);
        $this->context->method('getLogger')
            ->willReturn($this->logger);
        $this->context->method('getRequest')
            ->willReturn($this->request);
        $this->context->method('getMarketplace')
            ->willReturn($this->marketplace);

        $this->index = new Index(
            $this->context,
            $this->redirect,
            $this->request
        );
    }

    public function testExecute()
    {
        $this->messageManager->expects($this->never())
            ->method('addErrorMessage');
        $this->context->expects($this->never())
            ->method('getMessageManagerInterface');
        $this->redirect->expects($this->never())
            ->method('redirect');
        $this->marketplace->expects($this->once())
            ->method('punchout');
        $this->context->expects($this->never())
            ->method('getLogger');
        $this->logger->expects($this->never())
            ->method('error');

        $this->index->execute();
    }
}
