<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Punchout\Test\Unit\ViewModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Punchout\ViewModel\TazToken;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Punchout\Helper\Data as PunchoutHelper;

use Magento\Framework\View\Element\Block\ArgumentInterface;


class TazTokenTest extends TestCase
{
	protected $punchoutHelperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $tazTokenMock;
    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
		$this->punchoutHelperMock = $this->getMockBuilder(PunchoutHelper::class)
										->setMethods(['getTazToken'])
											->disableOriginalConstructor()
												->getMock();

		$this->objectManager = new ObjectManager($this);

        $this->tazTokenMock = $this->objectManager->getObject(
								TazToken::class,
								[
									'punchoutHelper' => $this->punchoutHelperMock
								]
							);
	}

    /**
     * Test getTazToken
     */
    public function testGetTazToken()
    {
		$publicFlag = false;
		$this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn('TAZ_TOKEN');
        $result = $this->tazTokenMock->getTazToken($publicFlag);
		$this->assertIsString($result);
    }

}

