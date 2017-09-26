<?php

class Motive_Easymarketing_Block_Page_Tracker extends Mage_Core_Block_Template
{
    protected $_helper;

    public function __construct() {
        $this->_helper = Mage::helper('easymarketing');
        parent::_construct();
    }

    public function getGoogleVerificationCode() {
        if($this->_helper->dbFetchOne("google_verification_enable")) {
            return $this->_helper->dbFetchOne("google_verification_meta");
        } else {
            return FALSE;
        }
    }

    public function getGoogleLeadTracker() {
        if($this->_helper->getConfig('easymarketingsection/easmarketinggeneral/google_tracking_enable')) {
            return $this->_helper->dbFetchOne("google_lead_code");
        } else {
            return FALSE;
        }
    }

    public function getFacebookLeadTracker() {
        if($this->_helper->getConfig('easymarketingsection/easmarketinggeneral/facebook_tracking_enable')) {
            return $this->_helper->dbFetchOne("facebook_lead_code");
        } else {
            return FALSE;
        }
    }
}