<?php

/**
 * Class Webguys_Easytemplate_Model_Group
 *
 * @method setEntityType
 * @method getEntityType
 * @method setEntityId
 * @method getEntityId
 * @method setStoreId
 * @method getStoreId
 * @method setCopyOf
 * @method getCopyOf
 * */
class Webguys_Easytemplate_Model_Group extends Mage_Core_Model_Abstract
{

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'easytemplate_group';

    protected function _construct()
    {
        $this->_init('easytemplate/group');
    }

    public function getFrontendUrl()
    {
        if ($this->getEntityType() == Webguys_Easytemplate_Helper_Page::ENTITY_TYPE_PAGE) {
            $page = Mage::getModel('cms/page');
            $page->load($this->getEntityId());

            $urlModel = Mage::getModel('core/url')->setStore($page->getData('_first_store_id'));
            $href = $urlModel->getUrl(
                $page->getIdentifier(),
                array(
                    '_current' => false,
                    '_query' => '___store=' . $page->getStoreCode() . '&easytemplate_preview=' . $this->getPreviewHash()
                )
            );

            return $href;
        }

        // TODO: Other Entity-Types?

        return null;
    }

    public function getCopyOfInstance()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $collection = Mage::getModel('easytemplate/group')->getCollection()
                ->addFieldToFilter('entity_type', $this->getEntityType())
                ->addFieldToFilter('entity_id', $this->getEntityId())
                ->addFieldToFilter('copy_of', $this->getId());

            if ($collection->getSize() == 1) {
                $group = Mage::getModel('easytemplate/group');
                $group->load($collection->setPageSize(1)->setCurPage(1)->getFirstItem()->getId());

                if ($group->getId()) {
                    return $group;
                }
            }
        } elseif ($preview = Mage::app()->getRequest()->getParam('easytemplate_preview')) {
            $collection = Mage::getModel('easytemplate/group')->getCollection()
                ->addFieldToFilter('entity_type', $this->getEntityType())
                ->addFieldToFilter('entity_id', $this->getEntityId())
                ->addFieldToFilter('copy_of', $this->getId());

            if ($collection->getSize() == 1) {
                /** @var Webguys_Easytemplate_Model_Group $previewGroup */
                $previewGroup = $collection->setPageSize(1)->setCurPage(1)->getFirstItem();
                if ($previewGroup->getPreviewHash() == $preview) {
                    // Dispatch Event to (may) disable Varnish Caching
                    Mage::dispatchEvent('easytemplate_rendering_preview', array('group' => $previewGroup));

                    $previewGroup->load($collection->getFirstItem()->getId());
                    return $previewGroup;
                }
            }
        }

        return $this;
    }

    public function getPreviewHash()
    {
        return md5(
            Mage::helper('core')->encrypt(
                $this->getEntityId() . $this->getEntityType() . $this->getId() . $this->getCopyOf()
            )
        );
    }

    public function duplicate()
    {
        if (!$this->getId()) {
            throw new Exception("Could not clone empty entity");
        }

        /** @var $newGroup Webguys_Easytemplate_Model_Group */
        $newGroup = Mage::getModel('easytemplate/group');
        $newGroup->setData($this->getData());
        $newGroup->setId(null);
        $newGroup->setCopyOf($this->getId());
        $newGroup->save();

        foreach ($this->getTemplateCollection() AS $templateModel) {
            /** @var $newTemplate Webguys_Easytemplate_Model_Template */
            $newTemplate = $templateModel->duplicate($newGroup->getId());
            $newTemplate->save();
        }

        return $newGroup;
    }

    public function importData($data)
    {
        if (!$this->getId()) {
            throw new Exception("Could not import data to empty entity");
        }

        /** @var $helper Webguys_Easytemplate_Helper_Cache */
        $helper = Mage::helper('easytemplate/cache');
        $helper->flushCache();

        foreach ($data AS $id => $template_data) {

            /** @var $template Webguys_Easytemplate_Model_Template */
            $template = Mage::getModel('easytemplate/template');
            if (is_numeric($id)) {
                $template->load($id);
            } else {
                // Used for file handling to identify entries of array
                $template->setTemporaryId($id);
            }
            $template->setGroupId($this->getId());

            if ($template_data['is_delete'] == '1') {
                if ($template->getId()) {
                    $template->delete();
                }
            } else {
                $template->importData($template_data);
                $template->save();
            }

        }

    }

    /**
     * @return Webguys_Easytemplate_Model_Template[]
     */
    public function getTemplateCollection()
    {
        /** @var $configModel Webguys_Easytemplate_Model_Input_Parser */
        $configModel = Mage::getSingleton('easytemplate/input_parser');
        $validTemplates = array();

        foreach ($configModel->getTemplates() as $template) {
            $validTemplates[] = $template->getCode();
        }

        /** @var $collection Webguys_Easytemplate_Model_Resource_Template_Collection */
        $collection = Mage::getModel('easytemplate/template')->getCollection();
        $collection->addGroupFilter($this);
        $collection->addFieldToFilter('code', array('in' => $validTemplates));
        $collection->getSelect()->order('main_table.position');

        foreach ($collection AS &$model) {
            $model->load($model->getId());
        }

        return $collection;
    }

    protected function _afterDelete()
    {
        $dir = Mage::getBaseDir('media') . DS . 'easytemplate' . DS . $this->getId();
        $io = new Varien_Io_File();
        if ($io->fileExists($dir)) {
            Mage::helper('easytemplate/file')->rrmdir($dir);
        }
        return parent::_afterDelete();
    }

}
