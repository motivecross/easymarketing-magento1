<?php

class Motive_Easymarketing_Model_Observer
{
    protected $_helper;

    protected $_endpointUrl = 'https://api.easymarketing.de/configure_endpoints';

    protected $_conversionTrackerUrl = 'https://api.easymarketing.de/conversion_tracker';

    protected $_leadTrackerUrl = 'https://api.easymarketing.de/lead_tracker';

    protected $_googleRemarketingUrl = 'https://api.easymarketing.de/google_remarketing_code';

    public function __construct() {
        $this->_helper = Mage::helper('easymarketing');
    }

    public function adminSystemConfigChangedSectionEasymarketingsection(Varien_Object $observer) {
        if(!$this->_helper->getConfig('easymarketingsection/easmarketinggeneral/enable')) {
            return;
        }

        $this->_helper->log('Easymarketing Configuration Save START');

        try {
            $storeId = $this->_helper->getParam('store');

            $baseUrl = Mage::getBaseUrl();

            $accessToken = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/access_token');

            /* Endpoint Configuration */
            $this->_helper->log('Call configure_endpoints START');

            $paramsArray['website_url'] = parse_url($baseUrl, PHP_URL_HOST);

            $paramsArray['access_token'] = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/access_token', $storeId);

            $paramsArray['shop_token'] = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/shop_token', $storeId);

            $rootCategory = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/rootcategory');
            if(empty($rootCategory)) $rootCategory = 1;
            $paramsArray['shop_category_root_id'] = $rootCategory;

            $paramsArray['categories_api_endpoint'] = $baseUrl . 'easymarketing/api/categories';

            $paramsArray['products_api_endpoint'] = $baseUrl . 'easymarketing/api/products';

            $result = $this->_helper->emserviceCall($this->_endpointUrl . '?access_token=' . $accessToken, $paramsArray);

            if($result['http_status'] == '401') {
                $this->_helper->log('Wrong Access Token');
                $this->_helper->dbUpdateOne("configuration_status", 0);
                $this->_helper->dbUpdateOne('configuration_last_errors', 'Configure Endpoints: Wrong Access Token');

            } elseif($result['http_status'] == '200') {
                $this->_helper->log($result['content']);
                $this->_helper->dbUpdateOne("configuration_status", 1);

            } elseif($result['http_status'] == '400') {
                $resultArray = json_decode($result['content'], true);

                $this->_helper->log($result['content']);
                $this->_helper->dbUpdateOne("configuration_status", 0);
                $this->_helper->dbUpdateOne('configuration_last_errors', implode(', ', $resultArray['errors']));

            } else {
                $this->_helper->log('Unknown HTTP Response');
            }

            $this->_helper->log('Call configure_endpoints END');
            /* */

            /* Conversion + Lead Tracking */
            if($this->_helper->getConfig('easymarketingsection/easmarketinggeneral/google_tracking_enable') || $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/facebook_tracking_enable')) {
                $this->_helper->log('Call conversion_tracker START');

                $result = $this->_helper->emserviceCall($this->_conversionTrackerUrl . '?access_token=' . $accessToken . '&website_url=' . parse_url($baseUrl, PHP_URL_HOST));

                if($result['http_status'] == '401') {
                    $this->_helper->log('Wrong Access Token');

                } elseif($result['http_status'] == '200') {
                    $this->_helper->log($result['content']);
                    $resultArray = json_decode($result['content'], true);
                    $updatedGoogle = $this->_helper->dbUpdateOne("google_conversion_code", $resultArray['code']);
                    $updatedFb = 0;
                    if(!empty($resultArray['fb_code'])) {
                        $updatedFb = $this->_helper->dbUpdateOne("facebook_conversion_code", $resultArray['fb_code']);
                    }

                    if($updatedGoogle || $updatedFb) {
                        Mage::getSingleton('adminhtml/session')->addWarning('Für die Trackeraktivierung müssen die Magento Caches geleert werden.');
                    }
                } elseif($result['http_status'] == '422') {
                    $this->_helper->log($result['content']);
                } else {
                    $this->_helper->log('Unknown HTTP Response');
                }

                $this->_helper->log('Call conversion_tracker END');

                /***/

                $this->_helper->log('Call lead_tracker START');

                $result = $this->_helper->emserviceCall($this->_leadTrackerUrl . '?access_token=' . $accessToken . '&website_url=' . parse_url($baseUrl, PHP_URL_HOST));

                if($result['http_status'] == '401') {
                    $this->_helper->log('Wrong Access Token');

                } elseif($result['http_status'] == '200') {
                    $this->_helper->log($result['content']);
                    $resultArray = json_decode($result['content'], true);
                    $updatedGoogle = $this->_helper->dbUpdateOne("google_lead_code", $resultArray['code']);
                    $updatedFb = 0;
                    if(!empty($resultArray['fb_code'])) {
                        $updatedFb = $this->_helper->dbUpdateOne("facebook_lead_code", $resultArray['fb_code']);
                    }

                    if($updatedGoogle || $updatedFb) {
                        Mage::getSingleton('adminhtml/session')->addWarning('Für die Trackeraktivierung müssen die Magento Caches geleert werden.');
                    }
                } elseif($result['http_status'] == '422') {
                    $this->_helper->log($result['content']);
                } else {
                    $this->_helper->log('Unknown HTTP Response');
                }

                $this->_helper->log('Call lead_tracker END');
            }
            /* */

            /* Google Remarketing Tracking */
            if($this->_helper->getConfig('easymarketingsection/easmarketinggeneral/google_remarketing_enable')) {
                $this->_helper->log('Call google_remarketing_code START');

                $result = $this->_helper->emserviceCall($this->_googleRemarketingUrl . '?access_token=' . $accessToken . '&website_url=' . parse_url($baseUrl, PHP_URL_HOST));

                if($result['http_status'] == '401') {
                    $this->_helper->log('Wrong Access Token');

                } elseif($result['http_status'] == '200') {
                    $this->_helper->log($result['content']);
                    $resultArray = json_decode($result['content'], true);

                    if(!empty($resultArray['code'])) {
                        $updatedGoogle = $this->_helper->dbUpdateOne("google_remarketing_code", $resultArray['code']);

                        if($updatedGoogle) {
                            Mage::getSingleton('adminhtml/session')->addWarning('Für die Trackeraktivierung müssen die Magento Caches geleert werden.');
                        }
                    } else {
                        Mage::getSingleton('adminhtml/session')->addError('Kein Remarketing Code verfügbar.');
                    }

                } elseif($result['http_status'] == '204') {
                    $resultArray = json_decode($result['content'], true);
                    $this->_helper->log($result['content']);
                    Mage::getSingleton('adminhtml/session')->addError($resultArray['message']);
                } else {
                    $this->_helper->log('Unknown HTTP Response');
                }

                $this->_helper->log('Call google_remarketing_code END');
            }
            /* */

        } catch(Exception $exception) {
            $errorMessage = $exception->getFile() . " - " . $exception->getLine() . ": " . $exception->getMessage() . "\n" . $exception->getTraceAsString();
            $this->_helper->error($errorMessage);
        }

        $this->_helper->log('Easymarketing Configuration Save END');
    }

}