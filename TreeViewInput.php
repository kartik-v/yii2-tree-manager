<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package   yii2-tree-manager
 * @version   1.0.6
 */

namespace kartik\tree;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\base\Model;
use yii\base\InvalidConfigException;
use yii\web\View;

/**
 * An input widget that extends kartik\tree\TreeView, and allows one to
 * select records from the tree.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since  1.0
 */
class TreeViewInput extends TreeView
{
    /**
     * Caret rendered for the dropdown toggle
     */
    const CARET = '<div class="kv-carets"><span class="caret kv-dn"></span><span class="caret kv-up"></span></div>';

    /**
     * @var Model the data model that this widget is associated with.
     */
    public $model;

    /**
     * @var string the model attribute that this widget is associated with.
     */
    public $attribute;

    /**
     * @var string the input name. This must be set if [[model]] and [[attribute]] are not set.
     */
    public $name;

    /**
     * @var string the input value.
     */
    public $value;

    /**
     * @var bool whether to show the input as a dropdown select. If set to `false`, it will display directly the tree
     *     view selector widget. Defaults to `true`. The `BootstrapPluginAsset` will automatically be loaded if this is
     *     set to `true`. Defaults to `true`.
     */
    public $asDropdown = true;

    /**
     * @var bool whether to autoclose the dropdown on input selection when `asDropdown` is true. Defaults to `true`.
     */
    public $autoCloseOnSelect = true;

    /**
     * @var bool whether to show the toolbar in the footer. Defaults to `false`.
     */
    public $showToolbar = false;

    /**
     * @var array the configuration of the tree view dropdown. The following
     * configuration options are available:
     * - input: array the HTML attributes for the dropdown input container which displays
     *   the selected tree items. The following special options are available:
     *   - placeholder: string, defaults to `Select...`
     * - dropdown: array, the HTML attributes for the dropdown tree view menu.
     * - options: array, the HTML attributes for the wrapper container
     * - caret: string, the markup for rendering the dropdown indicator for up and down.
     *   Defaults to TreeViewInput::CARET.
     */
    public $dropdownConfig = [];

    /**
     * @var array the HTML attributes for the input that will store the
     * selected nodes for the widget
     */
    public $options = ['class' => 'form-control hide'];

    /**
     * @var string the placeholder for the dropdown input
     */
    private $_placeholder;

    /**
     * @var bool whether the input is disabled
     */
    private $_disabled;

    /**
     * @inheritdoc
     */
    protected function initTreeView()
    {
        if (!$this->hasModel() && $this->name === null) {
            throw new InvalidConfigException("Either 'name', or 'model' and 'attribute' properties must be specified.");
        }
        $this->showCheckbox = true;
        $css = 'kv-tree-input-widget';
        if (!$this->showToolbar) {
            $css .= ' kv-tree-nofooter';
        }
        Html::addCssClass($this->treeOptions, $css);
        parent::initTreeView();
        $this->_hasBootstrap = $this->showTooltips || $this->asDropdown;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->hasModel()) {
            $this->value = Html::getAttributeValue($this->model, $this->attribute);
        }
        $this->_disabled = ArrayHelper::getValue($this->options, 'disabled', false);
        if ($this->asDropdown) {
            $this->initDropdown();
        }
        parent::run();
        $this->registerInputAssets();
    }

    /**
     * Initialize tree dropdown menu settings and options
     */
    protected function initDropdown()
    {
        $config = $this->dropdownConfig;
        $input = ArrayHelper::getValue($config, 'input', []);
        $dropdown = ArrayHelper::getValue($config, 'dropdown', []);
        $options = ArrayHelper::getValue($config, 'options', []);
        $css = 'form-control dropdown-toggle kv-tree-input';
        if ($this->_disabled) {
            $css .= ' disabled';
        }
        Html::addCssClass($input, $css);
        Html::addCssClass($dropdown, 'dropdown-menu kv-tree-dropdown');
        Html::addCssClass($options, 'dropdown kv-tree-dropdown-container');
        $id = $this->options['id'] . '-tree-input';
        $this->_placeholder = ArrayHelper::remove($input, 'placeholder', Yii::t('kvtree', 'Select...'));
        $this->_placeholder = Html::tag('span', $this->_placeholder, ['class' => 'kv-placeholder']);
        $config['dropdown'] = array_replace_recursive([
            'id' => $id . '-menu',
            'role' => 'menu',
            'aria-labelledby' => $id,
        ], $dropdown);
        $config['input'] = array_replace_recursive([
            'id' => $id,
            'tabindex' => -1,
            'data-toggle' => 'dropdown',
            'aria-haspopup' => 'true',
            'aria-expanded' => 'false',
        ], $input);
        if (empty($config['caret'])) {
            $config['caret'] = self::CARET;
        }
        $config['options'] = $options;
        $this->dropdownConfig = $config;
    }

    /**
     * @inheritdoc
     */
    public function renderWidget()
    {
        if (!$this->showToolbar) {
            $this->wrapperTemplate = strtr($this->wrapperTemplate, ['{footer}' => '']);
        }
        $content = strtr($this->renderWrapper(), [
                '{heading}' => $this->renderHeading(),
                '{search}' => $this->renderSearch(),
                '{toolbar}' => $this->renderToolbar(),
            ]) . "\n" .
            $this->getInput();
        if ($this->asDropdown) {
            return $this->renderDropdown($content);
        }
        return $content;
    }

    /**
     * Generates the dropdown tree menu
     *
     * @param string $content the content to be embedded in the dropdown menu
     *
     * @return string
     */
    protected function renderDropdown($content)
    {
        $config = $this->dropdownConfig;
        $input = Html::tag('div', $config['caret'] . $this->_placeholder, $config['input']);
        $dropdown = Html::tag('div', $content, $config['dropdown']);
        return Html::tag('div', $input . $dropdown, $config['options']);
    }

    /**
     * Generates the hidden input for storage
     *
     * @return string
     */
    public function getInput()
    {
        if ($this->hasModel()) {
            return Html::activeHiddenInput($this->model, $this->attribute, $this->options);
        }
        return Html::hiddenInput($this->name, $this->value, $this->options);
    }

    /**
     * Renders the markup for the button actions toolbar
     *
     * @return string
     */
    public function renderToolbar()
    {
        if (!$this->showToolbar) {
            return '';
        }
        unset($this->toolbar[self::BTN_CREATE], $this->toolbar[self::BTN_CREATE_ROOT], $this->toolbar[self::BTN_REMOVE]);
        return parent::renderToolbar();
    }

    /**
     * @return bool whether this widget is associated with a data model.
     */
    protected function hasModel()
    {
        return $this->model instanceof Model && $this->attribute !== null;
    }

    /**
     * Registers assets for TreeViewInput
     */
    public function registerInputAssets()
    {
        if (!$this->asDropdown) {
            return;
        }
        $view = $this->getView();
        TreeViewInputAsset::register($view);
        $id = $this->options['id'];
        $name = 'treeinput';
        $opts = Json::encode([
            'treeId' => $this->treeOptions['id'],
            'inputId' => $this->dropdownConfig['input']['id'],
            'dropdownId' => $this->dropdownConfig['dropdown']['id'],
            'placeholder' => $this->_placeholder,
            'value' => empty($this->value) ? '' : $this->value,
            'caret' => $this->dropdownConfig['caret'],
            'autoCloseOnSelect' => $this->autoCloseOnSelect
        ]);
        $var = $name . '_' . hash('crc32', $opts);
        $this->options['data-krajee-' . $name] = $var;
        $view->registerJs("var {$var}={$opts};", View::POS_HEAD);
        $view->registerJs("jQuery('#{$id}').{$name}({$var});");
    }
}
