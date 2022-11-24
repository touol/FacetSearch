<?php

/**
 * The home manager controller for FacetSearch.
 *
 */
class FacetSearchHomeManagerController extends modExtraManagerController
{
    /** @var FacetSearch $FacetSearch */
    public $FacetSearch;


    /**
     *
     */
    public function initialize()
    {
        $this->FacetSearch = $this->modx->getService('FacetSearch', 'FacetSearch', MODX_CORE_PATH . 'components/facetsearch/model/');
        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return ['facetsearch:manager', 'facetsearch:default'];
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('facetsearch');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        $this->addCss($this->FacetSearch->config['cssUrl'] . 'mgr/main.css');
        $this->addJavascript($this->FacetSearch->config['jsUrl'] . 'mgr/facetsearch.js');
        $this->addJavascript($this->FacetSearch->config['jsUrl'] . 'mgr/misc/utils.js');
        $this->addJavascript($this->FacetSearch->config['jsUrl'] . 'mgr/misc/combo.js');
        $this->addJavascript($this->FacetSearch->config['jsUrl'] . 'mgr/misc/default.grid.js');
        $this->addJavascript($this->FacetSearch->config['jsUrl'] . 'mgr/misc/default.window.js');
        $this->addJavascript($this->FacetSearch->config['jsUrl'] . 'mgr/widgets/items/grid.js');
        $this->addJavascript($this->FacetSearch->config['jsUrl'] . 'mgr/widgets/items/windows.js');
        $this->addJavascript($this->FacetSearch->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->FacetSearch->config['jsUrl'] . 'mgr/sections/home.js');

        $this->addJavascript(MODX_MANAGER_URL . 'assets/modext/util/datetime.js');

        $this->FacetSearch->config['date_format'] = $this->modx->getOption('facetsearch_date_format', null, '%d.%m.%y <span class="gray">%H:%M</span>');
        $this->FacetSearch->config['help_buttons'] = ($buttons = $this->getButtons()) ? $buttons : '';

        $this->addHtml('<script type="text/javascript">
        FacetSearch.config = ' . json_encode($this->FacetSearch->config) . ';
        FacetSearch.config.connector_url = "' . $this->FacetSearch->config['connectorUrl'] . '";
        Ext.onReady(function() {MODx.load({ xtype: "facetsearch-page-home"});});
        </script>');
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        $this->content .=  '<div id="facetsearch-panel-home-div"></div>';
        return '';
    }

    /**
     * @return string
     */
    public function getButtons()
    {
        $buttons = null;
        $name = 'FacetSearch';
        $path = "Extras/{$name}/_build/build.php";
        if (file_exists(MODX_BASE_PATH . $path)) {
            $site_url = $this->modx->getOption('site_url').$path;
            $buttons[] = [
                'url' => $site_url,
                'text' => $this->modx->lexicon('facetsearch_button_install'),
            ];
            $buttons[] = [
                'url' => $site_url.'?download=1&encryption_disabled=1',
                'text' => $this->modx->lexicon('facetsearch_button_download'),
            ];
            $buttons[] = [
                'url' => $site_url.'?download=1',
                'text' => $this->modx->lexicon('facetsearch_button_download_encryption'),
            ];
        }
        return $buttons;
    }
}