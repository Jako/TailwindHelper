<?php
/**
 * @package consentfriend
 * @subpackage plugin
 */

namespace TreehillStudio\TailwindHelper\Plugins\Events;

use TreehillStudio\TailwindHelper\Plugins\Plugin;

class OnManagerPageBeforeRender extends Plugin
{
    public function process()
    {
        if ($this->modx->user && $this->modx->user->hasSessionContext('mgr')) {
            $assetsUrl = $this->tailwindhelper->getOption('assetsUrl');
            $jsUrl = $this->tailwindhelper->getOption('jsUrl') . 'mgr/';
            $jsSourceUrl = $assetsUrl . '../../../source/js/mgr/';
            $cssUrl = $this->tailwindhelper->getOption('cssUrl') . 'mgr/';
            $cssSourceUrl = $assetsUrl . '../../../source/css/mgr/';

            $this->modx->controller->addLexiconTopic('tailwindhelper:default');

            if ($this->tailwindhelper->getOption('debug') && ($this->tailwindhelper->getOption('assetsUrl') != MODX_ASSETS_URL . 'components/tailwindhelper/')) {
                $this->modx->controller->addCss($cssSourceUrl . 'tailwindhelper.css?v=v' . $this->tailwindhelper->version);
                $this->modx->controller->addJavascript($jsSourceUrl . 'tailwindhelper.js?v=v' . $this->tailwindhelper->version);
                $this->modx->controller->addJavascript($jsSourceUrl . 'helper/util.js?v=v' . $this->tailwindhelper->version);
            } else {
                $this->modx->controller->addCss($cssUrl . 'tailwindhelper.min.css?v=v' . $this->tailwindhelper->version);
                $this->modx->controller->addJavascript($jsUrl . 'tailwindhelper.min.js?v=v' . $this->tailwindhelper->version);
            }
            $this->modx->controller->addHtml(
                '<script type="text/javascript">
                    Ext.onReady(function() {
                        TailwindHelper.config = ' . json_encode($this->tailwindhelper->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . ';
                    });
                </script>'
            );
        }
    }
}
