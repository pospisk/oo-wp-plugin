<?php

/**
 *
 *    Copyright (C) 2018 onOffice GmbH
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

use onOffice\WPlugin\Favorites;
use onOffice\WPlugin\Model\FormModel;
use onOffice\WPlugin\Model\InputModelOption;
use onOffice\WPlugin\Renderer\InputModelRenderer;
use onOffice\WPlugin\Types\MapProvider;
use function __;
use function do_settings_sections;
use function get_option;
use function settings_fields;
use function submit_button;

/**
 *
 */

class AdminPageModules
	extends AdminPage
{
	/**
	 *
	 * @param string $pageSlug
	 *
	 */

	public function __construct($pageSlug)
	{
		$this->addFormModelFavorites($pageSlug);
		$this->addFormModelMapProvider($pageSlug);

		parent::__construct($pageSlug);
	}


	/**
	 *
	 * @param string $pageSlug
	 *
	 */

	private function addFormModelFavorites(string $pageSlug)
	{
		$groupSlugFavs = 'onoffice-favorization';
		$enableFavLabel = __('Enable Watchlist', 'onoffice');
		$favButtonLabel = __('Expression used', 'onoffice');
		$pInputModelEnableFav = new InputModelOption($groupSlugFavs, 'enableFav',
			$enableFavLabel, InputModelOption::SETTING_TYPE_BOOLEAN);
		$pInputModelEnableFav->setHtmlType(InputModelOption::HTML_TYPE_CHECKBOX);
		$pInputModelEnableFav->setValuesAvailable(1);
		$pInputModelEnableFav->setValue(get_option($pInputModelEnableFav->getIdentifier()) == 1);
		$pInputModelFavButtonLabel = new InputModelOption($groupSlugFavs, 'favButtonLabelFav',
			$favButtonLabel, InputModelOption::SETTING_TYPE_NUMBER);
		$pInputModelFavButtonLabel->setHtmlType(InputModelOption::HTML_TYPE_RADIO);
		$pInputModelFavButtonLabel->setValue(get_option($pInputModelFavButtonLabel->getIdentifier()));
		$pInputModelFavButtonLabel->setValuesAvailable([
			Favorites::KEY_SETTING_MEMORIZE => __('Watchlist', 'onoffice'),
			Favorites::KEY_SETTING_FAVORIZE => __('Favorise', 'onoffice'),
		]);

		$pFormModel = new FormModel();
		$pFormModel->addInputModel($pInputModelEnableFav);
		$pFormModel->addInputModel($pInputModelFavButtonLabel);
		$pFormModel->setGroupSlug($groupSlugFavs);
		$pFormModel->setPageSlug($pageSlug);
		$pFormModel->setLabel(__('Watchlist', 'onoffice'));

		$this->addFormModel($pFormModel);
	}


	/**
	 *
	 * @param string $pageSlug
	 *
	 */

	private function addFormModelMapProvider(string $pageSlug)
	{
		$groupSlugMaps = 'onoffice-maps';
		$mapProviderLabel = __('Map Provider', 'onoffice');
		$pInputModelMapProvider = new InputModelOption($groupSlugMaps, 'mapprovider',
			$mapProviderLabel, InputModelOption::SETTING_TYPE_NUMBER);
		$pInputModelMapProvider->setHtmlType(InputModelOption::HTML_TYPE_RADIO);
		$selectedValue = get_option($pInputModelMapProvider->getIdentifier(), MapProvider::PROVIDER_DEFAULT);
		$pInputModelMapProvider->setValue($selectedValue);
		$pInputModelMapProvider->setValuesAvailable([
			MapProvider::OPEN_STREET_MAPS => __('OpenStreetMap', 'onoffice'),
			MapProvider::GOOGLE_MAPS => __('Google Maps', 'onoffice'),
		]);

		$pFormModel = new FormModel();
		$pFormModel->addInputModel($pInputModelMapProvider);
		$pFormModel->setGroupSlug($groupSlugMaps);
		$pFormModel->setPageSlug($pageSlug);
		$pFormModel->setLabel(__('Maps', 'onoffice'));

		$this->addFormModel($pFormModel);
	}


	/**
	 *
	 */

	public function renderContent()
	{
		$this->generatePageMainTitle('Modules');

		echo '<form method="post" action="options.php">';

		/* @var $pInputModelRenderer InputModelRenderer */
		$pInputModelRenderer = $this->getContainer()->get(InputModelRenderer::class);

		foreach ($this->getFormModels() as $pFormModel) {
			$pInputModelRenderer->buildForm($pFormModel);
		}

		settings_fields($this->getPageSlug());
		do_settings_sections($this->getPageSlug());

		submit_button();
		echo '</form>';
	}
}
