<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package   yii2-tree-manager
 * @version   1.0.6
 */

namespace kartik\tree\models;

use yii\db\ActiveRecord;

/**
 * This is the base model class for the nested set tree structure
 *
 * @property string  $id
 * @property string  $root
 * @property string  $lft
 * @property string  $rgt
 * @property integer $lvl
 * @property string  $name
 * @property string  $icon
 * @property int     $icon_type
 * @property bool    $active
 * @property bool    $selected
 * @property bool    $disabled
 * @property bool    $readonly
 * @property bool    $visible
 * @property bool    $collapsed
 * @property bool    $movable_u
 * @property bool    $movable_d
 * @property bool    $movable_l
 * @property bool    $movable_r
 * @property bool    $removable
 * @property bool    $removable_all
 *
 * @method initDefaults()
 * @method makeRoot()
 * @method appendTo() appendTo(Tree $node)
 * @method insertBefore() insertBefore(Tree $node)
 * @method insertAfter() insertAfter(Tree $node)
 * @method TreeQuery parents() parents(int $depth = null)
 * @method TreeQuery children()
 * @method bool isRoot()
 * @method bool isLeaf()
 * @method bool delete()
 * @method bool deleteWithChildren()
 */
class Tree extends ActiveRecord
{
    use TreeTrait;

    /**
     * @var string the classname for the TreeQuery that implements the NestedSetQueryBehavior.
     * If not set this will default to `kartik\tree\models\TreeQuery`.
     */
    public static $treeQueryClass;

    /**
     * @var bool whether to HTML encode the tree node names. Defaults to `true`.
     */
    public $encodeNodeNames = true;

    /**
     * @var bool whether to HTML purify the tree node icon content before saving.
     * Defaults to `true`.
     */
    public $purifyNodeIcons = true;

    /**
     * @var array activation errors for the node
     */
    public $nodeActivationErrors = [];

    /**
     * @var array node removal errors
     */
    public $nodeRemovalErrors = [];

    /**
     * @var bool attribute to cache the `active` state before a model update. Defaults to `true`.
     */
    public $activeOrig = true;
}
