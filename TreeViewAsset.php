<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package yii2-tree
 * @version 1.0.0
 */

namespace kartik\tree;

use Yii;

/**
 * Asset bundle for TreeView widget.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class TreeViewAsset extends \kartik\base\AssetBundle
{
    public function init()
    {
		$this->setSourcePath(__DIR__ . '/assets');
		$this->setupAssets('js', ['js/jstree']);
        parent::init();
    }
}
