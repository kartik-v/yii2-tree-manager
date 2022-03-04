<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2022
 * @package   yii2-tree-manager
 * @version   1.1.3
 */

namespace kartik\tree\models;

use yii\db\ActiveRecord;

/**
 * This is the base model class for the nested set tree structure. To use this in your project, create your model for
 * storing the tree structure extending the [[Tree]] model. You can alternatively build your own model extending from
 * [[ActiveRecord]] but modify it to use the [[TreeTrait]].
 *
 * You must provide the table name in the model. Optionally, you can add rules, or edit the various methods like
 * [[isVisible]], [[isDisabled]] etc. to identify allowed flags for nodes.
 *
 * For example,
 *
 * ```php
 * namespace frontend\models;
 *
 * use Yii;
 *
 * class Tree extends \kartik\tree\models\Tree
 * {
 *
 *     public static function tableName()
 *     {
 *         return 'tbl_tree';
 *     }
 * }
 * ```
 *
 * @property int|string $id the node identifier
 * @property int $root the node root identifier
 * @property int $lft the node nested set left value  
 * @property int $rgt the node nested set right value
 * @property int $lvl the node depth level
 * @property string $name the name for identifying the current node record 
 * @property string $icon the icon to be displayed for the node
 * @property int $icon_type the icon type (whether CSS or raw image)
 * @property bool $active whether the node is active
 * @property bool $selected whether the node is selected
 * @property bool $disabled whether the node is disabled
 * @property bool $readonly whether the node is readonly
 * @property bool $visible whether the node is visible
 * @property bool $collapsed whether the node is collapsed
 * @property bool $movable_u whether the node is movable up (sibling)
 * @property bool $movable_d whether the node is movable down (sibling)
 * @property bool $movable_l whether the node is movable one level up (parent) to the left
 * @property bool $movable_r whether the node is movable one level down (child) to the right
 * @property bool $removable whether the node is removable
 * @property bool $removable_all whether the node and all its children are removable
 *
 * @method bool makeRoot(bool $runValidation , array $attributes) Creates the root node if the active record is new or moves it as the root node.
 * @method bool prependTo(Tree $node, bool $runValidation, array $attributes) Creates a node as the first child of the target node if the active record is new or moves it as the first child of the target node.
 * @method bool appendTo(Tree $node, bool $runValidation, array $attributes) Creates a node as the last child of the target node if the active record is new or moves it as the last child of the target node.
 * @method bool insertBefore(Tree $node, bool $runValidation, array $attributes) Creates a node as the previous sibling of the target node if the active record is new or moves it as the previous sibling of the target node.
 * @method bool insertAfter(Tree $node, bool $runValidation, array $attributes) Creates a node as the next sibling of the target node if the active record is new or moves it as the next sibling of the target node.
 * @method TreeQuery parents(int $depth) Gets the parents of the node.
 * @method TreeQuery children(int $depth) Gets the children of the node.
 * @method TreeQuery leaves() Gets the leaves of the node.
 * @method TreeQuery prev() Gets the previous sibling of the node.
 * @method TreeQuery next() Gets the next sibling of the node.
 * @method bool isRoot() Determines whether the node is root.
 * @method bool isLeaf() Determines whether the node is leaf.
 * @method bool isChildOf() Determines whether the node is child of the parent node.
 * @method int|bool delete() Deletes the current node only. Returns the number of rows deleted or false if the deletion is unsuccessful for some reason.
 * @method bool deleteWithChildren() Deletes a node and its children. Returns the number of rows deleted or false if the deletion is unsuccessful for some reason.
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
     * @var bool whether to HTML encode the tree node names.
     */
    public $encodeNodeNames = true;

    /**
     * @var bool whether to HTML purify the tree node icon content before saving.
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
     * @var bool attribute to cache the `active` state before a model update.
     */
    public $activeOrig = true;
}
