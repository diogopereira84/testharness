<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Import\Test\Unit\Controller\Adminhtml\Import;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\ImportExport\Model\Import\SampleFileProvider;
use Magento\Framework\Message\ManagerInterface;
use Fedex\Import\Plugin\EntityTypeArrayPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Import\Controller\Adminhtml\Import\Download;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\ImportExport\Model\Import\ConfigInterface as ImportConfig;

class EntityTypeArrayPluginTest extends TestCase
{
    protected $requestMock;
    /**
     * @var (\Magento\ImportExport\Model\Import\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ImportConfigMock;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $MockEntityType;
    private \Closure $proceed;

    /**
     * Set up method
     */
    protected function setUp(): void
    {

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ImportConfigMock = $this->getMockBuilder(ImportConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->MockEntityType = $this->objectManagerHelper->getObject(
            EntityTypeArrayPlugin::class,
            [
                'request' => $this->requestMock,
                'importConfig' =>$this->ImportConfigMock
            ]
        );
    }

    /**
     * Test method for aroundToOptionArray
     *
     * @return void
     */
    public function testaroundToOptionArray()
    {
        $className = \Magento\ImportExport\Controller\Adminhtml\Import\Download::class;
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject = $this->createMock($className);

        $this->proceed = function () use ($subject) {
            return $subject;
        };
        $this->requestMock->method('getRouteName')->willReturn('import');
        $this->MockEntityType->aroundToOptionArray($subject, $this->proceed);
    }

    /**
     * Test method for aroundToOptionArray with different route name
     *
     * @return void
     */
    public function testaroundToOptionArrayWithdifferentRouteName()
    {
        $className = \Magento\ImportExport\Controller\Adminhtml\Import\Download::class;
        /** @var \Magento\SalesRule\Model\Rule|MockObject $subject */
        $subject = $this->createMock($className);
        $this->proceed = function () use ($subject) {
            return $subject;
        };
        $this->requestMock->method('getRouteName')->willReturn('test');
        $this->MockEntityType->aroundToOptionArray($subject, $this->proceed);
    }
}
