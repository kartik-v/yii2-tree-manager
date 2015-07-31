<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package yii2-tree-manager
 * @version 1.0.4
 */

namespace kartik\tree\models;

use creocoder\nestedsets\NestedSetsQueryBehavior;

/**
 * This is the base query class for the nested set tree
 */
class TreeQuery extends \yii\db\ActiveQuery
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            NestedSetsQueryBehavior::className(),
        ];
    }
}