<?php

require_once(Mage::getBaseDir() . '/app/code/community/Monext/Payline/lib/paylineSDK.php');


/**
 * Renderer for Payline banner in System Configuration
 *
 */
class Monext_Payline_Block_Adminhtml_System_Config_Fieldset_Welcome
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'payline/system/config/fieldset/welcome.phtml';

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $elementOriginalData = $element->getOriginalData();
        if (isset($elementOriginalData['help_link'])) {
            $this->setHelpLink($elementOriginalData['help_link']);
        }
        $js = '
            paylineToggleSection = function(section, id, url) {
                if (section.up("div").hasClassName("can-be-disabled") && section.up("div").hasClassName("disabled")) {
                    return false;
                }
                Fieldset.toggleCollapse(id, url);
            }


            paylineToggleSolution = function(id, url) {
                var doScroll = false;
                Fieldset.toggleCollapse(id, url);
                if ($(this).hasClassName("open")) {
                    $$(".with-button button.button").each(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName("open")) {
                            $(anotherButton).click();
                            doScroll = true;
                        }
                    }.bind(this));
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this));
                    window.scrollTo(pos[0], pos[1] - 45);
                }
            }

            paylineToggleAllSections = function()
            {
                var isEnabled = $("payline_payline_common_payline_enabled").value
                $$(".can-be-disabled").each(function(elem) {
                    if (isEnabled>0) {
                        elem.removeClassName("disabled");
                    } else {
                        if( elem.adjacent("fieldset")) {
                            var sectionId = elem.adjacent("fieldset")[0].id;
                            var state = $(sectionId+"-state").value;
                            if(state>0) {
                                Fieldset.toggleCollapse(sectionId);
                            }
                        }
                        elem.addClassName("disabled");
                    }
                })
            }


            document.observe("dom:loaded", function() {
                paylineToggleAllSections();
                $("payline_payline_common_payline_enabled").observe("change", paylineToggleAllSections);
            });
        ';
        return $this->toHtml() . $this->helper('adminhtml/js')->getScript($js);
    }

    public function getVersion()
    {
        $version = (string) $this->_getModuleConfig()->version;

        return $version;
    }

    public function getRelease()
    {
        $release = (string) $this->_getModuleConfig()->release;

        return $release;
    }

    protected function _getModuleConfig()
    {
        $config  = Mage::getConfig();
        return $config->getModuleConfig('Monext_Payline');
    }

    public function getPaylineLogo()
    {
        return $this->getSkinUrl('images/monext/payline-logo.png',  array('_area'=>'frontend'));
    }

    public function isProduction()
    {
        return Mage::helper('payline')->isProduction();
    }



}
