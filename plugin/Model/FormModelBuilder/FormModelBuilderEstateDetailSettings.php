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

namespace onOffice\WPlugin\Model\FormModelBuilder;

use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Controller\Exception\UnknownModuleException;
use onOffice\WPlugin\DataView\DataDetailView;
use onOffice\WPlugin\DataView\DataDetailViewHandler;
use onOffice\WPlugin\DataView\DataListView;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorGeoPositionBackend;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorInternalAnnotations;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorReadAddress;
use onOffice\WPlugin\Fieldnames;
use onOffice\WPlugin\Model\FormModel;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactory;
use onOffice\WPlugin\Model\InputModel\InputModelOptionFactoryDetailView;
use onOffice\WPlugin\Model\InputModelBase;
use onOffice\WPlugin\Model\InputModelDB;
use onOffice\WPlugin\Model\InputModelOption;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\ImageTypes;
use onOffice\WPlugin\Types\MovieLinkTypes;
use function __;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 * This class must not use InputModelDB!
 *
 */

class FormModelBuilderEstateDetailSettings
	extends FormModelBuilder
{
	/** @var InputModelOptionFactoryDetailView */
	private $_pInputModelDetailViewFactory = null;

	/** @var DataDetailView */
	private $_pDataDetailView = null;


	/**
	 *
	 */

	public function __construct()
	{
		$pFieldCollection = new FieldModuleCollectionDecoratorInternalAnnotations
			(new FieldModuleCollectionDecoratorReadAddress
				(new FieldModuleCollectionDecoratorGeoPositionBackend(new FieldsCollection())));
		$pFieldnames = new Fieldnames($pFieldCollection);
		$pFieldnames->loadLanguage();
		$this->setFieldnames($pFieldnames);
	}


	/**
	 *
	 * @return FormModel
	 *
	 */

	public function generate(string $pageSlug): FormModel
	{
		$this->_pInputModelDetailViewFactory = new InputModelOptionFactoryDetailView($pageSlug);
		$pDataDetailViewHandler = new DataDetailViewHandler();
		$this->_pDataDetailView = $pDataDetailViewHandler->getDetailView();

		$pFormModel = new FormModel();
		$pFormModel->setLabel(__('Detail View', 'onoffice'));
		$pFormModel->setGroupSlug('onoffice-detailview-settings-main');
		$pFormModel->setPageSlug($pageSlug);

		return $pFormModel;
	}


	/**
	 *
	 * @return InputModelDB
	 *
	 */

	public function getCheckboxEnableSimilarEstates()
	{
		$labelExpose = __('Show Similar Estates', 'onoffice');
		$pInputModelActivate = $this->_pInputModelDetailViewFactory->create
			(InputModelOptionFactoryDetailView::INPUT_FIELD_ENABLE_SIMILAR_ESTATES, $labelExpose);
		$pInputModelActivate->setHtmlType(InputModelOption::HTML_TYPE_CHECKBOX);
		$pInputModelActivate->setValuesAvailable(1);
		$pInputModelActivate->setValue($this->_pDataDetailView->getDataDetailViewActive());

		return $pInputModelActivate;
	}


	/**
	 *
	 * @return InputModelDB
	 *
	 */

	public function createInputModelPictureTypes()
	{
		$allPictureTypes = ImageTypes::getAllImageTypes();

		$pInputModelPictureTypes = $this->_pInputModelDetailViewFactory->create
			(InputModelOptionFactoryDetailView::INPUT_PICTURE_TYPE, null, true);
		$pInputModelPictureTypes->setHtmlType(InputModelOption::HTML_TYPE_CHECKBOX);
		$pInputModelPictureTypes->setValuesAvailable($allPictureTypes);
		$pictureTypes = $this->_pDataDetailView->getPictureTypes();

		if (null == $pictureTypes)
		{
			$pictureTypes = array();
		}

		$pInputModelPictureTypes->setValue($pictureTypes);

		return $pInputModelPictureTypes;
	}


	/**
	 *
	 * @return InputModelDB
	 *
	 */

	public function createInputModelExpose()
	{
		$labelExpose = __('PDF-Expose', 'onoffice');

		$pInputModelExpose = $this->_pInputModelDetailViewFactory->create
			(InputModelOptionFactoryDetailView::INPUT_EXPOSE, $labelExpose);
		$pInputModelExpose->setHtmlType(InputModelOption::HTML_TYPE_SELECT);
		$exposes = array('' => '') + $this->readExposes();
		$pInputModelExpose->setValuesAvailable($exposes);
		$pInputModelExpose->setValue($this->_pDataDetailView->getExpose());

		return $pInputModelExpose;
	}


	/**
	 *
	 * @return InputModelDB
	 *
	 */

	public function createInputModelMovieLinks()
	{
		$labelMovieLinks = __('Movie Links', 'onoffice');

		$pInputModelMedia = $this->_pInputModelDetailViewFactory->create
			(InputModelOptionFactoryDetailView::INPUT_MOVIE_LINKS, $labelMovieLinks);
		$pInputModelMedia->setHtmlType(InputModelOption::HTML_TYPE_SELECT);
		$options = array(
			MovieLinkTypes::MOVIE_LINKS_NONE => __('Disabled', 'onoffice'),
			MovieLinkTypes::MOVIE_LINKS_LINK => __('Link', 'onoffice'),
			MovieLinkTypes::MOVIE_LINKS_PLAYER => __('Player', 'onoffice'),
		);
		$pInputModelMedia->setValuesAvailable($options);
		$pInputModelMedia->setValue($this->_pDataDetailView->getMovieLinks());

		return $pInputModelMedia;
	}


	/**
	 *
	 * @param string $category
	 * @param array $fieldNames
	 * @param string $categoryLabel
	 * @return InputModelDB
	 *
	 */

	public function createInputModelFieldsConfigByCategory($category, $fieldNames, $categoryLabel)
	{
		$pInputModelFieldsConfig = new InputModelOption
			(null, $category, null, InputModelDBFactory::INPUT_FIELD_CONFIG);
		$pInputModelFieldsConfig->setIsMulti(true);

		$pInputModelFieldsConfig->setHtmlType(InputModelBase::HTML_TYPE_CHECKBOX_BUTTON);
		$pInputModelFieldsConfig->setValuesAvailable($fieldNames);
		$pInputModelFieldsConfig->setId($category);
		$pInputModelFieldsConfig->setLabel($categoryLabel);
		$fields = $this->getValue(DataListView::FIELDS);

		if (null == $fields)
		{
			$fields = array();
		}

		$pInputModelFieldsConfig->setValue($fields);

		return $pInputModelFieldsConfig;
	}


	/**
	 *
	 * @return InputModelDB
	 *
	 */

	public function createSortableFieldList($module, $htmlType)
	{
		$fields = [];

		if ($module == onOfficeSDK::MODULE_ESTATE) {
			$pInputModelFieldsConfig = $this->_pInputModelDetailViewFactory->create
				(InputModelOptionFactoryDetailView::INPUT_FIELD_CONFIG, null, true);
			$fields = $this->_pDataDetailView->getFields();
		} elseif ($module == onOfficeSDK::MODULE_ADDRESS) {
			$pInputModelFieldsConfig = $this->_pInputModelDetailViewFactory->create
				(InputModelOptionFactoryDetailView::INPUT_FIELD_CONTACTDATA_ONLY, null, true);
			$fields = $this->_pDataDetailView->getAddressFields();
		} else {
			throw new UnknownModuleException();
		}

		$pInputModelFieldsConfig->setHtmlType($htmlType);
		$fieldNames = $this->getFieldnames()->getFieldList($module);
		$pInputModelFieldsConfig->setValuesAvailable($fieldNames);
		$pInputModelFieldsConfig->setValue($fields);
		return $pInputModelFieldsConfig;
	}


	/**
	 *
	 * @return InputModelDB
	 *
	 */

	public function createInputModelAddressFieldsConfig()
	{
		$pInputModelFieldsConfig = $this->_pInputModelDetailViewFactory->create(
			InputModelOptionFactoryDetailView::INPUT_FIELD_CONTACTDATA_ONLY, null, true);

		$fieldNames = $this->readFieldnames(onOfficeSDK::MODULE_ADDRESS);
		$pInputModelFieldsConfig->setHtmlType(InputModelOption::HTML_TYPE_COMPLEX_SORTABLE_CHECKBOX_LIST);
		$pInputModelFieldsConfig->setValuesAvailable($fieldNames);
		$fields = $this->_pDataDetailView->getAddressFields();
		$pInputModelFieldsConfig->setValue($fields);

		return $pInputModelFieldsConfig;
	}


	/**
	 *
	 * @param string $field
	 * @return InputModelDB
	 *
	 */

	public function createInputModelTemplate(string $field = InputModelOptionFactoryDetailView::INPUT_TEMPLATE)
	{
		$labelTemplate = __('Template', 'onoffice');

		$pInputModelTemplate = $this->_pInputModelDetailViewFactory->create($field, $labelTemplate);
		$pInputModelTemplate->setHtmlType(InputModelOption::HTML_TYPE_SELECT);
		$pInputModelTemplate->setValuesAvailable($this->readTemplatePaths('estate'));
		$pInputModelTemplate->setValue($this->getTemplateValueByField($field));

		return $pInputModelTemplate;
	}


	/**
	 *
	 * @param string $field
	 * @return string
	 *
	 */

	private function getTemplateValueByField(string $field): string
	{
		switch ($field) {
			case InputModelOptionFactoryDetailView::INPUT_TEMPLATE:
				return $this->_pDataDetailView->getTemplate();
			case InputModelOptionFactoryDetailView::INPUT_FIELD_SIMILAR_ESTATES_TEMPLATE:
				return $this->_pDataDetailView->getDataViewSimilarEstates()->getTemplate();
			default:
				return '';
		}
	}


	/**
	 *
	 * @return InputModelDB
	 *
	 */

	public function createInputModelSimilarEstateKind()
	{
		$pDataViewSimilarEstates = $this->_pDataDetailView->getDataViewSimilarEstates();

		$labelSameKind = __('Same Kind of Estate', 'onoffice');

		$pInputModelSimilarEstateKind = $this->_pInputModelDetailViewFactory->create
			(InputModelOptionFactoryDetailView::INPUT_FIELD_SIMILAR_ESTATES_SAME_KIND, $labelSameKind);
		$pInputModelSimilarEstateKind->setHtmlType(InputModelOption::HTML_TYPE_CHECKBOX);

		$pInputModelSimilarEstateKind->setValuesAvailable(1);
		$pInputModelSimilarEstateKind->setValue($pDataViewSimilarEstates->getSameEstateKind());

		return $pInputModelSimilarEstateKind;
	}


	/**
	 *
	 * @return InputModelDB
	 *
	 */

	public function createInputModelSimilarEstateMarketingMethod()
	{
		$pDataViewSimilarEstates = $this->_pDataDetailView->getDataViewSimilarEstates();

		$labelSameMarketingMethod = __('Same Marketing Method', 'onoffice');

		$pInputModelSameMarketingMethod = $this->_pInputModelDetailViewFactory->create
			(InputModelOptionFactoryDetailView::INPUT_FIELD_SIMILAR_ESTATES_SAME_MARKETING_METHOD, $labelSameMarketingMethod);
		$pInputModelSameMarketingMethod->setHtmlType(InputModelOption::HTML_TYPE_CHECKBOX);

		$pInputModelSameMarketingMethod->setValuesAvailable(1);
		$pInputModelSameMarketingMethod->setValue($pDataViewSimilarEstates->getSameMarketingMethod());

		return $pInputModelSameMarketingMethod;
	}


	/**
	 *
	 * @return InputModelDB
	 *
	 */

	public function createInputModelSameEstatePostalCode()
	{
		$pDataViewSimilarEstates = $this->_pDataDetailView->getDataViewSimilarEstates();

		$labelSamePostalCode = __('Same Postal Code', 'onoffice');

		$pInputModelSamePostalCode = $this->_pInputModelDetailViewFactory->create
			(InputModelOptionFactoryDetailView::INPUT_FIELD_SIMILAR_ESTATES_SAME_POSTAL_CODE, $labelSamePostalCode);
		$pInputModelSamePostalCode->setHtmlType(InputModelOption::HTML_TYPE_CHECKBOX);

		$pInputModelSamePostalCode->setValuesAvailable(1);
		$pInputModelSamePostalCode->setValue($pDataViewSimilarEstates->getSamePostalCode());

		return $pInputModelSamePostalCode;
	}


	/**
	 *
	 * @return InputModelDB
	 *
	 */

	public function createInputModelSameEstateRadius()
	{
		$pDataViewSimilarEstates = $this->_pDataDetailView->getDataViewSimilarEstates();

		$labelRadius = __('Radius', 'onoffice');

		$pInputModelRadius = $this->_pInputModelDetailViewFactory->create
			(InputModelOptionFactoryDetailView::INPUT_FIELD_SIMILAR_ESTATES_RADIUS, $labelRadius);
		$pInputModelRadius->setHtmlType(InputModelOption::HTML_TYPE_TEXT);

		$pInputModelRadius->setValuesAvailable(1);
		$pInputModelRadius->setValue($pDataViewSimilarEstates->getRadius());

		return $pInputModelRadius;
	}


	/**
	 *
	 * @return InputModelDB
	 *
	 */

	public function createInputModelSameEstateAmount()
	{
		$pDataViewSimilarEstates = $this->_pDataDetailView->getDataViewSimilarEstates();

		$labelAmount = __('Amount of Estates', 'onoffice');

		$pInputModelAmount = $this->_pInputModelDetailViewFactory->create
			(InputModelOptionFactoryDetailView::INPUT_FIELD_SIMILAR_ESTATES_AMOUNT, $labelAmount);
		$pInputModelAmount->setHtmlType(InputModelOption::HTML_TYPE_TEXT);

		$pInputModelAmount->setValuesAvailable(1);
		$pInputModelAmount->setValue($pDataViewSimilarEstates->getRecordsPerPage());

		return $pInputModelAmount;
	}
}
