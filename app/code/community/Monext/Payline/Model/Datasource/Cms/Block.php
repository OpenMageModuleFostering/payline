<?php
class Monext_Payline_Model_Datasource_Cms_Block
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $options = Mage::getResourceModel('cms/block_collection')
                ->load();
            $this->_options = $this->_toOptionIdArray($options);
        }
        return $this->_options;
    }
    
    protected function _toOptionIdArray($options)
    {
        $res = array();
        $res[] = array('value' => '', 'label' => Mage::helper('payline')->__('none'));
        foreach ($options as $item) {
            $identifier = $item->getData('identifier');

            $data['value'] = $identifier;
            $data['label'] = $identifier;

            $res[] = $data;
        }

        return $res;
    }

}
