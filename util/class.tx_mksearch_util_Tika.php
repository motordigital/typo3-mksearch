<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 das Medienkombinat
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * Tika controller class
 */
class tx_mksearch_util_Tika {
	private static $instance = null;
	private $tikaJar = null;
	private $tikaAvailable = -1;
	private $tikaLocaleType;

	/**
	 * @return tx_mksearch_util_Tika
	 */
	public static function getInstance() {
		$tikaJar = tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'tikaJar');
		self::$instance = new tx_mksearch_util_Tika($tikaJar);
		return self::$instance;
	}
	private function __construct($tikaJar) {
		$this->setTikaJar($tikaJar);
		$this->tikaLocaleType = tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'tikaLocaleType');
	}
	/**
	 * Whether or not Tika is available on server.
	 * @return boolean
	 */
	public function isAvailable() {
		if($this->tikaAvailable != -1) {
			return $this->tikaAvailable == 1 ? true : false;
		}
		if (!is_file($this->tikaJar)) {
			tx_rnbase_util_Logger::warn('Tika Jar not found!', 'mksearch', array('Jar'=> $this->tikaJar));
			$this->tikaAvailable = 0;
			return $this->tikaAvailable;
		}
		
		if (!t3lib_exec::checkCommand('java')) {
			tx_rnbase_util_Logger::warn('Java not found! Java is required to run Apache Tika.', 'mksearch');
			$this->tikaAvailable = 0;
			return $this->tikaAvailable;
		}
		$this->tikaAvailable = 1;
		return $this->tikaAvailable;
	}
	private function setTikaJar($tikaJar) {
		$this->tikaJar = $tikaJar;
	}
	
	/**
	 * Umlaute in Dateinamen werden durch escapeshellarg entfernt
	 * außer es ist der korrekte LC_CTYPE gesetzt. Sollte auf de_DE.UTF-8
	 * stehen 
	 * 
	 * @see http://www.php.net/manual/de/function.escapeshellarg.php#99213
	 * 
	 * @return void
	 */
	private function setLocaleTypeForNonWindowsSystems() {
		if(TYPO3_OS != 'WIN' && $this->tikaLocaleType){
			setlocale(LC_CTYPE, $this->tikaLocaleType);
		}
	}
	
	/**
	 * @return void
	 */
	private function resetLocaleType() {
		setlocale(LC_CTYPE, '');
	}

	/**
	 * Extracs text from a file using Apache Tika
	 *
	 * @param	string		Content which should be processed.
	 * @param	string		Content type
	 * @param	array		Configuration array
	 * @return string
	 * @throws Exception
	 */
	public function extractContent($file, &$tikaCommand = null) {
		if(!$this->isAvailable())
			throw new Exception('Tika not available!');

		return $this->shell_exec($file, 't');
	}

	/**
	 * Extracs text from a file using Apache Tika
	 *
	 * @param	string		Content which should be processed.
	 * @param	string		Content type
	 * @param	array		Configuration array
	 * @return string
	 * @throws Exception
	 */
	public function extractLanguage($file) {
		if(!$this->isAvailable())
			throw new Exception('Tika not available!');

		return $this->shell_exec($file, 'l');
	}

	/**
	 * Extracs meta data from a file using Apache Tika
	 *
	 * @param	string file path.
	 * @return array
	 * @throws Exception
	 */
	public function extractMetaData($file) {
		if(!$this->isAvailable())
			throw new Exception('Tika not available!');

		$absFile = self::checkFile($file);
		
		$this->setLocaleTypeForNonWindowsSystems();
		
		$tikaCommand = t3lib_exec::getCommand('java')
			. ' -Dfile.encoding=UTF8' // forces UTF8 output
			. ' -jar ' . escapeshellarg($this->tikaJar)
			. ' -m ' . escapeshellarg($absFile);
			
		$this->resetLocaleType();

		$shellOutput = array();
		$tikaCommand = $this->getTikaCommandWithLocaleTypePrefixForNonWindowsSystems($tikaCommand);
		exec($tikaCommand, $shellOutput);

		$ret = array();
		foreach ($shellOutput as $line) {
			list($meta, $value) = explode(':', $line, 2);
			$ret[$meta] = trim($value);
		}
		return $ret;
	}
	
	/**
	 * @param string $file
	 * @param string $tikaCmdType
	 * 
	 * @return string
	 */
	private function shell_exec($file, $tikaCmdType) {
		$absFile = self::checkFile($file);
		
		$this->setLocaleTypeForNonWindowsSystems();
		
		$tikaCommand = t3lib_exec::getCommand('java')
			. ' -Dfile.encoding=UTF-8' // forces UTF8 output
			. ' -jar ' . escapeshellarg($this->tikaJar)
			. ' -' . $tikaCmdType . ' ' . escapeshellarg($absFile);
			
		$this->resetLocaleType();
		
		return trim(shell_exec(
			$this->getTikaCommandWithLocaleTypePrefixForNonWindowsSystems(
				$tikaCommand
			)
		));
	}
	
	/**
	 * @param string $tikaCommand
	 * 
	 * @return string
	 */
	private function getTikaCommandWithLocaleTypePrefixForNonWindowsSystems($tikaCommand) {
		if(TYPO3_OS != 'WIN' && $this->tikaLocaleType){
			$tikaCommand = 'LANG=' . $this->tikaLocaleType . ' ' . $tikaCommand;
		}
		
		return $tikaCommand;
	}

	/**
	 * Check if a file exists and is readable within TYPO3.
	 * @param	string File name
	 * @return string File name with absolute path or FALSE.
	 * @throws Exception
	 */
	private static function checkFile ($fName)	{
		$absFile = t3lib_div::getFileAbsFileName($fName);
		$absFile = self::fixFilenameWithPossibleUmlautsForWindows($absFile);
		if(!(t3lib_div::isAllowedAbsPath($absFile) && @is_file($absFile))) {
			throw new Exception('File not found: '.$absFile);
		}
		if(!@is_readable($absFile)) {
			throw new Exception('File is not readable: '.$absFile);
		}

		return $absFile;
	}
	
	/**
	 * Umlaute werden unter Windows in Dateinamen nicht korrekt interpretiert!
	 * 
	 * @param string $fileName
	 * 
	 * @return string
	 */
	private static function fixFilenameWithPossibleUmlautsForWindows($fileName) {
		if(TYPO3_OS == 'WIN') {
			$fileName = utf8_decode($fileName);
		}
		
		return $fileName;
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Tika.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Tika.php']);
}