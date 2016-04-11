<?php

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2015, onOffice(R) Software AG
 *
 */

namespace onOffice\WPlugin;

/**
 *
 */

class ConfigWrapper {
	/** @var \onOffice\WPlugin\ConfigWrapper */
	private static $_pInstance = null;

	/** @var array */
	private $_config = null;


	/**
	 *
	 * @return \onOffice\WPlugin\ConfigWrapper
	 *
	 */

	public static function getInstance() {
		if ( null === self::$_pInstance ) {
			self::$_pInstance = new static;
		}

		return self::$_pInstance;
	}


	/**
	 *
	 */

	private function __construct() {
		$this->_config = $this->readConfig();
	}


	/**
	 *
	 * @return string
	 *
	 */

	public static function getSubPluginPath() {
		return ABSPATH . 'wp-content/plugins/onoffice-personalized';
	}


	/**
	 *
	 * @return array
	 *
	 */

	private function readConfig() {
		$config = array();

		$configFile = dirname( __FILE__ ) . '/../config.php';
		$configFilePersonalized = self::getSubPluginPath() . '/config.php';

		if ( is_file( $configFilePersonalized ) ) {
			include $configFilePersonalized;
		} else {
			include $configFile;
		}

		return $config;
	}


	/**
	 *
	 */

	private function __clone() {}


	/**
	 *
	 * @param string $key
	 * @return mixed
	 *
	 */

	public function getConfigByKey($key) {
		if ( array_key_exists( $key, $this->_config ) ) {
			return $this->_config[$key];
		}
		return null;
	}


	/**
	 *
	 * @return mixed
	 *
	 */

	public function getConfig() {
		return $this->_config;
	}
}