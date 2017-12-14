<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 René Nitzsche <dev@dmk-ebusiness.de>
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

use Elastica\Client;
use Elastica\Connection;

tx_rnbase::load('Tx_Rnbase_Scheduler_Task');

/**
 *
 */
class tx_mksearch_scheduler_ElasticSearchCleanIndexTask extends Tx_Rnbase_Scheduler_Task
{

    /**
     * Tables to clean Index From
     * @var String
     */
    private $url;

    /**
     * Function executed from the Scheduler.
     * Sends an email
     *
     * @return  void
     */
    public function execute()
    {
        $success = true;

        try {
            $data_string = '{"query": {"match_all": {}}}';

            $ch = curl_init(trim($this->getUrl()).'/perfar/_delete_by_query?conflicts=proceed');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
            );

            $result = curl_exec($ch);
            curl_close($ch);

        } catch (Exception $e) {
            tx_rnbase_util_Logger::fatal('Indexing failed!', 'mksearch', array('Exception' => $e->getMessage()));
            //Da die Exception gefangen wird, würden die Entwickler keine Mail bekommen
            //also machen wir das manuell
            if ($addr = tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'sendEmailOnException')) {
                //die Mail soll immer geschickt werden
                $aOptions = array('ignoremaillock' => true);
                //tx_rnbase_util_Misc::sendErrorMail($addr, 'tx_mksearch_scheduler_IndexTask', $e, $aOptions);
            }
            $success = false;
        }

        return $success;
    }



    /**
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param String $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/scheduler/class.tx_mksearch_scheduler_CleanIndexTask.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/scheduler/class.tx_mksearch_scheduler_CleanIndexTask.php']);
}
