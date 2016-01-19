/*!
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package yii2-tree-manager
 * @version 1.0.6
 *
 * Tree View Input Widget Management Script
 *
 * Author: Kartik Visweswaran
 * Copyright: 2015 - 2016, Kartik Visweswaran, Krajee.com
 * For more JQuery plugins visit http://plugins.krajee.com
 * For more Yii related demos visit http://demos.krajee.com
 */
(function ($) {
    "use strict";

    var isEmpty, TreeInput;

    isEmpty = function (value, trim) {
        return value === null || value === undefined || value.length === 0 || (trim && $.trim(value) === '');
    };

    TreeInput = function (element, options) {
        var self = this;
        self.$element = $(element);
        self.init(options);
        self.listen();
    };

    TreeInput.prototype = {
        constructor: TreeInput,
        init: function (options) {
            var self = this, i, $node, desc, key, keys, list = [];
            $.each(options, function (key, data) {
                self[key] = data;
            });
            self.$tree = $('#' + self.treeId);
            self.$input = $('#' + self.inputId);
            self.$dropdown = $('#' + self.dropdownId);
            if (isEmpty(self.placeholder)) {
                self.placeholder = '&nbsp;';
            }
            if (isEmpty(self.value)) {
                self.$input.html(self.caret + self.placeholder);
                return;
            }
            keys = self.value.toString().split(',');
            for (i = 0; i < keys.length; i++) {
                $node = self.$tree.find('li[data-key="' + keys[i] + '"]');
                desc = $node.find('>.kv-tree-list .kv-node-label').text();
                list.push(desc);
            }
            self.setInput(list);
        },
        setInput: function (list) {
            var self = this, out = '';
            self.$input.removeClass('has-multi');
            if (isEmpty(list) || isEmpty(list[0])) {
                out = self.placeholder;
            } else {
                if (list.length === 1) {
                    out = list[0];
                } else {
                    out = '<ul class="kv-tree-input-values"><li>' + list.join('</li><li>') +
                        '</li></ul><div class="clearfix"></div>';
                    self.$input.addClass('has-multi');
                }
            }
            self.$input.html(self.caret + out);

        },
        listen: function () {
            var self = this;
            self.$dropdown.on('click', function (e) {
                e.stopPropagation();
            });
            self.$element.on('treeview.change', function (event, keys, desc) {
                self.setInput(desc.split(','));
                if (self.autoCloseOnSelect) {
                    self.$input.closest('.kv-tree-dropdown-container').removeClass('open');
                }
            });
        }
    };

    $.fn.treeinput = function (option) {
        var args = Array.apply(null, arguments), $this, data, options;
        args.shift();
        return this.each(function () {
            $this = $(this);
            data = $this.data('treeinput');
            options = typeof option === 'object' && option;
            if (!data) {
                data = new TreeInput(this, $.extend({}, $.fn.treeinput.defaults, options, $(this).data()));
                $this.data('treeinput', data);
            }
            if (typeof option === 'string') {
                data[option].apply(data, args);
            }
        });
    };

    $.fn.treeinput.defaults = {
        treeId: '',
        inputId: '',
        dropdownId: '',
        placeholder: '',
        value: '',
        caret: '',
        autoCloseOnSelect: true
    };

    $.fn.treeinput.Constructor = TreeInput;

})(window.jQuery);