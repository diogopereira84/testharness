<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\CmsImportExport\Test\Unit\Controller\Adminhtml\Index;

use Fedex\CmsImportExport\Controller\Adminhtml\Index\Save;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use \Fedex\CmsImportExport\Model\Import\Cms;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Save
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SaveTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $save;
    /**
     * @var Redirect|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $redirectResultMock;

    /**
     * @var RedirectFactory
     */
    protected $redirectFactoryMock;

    /**
     * @var Cms|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cms;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->redirectFactoryMock = $this->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->redirectResultMock = $this->createMock(Redirect::class);

        $this->cms = $this->getMockBuilder(Cms::class)
            ->setMethods(
                [
                    'importData'
                ]
            )
         ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->save = $this->objectManager->getObject(
            Save::class,
            [
                'cms' => $this->cms,
                'resultRedirectFactory' => $this->redirectFactoryMock
            ]
        );
    }

    /**
     * Test execute function
     */
    public function testExecute()
    {
        $this->redirectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->redirectResultMock);
        $this->cms->expects($this->once())
            ->method('importData')
            ->willReturnSelf();
        $this->assertEquals(null, $this->save->execute());
    }
}
