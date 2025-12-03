<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Observer;

use Fedex\MarketplaceCheckout\Observer\AddBodyClassObserver;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Page\Config;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class AddBodyClassObserverTest
 */
class AddBodyClassObserverTest extends TestCase
{
    /**
     * @var Congig
     */
    protected $pageConfigMock;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var AddBodyClassObserver
     */
    protected $addBodyClassObserver;

    /**
     * @var Observer
     */
    protected $observer;

    protected function setUp(): void
    {
        $this->pageConfigMock = $this->createMock(Config::class);

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->addBodyClassObserver = $this->objectManager->getObject(
            AddBodyClassObserver::class,
            [
                'pageConfig' => $this->pageConfigMock,
            ]
        );
    }

    /**
     * Tests that the execute method adds the 'sequence-numbers' body class
     * to the page configuration and returns the observer instance.
     *
     * @return void
     */
    public function testExecuteAddsBodyClass(): void
    {
        $this->pageConfigMock
            ->expects($this->any())
            ->method('addBodyClass')
            ->with('sequence-numbers');

        $result = $this->addBodyClassObserver->execute($this->observer);
        $this->assertEquals($this->addBodyClassObserver, $result);
    }
}