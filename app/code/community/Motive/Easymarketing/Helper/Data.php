<?php

class Motive_Easymarketing_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function log($message) {
        Mage::log($message, 6, 'easymarketing-api.log');
    }

    public function error($message) {
        Mage::log($message, 3, 'easymarketing-api.log');
    }

    public function sendErrorAndExit($message) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        echo $message;
        $this->error('ERROR: ' . $message);
        exit;
    }

    public function getConfig($configPath, $storeId = false) {
        if($storeId) {
            return Mage::getStoreConfig($configPath, $storeId);
        } else {
            return Mage::getStoreConfig($configPath, Mage::app()->getStore());
        }
    }

    public function getParam($param) {
        return $this->_getRequest()->getParam($param);
    }

    protected function checkShoptoken() {
        $shopToken = $this->getParam('shop_token');
        if(!empty($shopToken) && $shopToken == $this->getConfig('easymarketingsection/easmarketinggeneral/shop_token')) {
            return true;
        } else {
            return false;
        }
    }

    public function apiStart() {

        if(!$this->getConfig('easymarketingsection/easmarketinggeneral/enable')) {
            $this->sendErrorAndExit('Module not activated');
        }

        if(!$this->checkShoptoken()) {
            $this->sendErrorAndExit('Wrong Shop Token');
        }
    }

    public function getAllMandatoryParams($params) {
        $returnArray = array();
        foreach($params as $param) {
            $value = $this->getParam($param);
            if(empty($value) && $value !== 0 && $value !== '0') {
                $this->sendErrorAndExit('Not enough parameters');
            } else {
                $returnArray[$param] = $value;
            }
        }
        return $returnArray;
    }

    public function getAllAttributes() {
        $entity_type = Mage::getModel('eav/entity_type')->loadByCode(Mage_Catalog_Model_Product::ENTITY);
        $attributeCollection = Mage::getResourceModel('eav/entity_attribute_collection')->setEntityTypeFilter($entity_type);

        $attributeCollection->addFieldToFilter('frontend_input', array('text', 'select', 'decimal', 'date', 'price', 'textarea', 'weight', 'multiselect'));
        $attributeCollection->setOrder('frontend_label', 'ASC');
        $attributes = $attributeCollection->load()->getItems();

        $returnArray = array();
        foreach($attributes as $attribute) {
            if($attribute->getIsUserDefined()) {
                $returnArray[] = ['value' => $attribute->getAttributeCode(), 'label' => $attribute->getStoreLabel() . ' (' . $attribute->getAttributeCode() . ')'];
            }
        }

        return $returnArray;
    }

    public function getAttributeByCode($code) {
        $attribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $code);

        return $attribute;
    }

    public function dbFetchOne($field, $storeId = 0) {

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $fetch = $connection->fetchOne('SELECT data_value FROM `easymarketing_data` WHERE data_name = "' . $field . '"' . ' AND data_scope = "' . $storeId . '"');
        return $fetch;
    }

    public function dbUpdateOne($field, $value, $storeId = 0) {

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $updatedRows = $connection->update('easymarketing_data', array('data_value' => $value), 'data_name = "' . $field . '"');

        return $updatedRows;
    }

    public function emservicecallStart() {

        $accessToken = $this->getConfig('easymarketingsection/easmarketinggeneral/access_token');

        if(empty($accessToken)) {
            return false;
        } else {
            return $accessToken;
        }
    }

    public function emserviceCall($url, $paramsArray = array()) {
        $data_string = "";

        $ch = curl_init($url);
        if(empty($paramsArray)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        } else {
            $data_string = json_encode($paramsArray, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        $result = curl_exec($ch);

        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        return array('http_status' => $httpStatus, 'content' => $result);
    }
}