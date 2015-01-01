<?php

namespace kartik\tree\models;

use creocoder\nestedsets\NestedSetsQueryBehavior;

/**
 * This is the base query class for the nested set tree
 */
class TreeQuery extends \yii\db\ActiveQuery
{
    public function behaviors() {
        return [
            NestedSetsQueryBehavior::className(),
        ];
    }
}