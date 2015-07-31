<?php
/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package   yii2-tree-manager
 * @version   1.0.4
 */

use kartik\form\ActiveForm;
use kartik\tree\Module;
use kartik\tree\TreeView;
use yii\bootstrap\Alert;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\widgets\Select2;

/**
 * @var yii\web\View            $this
 * @var kartik\tree\models\Tree $node
 * @var kartik\form\ActiveForm  $form
 */
?>

<?php
/**
 * SECTION 1: Initialize node view params & setup helper methods.
 */
?>
<?php
extract($params);
$isAdmin = ($isAdmin == true || $isAdmin === "true"); // admin mode flag
$inputOpts = [];                                      // readonly/disabled input options for node
$flagOptions = ['class' => 'kv-parent-flag'];         // node options for parent/child

// parse parent info
if (empty($parentKey)) {
    $parent = $node->parents(1)->one();
    $parentKey = empty($parent) ? '' : Html::getAttributeValue($parent, $keyAttribute);
} elseif ($parentKey == 'root') {
    $parent = '';
} else {
    $parent = $modelClass::findOne($parentKey);
}
$parentName = empty($parent) ? '' : $parent->$nameAttribute . ' &raquo; ';

// get module and setup form
$module = TreeView::module(); // the treemanager module
$formOptions['id'] = 'kv-' . uniqid();
$form = ActiveForm::begin([   // the active form instance
    'action' => $action,
    'options' => $formOptions
]);
// the primary key input field
if ($showIDAttribute) {
    $options = ['readonly' => true];
    if ($node->isNewRecord) {
        $options['value'] = Yii::t('kvtree', '(new)');
    }
    $keyField = $form->field($node, $keyAttribute)->textInput($options);
} else {
    $keyField = Html::activeHiddenInput($node, $keyAttribute);
}

// initialize for create or update
if ($node->isNewRecord) {
    $name = Yii::t('kvtree', 'Untitled');
} else {
    $name = $node->$nameAttribute;
    if ($node->isReadonly()) {
        $inputOpts['readonly'] = true;
    }
    if ($node->isDisabled()) {
        $inputOpts['disabled'] = true;
    }
    $flagOptions['disabled'] = $node->isLeaf();
}

// show alert helper
$showAlert = function ($type, $body = '', $hide = true) {
    $class = "alert alert-{$type}";
    if ($hide) {
        $class .= ' hide';
    }
    return Html::tag('div', '<div>' . $body . '</div>', ['class' => $class]);
};

// render additional view content helper
$renderContent = function ($part) use ($nodeAddlViews, $params, $form) {
    if (empty($nodeAddlViews[$part])) {
        return '';
    }
    $p = $params;
    $p['form'] = $form;
    return $this->render($nodeAddlViews[$part], $p);
};
?>

<?php
/**
 * SECTION 2: Initialize hidden attributes. In case you are extending this
 * and creating your own view, it is mandatory to set all these hidden
 * inputs as defined below.
 */
?>
<?= Html::hiddenInput('treeNodeModify', $node->isNewRecord) ?>
<?= Html::hiddenInput('parentKey', $parentKey) ?>
<?= Html::hiddenInput('currUrl', $currUrl) ?>
<?= Html::hiddenInput('modelClass', $modelClass) ?>
<?= Html::hiddenInput('softDelete', $softDelete) ?>
<?= Html::hiddenInput('formOptions', Json::encode($formOptions)) ?>

<?php
/**
 * SECTION 3: Setup form action buttons.
 */
?>
<?php if (empty($inputOpts['disabled']) || ($isAdmin && $showFormButtons)): ?>
    <div class="pull-right">
        <button type="reset" class="btn btn-default">
            <i class="glyphicon glyphicon-repeat"></i> <?= Yii::t('kvtree', 'Reset') ?>
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="glyphicon glyphicon-floppy-disk"></i> <?= Yii::t('kvtree', 'Save') ?>
        </button>
    </div>
<?php endif; ?>
    <h3><?= $parentName . $name ?></h3>
    <div class="clearfix"></div>
    <hr style="margin: 10px 0;">

<?php
/**
 * SECTION 4: Setup alert containers. Mandatory to set this up.
 */
?>
    <div class="kv-treeview-alerts">
        <?php
        $session = Yii::$app->session;
        if ($session->hasFlash('success')) {
            echo $showAlert('success', $session->getFlash('success'), false);
        } else {
            echo $showAlert('success');
        }
        if ($session->hasFlash('error')) {
            echo $showAlert('danger', $session->getFlash('error'), false);
        } else {
            echo $showAlert('danger');
        }
        echo $showAlert('warning');
        echo $showAlert('info');
        ?>
    </div>

<?php
/**
 * SECTION 5: Additional views part 1 - before all form attributes.
 */
?>
<?php
echo $renderContent(Module::VIEW_PART_1);
?>

<?php
/**
 * SECTION 6: Basic node attributes for editing.
 */
?>
<?php if ($iconsList == 'text' || $iconsList == 'none'): ?>
    <?php if ($showIDAttribute): ?>
        <div class="row">
            <div class="col-sm-4">
                <?= $keyField ?>
            </div>
            <div class="col-sm-8">
                <?= $form->field($node, $nameAttribute)->textInput($inputOpts) ?>
            </div>
        </div>
    <?php else: ?>
        <?= $keyField ?>
        <?= $form->field($node, $nameAttribute)->textInput($inputOpts) ?>
    <?php endif; ?>
    <?php if ($iconsList === 'text'): ?>
        <div class="row">
            <div class="col-sm-4">
                <?= $form->field($node, $iconTypeAttribute)->dropdownList([
                    TreeView::ICON_CSS => 'CSS Suffix',
                    TreeView::ICON_RAW => 'Raw Markup',
                ], $inputOpts) ?>
            </div>
            <div class="col-sm-8">
                <?= $form->field($node, $iconAttribute)->textInput($inputOpts) ?>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="row">
        <div class="col-sm-6">
            <?= $keyField ?>
            <?= Html::activeHiddenInput($node, $iconTypeAttribute) ?>
            <?= $form->field($node, $nameAttribute)->textArea(['rows' => 3] + $inputOpts) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($node, $iconAttribute)->multiselect($iconsList, [
                'item' => function ($index, $label, $name, $checked, $value) use ($inputOpts) {
                    if ($index == 0 && $value == '') {
                        $checked = true;
                        $value = '';
                    }
                    return '<div class="radio">' . Html::radio($name, $checked, [
                        'value' => $value,
                        'label' => $label,
                        'disabled' => !empty($inputOpts['readonly']) || !empty($inputOpts['disabled'])
                    ]) . '</div>';
                },
                'selector' => 'radio',
            ]) ?>
        </div>
    </div>
<?php endif; ?>

<?php
/**
 * SECTION 7: Additional views part 2 - before admin zone.
 */
?>
<?= $renderContent(Module::VIEW_PART_2) ?>

<?php
/**
 * SECTION 8: Administrator attributes zone.
 */
?>
<?php if ($isAdmin): ?>
    <h4><?= Yii::t('kvtree', 'Admin Settings') ?></h4>

    <?php
    /**
     * SECTION 9: Additional views part 3 - within admin zone
     * BEFORE mandatory attributes.
     */
    ?>
    <?= $renderContent(Module::VIEW_PART_3) ?>

    <?php
    /**
     * SECTION 10: Default mandatory admin controlled attributes.
     */
    ?>
    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($node, 'active')->checkbox() ?>
            <?= $form->field($node, 'selected')->checkbox() ?>
            <?= $form->field($node, 'collapsed')->checkbox($flagOptions) ?>
            <?= $form->field($node, 'visible')->checkbox() ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($node, 'readonly')->checkbox() ?>
            <?= $form->field($node, 'disabled')->checkbox() ?>
            <?= $form->field($node, 'removable')->checkbox() ?>
            <?= $form->field($node, 'removable_all')->checkbox($flagOptions) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($node, 'movable_u')->checkbox() ?>
            <?= $form->field($node, 'movable_d')->checkbox() ?>
            <?= $form->field($node, 'movable_l')->checkbox() ?>
            <?= $form->field($node, 'movable_r')->checkbox() ?>
        </div>
    </div>

    <?php
    /**
     * SECTION 11: Additional views part 4 - within admin zone
     * AFTER mandatory attributes.
     */
    ?>
    <?= $renderContent(Module::VIEW_PART_4) ?>
<?php endif; ?>
<?php ActiveForm::end() ?>

<?php
/**
 * SECTION 12: Additional views part 5 accessible by all users
 * after admin zone.
 */
?>
<?= $renderContent(Module::VIEW_PART_5) ?>
