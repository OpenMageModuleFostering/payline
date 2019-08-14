<?php

class Monext_Payline_Block_Adminhtml_Managecontracts_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Init form
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('contract_form');
        $this->setTitle(Mage::helper('payline')->__('Contract Informations'));
    }

    protected function _prepareForm()
    {
        $model = Mage::registry('current_contract');

        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getData('action'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));

        /*************************************************************************************************************/
        /* Fieldset 																								 */
        /*************************************************************************************************************/
        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => Mage::helper('payline')->__('Contract Informations'),
            'class'  => 'fieldset-wide'
        ));


		$fieldset->addField('name', 'text', array(
			'name'      => 'name',
			'label'     => Mage::helper('payline')->__('Name'),
			'title'     => Mage::helper('payline')->__('Name'),
			'required'  => true,
			'disabled'  => true
		));

		$fieldset->addField('number', 'text', array(
			'name'      => 'number',
			'label'     => Mage::helper('payline')->__('Number'),
			'title'     => Mage::helper('payline')->__('Number'),
			'required'  => true,
			'disabled'  => true
		));

		$fieldset->addField('point_of_sell', 'text', array(
			'name'      => 'point_of_sell',
			'label'     => Mage::helper('payline')->__('Point Of Sell'),
			'title'     => Mage::helper('payline')->__('Point Of Sell'),
			'required'  => true,
			'disabled'  => true
		));

		$fieldset->addField('is_primary', 'checkbox', array(
			'name'      => 'is_primary',
			'label'     => Mage::helper('payline')->__('Primary'),
			'title'     => Mage::helper('payline')->__('Primary'),
			'required'  => false,
			'checked'   => ($model->getIsPrimary() ? true : false),
			'onclick'   => 'this.value = this.checked ? 1 : 0'
		));

		$fieldset->addField('is_secondary', 'checkbox', array(
			'name'      => 'is_secondary',
			'label'     => Mage::helper('payline')->__('Secondary'),
			'title'     => Mage::helper('payline')->__('Secondary'),
			'required'  => false,
			'checked'   => ($model->getIsSecondary() ? true : false),
			'onclick'   => 'this.value = this.checked ? 1 : 0'
		));


		$fieldset->addField('is_secure', 'checkbox', array(
		        'name'      => 'is_secure',
		        'label'     => Mage::helper('payline')->__('Secure'),
		        'title'     => Mage::helper('payline')->__('Secure'),
		        'required'  => false,
		        'checked'   => ($model->getIsSecure() ? true : false),
		        'onclick'   => 'this.value = this.checked ? 1 : 0'
		));

        /*************************************************************************************************************/
        /* Id 																								         */
        /*************************************************************************************************************/

        // Process id is transmitted when editting processes
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', array(
                'name' => 'id',
            ));
        }

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}