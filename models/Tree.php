<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2015
 * @package yii2-tree-manager
 * @version 1.0.3
 */
 
namespace kartik\tree\models;

use Yii;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use kartik\tree\TreeView;
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
    use TreeTrait;
}