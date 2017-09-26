<?php

class Motive_Easymarketing_SystemController extends Mage_Core_Controller_Front_Action
{
    protected $_helper;

    public $jsonParameters = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;

    protected $_getUrl = 'https://api.easymarketing.de/site_verification_data';

    protected $_performUrl = 'https://api.easymarketing.de/perform_site_verification';

    public function preDispatch() {
        $this->_helper = Mage::helper('easymarketing');
    }

    protected function sendResponse($result) {
        $this->getResponse()->setHeader('Content-type', 'application/json; charset=utf-8');
        $this->getResponse()->setBody(json_encode($result, $this->jsonParameters));
    }

    public function savegoogleverificationAction() {
        $this->_helper->log('Call google_verification START');

        if($this->_helper->dbFetchOne('google_verification_enable')) {
            $this->turnOff();
        } else {
            $this->turnOn();
        }

        $this->_helper->log('Call google_verification END');
    }

    protected function turnOff() {
        $this->_helper->dbUpdateOne("google_verification_status", 0);
        $this->_helper->dbUpdateOne("google_verification_enable", 0);

        Mage::app()->getCacheInstance()->flush();

        $this->sendResponse(array('status' => 3));
    }

    protected function turnOn() {
        if(!$accessToken = $this->_helper->emservicecallStart()) {
            $this->sendResponse(array('status' => 0));
        }

        $storeId = $this->_helper->getParam('store');

        $baseUrl = Mage::getBaseUrl();

        $result = $this->_helper->emserviceCall($this->_getUrl . '?access_token=' . $accessToken . '&website_url=' . parse_url($baseUrl, PHP_URL_HOST));

        $this->_helper->log($result['content']);

        if($result['http_status'] == '401') {
            $this->_helper->sendResponse(array('status' => 0));

        } elseif($result['http_status'] == '200') {
            $resultArray = json_decode($result['content'], true);
            $this->_helper->dbUpdateOne("google_verification_meta", $resultArray['meta_tag']);
            $this->_helper->dbUpdateOne("google_verification_enable", 1);

            Mage::app()->getCacheInstance()->flush();

            $paramsArray = array('website_url' => parse_url($baseUrl, PHP_URL_HOST), 'verification_type' => 'META');

            $result2 = $this->_helper->emserviceCall($this->_performUrl . '?access_token=' . $accessToken, $paramsArray);

            $this->_helper->log($result2['content']);

            if($result2['http_status'] == '401') {
                $this->sendResponse(array('status' => 0));

            } elseif($result2['http_status'] == '200') {
                $this->_helper->dbUpdateOne("google_verification_status", 1);

                $this->sendResponse(array('status' => 2));

            } elseif($result2['http_status'] == '400') {
                $this->_helper->dbUpdateOne("google_verification_status", 0);

                $resultArray2 = json_decode($result2['content'], true);

                $this->sendResponse(array('status' => 1, 'errors' => $resultArray2['errors']));

            } else {
                $this->_helper->log('Unknown HTTP Response');
                $this->sendResponse(array('status' => 0));
            }

        } elseif($result['http_status'] == '400') {
            $resultArray = json_decode($result['content'], true);

            $this->sendResponse(array('status' => 1, 'errors' => $resultArray['errors']));

        } else {
            $this->_helper->log('Unknown HTTP Response');
            $this->sendResponse(array('status' => 0));
        }
    }
}