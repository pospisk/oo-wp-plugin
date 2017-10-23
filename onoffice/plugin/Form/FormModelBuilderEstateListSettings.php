<?php

/**
 *
 *    Copyright (C) 2017 onOffice GmbH
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

namespace onOffice\WPlugin\Form;

use onOffice\WPlugin\Model;
use onOffice\WPlugin\FilterCall;
use onOffice\WPlugin\Record\RecordManagerReadListView;
use onOffice\WPlugin\Model\InputModel\ListView\InputModelDBFactory;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class FormModelBuilderEstateListSettings
	extends FormModelBuilder
{
	/** @var array */
	private $_dbValues = array();

	/** @var InputModelDBFactory */
	private $_pInputModelDBFactory = null;


	/**
	 *
	 * @param int $listViewId
	 * @return \onOffice\WPlugin\Model\FormModel
	 *
	 */

	public function generate($listViewId = null)
	{
		$this->_pInputModelDBFactory = new InputModelDBFactory();

		if ($listViewId !== null)
		{
			$pRecordReadManager = new \onOffice\WPlugin\Record\RecordManagerReadListView();
			$this->_dbValues = $pRecordReadManager->getRowById($listViewId);
		}

		$pInputModelName = $this->createInputModelName();
		$pInputModelFiltername =  $this->createInputModelFilter();
		$pInputModelSortBy = $this->createInputModelSortBy();
		$pInputModelSortOrder = $this->createInputModelSortOrder();
		$pInputModelRecordsPerPage = $this->createInputModelRecordsPerPage();
		$pInputModelShowStatus = $this->createInputModelShowStatus();
		$pInputModelIsReference = $this->createInputModelIsReference();
		$pInputModelTemplate = $this->createInputModelTemplate();
		$pInputModelExpose = $this->createInputModelExpose();
		$pInputModelPictureTypes = $this->createInputModelPictureTypes();

		$pFormModel = new Model\FormModel();
		$pFormModel->setLabel(__('list view', 'onoffice'));
		$pFormModel->addInputModel($pInputModelName);
		$pFormModel->addInputModel($pInputModelFiltername);
		$pFormModel->addInputModel($pInputModelSortBy);
		$pFormModel->addInputModel($pInputModelSortOrder);
		$pFormModel->addInputModel($pInputModelRecordsPerPage);
		$pFormModel->addInputModel($pInputModelShowStatus);
		$pFormModel->addInputModel($pInputModelIsReference);
		$pFormModel->addInputModel($pInputModelTemplate);
		$pFormModel->addInputModel($pInputModelExpose);
		$pFormModel->addInputModel($pInputModelPictureTypes);
		$pFormModel->setGroupSlug('onoffice-listview-settings');
		$pFormModel->setPageSlug($this->getPageSlug());

		return $pFormModel;
	}



	/**
	 *
	 * @return Model\InputModelDB
	 *
	 */

	private function createInputModelFilter()
	{
		$labelFiltername = __('filter name', 'onoffice');
		$pInputModelFiltername = $this->_pInputModelDBFactory->create
			(InputModelDBFactory::INPUT_FILTERID, $labelFiltername);
		$pInputModelFiltername->setHtmlType(Model\InputModelOption::HTML_TYPE_SELECT);

		$availableFilters = array(0 => '') + $this->readFilters();

		$pInputModelFiltername->setValuesAvailable($availableFilters);
		$filteridSelected = $this->getValue($pInputModelFiltername->getField());
		$pInputModelFiltername->setValue($filteridSelected);

		return $pInputModelFiltername;
	}


	/**
	 *
	 * @return Model\InputModelDB
	 *
	 */

	private function createInputModelIsReference()
	{
		$labelIsReference = __('detail view for reference estates', 'onoffice');
		$pInputModelIsReference = $this->_pInputModelDBFactory->create
			(InputModelDBFactory::INPUT_IS_REFERENCE, $labelIsReference);
		$pInputModelIsReference->setHtmlType(Model\InputModelOption::HTML_TYPE_CHECKBOX);
		$pInputModelIsReference->setValue(array($this->_dbValues['is_reference']));
		$pInputModelIsReference->setValuesAvailable(1);

		return $pInputModelIsReference;
	}


	/**
	 *
	 * @return Model\InputModelDB
	 *
	 */

	private function createInputModelSortBy()
	{
		$labelSortBy = __('sort by', 'onoffice');

		$pInputModelSortBy = $this->_pInputModelDBFactory->create
			(InputModelDBFactory::INPUT_SORTBY, $labelSortBy);
		$pInputModelSortBy->setHtmlType(Model\InputModelOption::HTML_TYPE_SELECT);
		$pInputModelSortBy->setValuesAvailable(array());

		return $pInputModelSortBy;
	}


	/**
	 *
	 * @return Model\InputModelDB
	 *
	 */

	private function createInputModelRecordsPerPage()
	{
		$labelRecordsPerPage = __('records per page', 'onoffice');
		$pInputModelRecordsPerPage = $this->_pInputModelDBFactory->create
			(InputModelDBFactory::INPUT_RECORDS_PER_PAGE, $labelRecordsPerPage);
		$pInputModelRecordsPerPage->setHtmlType(Model\InputModelOption::HTML_TYPE_SELECT);
		$pInputModelRecordsPerPage->setValuesAvailable(array('5' => '5', '10' => '10', '15' => '15'));
		$pInputModelRecordsPerPage->setValue($this->getValue('recordsPerPage'));

		return $pInputModelRecordsPerPage;
	}


	/**
	 *
	 * @return Model\InputModelDB
	 *
	 */

	private function createInputModelSortOrder()
	{
		$labelSortOrder = __('sort order', 'onoffice');
		$pInputModelSortOrder = $this->_pInputModelDBFactory->create
			(InputModelDBFactory::INPUT_SORTORDER, $labelSortOrder);
		$pInputModelSortOrder->setHtmlType(Model\InputModelOption::HTML_TYPE_SELECT);
		$pInputModelSortOrder->setValuesAvailable(array('asc' => __('ascending', 'onoffice'), 'desc' => __('descending', 'onoffice')));
		$pInputModelSortOrder->setValue($this->getValue('sortorder'));

		return $pInputModelSortOrder;
	}


	/**
	 *
	 * @return Model\InputModelDB
	 *
	 */

	private function createInputModelShowStatus()
	{
		$labelShowStatus = __('show estate status', 'onoffice');

		$pInputModelShowStatus = $this->_pInputModelDBFactory->create
			(InputModelDBFactory::INPUT_SHOW_STATUS, $labelShowStatus);
		$pInputModelShowStatus->setHtmlType(Model\InputModelOption::HTML_TYPE_CHECKBOX);
		$pInputModelShowStatus->setValue($this->getValue('show_status'));
		$pInputModelShowStatus->setValuesAvailable(1);

		return $pInputModelShowStatus;
	}


	/**
	 *
	 * @return Model\InputModelDB
	 *
	 */

	private function createInputModelName()
	{
		$labelName = __('view name', 'onoffice');

		$pInputModelName = $this->_pInputModelDBFactory->create
			(InputModelDBFactory::INPUT_LISTNAME, $labelName);
		$pInputModelName->setHtmlType(Model\InputModelOption::HTML_TYPE_TEXT);
		$pInputModelName->setValue($this->getValue($pInputModelName->getField()));

		return $pInputModelName;
	}


	/**
	 *
	 * @return Model\InputModelDB
	 *
	 */

	private function createInputModelPictureTypes()
	{
		$allPictureTypes = \onOffice\WPlugin\ImageType::getAllImageTypes();
		$labelPictureTypes = __('picture types', 'onoffice');

		$pInputModelPictureTypes = $this->_pInputModelDBFactory->create
			(InputModelDBFactory::INPUT_PICTURE_TYPE, $labelPictureTypes, true);
		$pInputModelPictureTypes->setHtmlType(Model\InputModelOption::HTML_TYPE_CHECKBOX);
		$pInputModelPictureTypes->setValuesAvailable($allPictureTypes);
		$pictureTypes = $this->getValue(RecordManagerReadListView::PICTURES);
		$pInputModelPictureTypes->setValue($pictureTypes);

		return $pInputModelPictureTypes;
	}


	/**
	 *
	 * @return Model\InputModelDB
	 *
	 */

	private function createInputModelExpose()
	{
		$labelExpose = __('PDF-Expose', 'onoffice');

		$pInputModelPictureTypes = $this->_pInputModelDBFactory->create
			(InputModelDBFactory::INPUT_EXPOSE, $labelExpose);
		$pInputModelPictureTypes->setHtmlType(Model\InputModelOption::HTML_TYPE_SELECT);
		$pInputModelPictureTypes->setValuesAvailable(array());
		$pInputModelPictureTypes->setValue(0);

		return $pInputModelPictureTypes;
	}


	/**
	 *
	 * @return Model\InputModelDB
	 *
	 */

	private function createInputModelTemplate()
	{
		$labelTemplate = __('template', 'onoffice');
		$selectedTemplate = $this->getValue('template');

		$pInputModelTemplate = $this->_pInputModelDBFactory->create
			(InputModelDBFactory::INPUT_TEMPLATE, $labelTemplate);
		$pInputModelTemplate->setHtmlType(Model\InputModelOption::HTML_TYPE_SELECT);

		$pInputModelTemplate->setValuesAvailable($this->readTemplates());
		$pInputModelTemplate->setValue($selectedTemplate);

		return $pInputModelTemplate;
	}


	/**
	 *
	 * @return array
	 *
	 */

	private function readTemplates()
	{
		$templateGlobFiles = glob(plugin_dir_path(ONOFFICE_PLUGIN_DIR).'onoffice/templates.dist/estate/*.php');
		$templateLocalFiles = glob(plugin_dir_path(ONOFFICE_PLUGIN_DIR).'onoffice-personalized/templates/estate/*.php');
		$templatesAll = array_merge($templateGlobFiles, $templateLocalFiles);
		$templates = array();

		foreach ($templatesAll as $value)
		{
			$value = str_replace(plugin_dir_path(ONOFFICE_PLUGIN_DIR), '', $value);
			$templates[$value] = $value;
		}

		return $templates;
	}


	/**
	 *
	 * @return array
	 *
	 */

	private function readFilters()
	{
		$pFilterCall = new FilterCall(\onOffice\SDK\onOfficeSDK::MODULE_ESTATE);
		return $pFilterCall->getFilters();
	}


	/**
	 *
	 * @param string $key
	 * @return mixed
	 *
	 */

	private function getValue($key)
	{
		if (array_key_exists($key, $this->_dbValues))
		{
			return $this->_dbValues[$key];
		}

		return null;
	}
}
