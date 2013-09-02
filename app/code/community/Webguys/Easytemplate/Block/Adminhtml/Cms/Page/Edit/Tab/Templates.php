<?php

class Webguys_Easytemplate_Block_Adminhtml_Cms_Page_Edit_Tab_Templates
    extends Mage_Adminhtml_Block_Widget
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function __construct()
    {
        parent::__construct();
        $this->setShowGlobalIcon(true);
        $this->setTemplate('easytemplate/cms/page/edit/templates.phtml');
    }

    protected function _prepareLayout()
    {
        $this->setChild('add_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('easytemplate')->__('Add New Template'),
                    'class' => 'add',
                    'id'    => 'add_new_template'
                ))
        );

        $this->setChild('templates_box',
            $this->getLayout()->createBlock('easytemplate/adminhtml_cms_page_edit_tab_templates_template')
        );

        return parent::_prepareLayout();
    }

    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    public function getTemplatesBoxHtml()
    {
        return $this->getChildHtml('templates_box');
    }

    public function isInTemplateMode()
    {
        if ($page = Mage::registry('cms_page')) {
            return $page->getViewMode() == 'easytemplate';
        }
        return false;
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('easytemplate')->__('Templates');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('easytemplate')->__('Templates');
    }

    /**
     * Returns status flag about this tab can be showen or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return false
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $action
     * @return bool
     */
    protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('cms/page/' . $action);
    }
}