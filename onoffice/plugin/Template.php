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

class Template
{
	/** @var \onOffice\WPlugin\EstateList */
	private $_pEstateList = null;

	/** @var string */
	private $_templateName = null;

	/** @var string */
	private $_dirName = null;

	/** @var \onOffice\WPlugin\Form */
	private $_pForm = null;


	/**
	 *
	 * @param string $templateName
	 * @param string $defaultTemplateName
	 *
	 */

	public function __construct( $templateName, $dirName, $defaultTemplateName ) {
		$this->_templateName = $templateName;
		$this->_dirName = $dirName;

		if ( ! file_exists( $this->getFilePath() ) ) {
			$this->_templateName = $defaultTemplateName;
		}
	}


	/**
	 *
	 * @param \onOffice\WPlugin\EstateList $pEstateList
	 *
	 */

	public function setEstateList( EstateList $pEstateList ) {
		$this->_pEstateList = $pEstateList;
	}


	/**
	 *
	 * @param \onOffice\WPlugin\Form $pForm
	 *
	 */

	public function setForm( Form $pForm ) {
		$this->_pForm = $pForm;
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function render() {
		$result = $this->getIncludeContents();

		return $result;
	}


	/**
	 *
	 * @return string
	 *
	 */

	private function getIncludeContents() {

		$filename = $this->getFilePath();

		if ( file_exists( $filename ) ) {
			ob_start();
			// vars which might be used in template
			$pEstates = $this->_pEstateList;
			$pForm = $this->_pForm;
			include $filename;
			return ob_get_clean();
		}

		return '';
	}


	/**
	 *
	 * @param string $templateName
	 * @return string
	 *
	 */

	private function getFilePath() {
		return ConfigWrapper::getSubPluginPath() . '/templates/' . $this->_dirName . '/'. $this->_templateName.'.php';
	}
}