<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Renjith Raveendran <renjith.raveendran.osv@fedex.com
 */

namespace Fedex\OKTA\Plugin;

use Fedex\OKTA\Api\Backend\AuthRepositoryInterface;

class AdminUserForm
{
    /**
     * @param AuthRepositoryInterface $authRepository
     */
    public function __construct(
        private AuthRepositoryInterface $authRepository
    )
    {
    }

    /**
     * @param \Magento\User\Block\User\Edit\Tab\Main $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundGetFormHtml(
        \Magento\User\Block\User\Edit\Tab\Main $subject,
        \Closure $proceed
    ) {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $subject->getForm();
        if (is_object($form)) {

            $fieldset = $form->getElement('base_fieldset');
            $userIdField = $fieldset->getElements()->searchById('user_id');
            $userRelationshipData = null;
            if ($userIdField) {
                $userId = $userIdField->getValue();
               $userRelationshipData =  $this->authRepository->getRelationshipData((int) $userId);
            }
            $fieldset->addField(
                'okta_user_data',
                'textarea',
                [
                    'name' => 'okta_user_data',
                    'label' => __('OKTA User Data'),
                    'title' => __('OKTA User Data'),
                    'value' => $userRelationshipData,
                    'disabled' => true
                ]
            );

            $subject->setForm($form);
        }

        return $proceed();
    }
}
