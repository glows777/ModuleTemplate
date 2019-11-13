<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 11 2018
 */

use Modules\ModuleTemplate\Models\ModuleTemplate;
use Models\Providers;

class ModuleTemplateController extends BaseController
{

    private $moduleDir;

    /**
     * Basic initial class
     */
    public function initialize(): void
    {
        $modulesDir                = $this->getDI()->getModulesDir();
        $this->moduleDir           = "{$modulesDir}/ModuleTemplate";
        $this->view->logoImagePath = "{$this->url->get()}public/img/cache/ModuleTemplate/logo.png";
        parent::initialize();
    }

    /**
     * Index page controller
     */
    public function indexAction(): void
    {
        $footerCollection = $this->assets->collection('footerJS');
        $footerCollection->addJs('js/pbx/main/form.js', true);
        $footerCollection->addJs("{$this->moduleDir}/public/js/module-template-index.js", true);
        $headerCollectionCSS = $this->assets->collection('headerCSS');
        $headerCollectionCSS->addCss("{$this->moduleDir}/public/css/module-template.css", true);

        $settings = ModuleTemplate::findFirst();
        if ($settings === false) {
            $settings = new ModuleTemplate();
        }
        // Для примера добавим на форму меню провайдеров
        $providers = Providers::find();
        $providersList = [];
        foreach ($providers as $provider){
            $providersList[ $provider->uniqid ] = $provider->getRepresent();
        }
        $options['providers']=$providersList;

        $this->view->form = new ModuleTemplateForm($settings, $options);
        $this->view->pick("{$this->moduleDir}/app/views/index");
    }

    /**
     * Save settings AJAX action
     */
    public function saveAction() :void
    {
        if ( ! $this->request->isPost()) {
            return;
        }
        $data   = $this->request->getPost();
        $record = ModuleTemplate::findFirst();

        if ( ! $record) {
            $record = new ModuleTemplate();
        }
        $this->db->begin();
        foreach ($record as $key => $value) {
            switch ($key) {
                case 'id':
                    break;
                case 'checkbox_field':
                case 'toggle_field':
                    if (array_key_exists($key, $data)) {
                        $record->$key = ($data[$key] === 'on') ? '1' : '0';
                    } else {
                        $record->$key = '0';
                    }
                    break;
                default:
                    if (array_key_exists($key, $data)) {
                        $record->$key = $data[$key];
                    } else {
                        $record->$key = '';
                    }
            }
        }

        if ($record->save() === FALSE) {
            $errors = $record->getMessages();
            $this->flash->error(implode('<br>', $errors));
            $this->view->success = false;
            $this->db->rollback();

            return;
        }

        $this->flash->success($this->translation->_('ms_SuccessfulSaved'));
        $this->view->success = false;
        $this->db->commit();
    }

}