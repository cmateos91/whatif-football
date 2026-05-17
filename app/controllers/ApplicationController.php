<?php

class ApplicationController
{
    protected $smarty;

    public function __construct()
    {
        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir(__DIR__ . '/../views/');
        $this->smarty->setCompileDir(__DIR__ . '/../../smarty/templates_c');
        $this->smarty->setPluginsDir(__DIR__ . '/../../lib/vendor/smarty/plugins/');
    }

    protected function render($template, $vars = [])
    {
        foreach ($vars as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        $this->smarty->assign('content_template', $template);
        $this->smarty->display('application.tpl');
    }

    protected function renderPartial($template, $vars = [])
    {
        foreach ($vars as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        $this->smarty->assign($template);
    }
}