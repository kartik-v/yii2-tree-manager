<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package yii2-tree
 * @version 1.0.0
 */

namespace kartik\tree;

use Yii;

/**
 * Asset bundle for TreeView widget for the JSTree plugins. 
 * Includes assets from JSTree jQuery plugin.
 *
 * @see http://www.jstree.com
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class TreePluginAsset extends \kartik\base\AssetBundle
{
    public $pluginJs = [];
    
    public function init()
    {
		$this->setSourcePath(__DIR__ . '/assets');
        if (!empty($this->pluginJs)) {
            $this->setupAssets('js', $pluginJs);
        }
        parent::init();
    }
}
