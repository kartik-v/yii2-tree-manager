<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2022
 * @package   yii2-tree
 * @version   1.1.3
 */

namespace kartik\tree;

use Yii;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\web\Application as WebApplication;

/**
 * Module is the tree management module for Yii Framework 2.0 that enables the [[TreeView]] widget functionality.
 *
 * To use, configure the module named `treemanager` in the modules section of your Yii configuration file.
 *
 * For example,
 * 
 * ```php
 * 'modules' => [
 *    'treemanager' =>  [
 *         'class' => '\kartik\tree\Module',
 *         // other module settings, refer detailed documentation
 *     ]
 * ]
 * ```
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since  1.0
 */
class Module extends \kartik\base\Module
{
    /**
     * @var string module name for the Krajee Tree management module
     */
    const MODULE = 'treemanager';
    /**
     * @var string manage node action
     */
    const NODE_MANAGE = 'manage';
    /**
     * @var string remove node action
     */
    const NODE_REMOVE = 'remove';
    /**
     * @var string move node action
     */
    const NODE_MOVE = 'move';
    /**
     * @var string save node action
     */
    const NODE_SAVE = 'save';
    /**
     * @var int section part 1 of the tree details form view
     */
    const VIEW_PART_1 = 1;
    /**
     * @var int section part 2 of the tree details form view
     */
    const VIEW_PART_2 = 2;
    /**
     * @var int section part 3 of the tree details form view
     */
    const VIEW_PART_3 = 3;
    /**
     * @var int section part 4 of the tree details form view
     */
    const VIEW_PART_4 = 4;
    /**
     * @var int section part 5 of the tree details form view
     */
    const VIEW_PART_5 = 5;

    /**
     * @var array the configuration of nested set attributes structure.
     */
    public $treeStructure = [];

    /**
     * @var array the configuration of additional data attributes for the tree
     */
    public $dataStructure = [];

    /**
     * @var string the name to identify the nested set behavior name in the [[\kartik\tree\models\Tree]] model.
     */
    public $treeBehaviorName = 'tree';

    /**
     * @var array the default configuration settings for the tree view widget
     */
    public $treeViewSettings = [
        'nodeView' => '@kvtree/views/_form',
        'nodeAddlViews' => [
            self::VIEW_PART_1 => '',
            self::VIEW_PART_2 => '',
            self::VIEW_PART_3 => '',
            self::VIEW_PART_4 => '',
            self::VIEW_PART_5 => '',
        ]
    ];

    /**
     * @var array the list of asset bundles that would be unset when rendering the node detail form via ajax
     */
    public $unsetAjaxBundles = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset',
        'yii\widgets\ActiveFormAsset',
        'yii\validators\ValidationAsset'
    ];

    /**
     * @var string a random salt that will be used to generate a hash signature for tree configuration.
     */
    public $treeEncryptSalt = 'SET_A_SALT_FOR_YII2_TREE_MANAGER';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->_msgCat = 'kvtree';
        parent::init();
        $this->treeStructure += [
            'treeAttribute' => 'root',
            'leftAttribute' => 'lft',
            'rightAttribute' => 'rgt',
            'depthAttribute' => 'lvl',
        ];
        $this->dataStructure += [
            'keyAttribute' => 'id',
            'nameAttribute' => 'name',
            'iconAttribute' => 'icon',
            'iconTypeAttribute' => 'icon_type'
        ];
        $nodeActions = ArrayHelper::getValue($this->treeViewSettings, 'nodeActions', []);
        if (Yii::$app instanceof WebApplication) {
            $nodeActions += [
                self::NODE_MANAGE => Url::to(['/treemanager/node/manage']),
                self::NODE_SAVE => Url::to(['/treemanager/node/save']),
                self::NODE_REMOVE => Url::to(['/treemanager/node/remove']),
                self::NODE_MOVE => Url::to(['/treemanager/node/move']),
            ];
        }
        $this->treeViewSettings['nodeActions'] = $nodeActions;
    }
}
