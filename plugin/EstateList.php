<?php

/**
 *
 *    Copyright (C) 2016 onOffice Software AG
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace onOffice\WPlugin;

use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\API\APIClientActionGeneric;
use onOffice\WPlugin\Controller\EstateListBase;
use onOffice\WPlugin\Controller\EstateListEnvironment;
use onOffice\WPlugin\Controller\EstateListEnvironmentDefault;
use onOffice\WPlugin\DataView\DataListView;
use onOffice\WPlugin\DataView\DataView;
use onOffice\WPlugin\DataView\DataViewFilterableFields;
use onOffice\WPlugin\Filter\DefaultFilterBuilder;
use onOffice\WPlugin\Filter\GeoSearchBuilder;
use onOffice\WPlugin\SDKWrapper;
use onOffice\WPlugin\ViewFieldModifier\EstateViewFieldModifierTypes;
use onOffice\WPlugin\ViewFieldModifier\ViewFieldModifierHandler;
use function add_action;
use function do_action;
use function esc_url;
use function get_page_link;
use function plugin_dir_url;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2015, onOffice(R) Software AG
 *
 */

class EstateList
	implements EstateListBase
{
	/** @var array */
	private $_records = [];

	/** @var array */
	private $_recordsRaw = [];

	/** @var EstateFiles */
	private $_pEstateFiles = null;

	/** @var array */
	private $_currentEstate = [];

	/** @var array */
	private $_estateContacts = [];

	/** @var int */
	private $_currentEstatePage = 1;

	/** @var int */
	private $_numEstatePages = null;

	/** @var int */
	private $_handleEstateContactPerson = null;

	/** @var DataView */
	private $_pDataView = null;

	/** @var string */
	private $_unitsViewName = null;

	/** @var bool */
	private $_formatOutput = true;

	/** @var EstateListEnvironment */
	private $_pEnvironment =  null;

	/** @var APIClientActionGeneric */
	private $_pApiClientAction = null;

	/** @var GeoSearchBuilder */
	private $_pGeoSearchBuilder = null;


	/**
	 *
	 * @param DataView $pDataView
	 * @param EstateListEnvironment $pEnvironment
	 *
	 */

	public function __construct(DataView $pDataView, EstateListEnvironment $pEnvironment = null)
	{
		$this->_pEnvironment = $pEnvironment ?? new EstateListEnvironmentDefault();
		$this->_pDataView = $pDataView;
		$pSDKWrapper = $this->_pEnvironment->getSDKWrapper();
		$this->_pApiClientAction = new APIClientActionGeneric
			($pSDKWrapper, onOfficeSDK::ACTION_ID_READ, 'estate');
		$this->_pGeoSearchBuilder = $this->_pEnvironment->getGeoSearchBuilder();
	}


	/**
	 *
	 * @return int
	 *
	 */

	protected function getNumEstatePages()
	{
		$recordNumOverAll = $this->getEstateOverallCount();
		$recordsPerPageView = $this->_pDataView->getRecordsPerPage();
		// 20 is the default of API in case recordsPerPage <= 0
		$recordsPerPage = $recordsPerPageView <= 0 ? 20 : $recordsPerPageView;
		$numEstatePages = (int)ceil($recordNumOverAll / $recordsPerPage);

		return $numEstatePages;
	}


	/**
	 *
	 * @return int
	 *
	 */

	protected function getRecordsPerPage()
	{
		return $this->_pDataView->getRecordsPerPage();
	}


	/**
	 *
	 * @return array
	 *
	 */

	protected function getPreloadEstateFileCategories()
	{
		return $this->_pDataView->getPictureTypes();
	}


	/**
	 *
	 * @param int $currentPage
	 * @param DataView $pDataListView
	 *
	 */

	public function loadEstates(int $currentPage = 1, DataView $pDataListView = null)
	{
		if ($pDataListView === null)
		{
			$pDataListView = $this->_pDataView;
		}

		$this->_pEnvironment->getFieldnames()->loadLanguage();
		$this->loadRecords($currentPage);

		$fileCategories = $this->getPreloadEstateFileCategories();

		$this->_pEstateFiles = $this->_pEnvironment->getEstateFiles($fileCategories);
		$estateIds = $this->getEstateIdToForeignMapping($this->_records);

		$pSDKWrapper = $this->_pEnvironment->getSDKWrapper();

		if ($estateIds !== []) {
			add_action('oo_beforeEstateRelations', [$this, 'registerContactPersonCall'], 10, 2);
			add_action('oo_afterEstateRelations', [$this, 'extractEstateContactPerson'], 10, 2);

			do_action('oo_beforeEstateRelations', $pSDKWrapper, $estateIds);

			$pSDKWrapper->sendRequests();

			do_action('oo_afterEstateRelations', $pSDKWrapper, $estateIds);
		}

		if ($pDataListView->getRandom()) {
			$this->_pEnvironment->shuffle($this->_records);
		}

		$this->_numEstatePages = $this->getNumEstatePages();
		$this->resetEstateIterator();
	}


	/**
	 *
	 * @param int $currentPage
	 *
	 */

	private function loadRecords(int $currentPage)
	{
		$estateParameters = $this->getEstateParameters($currentPage, $this->_formatOutput);
		$this->_pApiClientAction->setParameters($estateParameters);
		$this->_pApiClientAction->addRequestToQueue();

		$estateParametersRaw = $this->getEstateParameters($currentPage, false);
		$estateParametersRaw['data'] = $this->_pEnvironment->getEstateStatusLabel()->getFieldsByPrio();
		$estateParametersRaw['data'] []= 'vermarktungsart';
		$pApiClientActionRawValues = clone $this->_pApiClientAction;
		$pApiClientActionRawValues->setParameters($estateParametersRaw);
		$pApiClientActionRawValues->addRequestToQueue()->sendRequests();

		$this->_records = $this->_pApiClientAction->getResultRecords();
		$recordsRaw = $pApiClientActionRawValues->getResultRecords();
		$this->_recordsRaw = array_combine(array_column($recordsRaw, 'id'), $recordsRaw);
	}


	/**
	 *
	 * @param SDKWrapper $pSDKWrapper
	 * @param array $estateIds
	 *
	 */

	public function registerContactPersonCall(SDKWrapper $pSDKWrapper, array $estateIds)
	{
		$parameters = [
			'parentids' => array_keys($estateIds),
			'relationtype' => onOfficeSDK::RELATION_TYPE_CONTACT_BROKER,
		];

		$this->_handleEstateContactPerson = $pSDKWrapper->addRequest
			(onOfficeSDK::ACTION_ID_GET, 'idsfromrelation', $parameters);
	}


	/**
	 *
	 * @param SDKWrapper $pSDKWrapper
	 * @param array $estateIds
	 *
	 */

	public function extractEstateContactPerson(SDKWrapper $pSDKWrapper, array $estateIds)
	{
		$responseArrayContactPerson = $pSDKWrapper->getRequestResponse
			($this->_handleEstateContactPerson);
		$this->collectEstateContactPerson($responseArrayContactPerson, $estateIds);
	}


	/**
	 *
	 * @param int $currentPage
	 * @return array
	 *
	 */

	private function getEstateParameters(int $currentPage, bool $formatOutput)
	{
		$language = Language::getDefault();
		$pListView = $this->_pDataView;
		$filter = $this->_pEnvironment->getDefaultFilterBuilder()->buildFilter();

		$numRecordsPerPage = $this->getRecordsPerPage();

		$pFieldModifierHandler = new ViewFieldModifierHandler($pListView->getFields(),
			onOfficeSDK::MODULE_ESTATE);

		$requestParams = [
			'data' => $pFieldModifierHandler->getAllAPIFields(),
			'filter' => $filter,
			'estatelanguage' => $language,
			'outputlanguage' => $language,
			'listlimit' => $numRecordsPerPage,
			'formatoutput' => $formatOutput,
			'addMainLangId' => true,
		];

		if (!$pListView->getRandom()) {
			$offset = ( $currentPage - 1 ) * $numRecordsPerPage;
			$this->_currentEstatePage = $currentPage;
			$requestParams += [
				'listoffset' => $offset
			];
		}

		$requestParams += $this->addExtraParams();

		return $requestParams;
	}


	/**
	 *
	 * @return array
	 *
	 */

	protected function addExtraParams(): array
	{
		$pListView = $this->_pDataView;
		$requestParams = [];

		if ($pListView->getSortby() !== '' && !$this->_pDataView->getRandom()) {
			$requestParams['sortby'] = $pListView->getSortby();
		}

		if ($pListView->getSortorder() !== '') {
			$requestParams['sortorder'] = $pListView->getSortorder();
		}

		if ($pListView->getFilterId() !== 0) {
			$requestParams['filterid'] = $pListView->getFilterId();
		}

		// only do georange search if requested in listview configuration
		if ($pListView instanceof DataViewFilterableFields &&
			in_array(GeoPosition::FIELD_GEO_POSITION, $pListView->getFilterableFields(), true)) {
			$geoRangeSearchParameters = $this->getGeoSearchBuilder()->buildParameters();

			if ($geoRangeSearchParameters !== []) {
				$requestParams['georangesearch'] = $geoRangeSearchParameters;
			}
		}

		return $requestParams;
	}


	/**
	 *
	 * @param array $estateResponseArray
	 * @return array Mapping: mainEstateId => multiLangId
	 *
	 */

	private function getEstateIdToForeignMapping($estateResponseArray)
	{
		$estateIds = [];

		foreach ($estateResponseArray as $estate) {
			$elements = $estate['elements'];
			$estateMainId = $elements['mainLangId'] ?? $estate['id'];
			$estateIds[$estateMainId] = $estate['id'];
		}

		return $estateIds;
	}


	/**
	 *
	 * @param array $responseArrayContacts
	 * @param array $estateIds
	 *
	 */

	private function collectEstateContactPerson($responseArrayContacts, array $estateIds)
	{
		$records = $responseArrayContacts['data']['records'][0]['elements'] ?? [];
		$allAddressIds = [];

		foreach ($records as $estateId => $adressIds) {
			$subjectEstateId = $estateIds[$estateId];
			$this->_estateContacts[$subjectEstateId] = $adressIds;
			$allAddressIds = array_unique(array_merge($allAddressIds, $adressIds));
		}

		$fields = $this->_pDataView->getAddressFields();

		if ($fields !== [] && $allAddressIds !== []) {
			$this->_pEnvironment->getAddressList()->loadAdressesById($allAddressIds, $fields);
		}
	}


	/**
	 *
	 * @param string $modifier
	 * @return ArrayContainerEscape
	 *
	 */

	public function estateIterator($modifier = EstateViewFieldModifierTypes::MODIFIER_TYPE_DEFAULT)
	{
		global $numpages, $multipage, $page, $more;

		if (null !== $this->_numEstatePages &&
			!$this->_pDataView->getRandom()) {
			$multipage = true;

			$page = $this->_currentEstatePage;
			$more = true;
			$numpages = $this->_numEstatePages;
		}

		$pEstateFieldModifierHandler = $this->_pEnvironment->getViewFieldModifierHandler
			($this->_pDataView->getFields(), $modifier);

		$currentRecord = current($this->_records);
		next($this->_records);

		$this->_currentEstate['id'] = $currentRecord['id'];
		$recordElements = $currentRecord['elements'];
		$this->_currentEstate['mainId'] = $recordElements['mainLangId'] ??
			$this->_currentEstate['id'];

		if (false === $currentRecord) {
			return false;
		}

		$recordModified = $pEstateFieldModifierHandler->processRecord($currentRecord['elements']);
		$recordRaw = $this->_recordsRaw[$this->_currentEstate['id']]['elements'];

		if ($this->getShowEstateMarketingStatus()) {
			$pEstateStatusLabel = $this->_pEnvironment->getEstateStatusLabel();
			$recordModified['vermarktungsstatus'] = $pEstateStatusLabel->getLabel($recordRaw);
		}

		$pArrayContainer = new ArrayContainerEscape($recordModified);

		return $pArrayContainer;
	}


	/**
	 *
	 * @return int
	 *
	 */

	public function getEstateOverallCount()
	{
		return $this->_pApiClientAction->getResultMeta()['cntabsolute'];
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getFieldLabel($field): string
	{
		$recordType = onOfficeSDK::MODULE_ESTATE;
		$fieldNewName = $this->_pEnvironment->getFieldnames()->getFieldLabel($field, $recordType);

		return $fieldNewName;
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getEstateLink(): string
	{
		$pageId = $this->_pEnvironment->getDataDetailView()->getPageId();
		$fullLink = '#';

		if ($pageId !== 0) {
			$estate = $this->_currentEstate['mainId'];
			$fullLink = get_page_link($pageId).$estate;
		}

		return $fullLink;
	}


	/**
	 *
	 * @param array $types
	 * @return array
	 *
	 */

	public function	getEstatePictures(array $types = null)
	{
		$estateId = $this->_currentEstate['id'];
		$estateFiles = [];
		$estateImages = $this->_pEstateFiles->getEstatePictures($estateId);

		foreach ($estateImages as $image) {
			if (null !== $types && !in_array($image['type'], $types, true)) {
				continue;
			}
			$estateFiles []= $image['id'];
		}

		return $estateFiles;
	}


	/**
	 *
	 * Not supported in list view
	 * @return array Returns an array if Movie Links are active and displayed as Link
	 *
	 */

	public function getEstateMovieLinks(): array
	{
		return [];
	}


	/**
	 *
	 * Not supported in list view
	 * @param array $options
	 * @return array
	 *
	 */

	public function getMovieEmbedPlayers(array $options = []): array
	{
		return [];
	}


	/**
	 *
	 * @param int $imageId
	 * @param array $options
	 * @return string
	 *
	 */

	public function getEstatePictureUrl($imageId, array $options = null)
	{
		$currentEstate = $this->_currentEstate['id'];
		return $this->_pEstateFiles->getEstateFileUrl($imageId, $currentEstate, $options);
	}


	/**
	 *
	 * @param int $imageId
	 * @return string
	 *
	 */

	public function getEstatePictureTitle($imageId)
	{
		$currentEstate = $this->_currentEstate['id'];
		return $this->_pEstateFiles->getEstatePictureTitle($imageId, $currentEstate);
	}


	/**
	 *
	 * @param int $imageId
	 * @return string
	 *
	 */

	public function getEstatePictureText($imageId)
	{
		$currentEstate = $this->_currentEstate['id'];
		return $this->_pEstateFiles->getEstatePictureText($imageId, $currentEstate);
	}


	/**
	 *
	 * @param int $imageId
	 * @return array
	 *
	 */

	public function getEstatePictureValues($imageId)
	{
		$currentEstate = $this->_currentEstate['id'];
		return $this->_pEstateFiles->getEstatePictureValues($imageId, $currentEstate);
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getEstateContactIds(): array
	{
		$recordId = $this->_currentEstate['id'];
		return $this->_estateContacts[$recordId] ?? [];
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getEstateContacts()
	{
		$addressIds = $this->getEstateContactIds();
		$result = [];

		foreach ($addressIds as $addressId) {
			$currentAddressData = $this->_pEnvironment->getAddressList()->getAddressById($addressId);
			$pArrayContainerCurrentAddress = new ArrayContainerEscape($currentAddressData);
			$result []= $pArrayContainerCurrentAddress;
		}

		return $result;
	}


	/**
	 *
	 * @return int
	 *
	 */

	public function getCurrentEstateId(): int
	{
		return $this->_currentEstate['id'];
	}


	/**
	 *
	 * @return int
	 *
	 */

	public function getCurrentMultiLangEstateMainId()
	{
		return $this->_currentEstate['mainId'];
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getEstateUnits()
	{
		$estateId = $this->getCurrentMultiLangEstateMainId();
		$htmlOutput = '';

		if ($this->_unitsViewName != null) {
			$pEstateUnits = $this->_pEnvironment->getEstateUnitsByName($this->_unitsViewName);
			$pEstateUnits->loadByMainEstates($this);
			$unitCount = $pEstateUnits->getSubEstateCount($estateId);

			if ($unitCount > 0) {
				$htmlOutput = $pEstateUnits->generateHtmlOutput($estateId);
			}
		}

		return $htmlOutput;
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getDocument()
	{
		$queryVars = [
			'estateid' => $this->getCurrentMultiLangEstateMainId(),
			'language' => Language::getDefault(),
			'configindex' => $this->_pDataView->getName(),
		];

		$documentlink = plugin_dir_url(__DIR__).'document.php?'.http_build_query($queryVars);
		return esc_url($documentlink);
	}


	/**
	 *
	 * @return string[] An array of visible fields
	 *
	 */

	public function getVisibleFilterableFields(): array
	{
		$fieldsValues = $this->_pEnvironment
			->getOutputFields($this->_pDataView)
			->getVisibleFilterableFields();
		$result = [];
		foreach ($fieldsValues as $field => $value) {
			$result[$field] = $this->_pEnvironment
				->getFieldnames()
				->getFieldInformation($field, onOfficeSDK::MODULE_ESTATE);
			$result[$field]['value'] = $value;
		}
		return $result;
	}


	/**
	 *
	 */

	public function resetEstateIterator()
	{
		reset($this->_records);
	}


	/**
	 *
	 * @return bool
	 *
	 */

	public function getShowEstateMarketingStatus(): bool
	{
		return $this->_pDataView instanceof DataListView &&
			$this->_pDataView->getShowStatus();
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getEstateIds(): array
	{
		return array_column($this->_records, 'id');
	}


	/** @return EstateFiles */
	protected function getEstateFiles()
		{ return $this->_pEstateFiles; }

	/** @return DataView */
	public function getDataView(): DataView
		{ return $this->_pDataView; }

	/** @return DefaultFilterBuilder */
	public function getDefaultFilterBuilder(): DefaultFilterBuilder
		{ return $this->_pEnvironment->getDefaultFilterBuilder(); }

	/** @param DefaultFilterBuilder $pDefaultFilterBuilder */
	public function setDefaultFilterBuilder(DefaultFilterBuilder $pDefaultFilterBuilder)
		{ $this->_pEnvironment->setDefaultFilterBuilder($pDefaultFilterBuilder); }

	/** @return string */
	public function getUnitsViewName()
		{ return $this->_unitsViewName; }

	/** @param string $unitsViewName */
	public function setUnitsViewName($unitsViewName)
		{ $this->_unitsViewName = $unitsViewName; }

	/** @return GeoSearchBuilder */
	public function getGeoSearchBuilder(): GeoSearchBuilder
		{ return $this->_pGeoSearchBuilder; }

	/** @param GeoSearchBuilder $pGeoSearchBuilder */
	public function setGeoSearchBuilder(GeoSearchBuilder $pGeoSearchBuilder)
		{ $this->_pGeoSearchBuilder = $pGeoSearchBuilder; }

	/** @return bool */
	public function getFormatOutput(): bool
		{ return $this->_formatOutput; }

	/** @param bool $formatOutput */
	public function setFormatOutput(bool $formatOutput)
		{ $this->_formatOutput = $formatOutput; }

	/** @return EstateListEnvironment */
	public function getEnvironment(): EstateListEnvironment
		{ return $this->_pEnvironment; }
}