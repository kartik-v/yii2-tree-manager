<?php

namespace kartik\tree\models;

use Yii;
use \kartik\tree\TreeView;
use creocoder\nestedsets\NestedSetsBehavior;

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
 */
class Tree extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return ''; // ensure you return a valid table name in your extended class
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $module = TreeView::module();
        extract($module->dataStructure);
        return [
            [[$nameAttribute], 'required'],
            [
                [
                    $nameAttribute,
                    $iconAttribute,
                    $iconTypeAttribute,
                    'active',
                    'selected',
                    'disabled',
                    'readonly',
                    'visible',
                    'collapsed',
                    'movable_u',
                    'movable_d',
                    'movable_r',
                    'movable_l',
                    'removable',
                    'removable_all'
                ],
                'safe'
            ]
        ];
    }

    /**
     * Parses an attribute value if set - else returns the default
     *
     * @param string $attr the attribute name
     * @param mixed  $default the attribute default value
     *
     * @return mixed
     */
    protected function parse($attr, $default = true)
    {
        return isset($this->$attr) ? $this->$attr : $default;
    }

    /**
     * Validate if the node is active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->parse('active');
    }

    /**
     * Validate if the node is selected
     *
     * @return bool
     */
    public function isSelected()
    {
        return $this->parse('selected', false);
    }

    /**
     * Validate if the node is visible
     *
     * @return bool
     */
    public function isVisible()
    {
        return $this->parse('visible');
    }

    /**
     * Validate if the node is readonly
     *
     * @return bool
     */
    public function isReadonly()
    {
        return $this->parse('readonly');
    }

    /**
     * Validate if the node is disabled
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->parse('disabled');
    }

    /**
     * Validate if the node is collapsed
     *
     * @return bool
     */
    public function isCollapsed()
    {
        return $this->parse('collapsed');
    }

    /**
     * Validate if the node is movable
     *
     * @param string $dir the direction, one of 'u', 'd', 'l', or 'r'
     *
     * @return bool
     */
    public function isMovable($dir)
    {
        $attr = "movable_{$dir}";
        return $this->parse($attr);
    }

    /**
     * Validate if the node is removable
     *
     * @return bool
     */
    public function isRemovable()
    {
        return $this->parse('removable');
    }

    /**
     * Validate if the node is removable with descendants
     *
     * @return bool
     */
    public function isRemovableAll()
    {
        return $this->parse('removable_all');
    }

    /**
     * Activates a node (for undoing a soft deletion scenario)
     *
     * @return bool status of activation
     */
    public function activateNode()
    {
        $this->active = true;
        if ($this->isRemovableAll()) {
            $children = $this->children()->all();
            foreach ($children as $child) {
                $child->active = true;
                $child->save();
            }
        }
        return $this->save();
    }

    /**
     * Removes a node
     *
     * @param bool $softDelete whether to soft delete or hard delete
     *
     * @return bool status of activation/inactivation
     */
    public function removeNode($softDelete = true)
    {
        if ($softDelete == true) {
            $this->active = false;
            if ($this->isRemovableAll()) {
                $children = $this->children()->all();
                foreach ($children as $child) {
                    $child->active = false;
                    $child->save();
                }
            }
            return $this->save();
        } else {
            return $this->removable_all ? $this->deleteWithChildren() : $this->delete();
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $module = TreeView::module();
        $settings = ['class' => NestedSetsBehavior::className()] + $module->treeStructure;
        return [$settings];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new TreeQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function createQuery()
    {
        return new TreeQuery(['modelClass' => get_called_class()]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $module = TreeView::module();
        extract($module->treeStructure + $module->dataStructure);
        return [
            $keyAttribute => Yii::t('kvtree', 'ID'),
            $nameAttribute => Yii::t('kvtree', 'Name'),
            $treeAttribute => Yii::t('kvtree', 'Root'),
            $leftAttribute => Yii::t('kvtree', 'Left'),
            $rightAttribute => Yii::t('kvtree', 'Right'),
            $depthAttribute => Yii::t('kvtree', 'Depth'),
            $iconAttribute => Yii::t('kvtree', 'Icon'),
            $iconTypeAttribute => Yii::t('kvtree', 'Icon Type'),
            'active' => Yii::t('kvtree', 'Active'),
            'selected' => Yii::t('kvtree', 'Selected'),
            'disabled' => Yii::t('kvtree', 'Disabled'),
            'readonly' => Yii::t('kvtree', 'Read Only'),
            'visible' => Yii::t('kvtree', 'Visible'),
            'collapsed' => Yii::t('kvtree', 'Collapsed'),
            'movable_u' => Yii::t('kvtree', 'Movable Up'),
            'movable_d' => Yii::t('kvtree', 'Movable Down'),
            'movable_l' => Yii::t('kvtree', 'Movable Left'),
            'movable_r' => Yii::t('kvtree', 'Movable Right'),
            'removable' => Yii::t('kvtree', 'Removable'),
            'removable_all' => Yii::t('kvtree', 'Removable (with children)')
        ];
    }
}
