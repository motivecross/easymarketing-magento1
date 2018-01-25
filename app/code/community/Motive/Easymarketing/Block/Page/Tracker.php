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

    public function getGoogleConversionTracker() {
        if($this->_helper->getConfig('easymarketingsection/easmarketinggeneral/google_tracking_enable')) {
            $code = $this->_helper->dbFetchOne("google_conversion_code");

            try {
                $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
                $order = Mage::getModel('sales/order')->load($orderId);
                $total = $order->getGrandTotal();

                $code = str_replace('1.00', $total, $code);

            } catch(Exception $exception) {
                $errorMessage = $exception->getFile() . " - " . $exception->getLine() . ": " . $exception->getMessage() . "\n" . $exception->getTraceAsString();
                $this->_helper->error($errorMessage);
            }

            return $code;
        } else {
            return FALSE;
        }
    }

    public function getFacebookConversionTracker() {
        if($this->_helper->getConfig('easymarketingsection/easmarketinggeneral/facebook_tracking_enable')) {
            $code = $this->_helper->dbFetchOne("facebook_conversion_code");

            try {
                $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
                $order = Mage::getModel('sales/order')->load($orderId);
                $total = $order->getGrandTotal();

                $code = str_replace('0.00', $total, $code);

            } catch(Exception $exception) {
                $errorMessage = $exception->getFile() . " - " . $exception->getLine() . ": " . $exception->getMessage() . "\n" . $exception->getTraceAsString();
                $this->_helper->error($errorMessage);
            }

            return $code;
        } else {
            return FALSE;
        }
    }

    public function getGoogleRemarketing() {
        if($this->_helper->getConfig('easymarketingsection/easmarketinggeneral/google_remarketing_enable')) {
            $code = $this->_helper->dbFetchOne("google_remarketing_code");

            try {
                $replaceArray = array();

                $replaceArray[0] = "ecomm_prodid: [],";
                $replaceArray[2] = "";

                switch($this->getAction()->getFullActionName()) {
                    case 'cms_index_index':
                        $replaceArray[1] = "ecomm_pagetype: 'home',";
                        break;
                    case 'catalog_product_view':
                        $replaceArray[1] = "ecomm_pagetype: 'product'";

                        $product = Mage::registry('current_product');
                        if(!empty($product->getId())) {
                            $replaceArray[0] = "ecomm_prodid: " . $product->getId() . ",";
                            $replaceArray[2] = "ecomm_totalvalue: " . $product->getFinalPrice() . ",";

                            if($product->getTypeId() == "configurable") {
                                $children = $product->getTypeInstance()->getUsedProducts($product);
                                $child = Mage::getModel('catalog/product')->load(current($children)->getId());
                                $replaceArray[2] = "ecomm_totalvalue: " . $child->getFinalPrice() . ",";
                            }

                            $categoryIds = $product->getCategoryIds();
                            if(count($categoryIds)){
                                $category = Mage::getModel('catalog/category')->load($categoryIds[0]);

                                $categoryName = $category->getName();
                                $replaceArray[1] = "ecomm_pagetype: 'product',\necomm_category: '" . $categoryName . "',";
                            }
                        }
                        break;
                    case 'catalog_category_view':
                        $category = Mage::registry('current_category');
                        $categoryName = $category->getName();
                        $replaceArray[1] = "ecomm_pagetype: 'category',\necomm_category: '" . $categoryName . "',";
                        break;
                    case 'catalogsearch_result_index':
                        $replaceArray[1] = "ecomm_pagetype: 'searchresults'";
                        break;
                    case 'checkout_onepage_success':
                        $replaceArray[1] = "ecomm_pagetype: 'purchase',";

                        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
                        $order = Mage::getModel('sales/order')->load($orderId);
                        $subTotal = $order->getSubtotal();

                        if(!empty($subTotal)) {
                            $replaceArray[2] = "ecomm_totalvalue: " . $subTotal . ",";
                        }
                        break;
                    case 'checkout_cart_index':
                    case 'checkout_onepage_index':
                    case 'checkout_index_index':
                        $replaceArray[1] = "ecomm_pagetype: 'cart',";

                        $items = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();
                        $productIdArray = array();
                        $totalPrice = 0;
                        foreach($items as $item) {
                            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $item->getSku());
                            $productIdArray[] = $product->getId();
                            $totalPrice += ($product->getPrice() * $item->getQty());
                        }
                        if(!empty($productIdArray)) {
                            $replaceArray[0] = "ecomm_prodid: [" . implode(",", $productIdArray) . "],";
                            $replaceArray[2] = "ecomm_totalvalue: " . $totalPrice . ",";
                        }

                        break;
                    default:
                        $replaceArray[1] = "ecomm_pagetype: 'other'";
                }

                $code = str_replace(array("ecomm_prodid: 'REPLACE_WITH_VALUE',", "ecomm_pagetype: 'REPLACE_WITH_VALUE',", "ecomm_totalvalue: 'REPLACE_WITH_VALUE',"), $replaceArray, $code);

            } catch(Exception $exception) {
                $errorMessage = $exception->getFile() . " - " . $exception->getLine() . ": " . $exception->getMessage() . "\n" . $exception->getTraceAsString();
                $this->_helper->error($errorMessage);
            }

            return $code;
        } else {
            return FALSE;
        }
    }
}