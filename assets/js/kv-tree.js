/*!
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package yii2-tree-manager
 * @version 1.0.6
 * 
 * Tree View Validation Module.
 *
 * Author: Kartik Visweswaran
 * Copyright: 2015 - 2016, Kartik Visweswaran, Krajee.com
 * For more JQuery plugins visit http://plugins.krajee.com
 * For more Yii related demos visit http://demos.krajee.com
 */
(function ($) {
    /*jshint bitwise: false*/
    "use strict";
    var QUERY_PARAM = 'kvtree',
        defaultBtns = {
            'create': 'create',
            'createR': 'create-root',
            'trash': 'remove',
            'moveU': 'move-up',
            'moveD': 'move-down',
            'moveL': 'move-left',
            'moveR': 'move-right',
            'refresh': 'refresh'
        },
        isEmpty = function (value, trim) {
            return value === null || value === undefined || value.length === 0 || (trim && $.trim(value) === '');
        },
        escapeRegExp = function (str) {
            return str.replace(/[\-\[\]\/\{}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
        },
        addCss = function ($el, css) {
            $el.removeClass(css).addClass(css);
        },
        hashString = function (s) {
            return s.split("").reduce(function (a, b) {
                a = ((a << 5) - a) + b.charCodeAt(0);
                return a & a;
            }, 0);
        },
        delay = (function () {
            var timer = 0;
            return function (callback, ms) {
                clearTimeout(timer);
                timer = setTimeout(callback, ms);
            };
        })(),
        kvTreeCache = {
            timeout: 300000,
            data: {},
            remove: function (url) {
                delete kvTreeCache.data[url];
            },
            exist: function (url) {
                return !!kvTreeCache.data[url] && ((new Date().getTime() - kvTreeCache.data[url]._) < kvTreeCache.timeout);
            },
            get: function (url) {
                return kvTreeCache.data[url].data;
            },
            set: function (url, cachedData, callback) {
                kvTreeCache.remove(url);
                kvTreeCache.data[url] = {
                    _: new Date().getTime(),
                    data: cachedData
                };
                if ($.isFunction(callback)) {
                    callback(cachedData);
                }
            }
        },
        TreeView = function (element, options) {
            var self = this;
            self.$element = $(element);
            self.init(options);
            self.listen();
        };
    TreeView.prototype = {
        constructor: TreeView,
        init: function (options) {
            var self = this;
            $.each(options, function (key, data) {
                self[key] = data;
            });
            self.btns = $.extend({}, defaultBtns, self.btns);
            self.$tree = $('#' + self.treeId);
            self.$treeContainer = self.$tree.parent();
            self.$detail = $('#' + self.detailId);
            self.$toolbar = $('#' + self.toolbarId);
            self.$wrapper = $('#' + self.wrapperId);
            self.$searchContainer = self.$wrapper.find('.kv-search-container');
            self.$search = self.$wrapper.find('.kv-search-input');
            self.$clear = self.$wrapper.find('.kv-search-clear');
            self.select(self.$element.data('key'), true);
            kvTreeCache.timeout = self.cacheTimeout;
            self.selectNodes();
            self.validateTooltips();
        },
        validateTooltips: function () {
            var self = this;
            if (self.showTooltips) {
                self.$toolbar.find('.btn').tooltip();
                self.$detail.find('.btn').tooltip();
            }
        },
        trigAlert: function ($alert, callback) {
            var dur = this.alertFadeDuration;
            if (!callback || !$.isFunction(callback)) {
                callback = function () {
                };
            }
            setTimeout(function () {
                $alert.fadeOut(dur, callback());
            }, dur * 2);
        },
        selectNodes: function () {
            var self = this, selected = self.$element.val();
            if (selected.length === 0 || isEmpty(selected)) {
                return;
            }
            var $nodes = self.$tree.find('li');
            $nodes.removeClass('kv-selected');
            selected = selected.split(",");
            $(selected).each(function (i, key) {
                addCss(self.$tree.find('li[data-key="' + key + '"]'), 'kv-selected');
            });
        },
        raise: function (event) {
            var self = this;
            if (arguments.length > 1) {
                self.$element.trigger(event, arguments[1]);
            } else {
                self.$element.trigger(event);
            }
        },
        enableToolbar: function () {
            var self = this;
            self.$toolbar.find('button').removeAttr('disabled');
        },
        disableToolbar: function () {
            var self = this;
            self.$toolbar.find('button').attr('disabled', true);
            self.$toolbar.find('.kv-' + self.btns.createR).removeAttr('disabled');
        },
        enable: function (action) {
            var self = this;
            self.$toolbar.find('.kv-' + self.btns[action]).removeAttr('disabled');
        },
        disable: function (action) {
            var self = this;
            self.$toolbar.find('.kv-' + self.btns[action]).attr('disabled', true);
        },
        showAlert: function (msg, type, callback) {
            var self = this, $detail = self.$detail, $alert = $detail.find('.alert-' + type);
            $detail.find('.kv-select-node-msg').remove();
            $alert.removeClass('hide').hide().find('div').remove();
            $alert.append('<div>' + msg + '</div>').fadeIn(self.alertFadeDuration, function () {
                self.trigAlert($alert, callback);
            });
        },
        removeAlert: function () {
            var self = this;
            self.$detail.find('.alert').addClass('hide');
        },
        renderForm: function (key, par, mesg) {
            var self = this, $detail = self.$detail, parent = par || '', msg = mesg || false,
                params = hashString(key + self.modelClass + self.isAdmin + parent), $form = $detail.find('form'),
                vUrl = self.actions.manage, sep = vUrl && vUrl.indexOf('?') !== -1 ? '&' : '?';
            vUrl += encodeURI(sep + QUERY_PARAM + '=' + params);
            self.formViewBegin = true;
            self.parseCache();
            self.removeAlert();
            $.ajax({
                type: 'post',
                dataType: 'json',
                data: {
                    'id': key,
                    'modelClass': self.modelClass,
                    'isAdmin': self.isAdmin,
                    'formAction': self.formAction,
                    'formOptions': self.formOptions,
                    'parentKey': parent,
                    'iconsList': self.iconsList,
                    'currUrl': self.currUrl,
                    'softDelete': self.softDelete,
                    'showFormButtons': self.showFormButtons,
                    'showIDAttribute': self.showIDAttribute,
                    'multiple': self.multiple,
                    'nodeView': self.nodeView,
                    'nodeAddlViews': self.nodeAddlViews,
                    'breadcrumbs': self.breadcrumbs
                },
                url: vUrl,
                cache: true,
                beforeSend: function (jqXHR, settings) {
                    self.raise('treeview.beforeselect', [key, jqXHR, settings]);
                    if ($form.length) {
                        $form.off().yiiActiveForm('destroy').remove();
                    }
                    $detail.html('');
                    addCss($detail, 'kv-loading');
                },
                success: function (data, textStatus, jqXHR) {
                    var ev = data.status === 'error' ? 'treeview.selecterror' : 'treeview.selected';
                    $detail.html(data.out);
                    self.raise(ev, [key, data, textStatus, jqXHR]);
                    $detail.removeClass('kv-loading');
                    // form reset
                    $detail.find('button[type="reset"]').on('click', function () {
                        self.removeAlert();
                    });
                    self.removeAlert();
                    if (msg !== false && !isEmpty(msg.out)) {
                        self.showAlert(msg.out, msg.type);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    self.raise('treeview.selectajaxerror', [key, jqXHR, textStatus, errorThrown]);
                },
                complete: function (jqXHR) {
                    self.raise('treeview.selectajaxcomplete', [key, jqXHR]);
                    self.validateTooltips();
                }
            });
        },
        select: function (key, init, mesg) {
            if (isEmpty(key)) {
                return;
            }
            var self = this, $sel, isInit = init || false, msg = mesg || false,
                $currNode = self.$tree.find('li[data-key="' + key + '"]>.kv-tree-list .kv-node-detail');
            if ($currNode.length === 0) {
                return;
            }
            self.$tree.find('.kv-node-detail').removeClass('kv-focussed');
            addCss($currNode, 'kv-focussed');
            if (isInit) {
                self.$tree.find('li.kv-parent').each(function () {
                    var $node = $(this);
                    if ($node.has($currNode).length > 0) {
                        $node.removeClass('kv-collapsed');
                    }
                });
            } else {
                self.renderForm(key, null, msg);
            }
            $sel = $currNode.closest('li');
            if ($sel.hasClass('kv-disabled')) {
                self.disableToolbar();
            } else {
                self.enableToolbar();
            }
            if (!$sel.data('removable') || ($sel.hasClass('kv-inactive') && self.softDelete) ||
                (!$sel.data('removableAll') && $sel.hasClass('kv-parent'))) {
                self.disable('trash');
            }
            if (!$sel.data('movable-u')) {
                self.disable('moveU');
            }
            if (!$sel.data('movable-d')) {
                self.disable('moveD');
            }
            if (!$sel.data('movable-l')) {
                self.disable('moveL');
            }
            if (!$sel.data('movable-r')) {
                self.disable('moveR');
            }
            self.parseParentFlag(key);
        },
        parseParentFlag: function (key) {
            var self = this, $flags = self.$detail.find('input[class="kv-parent-flag"]'), $div,
                $node = self.$tree.find('li[data-key="' + key + '"]');
            $flags.each(function () {
                var $flag = $(this);
                $div = $flag.closest('div.checkbox');
                $div.removeClass('disabled');
                if ($node.hasClass('kv-parent')) {
                    $flag.removeAttr('disabled');
                } else {
                    $flag.attr('disabled', 'disabled');
                    $div.addClass('disabled');
                }
            });
        },
        remove: function () {
            var self = this, $nodeText = self.$tree.find('li .kv-node-detail.kv-focussed'),
                $node = $nodeText.closest('li'), msg = self.messages, $detail = self.$detail,
                $form = $detail.find('form'), $alert, clearNode;
            if ($nodeText.length === 0 && !$node.hasClass('kv-empty') || $node.hasClass(
                    'kv-disabled') || !window.confirm(msg.removeNode)) {
                return;
            }
            clearNode = function (isEmpty) {
                var m = isEmpty ? msg.emptyNodeRemoved : msg.nodeRemoved,
                    $parent = $node.closest('li.kv-parent');
                $node.remove();
                $alert = $detail.find('.alert');
                self.formViewBegin = false;
                $detail.find('.kv-select-node-msg').remove();
                if ($alert.length) {
                    $detail.before($alert).html('').append($alert);
                }
                if (!$parent.find('li').length) {
                    $parent.removeClass('kv-parent');
                }
                self.showAlert(m, 'info', function () {
                    $detail.append(
                        '<h4 class="alert text-center kv-select-node-msg" style="display:none;">' + msg.selectNode + '</h4>');
                    setTimeout(function () {
                        if (!self.formViewBegin) {
                            $detail.find('.kv-select-node-msg').fadeIn(self.alertFadeDuration);
                        }
                    }, self.alertFadeDuration);
                });
            };
            if ($node.hasClass('kv-empty')) {
                clearNode(true);
                return;
            }
            var key = $node.data('key');
            $.ajax({
                type: 'post',
                dataType: 'json',
                data: {
                    'id': key,
                    'class': self.modelClass,
                    'softDelete': self.softDelete
                },
                url: self.actions.remove,
                beforeSend: function (jqXHR, settings) {
                    self.raise('treeview.beforeremove', [key, jqXHR, settings]);
                    $form.hide();
                    self.removeAlert();
                    addCss($detail, 'kv-loading');
                },
                success: function (data, textStatus, jqXHR) {
                    if (data.status === 'success') {
                        if ((self.isAdmin || self.showInactive) && self.softDelete) {
                            self.showAlert(data.out, 'info');
                            $form.show();
                            var fld = self.modelClass.split('\\').pop(),
                                $cbx = $form.find('input[name="' + fld + '[active]"]');
                            $cbx.val(false);
                            $cbx.prop('checked', false);
                            addCss($node, 'kv-inactive');
                            if ($node.data('removableAll')) {
                                addCss($node.find('li'), 'kv-inactive');
                            }
                            addCss($node, 'kv-inactive');
                        } else {
                            clearNode();
                        }
                        if (!self.softDelete) {
                            self.disableToolbar();
                        }
                        self.raise('treeview.remove', [key, data, textStatus, jqXHR]);
                    } else {
                        self.showAlert(data.out, 'danger');
                        $form.show();
                        self.raise('treeview.removeerror', [key, data, textStatus, jqXHR]);
                    }
                    $detail.removeClass('kv-loading');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    self.raise('treeview.removeajaxerror', [key, jqXHR, textStatus, errorThrown]);
                },
                complete: function (jqXHR) {
                    self.raise('treeview.removeajaxcomplete', [jqXHR]);
                }
            });
        },
        move: function (dir) {
            var self = this, $nodeText = self.$tree.find('li .kv-node-detail.kv-focussed'),
                $nodeFrom = $nodeText.closest('li'), msg = self.messages, $detail = self.$detail,
                $nodeTo = null, keyFrom, keyTo, outMsg, isRoot = false, $parent, fnMove = function () {
                };
            if ($nodeText.length === 0 || $nodeFrom.hasClass('kv-disabled')) {
                return;
            }
            if ($nodeFrom.hasClass('kv-empty')) {
                window.alert(msg.nodeNewMove);
                return;
            }
            switch (dir) {
                case 'u':
                    $nodeTo = $nodeFrom.prev();
                    if ($nodeTo.length === 0) {
                        window.alert(msg.nodeTop);
                        return;
                    }
                    fnMove = function () {
                        $nodeTo.before($nodeFrom);
                    };
                    break;
                case 'd':
                    $nodeTo = $nodeFrom.next();
                    if ($nodeTo.length === 0) {
                        window.alert(msg.nodeBottom);
                        return;
                    }
                    fnMove = function () {
                        $nodeTo.after($nodeFrom);
                    };
                    break;
                case 'l':
                    $nodeTo = $nodeFrom.parent('ul').closest('li.kv-parent');
                    if ($nodeTo.length === 0) {
                        window.alert(msg.nodeLeft);
                        return;
                    }
                    $parent = $nodeTo.parent('ul');
                    isRoot = $parent.hasClass('kv-tree');
                    if (isRoot) {
                        $nodeTo = $parent.children('li:last-child');
                    }
                    fnMove = function () {
                        $nodeTo.after($nodeFrom);
                        if ($nodeTo.find('li').length === 0) {
                            $nodeTo.removeClass('kv-parent');
                            $nodeTo.find('ul').remove();
                        }
                    };
                    break;
                case 'r':
                    $nodeTo = $nodeFrom.prev();
                    if ($nodeTo.length === 0) {
                        window.alert(msg.nodeRight);
                        return;
                    }
                    fnMove = function () {
                        if ($nodeTo.find('li').length > 0) {
                            $nodeTo.children('ul').append($nodeFrom);
                        } else {
                            addCss($nodeTo, 'kv-parent');
                            $(document.createElement('ul')).appendTo($nodeTo).append($nodeFrom);
                        }
                    };
                    break;
                default:
                    throw "Invalid move direction '" + dir + "'";
            }
            keyFrom = $nodeFrom.data('key');
            keyTo = $nodeTo.data('key');
            $.ajax({
                type: 'post',
                dataType: 'json',
                data: {
                    'idFrom': keyFrom,
                    'idTo': keyTo,
                    'class': self.modelClass,
                    'dir': dir,
                    'allowNewRoots': self.allowNewRoots
                },
                url: self.actions.move,
                beforeSend: function (jqXHR, settings) {
                    self.raise('treeview.beforemove', [dir, keyFrom, keyTo, jqXHR, settings]);
                    addCss(self.$treeContainer, 'kv-loading-search');
                },
                success: function (data, textStatus, jqXHR) {
                    if ($detail.length > 0) {
                        self.removeAlert();
                    }
                    if (data.status === 'success') {
                        fnMove();
                        if (dir === 'l' || dir === 'r') {
                            kvTreeCache.timeout = 0;
                            if ($detail.length > 0) {
                                outMsg = {out: data.out, type: 'success'};
                            } else {
                                outMsg = false;
                            }
                            self.select(keyFrom, false, outMsg);
                            kvTreeCache.timeout = self.cacheTimeout;
                        } else {
                            if ($detail.length > 0) {
                                self.showAlert(data.out, 'success');
                            }
                        }
                        self.$tree.find('li.kv-collapsed').each(function () {
                            if ($(this).has($nodeFrom).length > 0) {
                                $(this).removeClass('kv-collapsed');
                            }
                        });
                        self.raise('treeview.move', [dir, keyFrom, keyTo, data, textStatus, jqXHR]);
                    } else {
                        if ($detail.length > 0) {
                            self.showAlert(data.out, 'danger');
                            self.raise('treeview.moveerror', [dir, keyFrom, keyTo, data, textStatus, jqXHR]);
                        }
                    }
                    self.$treeContainer.removeClass('kv-loading-search');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if ($detail.length > 0) {
                        self.removeAlert();
                        self.showAlert(errorThrown, 'danger');
                    }
                    self.$treeContainer.removeClass('kv-loading-search');
                    self.raise('treeview.moveajaxerror', [dir, keyFrom, keyTo, jqXHR, textStatus, errorThrown]);
                },
                complete: function (jqXHR) {
                    self.raise('treeview.moveajaxcomplete', [jqXHR]);
                }
            });
        },
        setSelected: function () {
            var self = this, keys = '', desc = '';
            self.$tree.find('.kv-selected').each(function () {
                var $node = $(this), sep = isEmpty(keys) ? '' : ',';
                keys += sep + $node.data('key');
                desc += sep + $node.find('>.kv-tree-list .kv-node-label').text();
            });
            self.$element.val(keys);
            self.raise('treeview.change', [keys, desc]);
            self.raise('change');
        },
        getNewNode: function () {
            var self = this;
            return '<div class="kv-tree-list" tabindex="-1">\n' +
                '   <div class="kv-node-indicators">&nbsp;</div>\n' +
                '   <div class="kv-node-detail kv-focussed">\n' +
                '       <span class="kv-node-label">' + self.messages.emptyNode + '</span>\n' +
                '   </div>\n' +
                '</div>';
        },
        create: function () {
            var self = this, $nodeText = self.$tree.find('li .kv-node-detail.kv-focussed'), $n, key,
                $node = $nodeText.closest('li'), msg = self.messages, content, $nodeDetail, $newNode;
            if ($node.hasClass('kv-disabled')) {
                window.alert(msg.nodeDisabled);
                return;
            }
            if ($nodeText.length === 0 || $node.hasClass('kv-empty')) {
                window.alert(msg.invalidCreateNode);
                return;
            }
            self.$toolbar.find('.kv-' + self.btns.trash).removeAttr('disabled');
            $newNode = $node.find('> ul > li.kv-empty');
            if ($newNode.length > 0) {
                key = $newNode.data('key').replace('empty-', '');
                self.renderForm(null, key);
                $nodeText.removeClass('kv-focussed');
                addCss($newNode.find('.kv-node-detail'), 'kv-focussed');
                return;
            }
            $newNode = $(document.createElement("li")).attr({
                'data-key': 'empty-' + $node.data('key'),
                'class': 'kv-empty'
            });
            content = self.getNewNode();
            $nodeText.removeClass('kv-focussed');
            $newNode.append(content);
            if ($node.hasClass('kv-parent')) {
                $node.children('ul').append($newNode);
            } else {
                addCss($node, 'kv-parent');
                $n = $(document.createElement("ul")).append($newNode);
                $node.append($n);
            }
            self.renderForm(null, $node.data('key'));
            $nodeDetail = $newNode.find('.kv-node-detail');
            $node.removeClass('kv-collapsed');
            $newNode.children('.kv-tree-list').focus();
            $nodeDetail.on('click', function () {
                self.$tree.find('.kv-node-detail').removeClass('kv-focussed');
                addCss($nodeDetail, 'kv-focussed');
                key = $newNode.data('key').replace('empty-', '');
                self.renderForm(null, key);
                self.$toolbar.find('.kv-' + self.btns.trash).removeAttr('disabled');
            });
            self.raise('treeview.create', [parent]);
        },
        createRoot: function () {
            var self = this, $treeRoot = self.$tree.find('.kv-tree'),
                $root = $treeRoot.children('li.kv-empty');
            self.$tree.find('.kv-node-detail').removeClass('kv-focussed');
            if ($root.length > 0) {
                addCss($root.find('.kv-node-detail'), 'kv-focussed');
                self.renderForm(null, 'root');
                return;
            }
            var content = self.getNewNode(),
                $node = $(document.createElement("li")).attr({'data-key': 'empty-root', 'class': 'kv-empty'});
            $node.html(content);
            $treeRoot.append($node);
            self.renderForm(null, 'root');
            var $nodeDetail = $node.find('.kv-node-detail');
            addCss($nodeDetail, 'kv-focussed');
            self.$toolbar.find('.kv-' + self.btns.trash).removeAttr('disabled');
            $nodeDetail.on('click', function () {
                self.$tree.find('.kv-node-detail').removeClass('kv-focussed');
                addCss($nodeDetail, 'kv-focussed');
                self.renderForm(null, 'root');
                self.$toolbar.find('.kv-' + self.btns.trash).removeAttr('disabled');
            });
            self.raise('treeview.createroot');
        },
        toggle: function ($tog) {
            var self = this, $node = $tog.closest('li.kv-parent'), nodeKey = $node.data('key');
            if ($node.hasClass('kv-collapsed')) {
                $node.removeClass('kv-collapsed');
                self.raise('treeview.expand', [nodeKey]);
            } else {
                addCss($node, 'kv-collapsed');
                self.raise('treeview.collapse', [nodeKey]);
            }
        },
        toggleAll: function (action, trig) {
            var self = this;
            if (action === 'expand') {
                self.$tree.removeClass('kv-collapsed');
                self.$tree.find('.kv-collapsed').removeClass('kv-collapsed');
                if (trig) {
                    self.raise('treeview.expandall');
                }
                return;
            }
            addCss(self.$tree.find('li.kv-parent'), 'kv-collapsed');
            addCss(self.$tree, 'kv-collapsed');
            if (trig) {
                self.raise('treeview.collapseall');
            }
        },
        check: function ($chk) {
            var self = this, isRoot = ($chk === true),
                $node = isRoot ? self.$tree : $chk.closest('li'),
                nodeKey = isRoot ? '' : $node.data('key'),
                isMultiple = self.multiple && self.multiple != 0; // jshint ignore:line
            if ($node.hasClass('kv-disabled') || (isRoot && !isMultiple)) {
                return;
            }
            if ($node.hasClass('kv-selected')) {
                $node.removeClass('kv-selected');
                if (!isMultiple) {
                    self.$tree.find('li:not(.kv-disabled)').removeClass('kv-selected');
                    self.$element.val('');
                    self.raise('treeview.change', ['', '']);
                    self.raise('change');
                } else {
                    $node.find('li:not(.kv-disabled)').removeClass('kv-selected');
                }
                self.raise('treeview.unchecked', [nodeKey]);
            } else {
                if (!isMultiple) {
                    self.$tree.find('li:not(.kv-disabled)').removeClass('kv-selected');
                    self.$element.val(nodeKey);
                    var desc = $node.find('>.kv-tree-list .kv-node-label').text();
                    self.raise('treeview.change', [nodeKey, desc]);
                    self.raise('change');
                } else {
                    addCss($node.find('li:not(.kv-disabled)'), 'kv-selected');
                }
                addCss($node, 'kv-selected');
                self.raise('treeview.checked', [nodeKey]);
            }
            if (isMultiple) {
                self.setSelected();
            }
        },
        clear: function () {
            var self = this;
            self.$treeContainer.removeClass('kv-loading-search');
            self.$tree.find('.kv-node-label').removeClass('kv-highlight');
        },
        parseCache: function () {
            var self = this;
            if (!self.enableCache) {
                return false;
            }
            $.ajaxPrefilter(function (options, originalOptions) {
                if (options.cache) {
                    var beforeSend = originalOptions.beforeSend || $.noop,
                        success = originalOptions.success || $.noop,
                        url = originalOptions.url;
                    //remove jQuery cache as we have our own kvTreeCache
                    options.cache = false;
                    options.beforeSend = function () {
                        beforeSend();
                        if (kvTreeCache.exist(url)) {
                            success(kvTreeCache.get(url));
                            return false;
                        }
                        return true;
                    };
                    options.success = function (data) {
                        kvTreeCache.set(url, data, success);
                    };
                }
            });
        },
        listen: function () {
            var self = this;
            // node toggle actions
            self.$tree.find('.kv-node-toggle').each(function () {
                $(this).on('click', function () {
                    self.toggle($(this));
                });
            });
            // node checkbox actions
            self.$tree.find('.kv-node-checkbox:not(.kv-disabled)').each(function () {
                $(this).on('click', function () {
                    self.check($(this));
                });
            });
            // node toggle all actions
            self.$treeContainer.find('.kv-root-node-toggle').on('click', function () {
                var $node = $(this), $root = $node.closest('.kv-tree-container');
                if ($root.hasClass('kv-collapsed')) {
                    self.toggleAll('expand', true);
                } else {
                    self.toggleAll('collapse', true);
                }
            });
            // node checkbox all actions
            self.$treeContainer.find('.kv-root-node-checkbox').on('click', function () {
                self.check(true);
            });
            // search
            self.$search.on('keyup', function () {
                var filter = $(this).val();
                self.clear();
                if (filter.length === 0) {
                    return;
                }
                addCss(self.$treeContainer, 'kv-loading-search');
                delay(function () {
                    self.toggleAll('collapse', false);
                    filter = escapeRegExp(filter);
                    self.$tree.find('.kv-node-label').each(function () {
                        var $label = $(this), text = $label.text();
                        var pos = text.search(new RegExp(filter, "i"));
                        if (pos < 0) {
                            $label.removeClass('kv-highlight');
                        } else {
                            addCss($label, 'kv-highlight');
                            self.$tree.find('li.kv-parent').each(function () {
                                var $node = $(this);
                                if ($node.has($label).length > 0) {
                                    $node.removeClass('kv-collapsed');
                                }
                            });
                        }
                    });
                    self.$treeContainer.removeClass('kv-loading-search');
                    self.raise('treeview.search');
                }, 1500);
            });
            // search clear
            self.$clear.on('click', function () {
                self.$search.val('');
                self.clear();
            });
            // select node
            self.$tree.find('.kv-node-detail').each(function () {
                $(this).on('click', function () {
                    var $el = $(this), $node = $el.closest('li'),
                        key = $node.data('key');
                    if (self.$tree.hasClass('kv-tree-input-widget')) {
                        $el.removeClass('kv-focussed');
                        self.check($node);
                        return;
                    }
                    if ($el.hasClass('kv-focussed')) {
                        return;
                    }
                    self.select(key);
                    self.removeAlert();
                    self.raise('treeview.select', [key]);
                });
            });
            // create node
            self.$toolbar.find('.kv-' + self.btns.create).on('click', function () {
                self.create();
            });
            // create root
            self.$toolbar.find('.kv-' + self.btns.createR).on('click', function () {
                self.createRoot();
            });
            // remove node
            self.$toolbar.find('.kv-' + self.btns.trash).on('click', function () {
                self.remove();
            });
            // move node up
            self.$toolbar.find('.kv-' + self.btns.moveU).on('click', function () {
                self.move('u');
            });
            // move node down
            self.$toolbar.find('.kv-' + self.btns.moveD).on('click', function () {
                self.move('d');
            });
            // move node left
            self.$toolbar.find('.kv-' + self.btns.moveL).on('click', function () {
                self.move('l');
            });
            // move node right
            self.$toolbar.find('.kv-' + self.btns.moveR).on('click', function () {
                self.move('r');
            });
            self.$detail.find('.alert').each(function () {
                var $alert = $(this);
                if (!$alert.hasClass('hide')) {
                    $alert.hide().fadeIn(1500);
                    self.trigAlert($alert);
                }
            });
        },
        expandAll: function () {
            this.toggleAll('expand');
        },
        collapseAll: function () {
            this.toggleAll('collapse');
        },
        checkAll: function () {
            var self = this;
            self.$tree.removeClass('kv-selected');
            self.check(true);
        },
        uncheckAll: function () {
            var self = this;
            addCss(self.$tree, 'kv-selected');
            self.check(true);
        },
        checkNode: function (key) {
            var self = this, $node = self.$tree.find('li[data-key="' + key + '"]');
            if ($node.length) {
                $node.removeClass('kv-selected');
                self.check($node);
            }
        },
        uncheckNode: function (key) {
            var self = this, $node = self.$tree.find('li[data-key="' + key + '"]');
            if ($node.length) {
                addCss($node, 'kv-selected');
                self.check($node);
            }
        }
    };
    $.fn.treeview = function (option) {
        var args = Array.apply(null, arguments), $this, data, options;
        args.shift();
        return this.each(function () {
            $this = $(this);
            data = $this.data('treeview');
            options = typeof option === 'object' && option;
            if (!data) {
                data = new TreeView(this, $.extend({}, $.fn.treeview.defaults, options, $(this).data()));
                $this.data('treeview', data);
            }
            if (typeof option === 'string') {
                data[option].apply(data, args);
            }
        });
    };
    $.fn.treeview.defaults = {
        btns: {},
        treeId: '',
        detailId: '',
        toolbarId: '',
        wrapperId: '',
        showTooltips: true,
        alertFadeDuration: 1000,
        cacheTimeout: 300000,
        showInactive: false,
        actions: {
            manage: '',
            move: '',
            delete: ''
        },
        messages: {
            emptyNode: '',
            nodeDisabled: '',
            invalidCreateNode: '',
            removeNode: '',
            nodeRemoved: '',
            emptyNodeRemoved: '',
            nodeNewMove: '',
            nodeTop: '',
            nodeBottom: '',
            nodeLeft: '',
            nodeRight: ''
        },
        breadcrumbs: {}
    };
    $.fn.treeview.Constructor = TreeView;
})(window.jQuery);