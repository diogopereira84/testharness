<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Ui\Component\Form\Users;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Data\OptionSourceInterface;
use Fedex\SelfReg\Ui\Component\Form\Users\Options;
use PHPUnit\Framework\TestCase;


class OptionsTest extends TestCase
{
    protected $optionsMock;
    protected function setUp(): void
    {

        $objectManagerHelper = new ObjectManager($this);

        $this->optionsMock = $objectManagerHelper->getObject(
            Options::class,
            [
            ]
        );
    }

    public function testToOptionArray()
    {
        $options[] = ['value' => '', 'label' => ''];
		$expectedResult = $this->optionsMock->toOptionArray();
		$this->assertEquals($options, $expectedResult);
    }
}
