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

namespace onOffice\WPlugin\Gui;

use onOffice\WPlugin\Model;
use onOffice\WPlugin\Record\RecordManager;
use onOffice\WPlugin\DataView\DataListViewFactory;
use onOffice\WPlugin\DataView\UnknownViewException;
use onOffice\WPlugin\Record\RecordManagerReadListView;
use onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilderEstateUnitListSettings;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class AdminPageEstateUnitSettings
	extends AdminPageEstateListSettingsBase
{
	/**
	 *
	 * @param string $pageSlug
	 *
	 */

	public function __construct($pageSlug)
	{
		parent::__construct($pageSlug);
		$this->setPageTitle(__('Edit Units View', 'onoffice'));
	}


	/**
	 *
	 */

	protected function buildForms()
	{
		add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
		$pFormModelBuilder = new FormModelBuilderEstateUnitListSettings($this->getPageSlug());
		$pFormModel = $pFormModelBuilder->generate($this->getListViewId());
		$this->addFormModel($pFormModel);

		$pInputModelName = $pFormModelBuilder->createInputModelName();
		$pFormModelName = new Model\FormModel();
		$pFormModelName->setPageSlug($this->getPageSlug());
		$pFormModelName->setGroupSlug(self::FORM_VIEW_NAME);
		$pFormModelName->setLabel(__('choose name', 'onoffice'));
		$pFormModelName->addInputModel($pInputModelName);
		$this->addFormModel($pFormModelName);

		$pInputModelRecords = $pFormModelBuilder->createInputModelRecordsPerPage();
		$pInputModelRandomOrder = $pFormModelBuilder->createInputModelRandomOrder();
		$pFormModelRecords = new Model\FormModel();
		$pFormModelRecords->setPageSlug($this->getPageSlug());
		$pFormModelRecords->setGroupSlug(self::FORM_VIEW_RECORDS_FILTER);
		$pFormModelRecords->setLabel(__('Records', 'onoffice'));
		$pFormModelRecords->addInputModel($pInputModelRecords);
		$pFormModelRecords->addInputModel($pInputModelRandomOrder);
		$this->addFormModel($pFormModelRecords);

		$pInputModelTemplate = $pFormModelBuilder->createInputModelTemplate();
		$pFormModelLayoutDesign = new Model\FormModel();
		$pFormModelLayoutDesign->setPageSlug($this->getPageSlug());
		$pFormModelLayoutDesign->setGroupSlug(self::FORM_VIEW_LAYOUT_DESIGN);
		$pFormModelLayoutDesign->setLabel(__('Layout & Design', 'onoffice'));
		$pFormModelLayoutDesign->addInputModel($pInputModelTemplate);
		$this->addFormModel($pFormModelLayoutDesign);

		$pInputModelPictureTypes = $pFormModelBuilder->createInputModelPictureTypes();
		$pFormModelPictureTypes = new Model\FormModel();
		$pFormModelPictureTypes->setPageSlug($this->getPageSlug());
		$pFormModelPictureTypes->setGroupSlug(self::FORM_VIEW_PICTURE_TYPES);
		$pFormModelPictureTypes->setLabel(__('Photo Types', 'onoffice'));
		$pFormModelPictureTypes->addInputModel($pInputModelPictureTypes);
		$this->addFormModel($pFormModelPictureTypes);

		$pInputModelDocumentTypes = $pFormModelBuilder->createInputModelExpose();
		$pFormModelDocumentTypes = new Model\FormModel();
		$pFormModelDocumentTypes->setPageSlug($this->getPageSlug());
		$pFormModelDocumentTypes->setGroupSlug(self::FORM_VIEW_DOCUMENT_TYPES);
		$pFormModelDocumentTypes->setLabel(__('Downloadable Documents', 'onoffice'));
		$pFormModelDocumentTypes->addInputModel($pInputModelDocumentTypes);
		$this->addFormModel($pFormModelDocumentTypes);

		$pInputModelFieldsConfig = $pFormModelBuilder->createInputModelFieldsConfig();
		$pFormModelFieldsConfig = new Model\FormModel();
		$pFormModelFieldsConfig->setPageSlug($this->getPageSlug());
		$pFormModelFieldsConfig->setGroupSlug(self::FORM_VIEW_FIELDS_CONFIG);
		$pFormModelFieldsConfig->setLabel(__('Fields Configuration', 'onoffice'));
		$pFormModelFieldsConfig->addInputModel($pInputModelFieldsConfig);
		$this->addFormModel($pFormModelFieldsConfig);
	}


	/**
	 *
	 */

	protected function generateMetaBoxes()
	{
		$pFormPictureTypes = $this->getFormModelByGroupSlug(self::FORM_VIEW_PICTURE_TYPES);
		$this->createMetaBoxByForm($pFormPictureTypes, 'side');

		$pFormLayoutDesign = $this->getFormModelByGroupSlug(self::FORM_VIEW_LAYOUT_DESIGN);
		$this->createMetaBoxByForm($pFormLayoutDesign, 'side');

		$pFormDocumentTypes = $this->getFormModelByGroupSlug(self::FORM_VIEW_DOCUMENT_TYPES);
		$this->createMetaBoxByForm($pFormDocumentTypes, 'normal');

		$pFormFieldsConfig = $this->getFormModelByGroupSlug(self::FORM_VIEW_FIELDS_CONFIG);
		$this->createMetaBoxByForm($pFormFieldsConfig, 'side');

		$pFormRecords = $this->getFormModelByGroupSlug(self::FORM_VIEW_RECORDS_FILTER);
		$this->createMetaBoxByForm($pFormRecords, 'side');
	}


	/**
	 *
	 * @param int $recordId
	 * @throws UnknownViewException
	 *
	 */

	protected function validate($recordId = null)
	{
		if ($recordId == null) {
			return;
		}

		$pRecordReadManager = new RecordManagerReadListView();
		$values = $pRecordReadManager->getRowById($recordId);
		$pFactory = new DataListViewFactory();
		$pDataListView = $pFactory->createListViewByRow($values);

		if ($pDataListView->getListType() !== 'units') {
			throw new UnknownViewException;
		}
	}


	/**
	 *
	 * @param array $row
	 * @return array
	 *
	 */

	protected function setFixedValues(array $row)
	{
		$row[RecordManager::TABLENAME_LIST_VIEW]['list_type'] = 'units';
		return $row;
	}
}
