<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\FXOCMConfigurator\Cron;

use Fedex\FXOCMConfigurator\Helper\Batchupload as BatchuploadHelper;
use Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace\CollectionFactory as UserworkspaceCollectionFactory;
use Fedex\FXOCMConfigurator\Model\UserworkspaceFactory;
use Psr\Log\LoggerInterface;

class DeleteWorkSpace
{

    /**
     * @var UserworkspaceCollectionFactory
     */
    protected $userworkspaceCollectionFactory;

    /**
     * Constructor
     *
     * @param Batchupload $batchuploadHelper
     * @param UserworkspaceCollectionFactory $userworkspaceCollectionFactory
     * @param Userworkspace $userworkspace
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected BatchuploadHelper $batchuploadHelper,
        UserworkspaceCollectionFactory $UserworkspaceCollectionFactory,
        protected UserworkspaceFactory $userworkspace,
        protected LoggerInterface $logger
    ) {
        $this->userworkspaceCollectionFactory = $UserworkspaceCollectionFactory;
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $getBackdate = $this->batchuploadHelper->getWorkSpaceDeleteDays();
        $backDate = date("y-m-d", strtotime("-" . $getBackdate . " days"));

        $userworkspaceCollection = $this->userworkspaceCollectionFactory->create();
        $userworkspaceCollection->addFieldToFilter("old_upload_date", [
            "lt" => $backDate,
        ]);


        if ($userworkspaceCollection->getSize()) {
            $this->reArrangeWorkSpaceData($userworkspaceCollection);
        }
    }

    /**
     * ReArrangeWorkSpaceData according date || Delete workSpace files and projects
     *
     * @param $userworkspaceCollection || Object
     * @return  null
     */
    public function reArrangeWorkSpaceData($userworkspaceCollection)
    {
        foreach ($userworkspaceCollection as $eachWorkspaceData) {

            $workspaceData = json_decode(
                $eachWorkspaceData->getWorkspaceData()
            );

            //Re Arrange Files data (delete file data)
            $reArrangeWorkSpaceFilesData = $this->reArrangeWorkSpaceFilesData(
                $workspaceData
            );
            $associatedDocumentIds =
                $reArrangeWorkSpaceFilesData["associatedDocuments"];
            $workspaceData->files = $reArrangeWorkSpaceFilesData["files"];

            //Re Arrange Project data (delete Project data)
            $workspaceData->projects = $this->reArrangeWorkSpaceProjectData(
                $workspaceData,
                $associatedDocumentIds
            );

            $workspaceDataNew = json_encode($workspaceData);
            if (sizeof($workspaceData->files) > 0) {
                //update workspace
                $this->updateWorkSpaceData($workspaceData,$workspaceDataNew,$eachWorkspaceData->getUserworkspaceId());
            } else {
                //Delete workspace row
                $this->deleteWorkSpaceData($eachWorkspaceData->getUserworkspaceId());
            }
        }
    }

    /**
     * ReArrangeWorkSpaceData Files according date || Delete workSpace files
     *
     * @param $userworkspaceCollection || Object
     * @return reArrangeWorkSpaceFilesDataArray[]
     */
    public function reArrangeWorkSpaceFilesData($workspaceData)
    {
        $formateBackDate = $this->getBackDate();
        $reArrangeWorkSpaceFilesDataArray = [];
        $count = 0;
        $associatedDocumentIds = [];
        foreach ($workspaceData->files as $workspaceDataFile) {
            if (
                isset($workspaceDataFile->uploadDateTime) &&
                isset($workspaceDataFile->id)
            ) {
                $uploadDateTime = explode(
                    "T",
                    $workspaceDataFile->uploadDateTime
                );
                $uploadDate = strtotime($uploadDateTime[0]);

                if ($uploadDate < $formateBackDate) {
                    $associatedDocumentIds[] =
                        $workspaceData->files[$count]->id;
                    // Unset files data with check old date
                    unset($workspaceData->files[$count]);
                }
                $count++;
            }
        }
        //Reset files index array after unset some values
        $workspaceData->files = array_values($workspaceData->files);
        $reArrangeWorkSpaceFilesDataArray["files"] = $workspaceData->files;
        $reArrangeWorkSpaceFilesDataArray[
            "associatedDocuments"
        ] = $associatedDocumentIds;
        return $reArrangeWorkSpaceFilesDataArray;
    }

    /**
     * ReArrangeWorkSpaceData Project according date || Delete workSpace Project
     *
     * @param $userworkspaceCollection || Object
     * @param $associatedDocumentIds[]
     * @return workspaceData || Object
     */
    public function reArrangeWorkSpaceProjectData(
        $workspaceData,
        $associatedDocumentIds
    ) {

        $projectCount = 0;
        foreach ($workspaceData->projects as $workspaceDataProject) {
            if (isset($workspaceDataProject->associatedDocuments)) {
                $sizeofProjectassociatedDocumentIds = sizeof(
                    $workspaceDataProject->associatedDocuments
                );
                $associatedDocumentsEachArray = [];
                foreach($workspaceDataProject->associatedDocuments as $associatedDocumentsEach){
                  $associatedDocumentsEachArray[] = $associatedDocumentsEach->id;
                }

                $matchArray = array_intersect(
                    $associatedDocumentIds,
                    $associatedDocumentsEachArray
                );
                if (
                    sizeof($matchArray) == $sizeofProjectassociatedDocumentIds
                ) {
                    // Unset projects data with check deleted associatedDocumentIds
                    unset($workspaceData->projects[$projectCount]);
                } elseif (sizeof($matchArray) > 0) {
                    $associatedDocumentIdsEachCount = 0;
                    foreach (
                        $workspaceDataProject->associatedDocuments
                        as $associatedDocumentIdsEach
                    ) {
                        if (
                            in_array(
                                $associatedDocumentIdsEach->id,
                                $associatedDocumentIds
                            )
                        ) {
                            unset(
                                $workspaceDataProject->associatedDocuments[
                                    $associatedDocumentIdsEachCount
                                ]
                            );
                        }
                        $associatedDocumentIdsEachCount++;
                    }
                    $workspaceDataProject->associatedDocuments = array_values(
                        $workspaceDataProject->associatedDocuments
                    );
                }

                $projectCount++;
            }
        }
        //Reset projects index array after unset some values
        return array_values($workspaceData->projects);
    }

    /**
     * Get Formated Date
     *
     * @param null
     * @return formateBackDate || string
     */
    public function getBackDate()
    {
        $getBackdate = $this->batchuploadHelper->getWorkSpaceDeleteDays();
        $backDate = date("y-m-d", strtotime("-" . $getBackdate . " days"));
        $formateBackDate = strtotime($backDate);
        return $formateBackDate;
    }

    /**
     * Update WorkSpace Data into Database
     *
     * @param $workSpaceData || Object
     * @param $workspaceDataNew || String
     * @param $userworkspaceId || bool
     * @return null
     */
    public function updateWorkSpaceData(
        $workspaceData,
        $workspaceDataNew,
        $userworkspaceId
    ) {
        $oldPploadDate = $this->getOldUploadedDate($workspaceData);
        $userworkspaceModel = $this->userworkspace->create();
        $userworkspaceModel->load($userworkspaceId, "userworkspace_id");
        $userworkspaceModel->setWorkspaceData($workspaceDataNew);
        $userworkspaceModel->setOldUploadDate($oldPploadDate);
        $userworkspaceModel->save();
    }

    /**
     * Delete WorkSpace Data
     *
     * @param $userworkspaceId || bool
     * @return null
     */
    public function deleteWorkSpaceData($userworkspaceId)
    {
        $userworkspaceModel = $this->userworkspace->create();
        $userworkspaceModel->load($userworkspaceId, "userworkspace_id");
        $userworkspaceModel->delete();
    }

    /**
     * Get Formated Date
     *
     * @param $workSpaceData || Object
     * @return formateBackDate || string
     */
    public function getOldUploadedDate($workSpaceData)
    {
        $uploadDateTime = [];
        foreach ($workSpaceData->files as $files) {
            if (isset($files->uploadDateTime)) {
                $uploadDateTime[] = $files->uploadDateTime;
            }
        }
        usort($uploadDateTime, function ($a, $b) {
            return strtotime($a) - strtotime($b);
        });
        if (is_array($uploadDateTime)) {
            $uploadDate = explode("T", $uploadDateTime[0]);
            return $uploadDate[0];
        }
    }
}
