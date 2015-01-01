yii2-tree-manager
=================

An enhanced tree management module with tree node selection and manipulation using nested sets.

## Demo
### _Extension is under development and not ready for testing._

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

> NOTE: Check the [composer.json](https://github.com/kartik-v/yii2-tree-manager/blob/master/composer.json) for this extension's requirements and dependencies. Read this [web tip /wiki](http://webtips.krajee.com/setting-composer-minimum-stability-application/) on setting the `minimum-stability` settings for your application's composer.json.

Either run

```
$ php composer.phar require kartik-v/yii2-tree-manager "dev-master"
```

or add

```
"kartik-v/yii2-tree-manager": "dev-master"
```

to the ```require``` section of your `composer.json` file.

## Usage

### TreeView

```php
use kartik\tree\TreeView;

echo TreeView::widget([
    // options
]);
```

## License

**yii2-tree-manager** is released under the BSD 3-Clause License. See the bundled `LICENSE.md` for details.