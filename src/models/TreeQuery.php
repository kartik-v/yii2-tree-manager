<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2018
 * @package   yii2-tree-manager
 * @version   1.0.9
 */

namespace kartik\tree\models;

use yii\db\ActiveQuery;
use creocoder\nestedsets\NestedSetsQueryBehavior;

/**
 * This is the base query class for the nested set tree
 */
class TreeQuery extends ActiveQuery
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            NestedSetsQueryBehavior::class,
        ];
    }
}
