<?php

class Monext_Payline_Helper_Category extends Mage_Core_Helper_Data
{
    protected $_category_tree;

    public function getAllCategoriesWithFullpathName()
    {
        if (is_null($this->_category_tree)) {
            $this->_category_tree = array();

            $categoryCollection = Mage::getModel('catalog/category')->getCollection()
                    ->addAttributeToSelect('name')
                    ->addAttributeToFilter('is_active','1');

            foreach ($categoryCollection as $category)
            {
                $path = explode('/', $category->getPath());
                $categoryFullPath = array();
                foreach ($path as $pathId)
                {
                    $categoryByPath = $categoryCollection->getItemById($pathId);
                    if($categoryByPath) {
                        $categoryFullPath[] = $categoryByPath->getName();
                    }
                }
                $this->_category_tree[$category->getId()] = implode('/', $categoryFullPath);
            }
        }

        return $this->_category_tree;
    }

    public function getCategoryFullpathName($category_id)
    {
        $categoryTree = $this->getAllCategoriesWithFullpathName();

        return (!empty($categoryTree[$category_id])) ? $categoryTree[$category_id] : '';
    }


} // end class
