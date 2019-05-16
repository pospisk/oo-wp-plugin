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

namespace onOffice\WPlugin\Controller;

use Exception;
use onOffice\WPlugin\API\APIClientCredentialsException;
use onOffice\WPlugin\Fieldnames;
use onOffice\WPlugin\Gui\AdminPageAddressList;
use onOffice\WPlugin\Gui\AdminPageAddressListSettings;
use onOffice\WPlugin\Gui\AdminPageAjax;
use onOffice\WPlugin\Gui\AdminPageApiSettings;
use onOffice\WPlugin\Gui\AdminPageBase;
use onOffice\WPlugin\Gui\AdminPageEstate;
use onOffice\WPlugin\Gui\AdminPageEstateListSettings;
use onOffice\WPlugin\Gui\AdminPageEstateUnitList;
use onOffice\WPlugin\Gui\AdminPageEstateUnitSettings;
use onOffice\WPlugin\Gui\AdminPageFormList;
use onOffice\WPlugin\Gui\AdminPageFormSettingsMain;
use onOffice\WPlugin\Gui\AdminPageModules;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Utility\__String;
use WP_Hook;
use const ONOFFICE_PLUGIN_DIR;
use function __;
use function add_action;
use function add_menu_page;
use function add_submenu_page;
use function admin_url;
use function esc_attr;
use function esc_html;
use function get_admin_url;
use function is_admin;
use function plugins_url;
use function wp_create_nonce;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_localize_script;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class AdminViewController
{
	/** @var string */
	private $_pageSlug = null;

	/** @var string[] */
	private $_ajaxHooks = array();

	/** @var AdminPageEstateListSettings */
	private $_pAdminListViewSettings = null;
	
	/** @var AdminPageAddressListSettings */
	private $_pAdminListViewSettingsAddress = null;

	/** @var AdminPageEstateUnitList */
	private $_pAdminUnitListSettings = null;

	/** @var AdminPageEstate */
	private $_pAdminPageEstates = null;

	/** @var AdminPageFormSettingsMain */
	private $_pAdminPageFormSettings = null;


	/**
	 *
	 */

	public function __construct()
	{
		$this->_pageSlug = 'onoffice';
	}


	/**
	 *
	 */

	public function onInit()
	{
		if (!is_admin()) {
			return;
		}

		$this->_pAdminListViewSettingsAddress = new AdminPageAddressListSettings($this->_pageSlug);
		$this->_ajaxHooks['admin_page_'.$this->_pageSlug.'-editlistviewaddress'] = $this->_pAdminListViewSettingsAddress;

		$this->_pAdminListViewSettings = new AdminPageEstateListSettings($this->_pageSlug);
		$this->_ajaxHooks['admin_page_'.$this->_pageSlug.'-editlistview'] = $this->_pAdminListViewSettings;

		$this->_pAdminUnitListSettings = new AdminPageEstateUnitSettings($this->_pageSlug);
		$this->_ajaxHooks['admin_page_'.$this->_pageSlug.'-editunitlist'] = $this->_pAdminUnitListSettings;

		$this->_pAdminPageFormSettings = new AdminPageFormSettingsMain($this->_pageSlug);
		$this->_ajaxHooks['admin_page_'.$this->_pageSlug.'-editform'] = $this->_pAdminPageFormSettings;

		$this->_pAdminPageEstates = new AdminPageEstate($this->_pageSlug);
		$pSelectedSubPage = $this->_pAdminPageEstates->getSelectedAdminPage();

		if ($pSelectedSubPage instanceof AdminPageAjax) {
			$this->_ajaxHooks['onoffice_page_'.$this->_pageSlug.'-estates'] = $pSelectedSubPage;
		}
	}


	/**
	 *
	 * Important note:
	 * - pages usually use the load-(page) hook for handleAdminNotices() but
	 * - ajax pages use it in order to pre-generate the form model.
	 * - page slugs must be chosen according to WP's sanitize_key() function because of
	 *   wp_ajax_closed_postboxes()
	 *
	 */

	public function register_menu()
	{
		add_action('admin_notices', [$this, 'displayAPIError']);
		$pUserCapabilities = new UserCapabilities;
		$roleMainPage = $pUserCapabilities->getCapabilityForRule(UserCapabilities::RULE_VIEW_MAIN_PAGE);
		$roleAddress = $pUserCapabilities->getCapabilityForRule(UserCapabilities::RULE_EDIT_VIEW_ADDRESS);
		$roleEstate = $pUserCapabilities->getCapabilityForRule(UserCapabilities::RULE_EDIT_VIEW_ESTATE);
		$roleForm = $pUserCapabilities->getCapabilityForRule(UserCapabilities::RULE_EDIT_VIEW_FORM);
		$roleModules = $pUserCapabilities->getCapabilityForRule(UserCapabilities::RULE_EDIT_MODULES);
		$roleSettings = $pUserCapabilities->getCapabilityForRule(UserCapabilities::RULE_EDIT_SETTINGS);

		// main page
		add_menu_page( __('onOffice', 'onoffice'), __('onOffice', 'onoffice'),
			$roleMainPage, $this->_pageSlug, function(){}, 'dashicons-admin-home');

		$pAdminPageAddresses = new AdminPageAddressList($this->_pageSlug);
		add_submenu_page( $this->_pageSlug, __('Addresses', 'onoffice'), __('Addresses', 'onoffice'),
			$roleAddress, $this->_pageSlug.'-addresses', array($pAdminPageAddresses, 'render'));

		// Estates
		$hookEstates = add_submenu_page( $this->_pageSlug, __('Estates', 'onoffice'),
			__('Estates', 'onoffice'), $roleEstate,
			$this->_pageSlug.'-estates',  array($this->_pAdminPageEstates, 'render'));
		add_action( 'load-'.$hookEstates, array($this->_pAdminPageEstates, 'handleAdminNotices'));
		$pSelectedSubPage = $this->_pAdminPageEstates->getSelectedAdminPage();
		if ($pSelectedSubPage instanceof AdminPageAjax) {
			add_action( 'load-'.$hookEstates, array($pSelectedSubPage, 'checkForms'));
		}

		// Forms
		$pAdminPageFormList = new AdminPageFormList($this->_pageSlug);
		$hookForms = add_submenu_page( $this->_pageSlug, __('Forms', 'onoffice'), __('Forms', 'onoffice'),
			$roleForm, $this->_pageSlug.'-forms', array($pAdminPageFormList, 'render'));
		add_action( 'load-'.$hookForms, array($pAdminPageFormList, 'handleAdminNotices'));

		// Edit Form (hidden page)
		$hookEditForm = add_submenu_page(null, null, null, $roleForm, $this->_pageSlug.'-editform',
			array($this->_pAdminPageFormSettings, 'render'));
		add_action( 'load-'.$hookEditForm, array($this->_pAdminPageFormSettings, 'initSubClassForGet'));
		add_action( 'load-'.$hookEditForm, array($this->_pAdminPageFormSettings, 'handleAdminNotices'));
		add_action( 'load-'.$hookEditForm, array($this->_pAdminPageFormSettings, 'checkForms'));

		// Modules
		$pAdminPageModules = new AdminPageModules($this->_pageSlug);
		add_submenu_page( $this->_pageSlug, __('Modules', 'onoffice'), __('Modules', 'onoffice'),
			$roleModules, $this->_pageSlug.'-modules', array($pAdminPageModules, 'render'));
		add_action( 'admin_init', array($pAdminPageModules, 'registerForms'));

		// Estates: edit list view (hidden page)
		$hookEditList = add_submenu_page(null, null, null, $roleEstate, $this->_pageSlug.'-editlistview',
			array($this->_pAdminListViewSettings, 'render'));
		add_action( 'load-'.$hookEditList, array($this->_pAdminListViewSettings, 'handleAdminNotices'));
		add_action( 'load-'.$hookEditList, array($this->_pAdminListViewSettings, 'checkForms'));

		// Estates: edit list view (hidden page)
		$hookEditUnitList = add_submenu_page(null, null, null, $roleEstate, $this->_pageSlug.'-editunitlist',
			array($this->_pAdminUnitListSettings, 'render'));
		add_action( 'load-'.$hookEditUnitList, array($this->_pAdminUnitListSettings, 'handleAdminNotices'));
		add_action( 'load-'.$hookEditUnitList, array($this->_pAdminUnitListSettings, 'checkForms'));

		// Address: edit list view (hidden page)
		$hookEditAddressList = add_submenu_page(null, null, null, $roleEstate, $this->_pageSlug.'-editlistviewaddress',
			array($this->_pAdminListViewSettingsAddress, 'render'));
		add_action( 'load-'.$hookEditAddressList, array($this->_pAdminListViewSettingsAddress, 'handleAdminNotices'));
		add_action( 'load-'.$hookEditAddressList, array($this->_pAdminListViewSettingsAddress, 'checkForms'));

		// Settings
		$pAdminSettingsPage = new AdminPageApiSettings($this->_pageSlug.'-settings');
		$hookSettings = add_submenu_page( $this->_pageSlug, __('Settings', 'onoffice'),
			__('Settings', 'onoffice'), $roleSettings, $this->_pageSlug.'-settings',
			array($pAdminSettingsPage, 'render'));
		add_action( 'admin_init', array($pAdminSettingsPage, 'registerForms'));
		add_action( 'load-'.$hookSettings, array($pAdminSettingsPage, 'handleAdminNotices'));
	}


	/**
	 *
	 * @param string $hook
	 *
	 */

	public function enqueue_ajax($hook)
	{
		if ($hook == '' || !array_key_exists($hook, $this->_ajaxHooks)) {
			return;
		}

		$pAdminView = $this->_ajaxHooks[$hook];
		$ajaxDataAdminPage = $pAdminView->getEnqueueData();
		$ajaxDataGeneral = array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'action' => $hook,
				'nonce' => wp_create_nonce($hook),
			);

		$ajaxData = array_merge($ajaxDataGeneral, $ajaxDataAdminPage);
		wp_enqueue_script('onoffice-ajax-settings',
			plugins_url('/js/ajax_settings.js', ONOFFICE_PLUGIN_DIR.'/index.php'), array('jquery'));

		wp_localize_script('onoffice-ajax-settings', 'onOffice_loc_settings', $ajaxData);
	}


	/**
	 *
	 * @throws Exception
	 *
	 */

	public function add_ajax_actions()
	{
		foreach ($this->_ajaxHooks as $hook => $pAdminPage) {
			if (!is_callable(array($pAdminPage, 'ajax_action'))) {
				throw new Exception(get_class($pAdminPage).' must be an instance of AdminPageAjax!');
			}

			add_action( 'wp_ajax_'.$hook, array($this->_ajaxHooks[$hook], 'ajax_action'));
		}
	}


	/**
	 *
	 */

	public function enqueue_css()
	{
		wp_enqueue_style('onoffice-admin-css',
			plugins_url('/css/admin.css', ONOFFICE_PLUGIN_DIR.'/index.php'));
	}


	/**
	 *
	 * @param string $hook
	 *
	 */

	public function enqueueExtraJs($hook)
	{
		if (__String::getNew($hook)->contains('onoffice')) {
			$pObject = $this->getObjectByHook($hook);

			if ($pObject !== null)
			{
				$pObject->doExtraEnqueues();
			}
		}
	}


	/**
	 *
	 * Todo: Delete if pages are being registered and accessible from
	 *	     a member variable by hook
	 *
	 * @global WP_Hook[] $wp_filter
	 * @param string $hook
	 * @return AdminPageBase
	 *
	 */

	private function getObjectByHook($hook)
	{
		global $wp_filter;
		$fullHook = $hook;

		if (isset($wp_filter[$fullHook]))
		{
			/* @var $pWpHook WP_Hook */
			$pWpHook = $wp_filter[$fullHook];

			foreach ($pWpHook->callbacks as $priority => $settingsPriorized)
			{
				foreach ($settingsPriorized as $settings)
				{
					$pObject = isset($settings['function']) && is_array($settings['function']) ?
						$settings['function'][0] : null;
					if ($pObject !== null) {
						return $pObject;
					}
				}
			}
		}
		return null;
	}


	/**
	 *
	 * @param array $links
	 * @return array
	 *
	 */

	public function pluginSettingsLink($links)
	{
		$url = get_admin_url().'admin.php?page='.$this->_pageSlug;
		$settings_link = '<a href="'.$url.'">'.__('Settings', 'onoffice').'</a>';
		array_unshift($links, $settings_link);
		return $links;
	}


	/**
	 *
	 */

	public function displayAPIError()
	{
		$pFieldnames = new Fieldnames(new FieldsCollection());

		try {
			$pFieldnames->loadLanguage();
		} catch (APIClientCredentialsException $pCredentialsException) {
			$class = 'notice notice-error';
			$label = __('API token and secret', 'onoffice');
			$loginCredentialsLink = sprintf('<a href="admin.php?page=onoffice-settings">%s</a>', $label);
			/* translators: %s will be replaced with the translation of 'API token and secret'. */
			$message = sprintf(esc_html(__('It looks like you did not enter any valid API '
				.'credentials. Please consider reviewing your %s.', 'onoffice')), $loginCredentialsLink);

			printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
		}
	}
}
