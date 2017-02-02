<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2017
 * @package   yii2-tree
 * @version   1.0.8
 */

namespace kartik\tree;

use Yii;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

/**
 * The tree management module for Yii Framework 2.0.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since  1.0
 */
class Module extends \kartik\base\Module
{
    /**
     * The module name for Krajee treeview
     */
    const MODULE = 'treemanager';
    /**
     * Manage node action
     */
    const NODE_MANAGE = 'manage';
    /**
     * Remove node action
     */
    const NODE_REMOVE = 'remove';
    /**
     * Move node action
     */
    const NODE_MOVE = 'move';
    /**
     * Save node action
     */
    const NODE_SAVE = 'save';
    /**
     * Tree details form view - Section Part 1
     */
    const VIEW_PART_1 = 1;
    /**
     * Tree details form view - Section Part 2
     */
    const VIEW_PART_2 = 2;
    /**
     * Tree details form view - Section Part 3
     */
    const VIEW_PART_3 = 3;
    /**
     * Tree details form view - Section Part 4
     */
    const VIEW_PART_4 = 4;
    /**
     * Tree details form view - Section Part 5
     */
    const VIEW_PART_5 = 5;
    /**
     * Session key variable name for storing the tree configuration encryption salt.
     */
    const SALT_SESS_KEY = "krajeeTreeConfigSalt";

    /**
     * @var array the configuration of nested set attributes structure
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
     * @var string a random salt that will be used to generate a hash signature for tree configuration. If not set, this
     * will be generated using [[\yii\base\Security::generateRandomKey()]] to generate a random key. The randomly
     * generated salt in the second case will be stored in a session variable identified by [[SALT_SESS_KEY]].
     */
    public $treeEncryptSalt;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->_msgCat = 'kvtree';
        parent::init();
        $app = Yii::$app;
        if ($app->has('session') && !isset($this->treeEncryptSalt)) {
            $session = $app->session;
            if (!$session->get(self::SALT_SESS_KEY)) {
                $session->set(self::SALT_SESS_KEY, $app->security->generateRandomKey());
            }
            $this->treeEncryptSalt = $session->get(self::SALT_SESS_KEY);
        } else {
            $this->treeEncryptSalt = '<$0ME_R@ND0M_$@LT>';
        }
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
        $nodeActions += [
            self::NODE_MANAGE => Url::to(['/treemanager/node/manage']),
            self::NODE_SAVE => Url::to(['/treemanager/node/save']),
            self::NODE_REMOVE => Url::to(['/treemanager/node/remove']),
            self::NODE_MOVE => Url::to(['/treemanager/node/move']),
        ];
        $this->treeViewSettings['nodeActions'] = $nodeActions;
    }
}
