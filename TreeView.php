<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package yii2-tree
 * @version 1.0.0
 */

namespace kartik\tree;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\bootstrap\Dropdown;
use yii\base\InvalidConfigException;
use kartik\base\Widget;

/**
 * An extended tree widget for Yii Framework 2 based on 
 * the JSTree jQuery plugin.
 *
 * @see http://www.jstree.com
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class TreeView extends Widget
{
    /**
     * Data sources
     */
    const DATA_JSON = 'json';
    const DATA_ARRAY = 'array';
    const DATA_HTML = 'html';
    const DATA_AJAX = 'ajax';
    const DATA_LAZY = 'lazy';
    const DATA_CALLBACK = 'callback';
    
    /**
     * Plugins
     */
    const PLUGIN_CHECKBOX = 'checkbox';
    const PLUGIN_CONTEXT_MENU = 'contextmenu';
    const PLUGIN_DRAG_DROP = 'dnd';
    const PLUGIN_SEARCH = 'search';
    const PLUGIN_SORT = 'sort';
    const PLUGIN_STATE = 'state';
    const PLUGIN_TYPES = 'types';
    const PLUGIN_UNIQUE = 'unique';
    const PLUGIN_WHOLE_ROW = 'wholerow';

    /**
     * @var array the data type of the source data for the JSTree plugin. Must be 
     * one of the TreeView::DATA_ constants. Defaults to `TreeView::DATA_JSON`.
     */    
    public $dataType = self::DATA_JSON;
    
    /**
     * @var mixed the source data for the JSTree plugin
     */    
    public $data;
    
    /**
     * @var array the tree plugins to enable. By default all plugins 
     * are disabled. You must set this as `$key => $value`, where:
     * $key: string, is the plugin name (one of TreeView::PLUGIN_ constants)
     * $value: boolean, whether enabled (`true`) or disabled (`false`).
     */
    public $treePlugins = []
    
    /**
     * @var array the default tree plugins configuration.
     */
    protected $_defaultTreePlugins = [
        self::PLUGIN_CHECKBOX => false,
        self::PLUGIN_CONTEXT_MENU => false,
        self::PLUGIN_DRAG_DROP => false,
        self::PLUGIN_SEARCH => false,
        self::PLUGIN_SORT => false,
        self::PLUGIN_STATE => false,
        self::PLUGIN_TYPES => false,
        self::PLUGIN_UNIQUE => false,
        self::PLUGIN_WHOLE_ROW => false,
    ];
    
    /**
     * @inherit doc
     */
    public function init() {
        $this->initTreePlugins();
        parent::init();
    }
    
    /**
     * Initialize JSTree plugins
     */
    public function initTreePlugins() {
        $this->treePlugins += $this->_defaultTreePlugins;
        foreach ($this->treePlugins as $plugin => $status) {
            if ($status === true) {
                $this->pluginOptions['plugins'][] = $plugin;
            }
        }
    }
    
    /**
     * Initialize Source Data
     */
    public function initTreeSource() {        
        if ($this->source === self::DATA_HTML) {
            return;
        }
        $data = $this->data;
        if ($this->source === self::DATA_ARRAY) {
            $data = Json::encode($this->data);
        } elseif ($this->source === self::DATA_AJAX) {
            $data = $this->data;
        } 
    }
    
    
    /**
     * Register the tree plugins
     * @param yii\web\View $view the view instance
     */
    protected function registerTreePlugins($view) {
        $js = [];
        foreach ($this->treePlugins as $plugin => $status) {
            if (!$status === true) {
                $js[] = "js/jstree.{$plugin}";
            }
        }
        if (empty($js)) {
            return;
        }
        TreePluginAsset::register($view)->pluginJs = $js;
    }
    
    /**
     * Registers the needed assets
     */
    public function registerAssets()
    {
        $view = $this->getView();
        TreeViewAsset::register($view);
        $this->registerTreePlugins($view);
    }
}