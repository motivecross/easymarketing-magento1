<?php

class Motive_Easymarketing_Block_Adminhtml_System_Config_Form_Field_Status extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_helper;

    protected $_extractionUrl = 'https://api.easymarketing.de/extraction_status';

    protected function _construct() {
        $this->_helper = Mage::helper('easymarketing');

        parent::_construct();
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<div>';

        if(!$this->_helper->getConfig('easymarketingsection/easmarketinggeneral/enable')) {
            $html .= "Modul nicht aktiviert";
        } else {

            try {
                $currentStatus = $this->_helper->dbFetchOne('configuration_status');

                if($currentStatus == 0) {
                    $lastErrors = $this->_helper->dbFetchOne('configuration_last_errors');
                    if(empty($lastErrors)) {
                        $html .= 'Konfiguration fehlerhaft.';
                    } else {
                        $html .= '- ' . str_replace(', ', '<br>- ', $lastErrors);
                    }
                } else {
                    $this->_helper->log('Call extraction_status START');

                    $accessToken = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/access_token');

                    $storeId = $this->_helper->getParam('store');
                    $baseUrl = Mage::getBaseUrl();

                    $result = $this->_helper->emserviceCall($this->_extractionUrl . '?access_token=' . $accessToken . '&website_url=' . parse_url($baseUrl, PHP_URL_HOST));

                    if($result['http_status'] == '401') {
                        $this->_helper->log('Wrong Access Token');
                        $html .= 'Wrong Access Token';

                    } elseif($result['http_status'] == '200') {
                        $this->_helper->log($result['content']);
                        $resultArray = json_decode($result['content'], true);
                        if($resultArray['api_properly_setup_at'] > 1) {
                            $html .= 'Erfolgreich eingerichtet.';
                            $html .= '<br>Kategorien indexiert: ' . $resultArray['num_categories'];
                            $html .= '<br>Produkte indexiert: ' . $resultArray['num_products'];
                            if(empty($resultArray['updated_at'])) {
                                $lastIndexed = "Nie";
                            } else {
                                $lastIndexed = date('d.m.Y H:i:s', $resultArray['updated_at']);
                            }
                            $html .= '<br>Letzte Indexierung: ' . $lastIndexed;
                        }
                    } elseif($result['http_status'] == '400') {
                        $resultArray = json_decode($result['content'], true);
                        $html .= implode(', ', $resultArray['errors']);
                        $this->_helper->log($result['content']);

                    } else {
                        $this->_helper->log('Unknown HTTP Response');
                        $html .= "Unknown HTTP Response";
                    }

                    $this->_helper->log('Call extraction_status END');
                }
            } catch(Exception $exception) {
                $errorMessage = $exception->getFile() . " - " . $exception->getLine() . ": " . $exception->getMessage() . "\n". $exception->getTraceAsString();
                $this->_helper->error($errorMessage);
                throw new \Exception($errorMessage);
            }
        }

        $html .= '</div>';
        return $html;
    }
}