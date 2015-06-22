yii2-tree-manager
=================

[![Latest Stable Version](https://poser.pugx.org/kartik-v/yii2-tree-manager/v/stable)](https://packagist.org/packages/kartik-v/yii2-tree-manager)
[![License](https://poser.pugx.org/kartik-v/yii2-tree-manager/license)](https://packagist.org/packages/kartik-v/yii2-tree-manager)
[![Total Downloads](https://poser.pugx.org/kartik-v/yii2-tree-manager/downloads)](https://packagist.org/packages/kartik-v/yii2-tree-manager)
[![Monthly Downloads](https://poser.pugx.org/kartik-v/yii2-tree-manager/d/monthly)](https://packagist.org/packages/kartik-v/yii2-tree-manager)
[![Daily Downloads](https://poser.pugx.org/kartik-v/yii2-tree-manager/d/daily)](https://packagist.org/packages/kartik-v/yii2-tree-manager)

An enhanced tree management module from Krajee with tree node selection and manipulation using nested sets. The extension features are listed below:

- A complete tree management solution which provides ability to manage hierarchical data stored using nested sets. Utilizes the [yii2-nested-sets](https://github.com/creocoder/yii2-nested-sets) extension to manage the tree structure in your database. Refer the documentation for yii2-nested-sets extension before you start using this module.
- A tree view built from scratch entirely without any third party plugins. The TreeView is designed using HTML5, jQuery & CSS3 features to work along with Yii PHP framework. 
- Styled with CSS3, includes jquery transitions and loading sections for ajax content, includes embedded alerts, and utilizes bootstrap css.
- Tree management feature options and modes:
    - View, edit, or administer the tree structure using **TreeView** widget as a selector and a dynamically rendered form to edit the tree node
    - The form works as both a detail view for the node OR as a management tool to add/edit/delete the node.
    - Form is rendered via ajax. It intelligently uses caching when the same node is clicked again (unless, the nodes are modified).
    - Unique Admin Mode for allowing administrator actions on tree.
    - Ability to add, edit, or delete tree nodes
    - Ability to reorder tree nodes (move up, down, left or right).
    - Configure tree node icons, styles, and ability to add checkboxes to tree nodes
    - i18N translations enabled across the module.
- Includes various jquery plugin events for advanced usage that are triggered on various tree manipulation actions.
- **Bonus:** Includes a **TreeViewInput** widget that allows you to use the treeview as an input widget. The TreeViewInput widget is uniquely designed by Krajee (using jQuery & PHP with HTML5/CSS) to appear as a dropdown selection menu. It allows multiple selection or single selection of values/nodes from the tree.    
- A Tree model that builds upon the yii2-nested-set model and is made to be easily extensible for various use cases. It includes prebuilt flags for each tree node. Check the Tree Model documentation for more.
- **active:** whether a tree node is active (if soft delete is enabled, the tree node will be just inactivated instead of deleting from database).
- **selected:** whether a tree node is selected by default.
- **disabled:** disables a tree node for editing or reorder
- **readonly:** a read only tree node that prevents editing, but can be reordered or moved up/down
- **visible:** whether a tree node is visible by default.
- **collapsed:** whether a tree node is collapsed by default.
- **movable_u:** whether a tree node is allowed to be movable up.
- **movable_d:** whether a tree node is allowed to be movable down.
- **movable_l:** whether a tree node is allowed to be movable left.
- **movable_r:** whether a tree node is allowed to be movable right.
- **removable:** whether a tree node is removable - will not be removed if children exist. If soft delete is enabled, then the node will be inactivated - else removed from database.
- **removable_all:** whether a tree node is removable with children. If soft delete is enabled, then the node and its children will be inactivated - else removed from database.
 
The following important PHP classes are available with this module:

1. **kartik\tree\Module:** _Module_, allows you to configure the module. You must setup a module named `treemanager`. Refer documentation for details. 
2. **kartik\tree\TreeView:** _Widget_, allows you to manage the tree in admin mode or normal user mode with actions and toolbar to add, edit, reorder, or delete tree nodes.
3. **kartik\tree\TreeViewInput:** _Widget_, allows you to use the treeview as a dropdown input either as a single select or multiple selection.
4. **kartik\tree\models\Tree:** _Model_, the entire tree data structure that uses the Nested set behavior from [yii2-nested-sets](https://github.com/creocoder/yii2-nested-sets) to manage the tree nodes.
5. **kartik\tree\models\TreeQuery:** _Query_, the query class as required for the Nested set model.
6. **kartik\tree\controllers\NodeController:** _Controller_, the controller actions that manages the editing of each node for create, update, delete, or reorder (move).

## Demo
You can see detailed [documentation](http://demos.krajee.com/tree-manager) and [TreeView demonstration](http://demos.krajee.com/tree-manager-demo/treeview) or [TreeViewInput demonstration](http://demos.krajee.com/tree-manager-demo/treeview-input) on usage of the extension.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

> NOTE: Check the [composer.json](https://github.com/kartik-v/yii2-tree-manager/blob/master/composer.json) for this extension's requirements and dependencies. Read this [web tip /wiki](http://webtips.krajee.com/setting-composer-minimum-stability-application/) on setting the `minimum-stability` settings for your application's composer.json.

Either run

```
$ php composer.phar require kartik-v/yii2-tree-manager "@dev"
```

or add

```
"kartik-v/yii2-tree-manager": "@dev"
```

to the ```require``` section of your `composer.json` file.

## Usage

### Step 1: Prepare Database
Create your database table to store the tree structure. Copy and modify the `schema/tree.sql` file (a MySQL example), to create the table `tbl_tree` (or for any table name you need). You can add columns you need to this table, but you cannot skip/drop any of the columns mentioned in the script. You can choose to rename the `id`, `root`, `lft`, `rgt`, `lvl`, `name`, `icon`, `icon_type` columns if you choose to - but these must be accordingly setup in the module.

### Step 2: Setup Model
Create your model for storing the tree structure extending `kartik\tree\models\Tree` class. You can alternatively build your own model extending from `yii\db\ActiveRecord` but modify it to use the `kartik\tree\models\TreeTrait`. You must provide the table name in the model. Optionally you can add rules, or edit the various methods like `isVisible`, `isDisabled` etc. to identify allowed flags for nodes.

So when extending from the `\kartik\tree\models\Tree`, you can set it like below:

```php
namespace frontend\models;

use Yii;

class Tree extends \kartik\tree\models\Tree
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_tree';
    }    
}
```

Alternatively, you can configure your model to not extend from `kartik\tree\models\Tree` and instead implement and use the `kartik\tree\models\TreeTrait`:

```php
namespace frontend\models;

use Yii;

class Tree extends \yii\db\ActiveRecord
{
    use kartik\tree\models\TreeTrait.

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_tree';
    }    
}
```

### Step 3: Setup Module
Configure the module named `treemanager` in the modules section of your Yii configuration file.

```php
'modules' => [
   'treemanager' =>  [
        'class' => '\kartik\tree\Module',
        // other module settings, refer detailed documentation
    ]
]
```

### Step 4: Using TreeView Widget
In your view files, you can now use the tree view directly to manage tree data as shown below:

```php
use kartik\tree\TreeView;
echo TreeView::widget([
    // single query fetch to render the tree
    'query'             => Tree::find()->addOrderBy('root, lft'), 
    'headingOptions'    => ['label' => 'Categories'],
    'isAdmin'           => false,                       // optional (toggle to enable admin mode)
    'displayValue'      => 1,                           // initial display value
    //'softDelete'      => true,                        // normally not needed to change
    //'cacheSettings'   => ['enableCache' => true]      // normally not needed to change
]);
```

### Step 5: Using TreeViewInput Widget
If you wish to use the tree input to select tree items, you can use the TreeViewInput widget as shown below. Normally you would use this as a dropdown with the `asDropdown` property set to `true`. If `asDropdown` is set to `false`, the treeview input widget will be rendered inline for selection.

```php
use kartik\tree\TreeViewInput;
echo TreeViewInput::widget([
    // single query fetch to render the tree
    'query'             => Tree::find()->addOrderBy('root, lft'), 
    'headingOptions'    => ['label' => 'Categories'],
    'name'              => 'kv-product',    // input name
    'value'             => '1,2,3',         // values selected (comma separated for multiple select)
    'asDropdown'        => true,            // will render the tree input widget as a dropdown.
    'multiple'          => true,            // set to false if you do not need multiple selection
    'fontAwesome'       => true,            // render font awesome icons
    'rootOptions'       => [
        'label' => '<i class="fa fa-tree"></i>', 
        'class'=>'text-success'
    ],                                      // custom root label
    //'options'         => ['disabled' => true],
]);
```

## License

**yii2-tree-manager** is released under the BSD 3-Clause License. See the bundled `LICENSE.md` for details.