<?php

/**
 *
 *    Copyright (C) 2020 onOffice GmbH
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

declare (strict_types=1);

namespace onOffice\WPlugin\Controller\ContentFilter;

use DI\DependencyException;
use DI\NotFoundException;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Controller\SearchParametersModelBuilderEstate;
use onOffice\WPlugin\Controller\SortList\SortListBuilder;
use onOffice\WPlugin\Controller\SortList\SortListDataModel;
use onOffice\WPlugin\DataView\DataListView;
use onOffice\WPlugin\DataView\DataListViewFactory;
use onOffice\WPlugin\DataView\UnknownViewException;
use onOffice\WPlugin\Factory\EstateListFactory;
use onOffice\WPlugin\Field\DistinctFieldsScriptRegistrator;
use onOffice\WPlugin\Field\UnknownFieldException;
use onOffice\WPlugin\Filter\DefaultFilterBuilderFactory;
use onOffice\WPlugin\Filter\GeoSearchBuilderFromInputVars;
use onOffice\WPlugin\Filter\SearchParameters\SearchParameters;
use onOffice\WPlugin\Template;
use onOffice\WPlugin\WP\WPQueryWrapper;

class ContentFilterShortCodeEstateList
{
	/** @var DataListViewFactory */
	private $_pDataListViewFactory;

	/** @var WPQueryWrapper */
	private $_pWPQueryWrapper;

	/** @var SortListBuilder */
	private $_pSortListBuilder;

	/** @var SearchParametersModelBuilderEstate */
	private $_pSearchParametersModelBuilderEstate;

	/** @var DistinctFieldsScriptRegistrator */
	private $_pDistinctFieldsScriptRegistrator;

	/** @var DefaultFilterBuilderFactory */
	private $_pDefaultFilterBuilderFactory;

	/** @var EstateListFactory */
	private $_pEstateDetailFactory;

	/** @var Template */
	private $_pTemplate;

	/** @var SearchParameters */
	private $_pSearchParameters;

	/**
	 * @param DataListViewFactory $pDataListViewFactory
	 * @param WPQueryWrapper $pWPQueryWrapper
	 * @param SortListBuilder $pSortListBuilder
	 * @param SearchParametersModelBuilderEstate $pSearchParametersModelBuilderEstate
	 * @param DistinctFieldsScriptRegistrator $pDistinctFieldsScriptRegistrator
	 * @param DefaultFilterBuilderFactory $pDefaultFilterBuilderFactory
	 * @param EstateListFactory $pEstateDetailFactory
	 * @param Template $pTemplate
	 * @param SearchParameters $pSearchParameters
	 */
	public function __construct(
		DataListViewFactory $pDataListViewFactory,
		WPQueryWrapper $pWPQueryWrapper,
		SortListBuilder $pSortListBuilder,
		SearchParametersModelBuilderEstate $pSearchParametersModelBuilderEstate,
		DistinctFieldsScriptRegistrator $pDistinctFieldsScriptRegistrator,
		DefaultFilterBuilderFactory $pDefaultFilterBuilderFactory,
		EstateListFactory $pEstateDetailFactory,
		Template $pTemplate,
		SearchParameters $pSearchParameters)
	{
		$this->_pDataListViewFactory = $pDataListViewFactory;
		$this->_pWPQueryWrapper = $pWPQueryWrapper;
		$this->_pSortListBuilder = $pSortListBuilder;
		$this->_pSearchParametersModelBuilderEstate = $pSearchParametersModelBuilderEstate;
		$this->_pDistinctFieldsScriptRegistrator = $pDistinctFieldsScriptRegistrator;
		$this->_pDefaultFilterBuilderFactory = $pDefaultFilterBuilderFactory;
		$this->_pEstateDetailFactory = $pEstateDetailFactory;
		$this->_pTemplate = $pTemplate;
		$this->_pSearchParameters = $pSearchParameters;
	}

	/**
	 * @param array $attributes
	 * @return string
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws UnknownFieldException
	 * @throws UnknownViewException
	 */
	public function render(array $attributes): string
	{
		$pListView = $this->_pDataListViewFactory->getListViewByName($attributes['view']);
		$result = '';

		if (is_object($pListView) && $pListView->getName() === $attributes['view']) {
			$pSortListModel = $this->_pSortListBuilder->build($pListView);
			$pListViewWithSortParams = $this->listViewWithSortParams($pListView, $pSortListModel);

			$this->registerNewPageLinkArgs($pListViewWithSortParams, $pSortListModel);
			$pListViewFilterBuilder = $this->_pDefaultFilterBuilderFactory
				->buildDefaultListViewFilter($pListViewWithSortParams);
			$availableOptionsEstates = $pListViewWithSortParams->getAvailableOptions();
			$this->_pDistinctFieldsScriptRegistrator->registerScripts
				(onOfficeSDK::MODULE_ESTATE, $availableOptionsEstates);

			$pGeoSearchBuilder = new GeoSearchBuilderFromInputVars();
			$pGeoSearchBuilder->setViewProperty($pListViewWithSortParams);

			$pEstateList = $this->_pEstateDetailFactory->createEstateList($pListViewWithSortParams);
			$pEstateList->setDefaultFilterBuilder($pListViewFilterBuilder);
			$pEstateList->setUnitsViewName($attributes['units']);
			$pEstateList->setGeoSearchBuilder($pGeoSearchBuilder);

			$pTemplate = $this->_pTemplate->withTemplateName($pListViewWithSortParams->getTemplate());
			$pTemplate->setEstateList($pEstateList);
			$pEstateList->loadEstates($this->_pWPQueryWrapper->getWPQuery()->get('page', 1));

			$result = $pTemplate->render();
		}
		return $result;
	}

	/**
	 * @param DataListView $pDataView
	 * @param SortListDataModel $pSortListDataModel
	 * @return DataListView
	 */
	private function listViewWithSortParams(DataListView $pDataView,
		SortListDataModel $pSortListDataModel): DataListView
	{
		$pListViewClone = clone $pDataView;
		$pListViewClone->setSortby($pSortListDataModel->getSelectedSortby());
		$pListViewClone->setSortorder($pSortListDataModel->getSelectedSortorder());
		return $pListViewClone;
	}

	/**
	 * @param DataListView $pListView
	 * @param SortListDataModel $pSortListDataModel
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws UnknownFieldException
	 */
	private function registerNewPageLinkArgs(DataListView $pListView, SortListDataModel $pSortListDataModel)
	{
		$pModel = $this->_pSearchParametersModelBuilderEstate
			->buildSearchParametersModel($pListView, $pSortListDataModel);
		$this->_pSearchParameters->registerNewPageLinkArgs($pModel);
	}
}