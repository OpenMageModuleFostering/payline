<?php
class Monext_Payline_Model_Resource_Eav_Mysql4_Setup extends Mage_Eav_Model_Entity_Setup
{
    /**
     * @return array
     */
    public function getDefaultEntities(){
        return array(
            'customer'=>array(
                'entity_model'          => 'customer/customer',
                'table'                 => 'customer/entity',
                'increment_model'       => 'eav/entity_increment_numeric',
                'increment_per_store'   => false,
                'additional_attribute_table' => 'customer/eav_attribute',
                'entity_attribute_collection' => 'customer/eav_attribute',
                'attributes' => array(
                    'wallet_id'=>array(
                        'label'         => 'Wallet ID',
                        'type'          => 'varchar',
                        'visible'       => false,
                        'required'      => false,
                    )
                )
            )
        );
    }
}