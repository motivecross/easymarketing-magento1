<?php

$installer = $this;

$installer->startSetup();

$installer->run("
    CREATE TABLE `easymarketing_data` (
      `data_id` int(11) NOT NULL auto_increment,
      `data_name` text,
      `data_value` text,
      `data_scope` int(11) NOT NULL,
      `data_modified` timestamp default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY  (`data_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    INSERT INTO easymarketing_data SET data_name = 'configuration_status', data_value = 0;
    INSERT INTO easymarketing_data SET data_name = 'configuration_last_errors', data_value = '';
    INSERT INTO easymarketing_data SET data_name = 'google_verification_enable', data_value = 0;
    INSERT INTO easymarketing_data SET data_name = 'google_verification_status', data_value = 0;
    INSERT INTO easymarketing_data SET data_name = 'google_verification_meta', data_value = '';
    INSERT INTO easymarketing_data SET data_name = 'google_conversion_code', data_value = '';
    INSERT INTO easymarketing_data SET data_name = 'facebook_conversion_code', data_value = '';
    INSERT INTO easymarketing_data SET data_name = 'google_lead_code', data_value = '';
    INSERT INTO easymarketing_data SET data_name = 'facebook_lead_code', data_value = '';
    INSERT INTO easymarketing_data SET data_name = 'google_remarketing_code', data_value = '';
");

$shopToken = sha1(mt_rand(10, 1000) . time());
Mage::getConfig()->saveConfig('easymarketingsection/easmarketinggeneral/shop_token', $shopToken, 'default', 0);

$installer->endSetup();