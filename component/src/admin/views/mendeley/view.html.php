<?php

class MendeleyViewMendeley extends JViewLegacy {

    public function display($tpl = null) {
        $this->addToolbar();
    }

    protected function addToolbar() {
        JToolbarHelper::title('Mendeley');
        if (JFactory::getUser()->authorise('core.admin', 'com_mendeley')) {
            JToolbarHelper::preferences('com_mendeley');
        }
    }
}
