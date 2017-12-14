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
tx_rnbase::load('Tx_Rnbase_Scheduler_Task');

/**
 *
 */
class tx_mksearch_scheduler_CleanIndexTask extends Tx_Rnbase_Scheduler_Task
{

    /**
     * Tables to clean Index From
     * @var String
     */
    private $cleanTables;

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



            $cleanTablesArray = explode(',',$this->getCleanTables());
            foreach ($cleanTablesArray as $table){
                $options = array();
                if (!empty($table) and is_string($table)){
                    $rows = tx_mksearch_util_ServiceRegistry::getIntIndexService()->resetIndexingQueueForTable(trim($table), $options);
                }
            }
            if (!empty($rows)) {//sonst gibts ne PHP Warning bei array_merge
                $rows = count(call_user_func_array('array_merge', array_values($rows)));
            }
            $msg = sprintf($rows ? '%d item(s) indexed' : 'No items in indexing queue.', $rows);
            if ($rows) { // TODO: Schalter im Task anlegen.
                tx_rnbase_util_Logger::info($msg, 'mksearch');
            }
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
     * @return mixed
     */
    public function getCleanTables()
    {
        return $this->cleanTables;
    }

    /**
     * @param mixed $cleanTables
     */
    public function setCleanTables($cleanTables)
    {
        $this->cleanTables = $cleanTables;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/scheduler/class.tx_mksearch_scheduler_CleanIndexTask.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/scheduler/class.tx_mksearch_scheduler_CleanIndexTask.php']);
}
