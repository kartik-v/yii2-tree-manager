<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2017
 * @package   yii2-tree-manager
 * @version   1.0.8
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
 * @property integer $icon_type
 * @property boolean $active
 * @property boolean $selected
 * @property boolean $disabled
 * @property boolean $readonly
 * @property boolean $visible
 * @property boolean $collapsed
 * @property boolean $movable_u
 * @property boolean $movable_d
 * @property boolean $movable_l
 * @property boolean $movable_r
 * @property boolean $removable
 * @property boolean $removable_all
 *
 * @method initDefaults()
 * @method makeRoot()
 * @method appendTo() appendTo(Tree $node)
 * @method insertBefore() insertBefore(Tree $node)
 * @method insertAfter() insertAfter(Tree $node)
 * @method TreeQuery parents() parents(int $depth = null)
 * @method TreeQuery children()
 * @method boolean isRoot()
 * @method boolean isLeaf()
 * @method boolean delete()
 * @method boolean deleteWithChildren()
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
     * @var boolean whether to HTML encode the tree node names.
     */
    public $encodeNodeNames = true;

    /**
     * @var boolean whether to HTML purify the tree node icon content before saving.
     */
    public $purifyNodeIcons = true;

    /**
     * @var array activation errors for the node.
     */
    public $nodeActivationErrors = [];

    /**
     * @var array node removal errors.
     */
    public $nodeRemovalErrors = [];

    /**
     * @var boolean attribute to cache the `active` state before a model update.
     */
    public $activeOrig = true;
}
