<?php

declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Adminhtml;

use Magento\Config\Model\ResourceModel\Config as ConfigModel;
use \Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Logging\Model\Event\ChangesFactory;
use Magento\Logging\Model\ResourceModel\Event;
use Magento\Logging\Model\ResourceModel\Event\Changes;
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;

class ToggleRetirementLogGenerator
{
    public const FULL_PATH = 'environment_toggle_configuration/environment_toggle';

    /**
     * @param ManagerInterface $managerInterface
     * @param GetEventForLogging $getEventForLogging
     * @param Event $eventResource
     * @param Changes $changesResource
     * @param ChangesFactory $eventChangesFactory
     * @param Session $authSession
     */
    public function __construct(
        private ManagerInterface $managerInterface,
        private GetEventForLogging $getEventForLogging,
        private Event $eventResource,
        private Changes $changesResource,
        protected ChangesFactory $eventChangesFactory,
        protected Session $authSession
    )
    {
    }

    /**
     * @param $listOfRetiredToggles
     * @return void
     */
    public function generateToggleRetirementLog($listOfRetiredToggles): void
    {
        try {
            $event = $this->generateEvent($this->getCurrentUserId(), $listOfRetiredToggles);

            $listOfRetiredToggles = $this->fillInSectionGroupInfo($listOfRetiredToggles);

            $this->generateRetiredTogglesChanges((int)$event->getId(), $listOfRetiredToggles);

        } catch (AlreadyExistsException | \Exception $exception) {
            $this->managerInterface->addWarningMessage('It was not possible to generated delete log due to: '
                .$exception->getMessage());
        }
    }

    /**
     * @return int
     */
    protected function getCurrentUserId(): int
    {
        return (int)$this->authSession->getUser()->getId();
    }

    /**
     * @param $userId
     * @param $listOfRetiredToggles
     * @return \Magento\Logging\Model\Event
     * @throws AlreadyExistsException
     */
    protected function generateEvent($userId, $listOfRetiredToggles): \Magento\Logging\Model\Event
    {
        $event = $this->getEventForLogging->execute($userId);
        $event->setEventCode('retired_toggles');
        $event->setAction('delete');
        $event->setInfo(__("%1 Toggles have been Retired. Click for more details.", count($listOfRetiredToggles)));
        $this->eventResource->save($event);

        return $event;
    }

    /**
     * @param $listOfRetiredToggles
     * @return array
     */
    protected function fillInSectionGroupInfo($listOfRetiredToggles): array
    {
        array_walk($listOfRetiredToggles, function (&$item) {
            $item = self::FULL_PATH . '/' . $item;
        });

        return $listOfRetiredToggles;
    }

    /**
     * @param $eventId
     * @param $listOfRetiredToggles
     * @return void
     * @throws AlreadyExistsException
     */
    protected function generateRetiredTogglesChanges($eventId, $listOfRetiredToggles): void
    {
        $changes = $this->eventChangesFactory->create();
        $changes->setEventId($eventId);
        $changes->setSourceName(ConfigModel::class);
        $changes->setOriginalData($listOfRetiredToggles);
        $this->changesResource->save($changes);
    }
}
