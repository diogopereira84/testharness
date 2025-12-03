<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Plugin;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Fedex\OKTA\Api\Backend\AuthRepositoryInterface;
use Magento\Framework\Data\Form\Element\Collection;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\Form;
use Magento\User\Block\User\Edit\Tab\Main;
use Fedex\OKTA\Plugin\AdminUserForm;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AdminUserFormTest extends TestCase
{
    /**
     * @var AdminUserForm
     */
    private AdminUserForm $adminUserForm;

    /**
     * @var Main|MockObject
     */
    private Main $mainMock;

    /**
     * @var Form|MockObject
     */
    private Form $formMock;

    /**
     * @var AuthRepositoryInterface|MockObject
     */
    private AuthRepositoryInterface $authRepository;

    /**
     * @var Fieldset|MockObject
     */
    private Fieldset $fieldsetMock;

    /**
     * @var Collection|MockObject
     */
    private Collection $collectionMock;

    /**
     * @var AbstractElement|MockObject
     */
    private AbstractElement $abstractElementMock;

    public function testAroundGetFormHtml()
    {
        $this->mainMock = $this->createMock(Main::class);
        $this->formMock = $this->createMock(Form::class);
        $this->fieldsetMock = $this->createMock(Fieldset::class);
        $this->collectionMock = $this->createMock(Collection::class);
        $this->authRepository = $this->createMock(AuthRepositoryInterface::class);
        $this->abstractElementMock = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock->expects($this->once())->method('searchById')->willReturn($this->abstractElementMock);
        $this->fieldsetMock->expects($this->once())->method('getElements')
            ->willReturn($this->collectionMock);
        $this->formMock->expects($this->once())->method('getElement')->with('base_fieldset')
            ->willReturn($this->fieldsetMock);
        $this->mainMock->expects($this->once())->method('getForm')->willReturn($this->formMock);

        $this->adminUserForm = new AdminUserForm($this->authRepository);
        $this->adminUserForm->aroundGetFormHtml($this->mainMock, function () {
        });
    }
}
