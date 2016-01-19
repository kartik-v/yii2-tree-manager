<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package   yii2-tree-manager
 * @version   1.0.6
 */

namespace kartik\tree;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\bootstrap\BootstrapPluginAsset;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use kartik\base\Config;
use kartik\base\Widget;
use kartik\tree\models\Tree;

/**
 * An enhanced tree view widget for Yii Framework 2 that allows management and manipulation of hierarchical data using
 * nested sets.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since  1.0
 */
class TreeView extends Widget
{
    /**
     * toolbar buttons
     */
    const BTN_CREATE_ROOT = 'create-root';
    const BTN_CREATE = 'create';
    const BTN_REMOVE = 'remove';
    const BTN_MOVE_UP = 'move-up';
    const BTN_MOVE_DOWN = 'move-down';
    const BTN_MOVE_LEFT = 'move-left';
    const BTN_MOVE_RIGHT = 'move-right';
    const BTN_REFRESH = 'refresh';
    const BTN_SEPARATOR = 'separator';

    /**
     * the icon types for each node stored in database
     */
    const ICON_CSS = 1;
    const ICON_RAW = 2;

    /**
     * the node reorder/movable directions
     */
    const MOVE_UP = 'u';
    const MOVE_DOWN = 'd';
    const MOVE_LEFT = 'l';
    const MOVE_RIGHT = 'r';

    /**
     * @var array the actions for managing, deleting, and moving the tree nodes. The keys must be one of 'manage',
     *     'save', 'remove', and 'move'. Defaults to:
     * ```
     *     [
     *         'save' => Url::to(['/treemanager/node/save']),
     *         'manage' => Url::to(['/treemanager/node/manage']),
     *         'remove' => Url::to(['/treemanager/node/remove']),
     *         'move' => Url::to(['/treemanager/node/move']),
     *     ]
     * ```
     */
    public $nodeActions = [];

    /**
     * @var string the view file that will render the form for editing the node.
     */
    public $nodeView;

    /**
     * @var array the list of additional view files that will be used to append content at various sections in the
     *     `nodeView` form.
     */
    public $nodeAddlViews = [];

    /**
     * @var ActiveQuery the query that will be used as the data source for the TreeView. For example:
     * `Tree::find()->addOrderBy('root, lft')`
     */
    public $query;

    /**
     * @var int the initial value (key) to be selected in the tree and displayed in the detail form. Defaults to 1.
     */
    public $displayValue = 1;

    /**
     * @var array the HTML attributes for the node detail form.
     */
    public $nodeFormOptions = [];

    /**
     * @var array the breadcrumbs settings for displaying the current node title based on parent hierarchy in the node
     *     details form/view (starting from the current node). The following settings are supported:
     * - depth: int, the depth to dig into the parent nodes for fetching the breadcrumb titles.  If set to `null` or
     *     `0` this will fetch breadcrumbs till infinite parent depth. Defaults to `null`.
     * - glue: string, the separator to glue each node name within the breadcrumbs. Defaults to ` &rsaquo; `.
     * - activeCss: string, the CSS class to be applied to the current node name in the breadcrumbs. Defaults to
     *     `kv-crumb-active`.
     * - untitled: string, the title to be displayed if this is a new untitled node record. Defaults to `Untitled`.
     */
    public $breadcrumbs = [];

    /**
     * @var string the comma separated initial value (keys) to be checked and selected in the tree
     */
    public $value = '';

    /**
     * @var string message shown on tree initialization when either the entire tree is empty or no node is found for
     *     the selected `displayValue`.
     */
    public $emptyNodeMsg;

    /**
     * @var array HTML attributes for the empty node message displayed.
     */
    public $emptyNodeMsgOptions = ['class' => 'kv-node-message'];

    /**
     * @var bool whether to show the key attribute (ID) in the node details form/view.
     */
    public $showIDAttribute = true;

    /**
     * @var bool whether to show the form action buttons in the node details form/view.
     */
    public $showFormButtons = true;

    /**
     * @var bool whether the tree is to be allowed for editing in admin mode. This will display all nodes and will
     *     allow to modify internal tree node flags. Defaults to `false`.
     */
    public $isAdmin = false;

    /**
     * @var bool whether the record will be soft deleted, when remove button is clicked. Defaults to `true`. The
     *     following actions are possible:
     * - If `true`, this will just set the `active` property of node to `false`.
     * - If `false`, it will attempt to hard delete the whole record.
     */
    public $softDelete = true;

    /**
     * @var bool whether to show a checkbox before each tree node label to allow multiple node selection.
     */
    public $showCheckbox = false;

    /**
     * @var bool whether to allow multiple selection of checkboxes. Defaults to `true`. If set to `false` will not show
     *     a checkbox and allow only single selection of tree nodes.
     */
    public $multiple = true;

    /**
     * @var int animation duration (ms) for fading in and out alerts that are displayed during manipulation of nodes.
     */
    public $alertFadeDuration = 1000;

    /**
     * @var array cache settings for displaying the detail form content for each tree node via ajax. The following
     *     options are supported:
     * - `enableCache`: bool, defaults to `true`.
     * - `cacheTimeout`: int, the cache timeout in milliseconds. Defaults to `300000` (or `5 minutes`).
     */
    public $cacheSettings = [];

    /**
     * @var bool whether to show inactive nodes
     */
    public $showInactive = false;

    /**
     * @var bool whether to use font awesome icons. Defaults to `false`.
     */
    public $fontAwesome = false;

    /**
     * @var array settings to edit the icon. The following settings are recognized:
     * - show: string, whether to display the icons selection as a list. If set to 'text', the icon will be shown as a
     *     plain text input along with icon type. If set to 'list', a list will be shown. If set to 'none', then no
     *     icon settings will be shown for editing.
     * - type: string, the iconTypeAttribute value, defaults to TreeView::ICON_CSS. Should be one of TreeView::ICON_CSS
     *     or TreeView::ICON_RAW.
     * - listData: array, the configuration of the icon list data to be shown for selection. This is mandatory if you
     *     set `show` to 'list'. You must set the data as `$key => $value` format. The list will be parsed to display
     *     the icon list and will depend on the `type`.
     *   - If type = TreeView::ICON_CSS: `$key` will be the icon suffix name and `$value` will be the description for
     *     the icon. The icon markup will be automatically parsed then based on whether its a glyphicon or font-awesome
     *     when `fontAwesome` property is `true`. For example:
     *     ```
     *          [
     *              'folder-close' => 'Folder',
     *              'file' => 'File',
     *              'tag' => 'Tag'
     *          ]
     *      ```
     *   - If type = TreeView::ICON_RAW:  `$key` is the icon markup to be stored and `$value` is the output markup to
     *     be displayed as a selection in the list. For example:
     *     ```
     *          [
     *              '<img src="images/folder.jpg">' => 'Folder',
     *              '<img src="images/file.jpg">' => 'File',
     *              '<img src="images/tag.jpg">' => 'Tag',
     *          ]
     *     ```
     */
    public $iconEditSettings = [
        'show' => 'text',
        'type' => self::ICON_CSS,
        'listData' => []
    ];

    /**
     * @var array the settings for the tree management toolbar
     */
    public $toolbar = [];

    /**
     * @var array the HTML attributes for the toolbar.
     */
    public $toolbarOptions = [];

    /**
     * @var array the HTML attributes for the button groups within the toolbar.
     */
    public $buttonGroupOptions = ['class' => 'btn-group-sm'];

    /**
     * @var array the default HTML attributes for the toolbar buttons
     */
    public $buttonOptions = ['class' => 'btn btn-default'];

    /**
     * @var array the default HTML attributes for the toolbar button icons
     */
    public $buttonIconOptions = [];

    /**
     * @var bool show toolbar button tooltips (using bootstrap tooltip plugin). The `BootstrapPluginAsset` will
     *     automatically be loaded if this is set to `true`.
     */
    public $showTooltips = true;

    /**
     * @var bool whether to auto load the bootstrap plugin assets if `showTooltips` is `true` OR if 
     * `TreeViewInput::asDropdown` is true. Defaults to `true`.
     */
    public $autoLoadBsPlugin = true;

    /**
     * @var string the icon markup for the child node if no icon was setup in the database.
     */
    public $defaultChildNodeIcon;

    /**
     * @var string the icon markup for the collapsed parent node if no icon was setup in the database.
     */
    public $defaultParentNodeIcon;

    /**
     * @var string the icon markup for the opened parent node if no icon was setup in the database.
     */
    public $defaultParentNodeOpenIcon;

    /**
     * @var array the HTML attributes for the child node icon.
     */
    public $childNodeIconOptions = ['class' => 'text-info'];

    /**
     * @var array the HTML attributes for the parent node icon.
     */
    public $parentNodeIconOptions = ['class' => 'text-warning'];

    /**
     * @var bool allow new root creation.
     */
    public $allowNewRoots = true;

    /**
     * @var array the configuration of various client alert messages
     */
    public $clientMessages = [];

    /**
     * @var array the HTML attributes for the topmost root node container. The following special options are
     *     recognized:
     * - label: string, the label for the topmost root node (this is not HTML encoded). Defaults to 'Root'. Set this to
     *     empty to not display a label.
     */
    public $rootOptions = ['class' => 'text-primary'];

    /**
     * @var array the HTML attributes for the root node's toggle indicator
     */
    public $rootNodeToggleOptions = ['class' => 'text-muted'];

    /**
     * @var array the HTML attributes for the root node's checkbox indicator
     */
    public $rootNodeCheckboxOptions = ['class' => 'text-success'];

    /**
     * @var array the HTML attributes for the node toggle indicator for each parent item in the tree
     */
    public $nodeToggleOptions = ['class' => 'text-muted'];

    /**
     * @var array the HTML attributes for the node checkbox indicator for all items in the tree
     */
    public $nodeCheckboxOptions = ['class' => 'text-success'];

    /**
     * @var array the HTML attributes for the indicator for expanding a node. The following special options are
     *     recognized:
     * - 'label': string, the label for the indicator. If not set will default to:
     *    - `<span class="fa fa-plus-square-o"></span>` if `fontAwesome` is true
     *    - `<span class="glyphicon glyphicon-expand"></span>` if `fontAwesome` is false
     */
    public $expandNodeOptions = [];

    /**
     * @var array the HTML attributes for the indicator for collapsing a node. The following special options are
     *     recognized:
     * - 'label': string, the label for the indicator. If not set will default to:
     *    - `<span class="fa fa-minus-square-o"></span>` if `fontAwesome` is true
     *    - `<span class="glyphicon glyphicon-collapse-down"></span>` if `fontAwesome` is false
     */
    public $collapseNodeOptions = [];

    /**
     * @var array the HTML attributes for the indicator which will represent a checked checkbox. The following special
     *     options are recognized:
     * - 'label': string, the label for the indicator. If not set will default to:
     *    - `<span class="fa fa-check-square-o"></span>` if `fontAwesome` is true
     *    - `<span class="glyphicon glyphicon-checked"></span>` if `fontAwesome` is false
     */
    public $checkedNodeOptions = [];

    /**
     * @var array the HTML attributes for the indicator which will represent an unchecked checkbox. The
     * following special options are recognized:
     * - 'label': string, the label for the indicator. If not set will default to:
     *    - `<span class="fa fa-square-o"></span>` if `fontAwesome` is true
     *    - `<span class="glyphicon glyphicon-unchecked"></span>` if `fontAwesome` is false
     */
    public $uncheckedNodeOptions = [];

    /**
     * @var array the HTML attributes for the wrapper container for the tree header, body, and footer.
     */
    public $treeWrapperOptions = ['class' => 'kv-tree-wrapper form-control'];

    /**
     * @var array the HTML attributes for the heading. The following additional option is recognized:
     * `label`: the label to display for the heading
     */
    public $headingOptions = ['class' => 'kv-tree-heading'];

    /**
     * @var array the HTML attributes for the tree header container
     */
    public $headerOptions = [];

    /**
     * @var array the HTML attributes for the search container
     */
    public $searchContainerOptions = ['class' => 'kv-search-sm'];

    /**
     * @var array the HTML attributes for the search input
     */
    public $searchOptions = ['class' => 'input-sm'];

    /**
     * @var array the HTML attributes for the search clear indicator
     */
    public $searchClearOptions = ['class' => 'close'];

    /**
     * @var array the HTML attributes for the tree footer container.
     */
    public $footerOptions = [];

    /**
     * @var array the HTML attributes for the tree selector container
     */
    public $treeOptions = ['style' => 'height:410px'];

    /**
     * @var array the HTML attributes for the detail form container which will display the details of the selected node
     */
    public $detailOptions = [];

    /**
     * @var array the HTML attributes for the input that will store the selected nodes for the widget
     */
    public $options = [];

    /**
     * @var string the main template for rendering the tree view navigation widget and the node detail view form.
     */
    public $mainTemplate = <<< HTML
<div class="row">
    <div class="col-sm-3">
        {wrapper}
    </div>
    <div class="col-sm-9">
        {detail}
    </div>
</div>
HTML;

    /**
     * @var string the wrapper template for rendering the tree view navigation widget
     */
    public $wrapperTemplate = "{header}\n{tree}{footer}";

    /**
     * @var string the template for rendering the header
     */
    public $headerTemplate = <<< HTML
<div class="row">
    <div class="col-sm-6">
        {heading}
    </div>
    <div class="col-sm-6">
        {search}
    </div>
</div>
HTML;

    /**
     * @var string the template for rendering the footer
     */
    public $footerTemplate = "{toolbar}";

    /**
     * @var Module the tree management module.
     */
    protected $_module;

    /**
     * @var string the icon prefix
     */
    protected $_iconPrefix = 'glyphicon glyphicon-';

    /**
     * @var mixed the icons list
     */
    protected $_iconsList;

    /**
     * @var array the queried tree nodes
     */
    protected $_nodes = [];

    /**
     * @var bool whether to load the bootstrap plugin asset
     */
    protected $_hasBootstrap = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->initTreeView();
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (!$this->_module->treeStructure['treeAttribute']) {
            $this->allowNewRoots = false;
        }
        $this->_nodes = $this->query->all();
        $this->initOptions();
        $this->registerAssets();
        echo $this->renderWidget();
    }

    /**
     * Initializes and validates the tree view configurations
     *
     * @throws InvalidConfigException
     */
    protected function initTreeView()
    {
        $this->validateSourceData();
        $this->_module = Config::initModule(Module::className());
        $this->initSelectedNode();
        if (empty($this->emptyNodeMsg)) {
            $this->emptyNodeMsg = Yii::t(
                'kvtree',
                'No valid tree nodes are available for display. Use toolbar buttons to add tree nodes.'
            );
        }
        $this->_hasBootstrap = $this->showTooltips;
        $this->breadcrumbs += [
            'depth' => null,
            'glue' => ' &raquo; ',
            'activeCss' => 'kv-crumb-active',
            'untitled' => Yii::t('kvtree', 'Untitled')
        ];
    }

    /**
     * Initializes the selected node
     *
     * @return void
     */
    protected function initSelectedNode()
    {
        $session = Yii::$app->session;
        $nodeSelected = $session->get('kvNodeId', '');
        if (!empty($nodeSelected)) {
            $this->displayValue = $nodeSelected;
            $session->set('kvNodeId', '');
        }
    }

    /**
     * Validation of source query data
     *
     * @throws InvalidConfigException
     */
    protected function validateSourceData()
    {
        if (empty($this->query) || !$this->query instanceof ActiveQuery) {
            throw new InvalidConfigException(
                "The 'query' property must be defined and must be an instance of '" . ActiveQuery::className() . "'."
            );
        }
        $class = isset($this->query->modelClass) ? $this->query->modelClass : null;
        if (empty($class) || !is_subclass_of($class, ActiveRecord::className())) {
            throw new InvalidConfigException("The 'query' must be implemented using 'ActiveRecord::find()' method.");
        }
        $trait = 'kartik\tree\models\TreeTrait';
        if (!self::usesTrait($class, $trait)) {
            throw new InvalidConfigException(
                "The model class '{$class}' for the 'query' must use the trait '{$trait}' or extend from '" .
                Tree::className() . "''."
            );
        }
    }

    /**
     * Check if the trait is used by a specific class or recursively by
     * any of the parent classes or parent traits
     *
     * @param string $class the class name to check
     * @param string $trait the trait class name
     * @param bool   $autoload whether to autoload the class
     *
     * @return bool whether the class has used the trait
     */
    protected static function usesTrait($class, $trait, $autoload = false)
    {
        $traits = [];
        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
            if (in_array($trait, $traits)) {
                return true;
            }
        } while ($class = get_parent_class($class));
        $traitsToSearch = $traits;
        while (!empty($traitsToSearch)) {
            $newTraits = class_uses(array_pop($traitsToSearch), $autoload);
            $traits = array_merge($newTraits, $traits);
            if (in_array($trait, $traits)) {
                return true;
            }
            $traitsToSearch = array_merge($newTraits, $traitsToSearch);
        };
        foreach ($traits as $t => $str) {
            $traits = array_merge(class_uses($t, $autoload), $traits);
            if (in_array($trait, $traits)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the tree view module
     *
     * @return Module
     */
    public static function module()
    {
        return Config::getModule(Module::MODULE);
    }

    /**
     * Generates the configuration for the widget based on
     * module level defaults
     *
     * @param array $config the widget configuration
     *
     * @throws InvalidConfigException
     * @return array
     */
    public static function getConfig($config = [])
    {
        $module = self::module();
        if (!empty($module->treeViewSettings)) {
            $config = array_replace_recursive($module->treeViewSettings, $config);
        }
        return $config;
    }

    /**
     * @inheritdoc
     */
    public static function begin($config = [])
    {
        $config = self::getConfig($config);
        return parent::begin($config);
    }

    /**
     * @inheritdoc
     */
    public static function widget($config = [])
    {
        $config = self::getConfig($config);
        return parent::widget($config);
    }

    /**
     * Initialize all options & settings for the widget
     */
    public function initOptions()
    {
        $this->_iconPrefix = $this->fontAwesome ? 'fa fa-' : 'glyphicon glyphicon-';
        if (empty($this->buttonIconOptions['class'])) {
            $this->buttonIconOptions['class'] = $this->fontAwesome ? 'kv-icon-10' : 'kv-icon-05';
        }
        $this->options['data-key'] = $this->displayValue;
        if (empty($this->options['class'])) {
            $this->options['class'] = 'form-control hide';
        }
        Html::addCssClass($this->headerOptions, 'kv-header-container');
        Html::addCssClass($this->headingOptions, 'kv-heading-container');
        Html::addCssClass($this->toolbarOptions, 'kv-toolbar-container');
        Html::addCssClass($this->footerOptions, 'kv-footer-container');
        $css = 'kv-tree-container';
        if ($this->showCheckbox) {
            $css .= ' kv-has-checkbox';
        }
        if (!$this->multiple) {
            $css .= ' kv-single-select';
        }
        Html::addCssClass($this->treeOptions, $css);
        Html::addCssClass($this->rootOptions, 'kv-tree-root');
        Html::addCssClass($this->nodeToggleOptions, 'kv-node-toggle');
        Html::addCssClass($this->nodeCheckboxOptions, 'kv-node-checkbox');
        Html::addCssClass($this->rootNodeToggleOptions, 'kv-root-node-toggle');
        Html::addCssClass($this->rootNodeCheckboxOptions, 'kv-root-node-checkbox');
        Html::addCssClass($this->detailOptions, 'kv-detail-container');
        Html::addCssClass($this->searchContainerOptions, 'kv-search-container');
        Html::addCssClass($this->searchOptions, 'kv-search-input form-control');
        Html::addCssClass($this->searchClearOptions, 'kv-search-clear');
        Html::addCssClass($this->expandNodeOptions, 'kv-node-expand');
        Html::addCssClass($this->collapseNodeOptions, 'kv-node-collapse');
        Html::addCssClass($this->childNodeIconOptions, 'kv-node-icon');
        Html::addCssClass($this->parentNodeIconOptions, 'kv-node-icon');
        Html::addCssClass($this->childNodeIconOptions, 'kv-icon-child');
        Html::addCssClass($this->parentNodeIconOptions, 'kv-icon-parent');
        if (empty($this->searchClearOptions['title'])) {
            $this->searchClearOptions['title'] = Yii::t('kvtree', 'Clear search results');
        }
        Html::addCssClass($this->buttonGroupOptions, 'btn-group');
        $this->treeWrapperOptions['id'] = $this->options['id'] . '-wrapper';
        $this->treeOptions['id'] = $this->options['id'] . '-tree';
        $this->detailOptions['id'] = $this->options['id'] . '-detail';
        $this->toolbarOptions['id'] = $this->options['id'] . '-toolbar';
        if (!isset($this->searchOptions['placeholder'])) {
            $this->searchOptions['placeholder'] = Yii::t('kvtree', 'Search...');
        }
        $this->toolbarOptions['role'] = 'toolbar';
        $this->buttonGroupOptions['role'] = 'group';
        $this->clientMessages += [
            'invalidCreateNode' => Yii::t('kvtree', 'Cannot create node. Parent node is not saved or is invalid.'),
            'emptyNode' => Yii::t('kvtree', '(new)'),
            'removeNode' => Yii::t('kvtree', 'Are you sure you want to remove this node?'),
            'nodeRemoved' => Yii::t('kvtree', 'The node was removed successfully.'),
            'nodeRemoveError' => Yii::t('kvtree', 'Error while removing the node. Please try again later.'),
            'nodeNewMove' => Yii::t('kvtree', 'Cannot move this node as the node details are not saved yet.'),
            'nodeTop' => Yii::t('kvtree', 'Already at top-most node in the hierarchy.'),
            'nodeBottom' => Yii::t('kvtree', 'Already at bottom-most node in the hierarchy.'),
            'nodeLeft' => Yii::t('kvtree', 'Already at left-most node in the hierarchy.'),
            'nodeRight' => Yii::t('kvtree', 'Already at right-most node in the hierarchy.'),
            'emptyNodeRemoved' => Yii::t('kvtree', 'The untitled node was removed.'),
            'selectNode' => Yii::t('kvtree', 'Select a node by clicking on one of the tree items.'),
        ];
        $defaultToolbar = [
            self::BTN_CREATE => [
                'icon' => 'plus',
                'options' => ['title' => Yii::t('kvtree', 'Add new'), 'disabled' => true]
            ],
            self::BTN_CREATE_ROOT => [
                'icon' => $this->fontAwesome ? 'tree' : 'tree-conifer',
                'options' => ['title' => Yii::t('kvtree', 'Add new root')]
            ],
            self::BTN_REMOVE => [
                'icon' => 'trash',
                'options' => ['title' => Yii::t('kvtree', 'Delete'), 'disabled' => true]
            ],
            self::BTN_SEPARATOR,
            self::BTN_MOVE_UP => [
                'icon' => 'arrow-up',
                'options' => ['title' => Yii::t('kvtree', 'Move Up'), 'disabled' => true]
            ],
            self::BTN_MOVE_DOWN => [
                'icon' => 'arrow-down',
                'options' => ['title' => Yii::t('kvtree', 'Move Down'), 'disabled' => true]
            ],
            self::BTN_MOVE_LEFT => [
                'icon' => 'arrow-left',
                'options' => ['title' => Yii::t('kvtree', 'Move Left'), 'disabled' => true]
            ],
            self::BTN_MOVE_RIGHT => [
                'icon' => 'arrow-right',
                'options' => ['title' => Yii::t('kvtree', 'Move Right'), 'disabled' => true]
            ],
            self::BTN_SEPARATOR,
            self::BTN_REFRESH => [
                'icon' => 'refresh',
                'options' => ['title' => Yii::t('kvtree', 'Refresh')],
                'url' => Yii::$app->request->url
            ],
        ];
        if (!$this->allowNewRoots) {
            unset($defaultToolbar[self::BTN_CREATE_ROOT]);
        }
        $this->toolbar = array_replace_recursive($defaultToolbar, $this->toolbar);
        if ($this->defaultChildNodeIcon === null) {
            $this->defaultChildNodeIcon = $this->getNodeIcon(1);
        }
        if ($this->defaultParentNodeIcon === null) {
            $this->defaultParentNodeIcon = $this->getNodeIcon(2);
        }
        if ($this->defaultParentNodeOpenIcon === null) {
            $this->defaultParentNodeOpenIcon = $this->getNodeIcon(3);
        }
        $this->_iconsList = $this->getIconsList();
    }

    /**
     * Gets the default node icon markup
     *
     * @param int $type 1 = child, 2 = parent, 3 = parent open
     *
     * @return string
     */
    protected function getNodeIcon($type)
    {
        $css = $this->_iconPrefix;
        switch ($type) {
            case 1:
                $css .= "file";
                break;
            case 2:
                $css .= ($this->fontAwesome ? 'folder' : 'folder-close') . " kv-node-closed";
                break;
            case 3:
                $css .= "folder-open kv-node-opened";
                break;
            default:
                return null;
        }
        return Html::tag('span', '', ['class' => $css]);
    }

    /**
     * Render the default node icon markup
     *
     * @param string $icon the current node's icon
     * @param int    $iconType the current node's icon type, must be one of:
     *                         - `TreeView::ICON_CSS` or `1`: if the icon css class suffix name is stored in $icon.
     *                         - `TreeView::ICON_RAW` or `2`: if the raw icon markup is stored in $icon.
     * @param bool   $child whether child or parent
     *
     * @return string
     */
    protected function renderNodeIcon($icon, $iconType, $child = true)
    {
        if (!empty($icon)) {
            $options = $child ? $this->childNodeIconOptions : $this->parentNodeIconOptions;
            $css = $this->_iconPrefix . $icon;
            $icon = $iconType == self::ICON_CSS ? Html::tag('span', '', ['class' => $css]) : $icon;
            return Html::tag('span', $icon, $options);
        }
        $content = $this->defaultParentNodeIcon . $this->defaultParentNodeOpenIcon;
        return Html::tag('span', $content, $this->parentNodeIconOptions) .
        Html::tag('span', $this->defaultChildNodeIcon, $this->childNodeIconOptions);
    }

    /**
     * Gets the default toggle icon based on fontAwesome setting
     *
     * @param string $action whether 'collapse' or 'expand'
     *
     * @return string
     */
    protected function getToggleIcon($action = 'collapse')
    {
        if ($action === 'expand') {
            return $this->fontAwesome ? 'plus-square-o' : 'expand';
        }
        return $this->fontAwesome ? 'minus-square-o' : 'collapse-down';
    }

    /**
     * Renders the default toggle icon markup based on fontAwesome setting
     *
     * @param string $action whether 'collapse' or 'expand'
     *
     * @return string
     */
    protected function renderToggleIcon($action = 'collapse')
    {
        $icon = $this->_iconPrefix . $this->getToggleIcon($action);
        $options = $action == 'expand' ? $this->expandNodeOptions : $this->collapseNodeOptions;
        $label = ArrayHelper::remove($options, 'label', '<span class="' . $icon . '"></span>');
        return Html::tag('span', $label, ['class' => "kv-node-{$action}"]);
    }

    /**
     * Renders the toggle icon container
     *
     * @param bool $root whether a root node
     *
     * @return string
     */
    protected function renderToggleIconContainer($root = false)
    {
        $content = $this->renderToggleIcon('expand') . $this->renderToggleIcon('collapse');
        $options = $root ? $this->rootNodeToggleOptions : $this->nodeToggleOptions;
        return Html::tag('span', $content, $options);
    }

    /**
     * Gets the checkbox icon based on fontAwesome setting
     *
     * @param bool $checked whether 'checked'
     *
     * @return string
     */
    protected function getCheckboxIcon($checked = false)
    {
        if ($checked) {
            return $this->fontAwesome ? 'check-square-o' : 'check';
        }
        return $this->fontAwesome ? 'square-o' : 'unchecked';
    }

    /**
     * Renders the checkbox icon markup based on fontAwesome setting
     *
     * @param bool $checked whether 'checked'
     *
     * @return string
     */
    protected function renderCheckboxIcon($checked = false)
    {
        $icon = $this->_iconPrefix . $this->getCheckboxIcon($checked);
        $options = $checked ? $this->checkedNodeOptions : $this->uncheckedNodeOptions;
        $label = ArrayHelper::remove($options, 'label', '<span class="' . $icon . '"></span>');
        $action = $checked ? 'checked' : 'unchecked';
        return Html::tag('span', $label, ['class' => "kv-node-{$action}"]);
    }

    /**
     * Renders the checkbox icon container
     *
     * @param bool $root whether its a root node
     *
     * @return string
     */
    protected function renderCheckboxIconContainer($root = false)
    {
        $content = $this->renderCheckboxIcon(true) . $this->renderCheckboxIcon(false);
        $options = $root ? $this->rootNodeCheckboxOptions : $this->nodeCheckboxOptions;
        return Html::tag('span', $content, $options);
    }

    /**
     * Renders a generic icon using icon suffix
     *
     * @param string $icon the icon suffix name
     * @param array  $options the HTML attributes for the icon container
     *
     * @return string
     */
    protected function renderIcon($icon, $options = [])
    {
        Html::addCssClass($options, $this->_iconPrefix . $icon);
        return Html::tag('span', '', $options);
    }

    /**
     * Renders the widget markup
     *
     * @return string
     */
    public function renderWidget()
    {
        $content = strtr($this->mainTemplate, [
            '{wrapper}' => $this->renderWrapper(),
            '{detail}' => $this->renderDetail(),
        ]);
        return strtr($content, [
            '{heading}' => $this->renderHeading(),
            '{search}' => $this->renderSearch(),
            '{toolbar}' => $this->renderToolbar(),
        ]) . "\n" .
        Html::textInput('kv-node-selected', $this->value, $this->options) . "\n";
    }

    /**
     * Renders the tree wrapper container
     *
     * @return string
     */
    public function renderWrapper()
    {
        $content = strtr($this->wrapperTemplate, [
            '{header}' => $this->renderHeader(),
            '{tree}' => $this->renderTree(),
            '{footer}' => $this->renderFooter(),
        ]);
        return Html::tag('div', $content, $this->treeWrapperOptions);
    }

    /**
     * Renders the markup for the button actions toolbar
     *
     * @return string
     */
    public function renderToolbar()
    {
        $out = Html::beginTag('div', $this->toolbarOptions) . "\n" .
            Html::beginTag('div', $this->buttonGroupOptions);
        foreach ($this->toolbar as $btn => $settings) {
            if ($settings === false) {
                continue;
            }
            if ($settings === self::BTN_SEPARATOR) {
                $out .= "\n</div>\n" . Html::beginTag('div', $this->buttonGroupOptions);
                continue;
            }
            $icon = ArrayHelper::getValue($settings, 'icon', '');
            $label = ArrayHelper::getValue($settings, 'label', '');
            $iconOptions = ArrayHelper::getValue($settings, 'iconOptions', []);
            $options = ArrayHelper::getValue($settings, 'options', []);
            $iconOptions = array_replace_recursive($this->buttonIconOptions, $iconOptions);
            $options = array_replace_recursive($this->buttonOptions, $options);
            Html::addCssClass($options, 'kv-toolbar-btn kv-' . $btn);
            if (!empty($icon)) {
                $icon = $this->renderIcon($icon, $iconOptions);
                $label = empty($label) ? $icon : $icon . ' ' . $label;
            }
            if (!empty($settings['url'])) {
                $out .= "\n" . Html::a($label, $settings['url'], $options);
            } else {
                $out .= "\n" . Html::button($label, $options);
            }
        }
        $out .= "</div>\n</div>";
        return $out;
    }

    /**
     * Renders the root markup for the tree
     *
     * @return string
     */
    public function renderRoot()
    {
        $content = $this->renderToggleIconContainer(true);
        if ($this->showCheckbox) {
            $content .= $this->renderCheckboxIconContainer(true);
        }
        $content .= ArrayHelper::remove($this->rootOptions, 'label', Yii::t('kvtree', 'Root'));
        return Html::tag('div', $content, $this->rootOptions);
    }

    /**
     * Renders the markup for the tree header container
     *
     * @return string
     */
    public function renderHeader()
    {
        return Html::tag('div', $this->headerTemplate, $this->headerOptions);
    }

    /**
     * Renders the markup for the tree footer container
     *
     * @return string
     */
    public function renderFooter()
    {
        return Html::tag('div', $this->footerTemplate, $this->footerOptions);
    }

    /**
     * Renders the markup for the search input
     *
     * @return string
     */
    public function renderSearch()
    {
        $clearLabel = ArrayHelper::remove($this->searchClearOptions, 'label', '&times;');
        $content = Html::tag('span', $clearLabel, $this->searchClearOptions) . "\n" .
            Html::textInput('kv-tree-search', null, $this->searchOptions);
        return Html::tag('div', $content, $this->searchContainerOptions);
    }

    /**
     * Renders the markup for the tree heading
     *
     * @return string
     */
    public function renderHeading()
    {
        $heading = ArrayHelper::remove($this->headingOptions, 'label', '');
        return Html::tag('div', $heading, $this->headingOptions);
    }

    /**
     * Renders the markup for the tree hierarchy - uses a fast non-recursive mode of tree traversal.
     *
     * @return string
     */
    public function renderTree()
    {
        $struct = $this->_module->treeStructure + $this->_module->dataStructure;
        extract($struct);
        $nodeDepth = $currDepth = $counter = 0;
        $out = Html::beginTag('ul', ['class' => 'kv-tree']) . "\n";
        foreach ($this->_nodes as $node) {
            /**
             * @var Tree $node
             */
            if (!$this->isAdmin && !$node->isVisible() || !$this->showInactive && !$node->isActive()) {
                continue;
            }
            /** @noinspection PhpUndefinedVariableInspection */
            $nodeDepth = $node->$depthAttribute;
            /** @noinspection PhpUndefinedVariableInspection */
            $nodeLeft = $node->$leftAttribute;
            /** @noinspection PhpUndefinedVariableInspection */
            $nodeRight = $node->$rightAttribute;
            /** @noinspection PhpUndefinedVariableInspection */
            $nodeKey = $node->$keyAttribute;
            /** @noinspection PhpUndefinedVariableInspection */
            $nodeName = $node->$nameAttribute;
            /** @noinspection PhpUndefinedVariableInspection */
            $nodeIcon = $node->$iconAttribute;
            /** @noinspection PhpUndefinedVariableInspection */
            $nodeIconType = $node->$iconTypeAttribute;

            $isChild = ($nodeRight == $nodeLeft + 1);
            $indicators = '';
            $css = '';

            if ($nodeDepth == $currDepth) {
                if ($counter > 0) {
                    $out .= "</li>\n";
                }
            } elseif ($nodeDepth > $currDepth) {
                $out .= Html::beginTag('ul') . "\n";
                $currDepth = $currDepth + ($nodeDepth - $currDepth);
            } elseif ($nodeDepth < $currDepth) {
                $out .= str_repeat("</li>\n</ul>", $currDepth - $nodeDepth) . "</li>\n";
                $currDepth = $currDepth - ($currDepth - $nodeDepth);
            }
            if (trim($indicators) == null) {
                $indicators = '&nbsp;';
            }
            $nodeOptions = [
                'data-key' => $nodeKey,
                'data-lft' => $nodeLeft,
                'data-rgt' => $nodeRight,
                'data-lvl' => $nodeDepth,
                'data-readonly' => static::parseBool($node->isReadonly()),
                'data-movable-u' => static::parseBool($node->isMovable('u')),
                'data-movable-d' => static::parseBool($node->isMovable('d')),
                'data-movable-l' => static::parseBool($node->isMovable('l')),
                'data-movable-r' => static::parseBool($node->isMovable('r')),
                'data-removable' => static::parseBool($node->isRemovable()),
                'data-removable-all' => static::parseBool($node->isRemovableAll()),
            ];
            if (!$isChild) {
                $css = ' kv-parent ';
            }
            if (!$node->isVisible() && $this->isAdmin) {
                $css .= ' kv-invisible';
            }
            if ($this->showCheckbox && $node->isSelected()) {
                $css .= ' kv-selected ';
            }
            if ($node->isCollapsed()) {
                $css .= ' kv-collapsed ';
            }
            if ($node->isDisabled()) {
                $css .= ' kv-disabled ';
            }
            if (!$node->isActive()) {
                $css .= ' kv-inactive ';
            }
            $indicators .= $this->renderToggleIconContainer(false) . "\n";
            $indicators .= $this->showCheckbox ? $this->renderCheckboxIconContainer(false) . "\n" : '';
            $css = trim($css);
            if (!empty($css)) {
                Html::addCssClass($nodeOptions, $css);
            }
            $out .= Html::beginTag('li', $nodeOptions) . "\n" .
                Html::beginTag('div', ['tabindex' => -1, 'class' => 'kv-tree-list']) . "\n" .
                Html::beginTag('div', ['class' => 'kv-node-indicators']) . "\n" .
                $indicators . "\n" .
                '</div>' . "\n" .
                Html::beginTag('div', ['tabindex' => -1, 'class' => 'kv-node-detail']) . "\n" .
                $this->renderNodeIcon($nodeIcon, $nodeIconType, $isChild) . "\n" .
                Html::tag('span', $nodeName, ['class' => 'kv-node-label']) . "\n" .
                '</div>' . "\n" .
                '</div>' . "\n";
            ++$counter;
        }
        $out .= str_repeat("</li>\n</ul>", $nodeDepth) . "</li>\n";
        $out .= "</ul>\n";
        return Html::tag('div', $this->renderRoot() . $out, $this->treeOptions);
    }

    /**
     * Parses a boolean variable and returns as integer
     *
     * @param bool $var the variable to parse
     *
     * @return int
     */
    protected static function parseBool($var)
    {
        return $var ? 1 : 0;
    }

    
    /**
     * Renders the markup for the detail form to edit/view the selected tree node
     *
     * @return string
     */
    public function renderDetail()
    {
        /**
         * @var Tree $modelClass
         */
        $modelClass = $this->query->modelClass;
        $node = $modelClass::findOne($this->displayValue);
        if (empty($node)) {
            $msg = Html::tag('div', $this->emptyNodeMsg, $this->emptyNodeMsgOptions);
            return Html::tag('div', $msg, $this->detailOptions);
        }
        $iconTypeAttribute = ArrayHelper::getValue($this->_module->dataStructure, 'iconTypeAttribute', 'icon_type');
        if ($this->_iconsList !== false) {
            $node->$iconTypeAttribute = ArrayHelper::getValue($this->iconEditSettings, 'type', self::ICON_CSS);
        }
        $params = $this->_module->treeStructure + $this->_module->dataStructure + [
                'node' => $node,
                'action' => $this->nodeActions[Module::NODE_SAVE],
                'formOptions' => $this->nodeFormOptions,
                'modelClass' => $modelClass,
                'currUrl' => Yii::$app->request->url,
                'isAdmin' => $this->isAdmin,
                'iconsList' => $this->_iconsList,
                'softDelete' => $this->softDelete,
                'showFormButtons' => $this->showFormButtons,
                'showIDAttribute' => $this->showIDAttribute,
                'nodeView' => $this->nodeView,
                'nodeAddlViews' => $this->nodeAddlViews,
                'breadcrumbs' => $this->breadcrumbs
            ];
        $content = $this->render($this->nodeView, ['params' => $params]);
        return Html::tag('div', $content, $this->detailOptions);
    }

    /**
     * Renders the markup for the detail form to edit/view the selected tree node
     *
     * @return string
     */
    protected function getIconsList()
    {
        $show = ArrayHelper::getValue($this->iconEditSettings, 'show', 'text');
        if ($show != 'list') {
            return $show;
        }
        $type = ArrayHelper::getValue($this->iconEditSettings, 'type', self::ICON_CSS);
        $settings = ArrayHelper::getValue($this->iconEditSettings, 'listData', []);
        if ($type === self::ICON_RAW) {
            return $settings;
        }
        $newSettings = [
            '' => '<em>' . Yii::t('kvtree', 'Default') . '</em> ( ' .
                Html::tag('span', $this->defaultParentNodeIcon, $this->parentNodeIconOptions) . ' / ' .
                Html::tag('span', $this->defaultParentNodeOpenIcon, $this->parentNodeIconOptions) . ' / ' .
                Html::tag('span', $this->defaultChildNodeIcon, $this->childNodeIconOptions) . ')'
        ];
        foreach ($settings as $suffix => $label) {
            $newSettings[$suffix] = Html::tag('span', '', ['class' => $this->_iconPrefix . $suffix]) . ' ' . $label;
        }
        return $newSettings;
    }

    /**
     * Registers the client assets for the widget
     */
    public function registerAssets()
    {
        $view = $this->getView();
        TreeViewAsset::register($view);
        if ($this->_hasBootstrap && $this->autoLoadBsPlugin) {
            BootstrapPluginAsset::register($view);
        }
        $this->pluginOptions += [
            'treeId' => $this->treeOptions['id'],
            'detailId' => $this->detailOptions['id'],
            'toolbarId' => $this->toolbarOptions['id'],
            'wrapperId' => $this->treeWrapperOptions['id'],
            'actions' => $this->nodeActions,
            'modelClass' => $this->query->modelClass,
            'formAction' => $this->nodeActions[Module::NODE_SAVE],
            'formOptions' => $this->nodeFormOptions,
            'currUrl' => Yii::$app->request->url,
            'messages' => $this->clientMessages,
            'alertFadeDuration' => $this->alertFadeDuration,
            'enableCache' => ArrayHelper::getValue($this->cacheSettings, 'enableCache', true),
            'cacheTimeout' => ArrayHelper::getValue($this->cacheSettings, 'cacheTimeout', 300000),
            'showTooltips' => $this->showTooltips,
            'isAdmin' => $this->isAdmin,
            'showInactive' => $this->showInactive,
            'softDelete' => $this->softDelete,
            'iconsList' => $this->_iconsList,
            'showFormButtons' => $this->showFormButtons,
            'showIDAttribute' => $this->showIDAttribute,
            'nodeView' => $this->nodeView,
            'nodeAddlViews' => $this->nodeAddlViews,
            'breadcrumbs' => $this->breadcrumbs,
            'multiple' => $this->multiple,
            'allowNewRoots' => $this->allowNewRoots
        ];
        $this->registerPlugin('treeview');
    }
}
