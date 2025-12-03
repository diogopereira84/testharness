<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Setup\Patch\Data;

use Magento\User\Model\UserFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class CreateMFTFAdminUser implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param UserFactory $userFactory
     * @param WriterInterface $writer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleDataSetupInterface    $moduleDataSetup,
        private UserFactory                 $userFactory,
        private WriterInterface             $writer,
        protected LoggerInterface             $logger
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $data = [
            'username'  => 'mftf_user',
            'firstname' => 'Magento',
            'lastname'    => 'MFTF',
            'email'     => 'fedex_mftf@fedex.com',
            'password'  => substr(str_shuffle(MD5(microtime())), 0, 10),
            'interface_locale' => 'en_US',
            'is_active' => 1
        ];


        $user = $this->userFactory->create();
        $user->setData($data);
        $user->setRoleId(1);

        try{
            // user module do not have repository class
            $user->save();
        } catch (\Exception $ex) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $ex->getMessage());
            $ex->getMessage();
        }

        if (null !== $user->getId()) {
            $this->writer->save(
                'okta_mftf/options/admin_user',
                $user->getId(),
                \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
