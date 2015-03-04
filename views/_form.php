<?php
/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package yii2-tree-manager
 * @version 1.5.0
 */

use kartik\form\ActiveForm;
use kartik\tree\Module;
use kartik\tree\TreeView;
use yii\bootstrap\Alert;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var kartik\tree\models\Tree $node
 * @var kartik\form\ActiveForm $form
 */
extract($params);
$isAdmin = ($isAdmin == true || $isAdmin === "true");
if (empty($parentKey)) {
    $parent = $node->parents(1)->one();
    $parentKey = empty($parent)? '' : Html::getAttributeValue($parent, $keyAttribute);
} elseif ($parentKey == 'root') {
    $parent = '';
} else {
    $parent = $modelClass::findOne($parentKey);
}
$parentName = empty($parent) ? '' : $parent->$nameAttribute . ' &raquo; ';
$inputOpts = [];
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
}
$form = ActiveForm::begin(['action' => $action]);
function showAlert($type, $body = '', $hide = true) {
    $class = "alert alert-{$type}";
    if ($hide) {
        $class .= ' hide';
    }
    return Html::tag('div', '<div>'.$body.'</div>', ['class'=>$class]);
}
function renderContent($part) {
    if (empty($nodeAddlViews[$part])) {
        return '';
    }
    $p = $params;
    $p['form'] = $form;
    return $this->render($nodeAddlViews[$part], $p); 
}
$module = TreeView::module();
// In case you are extending this form, it is mandatory to set 
// all these hidden inputs as defined below.
echo Html::hiddenInput('treeNodeModify', $node->isNewRecord);
echo Html::hiddenInput('parentKey', $parentKey);
echo Html::hiddenInput('currUrl', $currUrl);
echo Html::hiddenInput('modelClass', $modelClass);
echo Html::hiddenInput('softDelete', $softDelete);
$keyField = $form->field($node, $keyAttribute)->textInput(['readonly'=>true]);
?>
<?php if (empty($inputOpts['disabled']) || ($isAdmin && $showFormButtons)): ?>
<div class="pull-right">
    <?= Html::resetButton(
        '<i class="glyphicon glyphicon-repeat"></i> ' . Yii::t('kvtree', 'Reset'),
        ['class' => 'btn btn-default']
    ) ?>
    <?= Html::submitButton(
        '<i class="glyphicon glyphicon-floppy-disk"></i> ' . Yii::t('kvtree', 'Save'),
        ['class' => 'btn btn-primary']
    ) ?>
</div>
<?php endif; ?>
<h3><?= $parentName . $name ?></h3>
<div class="clearfix"></div>
<hr style="margin: 10px 0;">
<?php /* The alerts container is important */ ?>
<div class="kv-treeview-alerts">
<?php 
    if (Yii::$app->session->hasFlash('success')) {
        echo showAlert('success', Yii::$app->session->getFlash('success'), false);
    } else {
        echo showAlert('success');
    }
    if (Yii::$app->session->hasFlash('error')) {
        echo showAlert('danger', Yii::$app->session->getFlash('error'), false);
    } else {
        echo showAlert('danger');
    }
    echo showAlert('warning');
    echo showAlert('info');
?>
</div>
<?= renderContent(Module::VIEW_PART_1); ?>
<?php if ($iconsList == 'text' || $iconsList == 'none') : ?>
    <div class="row">
        <div class="col-sm-4">
            <?= $keyField ?>
        </div>
        <div class="col-sm-8">
            <?=  $form->field($node, $nameAttribute)->textInput($inputOpts) ?>
        </div>
    </div>
    <?php if ($iconsList === 'text') : ?>
        <div class="row">
            <div class="col-sm-4">
                <?= $form->field($node, $iconTypeAttribute)->dropdownList(
                    [
                        TreeView::ICON_CSS => 'CSS Suffix',
                        TreeView::ICON_RAW => 'Raw Markup',
                    ],
                    $inputOpts
                ) ?>
            </div>
            <div class="col-sm-8">
                <?= $form->field($node, $iconAttribute)->textInput($inputOpts) ?>
            </div>
        </div>
    <?php endif; ?>
<?php else : ?>
    <div class="row">
        <div class="col-sm-6">
            <?= $keyField ?>
            <?= Html::activeHiddenInput($node, $iconTypeAttribute) ?>
            <?= $form->field($node, $nameAttribute)->textArea(['rows' => 2] + $inputOpts) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($node, $iconAttribute)->multiselect(
                $iconsList,
                [
                    'item' => function ($index, $label, $name, $checked, $value) use($inputOpts) {
                        if ($index == 0 && $value == '') {
                            $checked = true;
                            $value = '';
                        }
                        return '<div class="radio">' . Html::radio(
                            $name,
                            $checked,
                            [
                                'value' => $value, 
                                'label' => $label, 
                                'disabled'=>!empty($inputOpts['readonly']) || !empty($inputOpts['disabled'])
                            ]
                        ) . '</div>';
                    },
                    'selector' => 'radio',
                ]
            ) ?>
        </div>
    </div>
<?php endif; ?>
<?= renderContent(Module::VIEW_PART_2); ?>
<?php if ($isAdmin): ?>
    <h4><?= Yii::t('kvtree', 'Admin Settings') ?></h4>
    <?= renderContent(Module::VIEW_PART_3); ?>
    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($node, 'active')->checkbox() ?>
            <?= $form->field($node, 'selected')->checkbox() ?>
            <?= $form->field($node, 'collapsed')->checkbox() ?>
            <?= $form->field($node, 'visible')->checkbox() ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($node, 'readonly')->checkbox() ?>
            <?= $form->field($node, 'disabled')->checkbox() ?>
            <?= $form->field($node, 'removable')->checkbox() ?>
            <?= $form->field($node, 'removable_all')->checkbox() ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($node, 'movable_u')->checkbox() ?>
            <?= $form->field($node, 'movable_d')->checkbox() ?>
            <?= $form->field($node, 'movable_l')->checkbox() ?>
            <?= $form->field($node, 'movable_r')->checkbox() ?>
        </div>
    </div>
    <?= renderContent(Module::VIEW_PART_4); ?>
<?php endif; ?>
<?php ActiveForm::end() ?>
<?= renderContent(Module::VIEW_PART_5); ?>