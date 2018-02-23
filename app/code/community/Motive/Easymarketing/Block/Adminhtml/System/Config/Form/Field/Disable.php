<?php

#######
# Motive X
# Sylter Str. 15, 90425 Nürnberg, Germany
# Telefon: +49 (0)911/49 522 566
# Mail: info@motive.de
# Internet: www.motive-x.com
#######

class Motive_Easymarketing_Block_Adminhtml_System_Config_Form_Field_Disable extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //$element->setDisabled('disabled');
        $element->setData('readonly', 1);
        $css = '<style type="text/css">input.input-text.input-disabled { background-color: #e9e9e9; border-color: #adadad; color: #303030; opacity: .5; cursor: not-allowed; }</style>';
        return $css . $element->getElementHtml();

    }
}