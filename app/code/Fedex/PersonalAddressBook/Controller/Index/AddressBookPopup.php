<?php

/**
* @category    Fedex
* @package     Fedex_PersonalAddressBook
* @copyright   Copyright (c) 2024 Fedex
* @author      Pallavi Kade <pallavi.kade.osv@fedex.com>
*/

namespace Fedex\PersonalAddressBook\Controller\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\PersonalAddressBook\Helper\Parties as Data;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Escaper;
use Magento\Framework\App\ObjectManager;
use Fedex\PersonalAddressBook\Block\View;

class AddressBookPopup implements ActionInterface
{
    /**
     * @var Escaper|mixed
     */
    private mixed $escaper;

    /**
   * @param ResultFactory $resultFactory
   * @param Raw $rawResult
   * @param ToggleConfig $toggleConfig
   * @param Escaper $escaper
   * @param Data $partiesHelper
   * @param View $view
   */
  public function __construct(
      private ResultFactory $resultFactory,
      private Raw $rawResult,
      private ToggleConfig $toggleConfig,
      protected Data $partiesHelper,
      private View $view,
      ?Escaper $escaper = null
  ) {
    $this->partiesHelper = $partiesHelper;
    $this->escaper = $escaper ?: ObjectManager::getInstance()->get(Escaper::class);
  }

  /**
   * @return string
   *
   */
  public function execute()
  {
    $addressBookData = $this->view->addressBookData();
    $totalRecords = $this->view->totalRecords();
    $totalPages = $totalRecords ? $this->generatePages($totalRecords) : 0;
    $defaultCount = 10; //TBD, Make it configurable
    $html = '';
    $html = '<div class="tab">
    <button class="tablinks active" id="personal">Personal <span id="personalspan">' . $totalRecords . '</span></button>
</div>
<div id="Personal" class="tabcontent" style="display: block;">
  <div class="personal-address-search-container" role="search" aria-label="message" style="display: block;">
    <div class="address-search-section flex-container">
      <label class="search-label" for="search">SEARCH BY:</label>
      <select aria-label="address-search-options" name="address-search-options" class="address-search-options" id="addressSearchOptions">
          <option value="lastName">Last Name</option>
          <option value="firstName">First Name</option>
          <option value="companyName">Company</option>
          <option value="personal_address_address">Address</option>
          <option value="personal_address_city">City</option>
          <option value="personal_address_state">State</option>
          <option value="personal_address_zip">Zip</option>
      </select>
      <div class="personal-address-search-control flex-container">
          <input type="text" name="personal-address-search" id="keyword" minlength="3" placeholder="Search by last name" class="personal-address-search-field"/>
        <div class="clear-all">
          <a href="javascript:void(0);" id="personalAddressClearAll" style="display:none;">Clear All</a>
        </div>
        <button type="submit" class="personal-address-search-button" aria-label="Personal Address Search" id="personalAddressSearch">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
            <rect opacity="0.01" width="32" height="32" fill="white"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M18.026 18.9688C16.7543 20.0289 15.1182 20.6667 13.3333 20.6667C9.28348 20.6667 6 17.3832 6 13.3333C6 9.28292 9.2832 6 13.3333 6C17.3835 6 20.6667 9.28292 20.6667 13.3333C20.6667 15.1182 20.0289 16.7543 18.9688 18.026L25.8047 24.8619C26.0651 25.1223 26.0651 25.5444 25.8047 25.8047C25.5444 26.0651 25.1223 26.0651 24.8619 25.8047L18.026 18.9688ZM19.3333 13.3333C19.3333 10.0193 16.6471 7.33333 13.3333 7.33333C10.0196 7.33333 7.33333 10.0193 7.33333 13.3333C7.33333 16.6468 10.0199 19.3333 13.3333 19.3333C16.6468 19.3333 19.3333 16.6468 19.3333 13.3333Z" fill="#333333"/>
          </svg>
        </button>
      </div>
    </div>
    <div class="error-message">
            <span class="error-cross">✕</span>
            Minimum character count of 2 required.
    </div>
    <label class="filter-label" for="filter"><strong>FILTER BY LAST NAME</strong></label>
        <div class="flex-container address-filter-section">
            <a class="clear-address-search search-bar-nav">CLEAR</a>
            ' . implode('', array_map(function ($alphaNavigation) {
      return '<a class="search-address-alphabet search-bar-nav" tabindex="0" id="alph_' . $this->escaper->escapeHtmlAttr($alphaNavigation) . '">' . $this->escaper->escapeHtmlAttr($alphaNavigation) . '</a>';
    }, range('A', 'Z'))) . '
        </div>
    <hr>
    <div class="data-grid-wrap" data-role="grid-wrapper">
             <table class="data-grid data table personal_address_book_table" data-role="grid">
                <thead class="data-header addressbookheader">
                    <tr>
                        <th class="first-col"></th>
                        <th class="data-grid-th _sortable _draggable">
                        <span class="data-grid-cell-content-name first-sort sorted" data-sort="lastName" data-order="desc" data-th="lastName" id="data-fullname">NAME</span>
                        </th>
                        <th class="data-grid-th _sortable _draggable">
                            <span class="data-grid-cell-content-company sorted"  data-th="companyName">COMPANY</span>
                        </th>
                        <th class="data-grid-th _sortable _draggable">
                            <span class="data-grid-cell-content-address sorted" data-th="personal_address_address">ADDRESS</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="addressbookdatacheckout">';
                if (!empty($addressBookData)) {
                    foreach ($addressBookData as $addressBook) {
                      if (!$defaultCount) {
                        break;
                      }
                      $phoneNo = $addressBook['phoneNumber'] ?? '';
                      $phoneNumExt = $addressBook['phoneNumberExten'] ?? '';
                      $html .= '<tr class="data-row disabled" id="tr-' . htmlspecialchars($addressBook['contactID'] ?? '') . '">
                    <td class="data-grid-radio-cell">
                        <label class="data-grid-radio-cell-inner">
                        <input aria-label="addressbook-popup-checkbox" class="admin__control-radio custom_row_radio" type="radio" data-action="select-row" id="idscheck' . htmlspecialchars($addressBook['contactID'] ?? '') . '" value="' . htmlspecialchars($addressBook['contactID'] ?? '') . '">
                        <input id="contactID" type="hidden" name="contactIDs[]" value="' . htmlspecialchars($addressBook['contactID'] ?? '') . '">
                        <input type="hidden" id="phoneNumber_' . htmlspecialchars($addressBook['contactID'] ?? '') . '" value="' . htmlspecialchars($phoneNo) . '">
                        <input type="hidden" id="phoneNumberExten_' . htmlspecialchars($addressBook['contactID'] ?? '') . '" value="' . htmlspecialchars($phoneNumExt) . '">
                        </label>
                    </td>
                    <td class="long-text-field" data-th="FIRST NAME">
                        <div class="data-grid-cell-content">' . htmlspecialchars(($addressBook['lastName'] ?? '') . ', ' . ($addressBook['firstName'] ?? '')) . '</div>
                    </td>
                    <td class="long-text-field" data-th="COMPANY">' . htmlspecialchars($addressBook['companyName'] ?? '') . '</td>
                    <td data-th="ADDRESS">
                        <div class="data-grid-cell-content">' .
                        $this->escaper->escapeHtml($addressBook['address']['streetLines'][0] ?? '') . '
' .
                        $this->escaper->escapeHtml(($addressBook['address']['city'] ?? '') . ', ' .
                            ($addressBook['address']['stateOrProvinceCode'] ?? '') . ' ' .
                            ($addressBook['address']['postalCode'] ?? '')) .
                            '</div>
                    </td>
                </tr>';
                $defaultCount--;
                    }
                } else {
                    $html .= '<tr><td colspan="4">No Record Found.</td></tr>';
                }

    $html .= '</tbody>
             </table><hr/>';
    if ($totalRecords) {
      $html .= '<div class="pagination-container">
              <div class="results-per-page">
                <label id="resultsPerPageLabel" for="resultsPerPage">Rows per page:</label>
                <select id="resultsPerPage" name="resultsPerPage" style="width: 108px;">
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>
              <div class="page-navigation">
                <span class="current-page-total" style="width: 79px;">1-10 of ' . $totalRecords . '</span>
                <button class="prev-page" disabled style="width: 32px; height:32px;">&lsaquo;</button>
                <!-- TBD Make it configurable -->
                <button class="next-page" 
                    ' . ($totalRecords <= 10 ? 'disabled' : '') . ' 
                    style="width: 32px; height:32px; margin-left:8px">
                    &rsaquo;
                </button>
                <input type="hidden" id="currentPage" name="currentPage" value="1">
                <input type="hidden" id="totalPages" name="totalPages" value="' . $totalPages . '">
                <input type="hidden" id="totalRecords" name="totalRecords" value="' . $totalRecords . '">
              </div>
          </div>';
    }

    $html .= '</div></div></div>';

    /** @var Raw $rawResult */
    $rawResult = $this->resultFactory->create(ResultFactory::TYPE_RAW);
    return $rawResult->setContents($html);
  }

  /**
   * @return int
   *
   */
  public function generatePages($totalRecords)
  {
    $pageSize = 10;
    $totalPages = intdiv($totalRecords, $pageSize) + ($totalRecords % $pageSize > 0 ? 1 : 0);
    return $totalPages;
  }
}