<?php
/**
 * @category    Fedex
 * @package     Fedex_CloudDriveIntegration
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CloudDriveIntegration\CustomerData;

use Fedex\CloudDriveIntegration\Helper\Data as ModuleConfig;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\Session as CustomerSession;
use \Psr\Log\LoggerInterface;

class ConfigFlag implements SectionSourceInterface
{
    /**
     * @param ModuleConfig $moduleConfig
     * @param CustomerSession $customerSession
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyInterface $company
     * @param CompanyFactory $companyFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleConfig $moduleConfig,
        private CustomerSession $customerSession,
        private CompanyRepositoryInterface $companyRepository,
        private CompanyInterface $company,
        private readonly CompanyFactory $companyFactory,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getSectionData(): array
    {
        $this->initCompany();
        $companyMasterToggle = $this->getCompanyIsBoxEnabled()
            || $this->getCompanyIsDropboxEnabled()
            || $this->getCompanyIsGoogleEnabled()
            || $this->getCompanyIsMicrosoftEnabled();

        $storeMasterToggle = $this->moduleConfig->isBoxEnabled()
            || $this->moduleConfig->isDropboxEnabled()
            || $this->moduleConfig->isGoogleEnabled()
            || $this->moduleConfig->isMicrosoftEnabled();

        $companyLevelAndStoreLevelToggles = [
            'enableCloudDrives' => $companyMasterToggle ? '1' : '0',
            'enableBox' => (string) $this->getCompanyIsBoxEnabled(),
            'enableDropbox' => (string) $this->getCompanyIsDropboxEnabled(),
            'enableGoogleDrive' => (string) $this->getCompanyIsGoogleEnabled(),
            'enableMicrosoftOneDrive' => (string) $this->getCompanyIsMicrosoftEnabled(),
        ];

        $onlyStoreLevelToggles = [
            'enableCloudDrives' => $storeMasterToggle ? '1' : '0',
            'enableBox' => (string) $this->moduleConfig->isBoxEnabled() ?? '0',
            'enableDropbox' => (string) $this->moduleConfig->isDropboxEnabled() ?? '0',
            'enableGoogleDrive' => (string) $this->moduleConfig->isGoogleEnabled() ?? '0',
            'enableMicrosoftOneDrive' => (string) $this->moduleConfig->isMicrosoftEnabled() ?? '0',
        ];

        /** E-359853 - Toggle Restructuring for Cloud Drive Integration */
        if (!$this->company?->getId()) {
            return $onlyStoreLevelToggles;
        }

        return $companyLevelAndStoreLevelToggles;
    }

    /**
     * @return void
     */
    private function initCompany(): void
    {
        $onDemandCompanyInfo = $this->customerSession->getOndemandCompanyInfo();
        $companyId = $onDemandCompanyInfo['company_id'] ?? null;

        if ($companyId !== null) {
            $company = $this->getCompanyById($companyId);
            if ($company?->getId()) {
                $this->company = $company;
                return;
            }
        }
        $this->company = $this->companyFactory->create();

    }

    /**
     * @param $entityId
     * @return \Magento\Company\Api\Data\CompanyInterface|null
     */
    private function getCompanyById($entityId)
    {
        try {
            return $this->companyRepository->get($entityId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->error(__('Company ID not found'));
            return null;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    /**
     * @return int
     */
    private function getCompanyIsBoxEnabled(): int
    {
        if (!$this->moduleConfig->isBoxEnabled()) {
            return 0;
        }

        return (int) $this->company->getData('box_enabled') ?? 0;
    }

    /**
     * @return int
     */
    private function getCompanyIsDropboxEnabled(): int
    {
        if (!$this->moduleConfig->isDropboxEnabled()) {
            return 0;
        }

        return (int) $this->company->getData('dropbox_enabled') ?? 0;
    }

    /**
     * @return int
     */
    private function getCompanyIsGoogleEnabled(): int
    {
        if (!$this->moduleConfig->isGoogleEnabled()) {
            return 0;
        }

        return (int) $this->company->getData('google_enabled') ?? 0;
    }

    /**
     * @return int
     */
    private function getCompanyIsMicrosoftEnabled(): int
    {
        if (!$this->moduleConfig->isMicrosoftEnabled()) {
            return 0;
        }

        return (int) $this->company->getData('microsoft_enabled') ?? 0;
    }
}
