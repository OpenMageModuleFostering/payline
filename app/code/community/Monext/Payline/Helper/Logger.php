<?php
/**
 * On Magento 1.3.2.4, the Mage::log method don't allow us to force log, so we've to log by an other way
 */
require_once(Mage::getModuleDir('',"Monext_Payline") . '/lib/paylineSDK.php');

class Monext_Payline_Helper_Logger extends Mage_Core_Helper_Abstract{
    const FILE  = 'payline.log';
    const LEVEL = Zend_Log::DEBUG;

    protected static $loggers=array();


     /**
     * forced log facility
     *
     * @param string $message
     * @param integer $level
     * @param string $file
     * @param bool $forceLog
     */
    public function log($message, $level=null, $file=null){
        $level  = is_null($level) ? self::LEVEL : $level;
        $file = empty($file) ? self::FILE : $file;

        try {
            if (!isset(self::$loggers[$file])) {
                $logFile = Mage::getBaseDir('var') . DS . 'log' . DS . $file;

                if (!is_dir(Mage::getBaseDir('var').DS.'log')) {
                    mkdir(Mage::getBaseDir('var').DS.'log', 0777);
                }

                if (!file_exists($logFile)) {
                    file_put_contents($logFile, '');
                    chmod($logFile, 0777);
                }

                $format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
                $formatter = new Zend_Log_Formatter_Simple($format);
                $writerModel = Mage::getStoreConfig('global/log/core/writer_model');
                if (!$writerModel) {
                    $writer = new Zend_Log_Writer_Stream($logFile);
                }
                else {
                    $writer = new $writerModel($logFile);
                }
                $writer->setFormatter($formatter);
                self::$loggers[$file] = new Zend_Log($writer);
            }

            if (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            if(Mage::getStoreConfig('payment/payline_common/environment') != paylineSDK::ENV_PROD and $level==Zend_Log::ERR) {
                Mage::getSingleton('core/session')->addError($message);
            }

            self::$loggers[$file]->log($message, $level);

        } catch (Exception $e) {

        }
    }
}