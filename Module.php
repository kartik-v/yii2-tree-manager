<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package yii2-tree
 * @version 1.0.0
 */

namespace kartik\tree;

use Yii;

/**
 * The Yii2 Tree management module
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class Module extends \yii\base\Module
{
    const NODE_ADD = 'add';
    const NODE_EDIT = 'edit';
    const NODE_DELETE = 'delete';
    const NODE_SELECT = 'select';
    
    /**
     * @var array the configuration of various CRUD actions
     * for managing the tree nodes
     */
    public $actions = [
        self::NODE_CREATE => '/treeview/node/create',
        self::NODE_UPDATE => '/treeview/node/update',
        self::NODE_DELETE => '/treeview/node/delete',
        self::NODE_VIEW   => '/treeview/node/view',
    ];
    
    /**
     * @var array the the internalization configuration for this module
     */
    public $i18n = [];

    /**
     * @inherit doc
     */
    public function init()
    {
        parent::init();
        $this->initI18N();

    }

    /**
     * Initialize i18N settings
     */
    public function initI18N()
    {
        Yii::setAlias('@kvtree', dirname(__FILE__));
        if (empty($this->i18n)) {
            $this->i18n = [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => '@kvtree/messages',
                'forceTranslation' => true
            ];
        }
        Yii::$app->i18n->translations['kvtree'] = $this->i18n;
    }
}