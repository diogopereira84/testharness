<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * @author Adithya Adithya <5174169@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Controller\User;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Fedex\Company\Controller\User\Groups;
use Magento\Framework\App\Action\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Model\Config as OndemandConfig;

class GroupsTest extends TestCase
{
    /**
     * @var (\Fedex\Ondemand\Model\Config & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $configMock;
    /**
     * @var Context
     */
    private $context;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var ToggleConfig
     */
    private $toggleMock;

    /**
     * @var Groups
     */
    private $groupsController;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->toggleMock = $this->createMock(ToggleConfig::class);
        $this->configMock = $this->createMock(OndemandConfig::class);
        $this->groupsController = new Groups($this->context, $this->resultPageFactory, $this->toggleMock, $this->configMock);
        $resultPageMock = $this->createMock(\Magento\Framework\View\Result\Page::class);
        $this->resultPageFactory->method('create')->willReturn($resultPageMock);
    }

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $resultPage = $this->groupsController->execute();
        $this->assertInstanceOf(\Magento\Framework\View\Result\Page::class, $resultPage);
    }
}