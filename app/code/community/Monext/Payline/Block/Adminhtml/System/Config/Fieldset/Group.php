<?php
/**
 * Fieldset renderer for PayPal solutions group
 *
 * @category    Mage
 * @package     Mage_Paypal
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Monext_Payline_Block_Adminhtml_System_Config_Fieldset_Group
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Return header comment part of html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        $groupConfig = $this->getGroup($element)->asArray();

        if (empty($groupConfig['help_url']) || !$element->getComment()) {
            return parent::_getHeaderCommentHtml($element);
        }

        $html = '<div class="comment">' . $element->getComment()
            . ' <a target="_blank" href="' . $groupConfig['help_url'] . '">'
            . Mage::helper('paypal')->__('Help') . '</a></div>';

        return $html;
    }

    /**
     * Return collapse state
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return bool
     */
    protected function _getCollapseState($element)
    {
        $extra = Mage::getSingleton('admin/session')->getUser()->getExtra();
        if (isset($extra['configState'][$element->getId()])) {
            return $extra['configState'][$element->getId()];
        }

        if ($element->getExpanded() !== null) {
            return 1;
        }

        return false;
    }

    /**
     * Return header title part of html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
//         return '<div class="entry-edit-head collapseable disabled" ><a id="' . $element->getHtmlId()
//         . '-head" href="#" onclick="return false;">' . $element->getLegend() . '</a></div>';

        return '<div class="entry-edit-head can-be-disabled collapseable" ><a id="' . $element->getHtmlId()
        . '-head" href="#" onclick="paylineToggleSection(this, \'' . $element->getHtmlId() . '\', \''
        . $this->getUrl('*/*/state') . '\'); return false;">' . $element->getLegend() . '</a></div>';
    }
}
