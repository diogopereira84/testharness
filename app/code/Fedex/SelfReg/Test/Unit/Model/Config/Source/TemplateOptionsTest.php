<?php

namespace Fedex\SelfReg\Test\Unit\Model\Config\Source;

use Fedex\SelfReg\Model\Config\Source\TemplateOptions;
use PHPUnit\Framework\TestCase;
use Magento\Email\Model\Template;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class TemplateOptionsTest extends TestCase
{
    protected $emailTemplateConfig;
    protected $data;
    protected function setUp(): void
    {
        $this->emailTemplateConfig = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->setMethods(['load','getTemplateText','getTemplateSubject','getCollection'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->data = $objectManagerHelper->getObject(
            TemplateOptions::class,
            [
                'emailTemplateConfig' => $this->emailTemplateConfig
            ]
        );
    }

    /**
     * Test for toOptionArray method.
     *
     * @return array
     */
    public function testToOptionArray()
    {
        $expected = [
            ['value' => '0', 'label' => __('Select email template')],
            ['value' => '1', 'label' => __('TemplateCode')]
        ];

        $template = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getTemplateText','getTemplateCode'])
            ->getMock();
        $this->emailTemplateConfig->expects($this->any())
            ->method('getCollection')
            ->willReturn([$template]);
        
        $template->expects($this->any())
            ->method('getId')
            ->willReturn('1');
        $template->expects($this->any())
            ->method('getTemplateCode')
            ->willReturn('TemplateCode');

        $result = $this->data->toOptionArray();
        $this->assertEquals($expected, $result);
    }
}
