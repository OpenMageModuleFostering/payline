<?php


class Monext_Payline_Block_Adminhtml_System_Config_Form_Field_Contracts extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_yesNoRenderer=array();

    //protected $_renderer = 'select';
    protected $_renderer = 'onoffswitch';

    public function __construct()
    {
        $this->setTemplate('payline/system/config/form/field/array.phtml');
        parent::__construct();
    }

    protected function _getYesnoRenderer($input)
    {
        if (empty($this->_yesNoRenderer[$input])) {
            $this->_yesNoRenderer[$input] = $this->getLayout()->createBlock(
                    (($this->_renderer == 'select') ?'core/html_select' :'payline/adminhtml_system_config_html_onoffswitch'),
                    'contract_yesno_' . $input,
                    array('is_render_to_js_template' => true, 'name'=>'contract_list[#{id}]['.$input.']', 'id'=>$input)
            );
            $this->_yesNoRenderer[$input]->setOptions(array('1' => Mage::helper('payline')->__('X'), '0' => Mage::helper('payline')->__('-')));
            $this->_yesNoRenderer[$input]->setExtraParams('style="width:60px"');
        }
        return $this->_yesNoRenderer[$input];
    }

    /**
     * Prepare to render
     */
    protected function _prepareToRender()
    {
        $this->addColumn('name', array(
                'label'    => Mage::helper('payline')->__('Name'),
        ));

        $this->addColumn('number', array(
                'label'    => Mage::helper('payline')->__('Number'),
        ));

        $this->addColumn('point_of_sell', array(
                'label'    => Mage::helper('payline')->__('Point Of Sell'),
        ));

        $this->addColumn('is_primary', array(
                'label'    => Mage::helper('payline')->__('Primary'),
                'renderer' => $this->_getYesnoRenderer('is_primary'),
                'options' => array('1' => Mage::helper('payline')->__('X'), '0' => Mage::helper('payline')->__('-'))
        ));

        $this->addColumn('is_secondary', array(
                'label'    => Mage::helper('payline')->__('Secondary'),
                'renderer' => $this->_getYesnoRenderer('is_secondary'),
                'options' => array('1' => Mage::helper('payline')->__('X'), '0' => Mage::helper('payline')->__('-'))
        ));

        $this->addColumn('is_secure', array(
                'label'    => Mage::helper('payline')->__('Secure'),
                'renderer' => $this->_getYesnoRenderer('is_secure'),
                'options' => array('1' => Mage::helper('payline')->__('X'), '0' => Mage::helper('payline')->__('-'))
        ));

        $this->addColumn('is_included_wallet_list', array(
                'label'    => Mage::helper('payline')->__('Wallet'),
                'renderer' => $this->_getYesnoRenderer('is_included_wallet_list'),
                'options' => array('1' => Mage::helper('payline')->__('X'), '0' => Mage::helper('payline')->__('-'))
        ));

        $this->_addAfter = false;
    }

    /**
     * Add type property to column
     *
     * @param string $name
     * @param array $params
     */
    public function addColumn($name, $params)
    {
    	parent::addColumn($name, $params);
    	if(array_key_exists($name,$this->_columns))
    		$this->_columns[$name]['type'] = empty($params['type'])  ? 'readonly'   : $params['type'];
    }


    /**
     * Prepare existing row data object
     *
     * @param Varien_Object
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {

        if ($this->_renderer == 'select') {
            $defaultOptionSet = 'selected="selected"';
        } else {
            $defaultOptionSet = 'checked="checked"';
        }


        if ($row->getData('is_primary')) {
            $row->setData(
                    'option_extra_attr_' . $this->_getYesnoRenderer('is_primary')->calcOptionHash(1),
                    $defaultOptionSet
            );
        }

        if ($row->getData('is_secondary')) {
            $row->setData(
                    'option_extra_attr_' . $this->_getYesnoRenderer('is_secondary')->calcOptionHash(1),
                    $defaultOptionSet
            );
        }

        if ($row->getData('is_secure')) {
            $row->setData(
                    'option_extra_attr_' . $this->_getYesnoRenderer('is_secure')->calcOptionHash(1),
                    $defaultOptionSet
            );
        }

        if ($row->getData('is_included_wallet_list')) {
            $row->setData(
                    'option_extra_attr_' . $this->_getYesnoRenderer('is_included_wallet_list')->calcOptionHash(1),
                    $defaultOptionSet
            );
        }

    }

    /**
     * Check if type property is defined and render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     */
    protected function _renderCellTemplate($columnName)
    {
    	if (empty($this->_columns[$columnName])) {
    		throw new Exception('Wrong column name specified.');
    	}
    	$column     = $this->_columns[$columnName];
    	if(!array_key_exists('type',$column) || $column['type'] == 'text' || $column['renderer'])
    		return parent::_renderCellTemplate($columnName);

    	$inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';
    	if (!empty($column['type'])) {
    	    if ($column['type']=='readonly') {
        	    return '#{' . $columnName . '}';
    	    }
    	}

    	return '<input type="' . $column['type'] . '" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
    			($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
    			(isset($column['class']) ? $column['class'] : 'input-text') . '"'.
    			(isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '/>';

    }

    /**
     *
     */
    public function getImportContractUrl()
    {
        return Mage::helper("adminhtml")->getUrl('adminhtml/payline_managecontracts/importFromConfig');
    }

    public function canDisplayImportButton()
    {
        $element = $this->getElement();

        return (!$element->getCanUseWebsiteValue() && !$element->getCanUseDefaultValue());
    }
}
