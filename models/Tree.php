<?php

namespace kartik\tree\models;

use Yii;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * This is the base model class for the nested set tree structure
 *
 * @property string $id
 * @property string $root
 * @property string $lft
 * @property string $rgt
 * @property integer $depth
 * @property string $name
 * @property string $icon
 * @property integer $icon_type
 *
 * @property bool $visible
 * @property bool $enabled
 * @property bool $movable
 */
class Tree extends \yii\db\ActiveRecord
{
    /**
     * @var bool whether the node is visible
     */
    public $visible = true;

    /**
     * @var bool whether the node is enabled for editing and deletion
     */
    public $enabled = true;

    /**
     * @var bool whether the node is movable for sorting
     */
    public $movable = true;

    /**
     * @var bool whether the node is removable. If `removableDescendants` is false,
     * the node will not be removed until all descendants are deleted.
     */
    public $removable = true;

    /**
     * @var bool whether the node is removable with descendants.
     */
    public $removableDescendants = false;
    
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
    public function behaviors() {
        return [
            NestedSetsBehavior::className(),
        ];
    }
      
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            ['name', 'required'],
        ];
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
        return [
            'id' => Yii::t('kvtree', 'ID'),
            'root' => Yii::t('kvtree', 'Root'),
            'lft' => Yii::t('kvtree', 'Left'),
            'rgt' => Yii::t('kvtree', 'Right'),
            'lvl' => Yii::t('kvtree', 'Depth'),
            'name' => Yii::t('kvtree', 'Name'),
            'icon' => Yii::t('kvtree', 'Icon'),
            'icon_type' => Yii::t('kvtree', 'Icon Type'),
            'visible' => Yii::t('kvtree', 'Visible'),
            'enabled' => Yii::t('kvtree', 'Enabled'),
            'movable' => Yii::t('kvtree', 'Movable'),
        ];
    }
}
