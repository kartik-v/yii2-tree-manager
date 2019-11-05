<?php
/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2019
 * @package   yii2-tree-manager
 * @version   1.1.3
 */

use kartik\form\ActiveForm;
use kartik\tree\Module;
use kartik\tree\TreeView;
use kartik\tree\models\Tree;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View $this
 * @var Tree $node
 * @var ActiveForm $form
 * @var array $formOptions
 * @var string $keyAttribute
 * @var string $nameAttribute
 * @var string $iconAttribute
 * @var string $iconTypeAttribute
 * @var string $iconsListShow
 * @var array|null $iconsList
 * @var string $formAction
 * @var array $breadcrumbs
 * @var array $nodeAddlViews
 * @var mixed $currUrl
 * @var boolean $isAdmin
 * @var boolean $showIDAttribute
 * @var boolean $showNameAttribute
 * @var boolean $showFormButtons
 * @var boolean $allowNewRoots
 * @var string $nodeSelected
 * @var string $nodeTitle
 * @var string $nodeTitlePlural
 * @var array $params
 * @var string $keyField
 * @var string $nodeView
 * @var string $nodeUser
 * @var string $nodeAddlViews
 * @var array $nodeViewButtonLabels
 * @var string $noNodesMessage
 * @var boolean $softDelete
 * @var string $modelClass
 * @var string $defaultBtnCss
 * @var string $treeManageHash
 * @var string $treeSaveHash
 * @var string $treeRemoveHash
 * @var string $treeMoveHash
 * @var string $hideCssClass
 */
?>

<?php
/**
 * SECTION 1: Initialize node view params & setup helper methods.
 */
?>
<?php
extract($params);
$session = Yii::$app->has('session') ? Yii::$app->session : null;

// parse parent key
if ($node->isNewRecord) {
    $parentKey = empty($parentKey) ? '' : $parentKey;
} elseif (empty($parentKey)) {
    $parent = $node->parents(1)->one();
    $parentKey = empty($parent) ? '' : Html::getAttributeValue($parent, $keyAttribute);
}

/** @var Module $module */
$module = TreeView::module();

// active form instance
$form = ActiveForm::begin(['action' => $formAction, 'options' => $formOptions]);

// helper function to show alert
$showAlert = function ($type, $body = '', $hide = true) use($hideCssClass) {
    $class = "alert alert-{$type}";
    if ($hide) { $class .= ' ' . $hideCssClass; }
    return Html::tag('div', '<div>' . $body . '</div>', ['class' => $class]);
};

// node identifier
$id = $node->isNewRecord ? null : $node->$keyAttribute;
// breadcrumbs
if (array_key_exists('depth', $breadcrumbs) && $breadcrumbs['depth'] === null) {
    $breadcrumbs['depth'] = '';
} elseif (!empty($breadcrumbs['depth'])) {
    $breadcrumbs['depth'] = (string)$breadcrumbs['depth'];
}
?>

<?php
/**
 * SECTION 2: Initialize hidden attributes. In case you are extending this and creating your own view, it is mandatory
 * to set all these hidden inputs as defined below.
 */
?>
<?= Html::hiddenInput('nodeTitle', $nodeTitle) ?>
<?= Html::hiddenInput('nodeTitlePlural', $nodeTitlePlural) ?>
<?= Html::hiddenInput('treeNodeModify', $node->isNewRecord) ?>
<?= Html::hiddenInput('parentKey', $parentKey) ?>
<?= Html::hiddenInput('currUrl', $currUrl) ?>
<?= Html::hiddenInput('modelClass', $modelClass) ?>
<?= Html::hiddenInput('nodeSelected', $nodeSelected) ?>
<?= Html::hiddenInput('treeManageHash', $treeManageHash) ?>
<?= Html::hiddenInput('treeRemoveHash', $treeRemoveHash) ?>
<?= Html::hiddenInput('treeMoveHash', $treeMoveHash) ?>

<?php
/**
 * BEGIN VALID NODE DISPLAY
 */
?>
<?php if (!$node->isNewRecord || !empty($parentKey)): ?>
    <?php
    $cbxOptions = ['custom' => true];                           // default checkbox/ radio options (useful for BS4)
    $isAdmin = ($isAdmin == true || $isAdmin === "true");       // admin mode flag
    $inputOpts = [];                                            // readonly/disabled input options for node
    $flagOptions = $cbxOptions + ['class' => 'kv-parent-flag']; // node options for parent/child

    /**
     * initialize for create or update
     */
    $depth = ArrayHelper::getValue($breadcrumbs, 'depth', '');
    $glue = ArrayHelper::getValue($breadcrumbs, 'glue', '');
    $activeCss = ArrayHelper::getValue($breadcrumbs, 'activeCss', '');
    $untitled = ArrayHelper::getValue($breadcrumbs, 'untitled', '');
    $name = $node->getBreadcrumbs($depth, $glue, $activeCss, $untitled);
    if ($node->isNewRecord && !empty($parentKey) && $parentKey !== TreeView::ROOT_KEY) {
        /**
         * @var Tree $modelClass
         * @var Tree $parent
         */
        if (empty($depth)) {
            $depth = null;
        }
        if ($depth === null || $depth > 0) {
            $parent = $modelClass::findOne($parentKey);
            $name = $parent->getBreadcrumbs($depth, $glue, null) . $glue . $name;
        }
    }
    if ($node->isReadonly()) { $inputOpts['readonly'] = true; }
    if ($node->isDisabled()) { $inputOpts['disabled'] = true; }
    if ($node->isLeaf()) {  $flagOptions['disabled'] = true;  }
    ?>

    <?php
    /**
     * SECTION 4: Setup form action buttons.
     */
    ?>
    <div class="kv-detail-heading">
        <div class="float-left kv-detail-crumbs"><span class="kv-crumb-active">View:&nbsp;</span></div>
        <div class="kv-detail-crumbs"><?= $name . ' (' . $node->$keyAttribute .')' ?></div>
        <div class="clearfix"></div>
    </div>

    <?php
    /**
     * SECTION 5: Setup alert containers. Mandatory to set this up.
     */
    ?>
    <div class="kv-treeview-alerts">
        <?php
        if ($session && $session->hasFlash('success')) {
            echo $showAlert('success', $session->getFlash('success'), false);
        } else {  echo $showAlert('success'); }

        if ($session && $session->hasFlash('error')) {
            echo $showAlert('danger', $session->getFlash('error'), false);
        } else { echo $showAlert('danger'); }

        echo $showAlert('warning');
        echo $showAlert('info');
        ?>
    </div>

    <?php
    /**
     * SECTION 7: Basic node attributes for editing.
     */
    ?>
          <div class="row">
            <?php
                switch ( $node->$iconTypeAttribute ) {
                    case TreeView::ICON_CSS: $type = "ICON_CSS"; break;
                    case TreeView::ICON_RAW: $type = "ICON_RAW"; break;
                    default: $type = 'Unknown (' . $node->$iconTypeAttribute . ')'; break;
                }

                $attribute = empty($node->$iconAttribute) ? 'Default' : $node->$iconAttribute;
            ?>
            <div class="col-sm-12">
                <h4><?= 'Icon Settings' ?></h4>
                <?= '<p><i>Type:</i> ' . $type . '&nbsp;&nbsp; <i>Show:</i> ' . $iconsListShow . ' &ndash; ' . $attribute . '</p>' ?>
            </div>
        </div>

    <?php
    /**
     * SECTION 9: Administrator attributes zone.
     */
    ?>
        <h4><?= Yii::t('kvtree', 'Admin Settings') ?></h4>

        <?php
        /**
         * SECTION 11: Default mandatory admin controlled attributes.
         */
        ?>
        <div class="row">
            <div class="col-sm-3">
                <?= '<i class="fas ' . ($node->isActive() ? 'fa-check-circle' : 'fa-circle') . '"></i> isActive()<br/>'  ?>
                <?= '<i class="fas ' . ($node->isActive() ? 'fa-check-circle' : 'fa-circle') . '"></i> isVisible()<br/>'  ?>
                <?= '<i class="fas ' . ($node->isReadonly() ? 'fa-check-circle' : 'fa-circle') . '"></i> isReadonly()<br/>'  ?>
                <?= '<i class="fas ' . ($node->isDisabled() ? 'fa-check-circle' : 'fa-circle') . '"></i> isDisabled()<br/>'  ?>
                <?= '<i class="fas ' . ($node->isChildAllowed() ? 'fa-check-circle' : 'fa-circle') . '"></i> isChildAllowed()<br/>'  ?>
            </div>
            <div class="col-sm-3">
                <?= '<i class="fas ' . ($node->isSelected() ? 'fa-check-circle' : 'fa-circle') . '"></i> isSelected()<br/>'  ?>
                <?= '<i class="fas ' . ($node->isCollapsed() ? 'fa-check-circle' : 'fa-circle') . '"></i> isCollapsed()<br/>'  ?>
                <?= '<i class="fas ' . ($node->isRemovable() ? 'fa-check-circle' : 'fa-circle') . '"></i> isRemovable()<br/>'  ?>
                <?= '<i class="fas ' . ($node->isRemovableAll() ? 'fa-check-circle' : 'fa-circle') . '"></i> isRemovableAll()<br/>'  ?>
            </div>
            <div class="col-sm-3">
                <?= '<i class="fas ' . ($node->isMovable('u') ? 'fa-check-circle' : 'fa-circle') . '"></i> isMovable(\'u\')<br/>'  ?>
                <?= '<i class="fas ' . ($node->isMovable('d') ? 'fa-check-circle' : 'fa-circle') . '"></i> isMovable(\'d\')<br/>'  ?>
                <?= '<i class="fas ' . ($node->isMovable('l') ? 'fa-check-circle' : 'fa-circle') . '"></i> isMovable(\'l\')<br/>'  ?>
                <?= '<i class="fas ' . ($node->isMovable('r') ? 'fa-check-circle' : 'fa-circle') . '"></i> isMovable(\'r\')<br/>'  ?>
            </div>
        </div>

<?php else: ?>
    <?= $noNodesMessage ?>
<?php endif; ?>
<?php
/**
 * END VALID NODE DISPLAY
 */
?>
<?php ActiveForm::end(); ?>
