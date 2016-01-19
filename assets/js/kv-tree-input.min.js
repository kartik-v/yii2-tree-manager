/*!
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package yii2-tree-manager
 * @version 1.0.6
 *
 * Tree View Input Widget Validation Module.
 *
 * Author: Kartik Visweswaran
 * Copyright: 2015 - 2016, Kartik Visweswaran, Krajee.com
 * For more JQuery plugins visit http://plugins.krajee.com
 * For more Yii related demos visit http://demos.krajee.com
 */!function(t){"use strict";var e,n;e=function(e,n){return null===e||void 0===e||0===e.length||n&&""===t.trim(e)},n=function(e,n){var i=this;i.$element=t(e),i.init(n),i.listen()},n.prototype={constructor:n,init:function(n){var i,r,l,o,u=this,a=[];if(t.each(n,function(t,e){u[t]=e}),u.$tree=t("#"+u.treeId),u.$input=t("#"+u.inputId),u.$dropdown=t("#"+u.dropdownId),e(u.placeholder)&&(u.placeholder="&nbsp;"),e(u.value))return void u.$input.html(u.caret+u.placeholder);for(o=u.value.toString().split(","),i=0;i<o.length;i++)r=u.$tree.find('li[data-key="'+o[i]+'"]'),l=r.find(">.kv-tree-list .kv-node-label").text(),a.push(l);u.setInput(a)},setInput:function(t){var n=this,i="";n.$input.removeClass("has-multi"),e(t)||e(t[0])?i=n.placeholder:1===t.length?i=t[0]:(i='<ul class="kv-tree-input-values"><li>'+t.join("</li><li>")+'</li></ul><div class="clearfix"></div>',n.$input.addClass("has-multi")),n.$input.html(n.caret+i)},listen:function(){var t=this;t.$dropdown.on("click",function(t){t.stopPropagation()}),t.$element.on("treeview.change",function(e,n,i){t.setInput(i.split(",")),t.autoCloseOnSelect&&t.$input.closest(".kv-tree-dropdown-container").removeClass("open")})}},t.fn.treeinput=function(e){var i,r,l,o=Array.apply(null,arguments);return o.shift(),this.each(function(){i=t(this),r=i.data("treeinput"),l="object"==typeof e&&e,r||(r=new n(this,t.extend({},t.fn.treeinput.defaults,l,t(this).data())),i.data("treeinput",r)),"string"==typeof e&&r[e].apply(r,o)})},t.fn.treeinput.defaults={treeId:"",inputId:"",dropdownId:"",placeholder:"",value:"",caret:"",autoCloseOnSelect:!0},t.fn.treeinput.Constructor=n}(window.jQuery);