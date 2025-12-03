<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
declare(strict_types=1);

namespace Fedex\FedexCsp\Test\Unit\Plugin\Form\FormKey;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Fedex\FedexCsp\Plugin\Form\FormKey\Validator;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;

class ValidatorTest extends TestCase
{
    protected $request;
    protected $formKeyValidator;
    protected $validator;
    /**
     * Setup
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->formKeyValidator = $this->getMockBuilder(FormKeyValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $objectManagerHelper = new ObjectManager($this);
        $this->validator = $objectManagerHelper->getObject(Validator::class, []);
    }

    /**
     * Test for afterValidate()
     */
    public function testAfterValidate()
    {
        $this->request->expects($this->once())->method('getParam')->willReturn('form_key');
        $this->validator->afterValidate($this->formKeyValidator, true, $this->request);
    }
}
