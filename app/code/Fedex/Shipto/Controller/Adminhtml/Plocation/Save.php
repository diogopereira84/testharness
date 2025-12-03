<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipto\Controller\Adminhtml\Plocation;

use Magento\Framework\App\ActionInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\RequestInterface;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Customer\Model\Session;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Save implements ActionInterface
{
    private const RECOMMENDED_STORES_ALL_LOCATION = 'recommended_stores_all_location';

    /**
     * Data Constructor
     *
     * @param RequestInterface $request
     * @param Logger $logger
     * @param ProductionLocationFactory $productionLocationFactory
     * @param JsonFactory $jsonFactory
     * @param SerializerInterface $serializer
     * @param Session $customerSession
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Logger $logger,
        private readonly ProductionLocationFactory $productionLocationFactory,
        private readonly JsonFactory $jsonFactory,
        private readonly SerializerInterface $serializer,
        private Session $customerSession,
        private readonly ToggleConfig $toggleConfig
    ) {
    }

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $data = $this->request->getParams();
        $response = [];

        $getAllLocationsFromSession = $this->customerSession->getAllLocations();
        if ($getAllLocationsFromSession == "") {
            $response['status'] = 'error';
            $response['message'] = 'Some unknown error please try again';
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ': Unknown error saving location.');
        } else {
            $getAllLocationsFromSession = $this->jsonToArray($getAllLocationsFromSession);

            if (
                $data &&
                isset($data['location_id']) &&
                $data['location_id'] > 0 &&
                !empty($data['company_id'])
            ) {
                $locationIds = explode(",", $data['location_id']);

                $keyArray = $this->prepareKeyArray($locationIds, $getAllLocationsFromSession);
                $response = $this->saveLocation($keyArray, $data);
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Location Id and Company Id are required';
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ': Location Id and Company Id not provided.');
            }
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }

    /**
     * Save production location
     *
     * @param array $keyArray
     * @param array $data
     * @return array $response
     */
    public function saveLocation($keyArray, $data)
    {
        $response = [];
        $locationModel = $this->productionLocationFactory->create();
        foreach ($keyArray as $locations) {
            $id = null;
            $newArray = $this->prepareArray($locations, $data);
            if ($data['is_restricted_product_location_toggle']) {
                $id = $locations['officeLocationId'];
            } else {
                $id = $locations['Id'];
            }
            $collection = $locationModel->getCollection()
                                ->addFieldToFilter('location_id', $id)
                                ->addFieldToFilter('company_id', $data['company_id'])
                                ->addFieldToFilter(
                                    'is_recommended_store',
                                    $data['is_recommended_store'] === self::RECOMMENDED_STORES_ALL_LOCATION ? 1 : 0
                                );

            if ($collection->getSize() == 0) {
                try {
                    $locationModel->setData($newArray);
                    $locationModel->save();
                    $response['status'] = 'success';
                    $response['message'] = 'Location save successfully';
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ': Locations save success.');
                } catch (\Exception $e) {
                    $response['status'] = 'error';
                    $response['message'] = $e->getMessage();
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ': ' . $e->getMessage());
                }
            } else {
                $response['status'] = 'success';
                $response['message'] = 'Location save successfully';
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ': Location saved successfully.');
            }
        }
        if ($data['is_restricted_product_location_toggle']) {
            // Handle Locations removal
            $this->handleLocationRemoval($locationModel, $data);
        }
            $this->customerSession->setAllLocations([]);
        return $response;
    }

    /**
     * Prepare key array for all locations
     *
     * @param array $locationIds
     * @param array $getAllLocationsFromSession
     * @return array $keyArray
     */
    public function prepareKeyArray($locationIds, $getAllLocationsFromSession)
    {
        $keyArray = [];
        foreach ($locationIds as $locationId) {
                $locationId = trim($locationId);
            if (array_key_exists($locationId, $getAllLocationsFromSession)) {
                $keyArray[] = $getAllLocationsFromSession[$locationId];
            }
        }
        return $keyArray;
    }

    /**
     * Prepare array for all locations
     *
     * @param array $locations
     * @param array $data
     * @return array $newArray
     */
    public function prepareArray($locations, $data)
    {
        $newArray = [];

        if ($data['is_restricted_product_location_toggle']) {
            $newArray['location_id'] = $locations['officeLocationId'];
            $newArray['location_name'] = $locations['locationName'];
            $newArray['company_id'] = $data['company_id'];
            $newArray['address1'] = isset($locations['address']['streetLines'][0]) ?
            $locations['address']['streetLines'][0] : '';
            $newArray['address2'] = isset($locations['address']['streetLines'][1]) ?
            $locations['address']['streetLines'][1] : '';
            $newArray['city'] = $locations['address']['city'];
            $newArray['state'] = $locations['address']['stateOrProvinceCode'];
            $newArray['country_id'] = $locations['address']['countryCode'];
            $newArray['postcode'] = $locations['address']['postalCode'];
            $newArray['telephone'] = $locations['phoneNumber'];
            $newArray['location_email'] = $locations['emailAddress'];
            $newArray['lat'] = isset($locations['geoCode']) ? $locations['geoCode']['latitude'] : null;
            $newArray['long'] = isset($locations['geoCode']) ? $locations['geoCode']['longitude'] : null;
            $newArray['hours_of_operation'] = $this->prepareHoursOfOperationsArray($locations);
            $newArray['created_at'] = date('Y-m-d');
            $newArray['updated_at'] = date('Y-m-d');
            $newArray['is_recommended_store'] =
                $data['is_recommended_store'] === self::RECOMMENDED_STORES_ALL_LOCATION ? 1 : 0;
        } else {
            $newArray['location_id'] = $locations['Id'];
            $newArray['location_name'] = $locations['name'];
            $newArray['company_id'] = $data['company_id'];
            $newArray['address1'] = $locations['address']['address1'];
            $newArray['address2'] = $locations['address']['address2'];
            $newArray['city'] = $locations['address']['city'];
            $newArray['state'] = $locations['address']['stateOrProvinceCode'];
            $newArray['country_id'] = $locations['address']['countryCode'];
            $newArray['postcode'] = $locations['address']['postalCode'];
            $newArray['address_type'] = $locations['address']['addressType'];
            $newArray['telephone'] = $locations['phone'];
            $newArray['location_email'] = $locations['email'];
            $newArray['location_type'] = $locations['locationType'];
            $newArray['location_available'] = $locations['available'] == 1 ? true : false;
            $newArray['availability_reason'] = $locations['availabilityReason'];
            $newArray['is_pickup_enable'] = $locations['pickupEnabled'] == 1 ? true : false;
            $newArray['lat'] = $locations['geoCode']['latitude'];
            $newArray['long'] = $locations['geoCode']['longitude'];
            $newArray['services'] = implode(",", $locations['services']);
            $newArray['hours_of_operation'] = $this->arrayToJson($locations['hoursOfOperation']);
            $newArray['created_at'] = date('Y-m-d');
            $newArray['updated_at'] = date('Y-m-d');
            $newArray['is_recommended_store'] =
                $data['is_recommended_store'] === self::RECOMMENDED_STORES_ALL_LOCATION ? 1 : 0;
        }

        return $newArray;
    }

    public function arrayToJson($data)
    {
        return $this->serializer->serialize($data);
    }

    public function jsonToArray($data)
    {
        return $this->serializer->unserialize($data);
    }

    /**
     * Prepare array for HoursOfOperations
     *
     * @param array $locations
     * @return array $formattedHoursOfOperations
     */
    public function prepareHoursOfOperationsArray($locations)
    {
        $formattedHoursOfOperations = [];
        if (isset($locations['operatingHours'])) {
            foreach ($locations['operatingHours'] as &$item) {
                $item['day'] = $item['dayOfWeek'];
                unset($item['dayOfWeek']);
            }
            $formattedHoursOfOperations = $locations['operatingHours'];
            return $this->arrayToJson($formattedHoursOfOperations);
        } else {
            return null;
        }
    }

    /**
     * Handle locations removal
     * @param string $locationModel $data
     * @param string $data
     */
    public function handleLocationRemoval($locationModel, $data)
    {
        try {
            // Check if recommended store selected then find and remove saved restricted locations
            $isRecommendedStore = 1;
            if ($data['is_recommended_store'] === self::RECOMMENDED_STORES_ALL_LOCATION) {
                $isRecommendedStore = 0;
            }
            $collection =  $locationModel->getCollection()
                ->addFieldToFilter('company_id', $data['company_id'])
                ->addFieldToFilter('is_recommended_store', $isRecommendedStore);

            if ($collection->getSize() > 0) {
                foreach ($collection->getData() as $locationObj) {
                    $locationModel->load($locationObj['id']);
                    $locationModel->delete();
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error occured while removing production locations
                 for the company id :' . $data ['company_id'] . ' is' . $e->getMessage());
        }
    }
}
