<?php

class Motive_Easymarketing_Block_Adminhtml_System_Config_Form_Googleverification extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected $_helper;

    protected $_getUrl = 'https://api.easymarketing.de/site_verification_data';

    protected $_performUrl = 'https://api.easymarketing.de/perform_site_verification';

    protected $_template = 'easymarketing/system/config/form/google_verification.phtml';

    protected function _construct() {
        $this->_helper = Mage::helper('easymarketing');

        parent::_construct();
    }

    public function render(Varien_Data_Form_Element_Abstract $element) {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        return $this->_toHtml();
    }

    public function getVerificationUrl() {
        $storeId = $this->_helper->getParam('store');
        $baseUrl = Mage::getBaseUrl();

        return $baseUrl . "easymarketing/system/savegoogleverification";
    }

    public function getButtonHtml() {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')->setData(
            [
                'id' => 'verification_button',
                'label' => __('Enable / Disable Google Site Verification'),
            ]
        );

        return $button->toHtml();
    }

    public function getCurrentStatus() {
        $enabled = $this->_helper->dbFetchOne("google_verification_enable");
        $status = $this->_helper->dbFetchOne("google_verification_status");
        $meta = $this->_helper->dbFetchOne("google_verification_meta");

        if(empty($enabled) || empty($status) || empty($meta)) {
            return 0;
        } else {
            return 1;
        }
    }

    public function getCurrentStatusImage() {
        if($this->getCurrentStatus()) {
            return $this->getSuccessImage();
        } else {
            return $this->getFailImage();
        }
    }

    public function getSuccessImage() {
        return $this->getSkinUrl('images/easymarketing/rule_component_apply.gif');
    }

    public function getFailImage() {
        return $this->getSkinUrl('images/easymarketing/rule_component_remove.gif');
    }
}