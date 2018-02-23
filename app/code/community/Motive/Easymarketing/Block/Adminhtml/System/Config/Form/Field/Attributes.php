<?php

#######
# Motive X
# Sylter Str. 15, 90425 Nürnberg, Germany
# Telefon: +49 (0)911/49 522 566
# Mail: info@motive.de
# Internet: www.motive-x.com
#######

class Motive_Easymarketing_Block_Adminhtml_System_Config_Form_Field_Attributes extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_helper;

    protected function _construct() {
        $this->_helper = Mage::helper('easymarketing');

        parent::_construct();
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {

        $html = '<table id="' . $element->getId() . '_table" class="ui_select_table" cellspacing="0" border="0" style="border: 1px solid #000;">';
        $html .= '<tbody><tr><td width="50%" style="padding: 0;">';

        $selected = explode(',', $element->getValue());

        $attributes = $this->_helper->getAllAttributes();

        $html .= '<div><ul id="' . $element->getId() . '_source" class="ui_select source sortable" style="list-style-type: none; margin-bottom: 0; height: 200px; overflow-x: hidden; overflow-y: scroll;">';
        if($attributes) {
            foreach($attributes as $attribute) {
                if(in_array($attribute['value'], $selected)) continue;
                $html .= '<li id="' . $element->getId() . '_source_' . $attribute['value'] . '" data-code="' . $attribute['value'] . '" style="border: 1px solid #bbb; background-color: #FFB6C1; padding: 3px; cursor: pointer; margin: 2px; font-size: 12px;">' . $attribute['label'] . '</li>';
            }
        }

        $html .= '</ul></div></td>';

        $html .= '<td width="50%" style="padding: 0;"><div><ul id="' . $element->getId() . '_selected" class="ui_select selected sortable" style="list-style-type: none; margin-bottom: 0; height: 200px; overflow-x: hidden; overflow-y: scroll;">';

        $selectedIterator = 0;
        $defaultValue = "";
        foreach($selected as $value) {
            if($selectedIterator == 0) {
                $defaultValue = $value;
                $selectedIterator++;
                continue;
            }
            if(!empty($value)) {
                $attribute = $this->_helper->getAttributeByCode($value);

                $html .= '<li id="' . $element->getId() . '_selected_' . $attribute['value'] . '" data-code="' . $attribute->getAttributeCode() . '" style="border: 1px solid #bbb; background-color: #90EE90; padding: 3px; cursor: pointer; margin: 2px; font-size: 12px;">' . $attribute->getStoreLabel() . ' (' . $attribute->getAttributeCode() . ')' . '</li>';
            }
        }

        $html .= '</ul></div></td></tr></tbody></table>';
        $html .= '<br><div><span style="display: inline-block; width: 30%;">' . $this->__("Default Value") . ': </span><input id="' . $element->getId() . '_default" type="text" style="width:70%;" value="' . $defaultValue . '"></span><br><br>';
        $html .= '<div style="display:none;">' . $element->getElementHtml() . '</div>';
        $html .= '<script type="text/javascript">
                        document.observe("dom:loaded", function() {
                            Position.includeScrollOffsets = true;
                            Sortable.create("' . $element->getId() . '_source", {containment: ["' . $element->getId() . '_source", "' . $element->getId() . '_selected"], dropOnEmpty: true, constraint: "horizontal", onUpdate: sortableStopped_' . $element->getId() . '});
                            Sortable.create("' . $element->getId() . '_selected", {containment: ["' . $element->getId() . '_selected", "' . $element->getId() . '_source"], dropOnEmpty: true, onUpdate: sortableStopped_' . $element->getId() . '});
                            
                            Event.observe("' . $element->getId() . '_default", "change", function() {
                                var values = [];
                                values.push(document.getElementById("' . $element->getId() . '_default").value);
                                $("' . $element->getId() . '_selected").childElements().each(function(element) {
                                    values.push(element.readAttribute("data-code"));
                                });
                                document.getElementById("' . $element->getId() . '").value = values.join(",");   
                            });
                        });
                        
                        var sortableStopped_' . $element->getId() . ' = function() {
                            if(this.element.id.indexOf("_selected") < 0) return;
                            var values = [];
                            values.push(document.getElementById("' . $element->getId() . '_default").value);
                            $("' . $element->getId() . '_selected").childElements().each(function(element) {
                                values.push(element.readAttribute("data-code"));
                            });
                            document.getElementById("' . $element->getId() . '").value = values.join(",");  
                        };
                </script>';

        return $html;
    }
}