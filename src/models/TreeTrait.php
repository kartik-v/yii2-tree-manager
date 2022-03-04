<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2022
 * @package   yii2-tree-manager
 * @version   1.1.3
 */

namespace kartik\tree\models;

use creocoder\nestedsets\NestedSetsBehavior;
use kartik\tree\Module;
use kartik\tree\TreeView;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\base\InvalidConfigException;

/**
 * Trait that must be used by the Tree model
 */
trait TreeTrait
{
    /**
     * @var array the list of boolean value attributes
     */
    public static $boolAttribs = [
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
        'removable_all',
        'child_allowed',
    ];

    /**
     * @var array the default list of boolean attributes with initial value = `false`
     */
    public static $falseAttribs = [
        'selected',
        'disabled',
        'readonly',
        'collapsed',
        'removable_all',
    ];

    /**
     * Declares the name of the database table associated with this AR class.
     * By default this method returns the class name as the table name by calling [[yii\helpers\Inflector::camel2id()]]
     * with prefix [[yii\db\Connection::tablePrefix]]. For example if [[yii\db\Connection::tablePrefix]] is `tbl_`,
     * `Customer` becomes `tbl_customer`, and `OrderItem` becomes `tbl_order_item`. You may override this method
     * if the table is not named after this convention.
     * @return string the table name
     */
    public static function tableName()
    {
        return ''; // ensure you return a valid table name in your extended class
    }


    /**
     * Creates an [[TreeQuery]] instance for query purpose.
     *
     * The returned [[TreeQuery]] instance can be further customized by calling
     * methods defined in [[yii\db\ActiveQueryInterface]] before `one()` or `all()` is called to return
     * populated ActiveRecord instances. For example,
     *
     * ```php
     * // find the node whose ID is 1
     * $node = CustomTree::find()->where(['id' => 1])->one();
     *
     * // find all active nodes and order them by their price:
     * $nodes = CustomTree::find()
     *     ->where(['status' => 1])
     *     ->orderBy('price')
     *     ->all();
     * ```
     *
     * This method is also called by [[yii\db\ActiveRecord::hasOne()]] and [[yii\db\ActiveRecord::hasMany()]] to
     * create a relational query.
     *
     * You may override this method to return a customized query. For example,
     *
     * ```php
     * use kartik\tree\models\Tree;
     *
     * class CustomTree extends Tree
     * {
     *     public static function find()
     *     {
     *         // use CustomTreeQuery instead of the default ActiveQuery
     *         return new CustomTreeQuery(get_called_class());
     *     }
     * }
     * ```
     *
     * The following code shows how to apply a default condition for all queries:
     *
     * ```php
     * use kartik\tree\models\Tree;
     *
     * class CustomTree extends Tree
     * {
     *     public static function find()
     *     {
     *         return parent::find()->where(['deleted' => false]);
     *     }
     * }
     *
     * // Use andWhere()/orWhere() to apply the default condition
     * // SELECT FROM custom_tree WHERE `deleted`=:deleted AND price > 30
     * $nodes = CustomTree::find()->andWhere('price > 30')->all();
     *
     * // Use where() to ignore the default condition
     * // SELECT FROM custom_tree WHERE price > 30
     * $nodes = CustomTree::find()->where('price > 30')->all();
     *
     * @return TreeQuery the newly created [[TreeQuery]] instance.
     */
    public static function find()
    {
        $treeQuery = isset(self::$treeQueryClass) ? self::$treeQueryClass : TreeQuery::class;
        return new $treeQuery(get_called_class());
    }

    /**
     * Creates the query for the [[Tree]] active record
     *
     * @return TreeQuery the newly created [[TreeQuery]] instance.
     */
    public static function createQuery()
    {
        $treeQuery = isset(self::$treeQueryClass) ? self::$treeQueryClass : TreeQuery::class;
        return new $treeQuery(['modelClass' => get_called_class()]);
    }

    /**
     * Returns a list of behaviors that this component should behave as.
     *
     * Child classes may override this method to specify the behaviors they want to behave as.
     *
     * The return value of this method should be an array of behavior objects or configurations
     * indexed by behavior names. A behavior configuration can be either a string specifying
     * the behavior class or an array of the following structure:
     *
     * ```php
     * 'behaviorName' => [
     *     'class' => 'BehaviorClass',
     *     'property1' => 'value1',
     *     'property2' => 'value2',
     * ]
     * ```
     *
     * Note that a behavior class must extend from [[yii\base\Behavior]]. Behaviors can be attached using a name or anonymously.
     * When a name is used as the array key, using this name, the behavior can later be retrieved using [[Tree::getBehavior()]]
     * or be detached using [[Tree::detachBehavior()]]. Anonymous behaviors can not be retrieved or detached.
     *
     * Behaviors declared in this method will be attached to the component automatically (on demand).
     *
     * @return array the behavior configurations.
     * @throws InvalidConfigException
     */
    public function behaviors()
    {
        /**
         * @var Module $module
         */
        $module = TreeView::module();
        $settings = ['class' => NestedSetsBehavior::class] + $module->treeStructure;
        return empty($module->treeBehaviorName) ? [$settings] : [$module->treeBehaviorName => $settings];
    }

    /**
     * Declares which DB operations should be performed within a transaction in different scenarios.
     * The supported DB operations are: [[Tree::OP_INSERT]], [[Tree::OP_UPDATE]] and [[Tree::OP_DELETE]],
     * which correspond to the [[Tree::insert()]], [[Tree::update()]] and [[Tree::delete()]] methods, respectively.
     * By default, these methods are NOT enclosed in a DB transaction.
     *
     * In some scenarios, to ensure data consistency, you may want to enclose some or all of them
     * in transactions. You can do so by overriding this method and returning the operations
     * that need to be transactional. For example,
     *
     * ```php
     * return [
     *     'admin' => Tree::OP_INSERT,
     *     'api' => Tree::OP_INSERT | Tree::OP_UPDATE | Tree::OP_DELETE,
     *     // the above is equivalent to the following:
     *     // 'api' => Tree::OP_ALL,
     *
     * ];
     * ```
     *
     * The above declaration specifies that in the "admin" scenario, the insert operation [[Tree::insert()]])
     * should be done in a transaction; and in the "api" scenario, all the operations should be done
     * in a transaction.
     *
     * @return array the declarations of transactional operations. The array keys are scenarios names,
     * and the array values are the corresponding transaction operations.
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[Tree::validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * Each rule is an array with the following structure:
     *
     * ```php
     * [
     *     ['attribute1', 'attribute2'],
     *     'validator type',
     *     'on' => ['scenario1', 'scenario2'],
     *     //...other parameters...
     * ]
     * ```
     *
     * where
     *
     *  - attribute list: required, specifies the attributes array to be validated, for single attribute you can pass a string;
     *  - validator type: required, specifies the validator to be used. It can be a built-in validator name,
     *    a method name of the model class, an anonymous function, or a validator class name.
     *  - on: optional, specifies the [[Tree::scenarios()]] array in which the validation
     *    rule can be applied. If this option is not set, the rule will apply to all scenarios.
     *  - additional name-value pairs can be specified to initialize the corresponding validator properties.
     *    Please refer to individual validator class API for possible properties.
     *
     * A validator can be either an object of a class extending [[yii\validators\Validator]], or a model class method
     * (called *inline validator*) that has the following signature:
     *
     * ```php
     * // $params refers to validation parameters given in the rule
     * function validatorName($attribute, $params)
     * ```
     *
     * In the above `$attribute` refers to the attribute currently being validated while `$params` contains an array of
     * validator configuration options such as `max` in case of `string` validator. The value of the attribute currently being validated
     * can be accessed as `$this->$attribute`. Note the `$` before `attribute`; this is taking the value of the variable
     * `$attribute` and using it as the name of the property to access.
     *
     * Yii also provides a set of [[yii\validators\Validator::builtInValidators|built-in validators]].
     * Each one has an alias name which can be used when specifying a validation rule.
     *
     * Below are some examples:
     *
     * ```php
     * [
     *     // built-in "required" validator
     *     [['username', 'password'], 'required'],
     *     // built-in "string" validator customized with "min" and "max" properties
     *     ['username', 'string', 'min' => 3, 'max' => 12],
     *     // built-in "compare" validator that is used in "register" scenario only
     *     ['password', 'compare', 'compareAttribute' => 'password2', 'on' => 'register'],
     *     // an inline validator defined via the "authenticate()" method in the model class
     *     ['password', 'authenticate', 'on' => 'login'],
     *     // a validator of class "DateRangeValidator"
     *     ['dateRange', 'DateRangeValidator'],
     * ];
     * ```
     *
     * Note, in order to inherit rules defined in the parent class, a child class needs to
     * merge the parent rules with child rules using functions such as `array_merge()`.
     *
     * @return array validation rules
     * @throws InvalidConfigException
     * @see Tree::scenarios()
     */
    public function rules()
    {
        /**
         * @var Module $module
         */
        $module = TreeView::module();
        $nameAttribute = $iconAttribute = $iconTypeAttribute = null;
        extract($module->dataStructure);
        $attributes = array_merge([$nameAttribute, $iconAttribute, $iconTypeAttribute], static::$boolAttribs);
        $rules = [
            [[$nameAttribute], 'required'],
            [$attributes, 'safe'],
        ];
        if ($this->encodeNodeNames) {
            $rules[] = [
                $nameAttribute,
                'filter',
                'filter' => function ($value) {
                    return Html::encode($value, false);
                },
            ];
        }
        if ($this->purifyNodeIcons) {
            $rules[] = [
                $iconAttribute,
                'filter',
                'filter' => function ($value) {
                    return HtmlPurifier::process($value);
                },
            ];
        }
        return $rules;
    }

    /**
     * Initialize default values
     */
    public function initDefaults()
    {
        /**
         * @var Tree $this
         * @var Module $module
         */
        $module = TreeView::module();
        $iconTypeAttribute = null;
        extract($module->dataStructure);
        $this->setDefault($iconTypeAttribute, TreeView::ICON_CSS);
        foreach (static::$boolAttribs as $attr) {
            $val = !in_array($attr, static::$falseAttribs);
            $this->setDefault($attr, $val);
        }
    }

    /**
     * Validate if the node is active
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->parse('active');
    }

    /**
     * Validate if the node is selected
     *
     * @return boolean
     */
    public function isSelected()
    {
        return $this->parse('selected', false);
    }

    /**
     * Validate if the node is visible
     *
     * @return boolean
     */
    public function isVisible()
    {
        return $this->parse('visible');
    }

    /**
     * Validate if the node is readonly
     *
     * @return boolean
     */
    public function isReadonly()
    {
        return $this->parse('readonly');
    }

    /**
     * Validate if the node is disabled
     *
     * @return boolean
     */
    public function isDisabled()
    {
        return $this->parse('disabled');
    }

    /**
     * Validate if the node is collapsed
     *
     * @return boolean
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
     * @return boolean
     */
    public function isMovable($dir)
    {
        $attr = "movable_{$dir}";
        return $this->parse($attr);
    }

    /**
     * Validate if the node is removable
     *
     * @return boolean
     */
    public function isRemovable()
    {
        return $this->parse('removable');
    }

    /**
     * Validate if the node is removable with descendants
     *
     * @return boolean
     */
    public function isRemovableAll()
    {
        return $this->parse('removable_all');
    }

    /**
     * Validate if the node can have children
     *
     * @return boolean
     */
    public function isChildAllowed()
    {
        return $this->parse('child_allowed');
    }

    /**
     * Activates a node (for undoing a soft deletion scenario)
     *
     * @param boolean $currNode whether to update the current node value also
     *
     * @return boolean status of activation
     * @throws InvalidConfigException
     */
    public function activateNode($currNode = true)
    {
        /**
         * @var Module $module
         */
        $this->nodeActivationErrors = [];
        $module = TreeView::module();
        extract($module->dataStructure);
        if ($this->isRemovableAll()) {
            $children = $this->children()->all();
            foreach ($children as $child) {
                /**
                 * @var Tree $child
                 */
                $child->active = true;
                if (!$child->save()) {
                    /** @noinspection PhpUndefinedVariableInspection */
                    $this->nodeActivationErrors[] = [
                        'id' => $child->$idAttribute,
                        'name' => $child->$nameAttribute,
                        'error' => $child->getFirstErrors(),
                    ];
                }
            }
        }
        if ($currNode) {
            $this->active = true;
            if (!$this->save()) {
                /** @noinspection PhpUndefinedVariableInspection */
                $this->nodeActivationErrors[] = [
                    'id' => $this->$idAttribute,
                    'name' => $this->$nameAttribute,
                    'error' => $this->getFirstErrors(),
                ];
                return false;
            }
        }
        return true;
    }

    /**
     * Removes a node
     *
     * @param boolean $softDelete whether to soft delete or hard delete
     * @param boolean $currNode whether to update the current node value also
     *
     * @return boolean status of activation/inactivation
     * @throws InvalidConfigException
     */
    public function removeNode($softDelete = true, $currNode = true)
    {
        /**
         * @var Module $module
         * @var Tree $child
         */
        if ($softDelete) {
            $this->nodeRemovalErrors = [];
            $module = TreeView::module();
            extract($module->dataStructure);
            if ($this->isRemovableAll()) {
                $children = $this->children()->all();
                foreach ($children as $child) {
                    $child->active = false;
                    if (!$child->save()) {
                        /** @noinspection PhpUndefinedVariableInspection */
                        $this->nodeRemovalErrors[] = [
                            'id' => $child->$keyAttribute,
                            'name' => $child->$nameAttribute,
                            'error' => $child->getFirstErrors(),
                        ];
                    }
                }
            }
            if ($currNode) {
                $this->active = false;
                if (!$this->save()) {
                    /** @noinspection PhpUndefinedVariableInspection */
                    $this->nodeRemovalErrors[] = [
                        'id' => $this->$keyAttribute,
                        'name' => $this->$nameAttribute,
                        'error' => $this->getFirstErrors(),
                    ];
                    return false;
                }
            }
            return true;
        } else {
            return $this->removable_all || $this->isRoot() && $this->children()->count() == 0 ?
                $this->deleteWithChildren() : $this->delete();
        }
    }


    /**
     * Returns the attribute labels.
     *
     * Attribute labels are mainly used for display purpose. For example, given an attribute
     * `firstName`, we can declare a label `First Name` which is more user-friendly and can
     * be displayed to end users.
     *
     * By default an attribute label is generated using [[yii\db\ActiveRecord::generateAttributeLabel()]].
     * This method allows you to explicitly specify attribute labels.
     *
     * Note, in order to inherit labels defined in the parent class, a child class needs to
     * merge the parent labels with child labels using functions such as `array_merge()`.
     *
     * @return array attribute labels (name => label)
     * @throws InvalidConfigException
     * @see yii\db\ActiveRecord::generateAttributeLabel()
     */
    public function attributeLabels()
    {
        /**
         * @var Module $module
         */
        $module = TreeView::module();
        $keyAttribute = $nameAttribute = $leftAttribute = $rightAttribute = $depthAttribute = null;
        $treeAttribute = $iconAttribute = $iconTypeAttribute = null;
        extract($module->treeStructure + $module->dataStructure);
        $labels = [
            $keyAttribute => Yii::t('kvtree', 'ID'),
            $nameAttribute => Yii::t('kvtree', 'Name'),
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
            'removable_all' => Yii::t('kvtree', 'Removable (with children)'),
            'child_allowed' => Yii::t('kvtree', 'Child Allowed'),
        ];
        if (!$treeAttribute) {
            $labels[$treeAttribute] = Yii::t('kvtree', 'Root');
        }
        return $labels;
    }

    /**
     * Generate and return the breadcrumbs for the node.
     *
     * @param int $depth the breadcrumbs parent depth
     * @param string $glue the pattern to separate the breadcrumbs
     * @param string $currCss the CSS class to be set for current node
     * @param string $new the name to be displayed for a new node
     *
     * @return string the parsed breadcrumbs
     * @throws InvalidConfigException
     */
    public function getBreadcrumbs($depth = 1, $glue = ' &raquo; ', $currCss = 'kv-crumb-active', $new = 'Untitled')
    {
        /**
         * @var Module $module
         */
        if ($this->isNewRecord || empty($this)) {
            return $currCss ? Html::tag('span', $new, ['class' => $currCss]) : $new;
        }
        $depth = empty($depth) ? null : intval($depth);
        $module = TreeView::module();
        $nameAttribute = ArrayHelper::getValue($module->dataStructure, 'nameAttribute', 'name');
        $crumbNodes = $depth === null ? $this->parents()->all() : $this->parents($depth - 1)->all();
        $crumbNodes[] = $this;
        $i = 1;
        $len = count($crumbNodes);
        $crumbs = [];
        foreach ($crumbNodes as $node) {
            $name = $node->$nameAttribute;
            if ($i === $len && $currCss) {
                $name = Html::tag('span', $name, ['class' => $currCss]);
            }
            $crumbs[] = $name;
            $i++;
        }
        return implode($glue, $crumbs);
    }

    /**
     * Sets default value of a model attribute
     *
     * @param string $attr the attribute name
     * @param mixed $val the default value
     */
    protected function setDefault($attr, $val)
    {
        if (empty($this->$attr)) {
            $this->$attr = $val;
        }
    }

    /**
     * Parses an attribute value if set - else returns the default
     *
     * @param string $attr the attribute name
     * @param mixed $default the attribute default value
     *
     * @return mixed
     */
    protected function parse($attr, $default = true)
    {
        return isset($this->$attr) ? $this->$attr : $default;
    }
}
