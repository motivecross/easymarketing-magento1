<?php

class Motive_Easymarketing_ApiController extends Mage_Core_Controller_Front_Action
{
    protected $_helper;

    public $jsonParameters = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;

    public function preDispatch() {
        $this->_helper = Mage::helper('easymarketing');
    }

    protected function sendResponse($result) {
        $this->getResponse()->setHeader('Content-type', 'application/json; charset=utf-8');
        $this->getResponse()->setBody(json_encode($result, $this->jsonParameters));
    }

    protected function getMappedConfig($field, $product) {
        $configVal = $this->_helper->getConfig('easymarketingsection/easymarketingassign/' . $field);

        $result = null;
        if(!empty($configVal)) {
            $attributeCodes = explode(',', $configVal);

            $attributeIterator = 0;
            foreach($attributeCodes as $attributeCode) {
                if($attributeIterator == 0) {
                    $attributeIterator++;
                    if(!empty($attributeCode)) {
                        return $attributeCode; // Default value
                    }
                    continue;
                }

                $value = $product->getData($attributeCode);
                if(empty($value)) {
                    $parentIDs = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                    if(!empty($parentIDs[0])) {
                        $parentProduct = Mage::getModel('catalog/product')->load($parentIDs[0]);
                        $value = $parentProduct->getData($attributeCode);
                        if(empty($value)) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }

                $attribute = $this->_helper->getAttributeByCode($attributeCode);
                $frontendInput = $attribute->getFrontendInput();

                if($frontendInput == 'multiselect' || $frontendInput == 'select') {
                    $multiselectValues = explode(',', $value);
                    $resultValues = array();
                    foreach($multiselectValues as $multiselectValue) {
                        $options = $attribute->getSource()->getAllOptions(false);
                        foreach($options as $option) {
                            if($option['value'] == $multiselectValue) {
                                $resultValues[] = $option['label'];
                                break;
                            }
                        }
                    }

                    $result = implode(", ", $resultValues);
                } else {
                    $result = $value;
                }
                break;
            }
        }

        return $result;
    }

    public function categoriesAction() {
        $this->_helper->log('Categories Endpoint START');

        try {

            $this->_helper->apiStart();

            $params = $this->_helper->getAllMandatoryParams(array('id'));

            if(!is_numeric($params['id']) || $params['id'] <= 0) {
                $this->_helper->sendErrorAndExit('Keine gültige ID');
            }

            $category = Mage::getModel('catalog/category')->load($params['id']);
            if(!$category->getId()) {
                $this->_helper->sendErrorAndExit('Keine Kategorie mit dieser ID');
            }

            $children = $category->getChildren();
            if(empty($children)) {
                $children = array();
            } else {
                $children = explode(',', $children);
            }

            $resultArray = array('id' => $category->getId(),
                'name' => $category->getName(),
                //'url' => $this->_categoryHelper->getCategoryUrl($category),
                'children' => $children
            );

            $this->sendResponse($resultArray);

        } catch(Exception $exception) {
            $errorMessage = $exception->getFile() . " - " . $exception->getLine() . ": " . $exception->getMessage() . "\n" . $exception->getTraceAsString();
            $this->_helper->error($errorMessage);
        }

        $this->_helper->log('Categories Endpoint END');

    }

    public function productsAction() {
        $this->_helper->log('Products Endpoint START');

        try {
            $this->_helper->apiStart();

            $params = $this->_helper->getAllMandatoryParams(array('offset', 'limit'));

            $offset = $limit = 0;

            $collection = Mage::getModel('catalog/product')->getCollection();
            $collection->setOrder('id', 'ASC');
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter('status', '1');
            $collection->addAttributeToFilter('type_id', array('neq' => 'bundle'));
            $collection->addAttributeToFilter('type_id', array('neq' => 'configurable'));
            $collection->addAttributeToFilter('type_id', array('neq' => 'grouped'));

            if(is_numeric($params['limit']) && $params['limit'] > 0) {
                $limit = $params['limit'];
                if(is_numeric($params['offset']) && $params['offset'] > 0) {
                    $offset = $params['offset'];
                }

                $collection->getSelect()->limit($limit, $offset);
            }

            $productsArray = array();
            foreach($collection->getItems() as $item) {
                $product = array();
                $productId = $item->getId();
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($item);

                $product['id'] = intval($productId);

                $parentIDs = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);

                $name = $this->getMappedConfig('name', $item);
                if(empty($name)) {
                    $name = $item->getName();
                }
                $product['name'] = $name;

                if($this->_helper->getConfig('easymarketingsection/easymarketingassign/useshortdescription')) {
                    $product['description'] = $item->getShortDescription();
                } else {
                    $product['description'] = $item->getDescription();
                }

                $product['categories'] = $item->getCategoryIds();

                $condition = $this->getMappedConfig('condition', $item);
                $conditionPossibilities = array('new', 'refurbished', 'used');
                if(in_array($condition, $conditionPossibilities)) {
                    $product['condition'] = $condition;
                } else {
                    $product['condition'] = 'new';
                }

                if($stockItem->getIsInStock()) {
                    $product['availability'] = 'in stock';
                } else {
                    $product['availability'] = 'not in stock';
                }

                $shippingProductId = 0;
                $price = $item->getPrice();
                if($item->getTypeId() == "configurable") {
                    $children = $item->getTypeInstance()->getUsedProducts($item);
                    $price = 9999999;
                    foreach ($children as $child){
                        if($child->getPrice() < $price) {
                            $price = $child->getPrice();
                            $shippingProductId = $child->getId();
                            $stockItem = $this->_stockItemRepository->get($shippingProductId);
                        }
                    }
                }

                $quantity = $stockItem->getQty();
                if(empty($quantity) && $quantity !== '0') {
                    $product['stock_quantity'] = null;
                } else {
                    $product['stock_quantity'] = intval($quantity);
                }

                $product['price'] = floatval($price);

                if($item->getVisibility() == 1 && !empty($parentIDs)) {
                    $urlProduct = Mage::getModel('catalog/product')->load($parentIDs[0]);
                    $product['url'] = $urlProduct->getProductUrl();
                } else {
                    $product['url'] = $item->getProductUrl();
                }

                if($item->getImage() == 'no_selection') {
                    $product['image_url'] = '';
                } else {
                    $product['image_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $item->getImage();
                }

                $product['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();

                $shippingArray = array();
                try {
                    /*$tmpInStock = $stockItem->getIsInStock();
                    if(!$tmpInStock) {
                        $tmpQty = $stockItem->getQty();
                        if($tmpQty < 1) {
                            $stockItem->setQty(1);
                        }
                        $stockItem->setIsInStock(1);
                        $stockItem->save();
                    }*/
                    $countryConf = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/shipping_countries');
                    if(empty($countryConf)) {
                        $countryConf = 'DE';
                    }
                    $countryArray = explode(",", $countryConf);
                    foreach($countryArray as $country) {
                        $quote = Mage::getModel('sales/quote');
                        if($shippingProductId != 0) {
                            $quoteProduct = Mage::getModel('catalog/product')->load($shippingProductId);
                        } else {
                            $quoteProduct = Mage::getModel('catalog/product')->load($productId);;
                        }
                        $quote->addProduct($quoteProduct);
                        $address = $quote->getShippingAddress();
                        $address->setCountryId($country);
                        $address->setCollectShippingRates(true);
                        $quote->setTotalsCollectedFlag(false)->collectTotals();
                        $address->collectShippingRates();

                        $rates = $address->getShippingRatesCollection();

                        $currentPrice = 9999999;
                        foreach($rates as $rate) {
                            $price = $rate->getData('price');
                            if($price < $currentPrice) {
                                $currentPrice = $price;
                            }
                        }
                        if($currentPrice == 9999999) {
                            continue;
                        } else {
                            $shippingArray[] = array('country' => $country, 'price' => floatval($currentPrice));
                        }
                    }
                    /*if(!$tmpInStock) {
                        $stockItem->setQty($tmpQty);
                        $stockItem->setIsInStock(0);
                        $stockItem->save();
                    }*/
                } catch(Exception $exception) {
                    $errorMessage = $exception->getFile() . " - " . $exception->getLine() . ": " . $exception->getMessage() . "\n". $exception->getTraceAsString();
                    $this->_helper->error($errorMessage);
                    $shippingArray = array();
                }

                $product['shipping'] = $shippingArray;

                $product['gtin'] = $this->getMappedConfig('gtin', $item);

                $product['google_category'] = $this->getMappedConfig('google_category', $item);
                $product['adult'] = $this->getMappedConfig('adult', $item);
                $product['brand'] = $this->getMappedConfig('brand', $item);
                $product['mpn'] = $this->getMappedConfig('mpn', $item);
                $product['unit_pricing_measure'] = $this->getMappedConfig('unit_pricing_measure', $item);
                $product['unit_pricing_base_measure'] = $this->getMappedConfig('unit_pricing_base_measure', $item);

                // If configurable product
                if(!empty($parentIDs)) {
                    $product['parent_id'] = $parentIDs[0];
                }
                $product['gender'] = $this->getMappedConfig('gender', $item);
                $product['age_group'] = $this->getMappedConfig('age_group', $item);
                $product['color'] = $this->getMappedConfig('color', $item);
                $product['size'] = $this->getMappedConfig('size', $item);
                $product['size_type'] = $this->getMappedConfig('size_type', $item);
                $product['size_system'] = $this->getMappedConfig('size_system', $item);
                $product['material'] = $this->getMappedConfig('material', $item);
                $product['pattern'] = $this->getMappedConfig('pattern', $item);

                $product['free_1'] = $this->getMappedConfig('free_1', $item);
                $product['free_2'] = $this->getMappedConfig('free_1', $item);
                $product['free_3'] = $this->getMappedConfig('free_1', $item);


                $productsArray[] = $product;
            }

            $resultArray = array('offset' => $offset,
                'products' => $productsArray
            );

            $this->sendResponse($resultArray);

        } catch(Exception $exception) {
            $errorMessage = $exception->getFile() . " - " . $exception->getLine() . ": " . $exception->getMessage() . "\n" . $exception->getTraceAsString();
            $this->_helper->error($errorMessage);
        }

        $this->_helper->log('Products Endpoint END');
    }
}