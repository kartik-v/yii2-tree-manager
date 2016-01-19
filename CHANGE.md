Change Log: `yii2-tree-manager`
===============================

## Version 1.0.6

**Date:** 19-Jan-2016

- (enh #98): Add new bool property `TreeViewInput::autoCloseOnSelect`.
- (enh #99): Add Chinese Translations
- (enh #100): Add composer branch alias to allow getting latest `dev-master` updates.

## Version 1.0.5

**Date:** 28-Dec-2015

- (enh #92): Ability to control auto loading of bootstrap plugin assets.
- (enh #94): Enhancements and fixes to `movable` node validations.

## Version 1.0.4

**Date:** 13-Dec-2015

- (enh #57): Add Indonesian translations.
- (enh #58): Add Polish translations.
- (bug #59): Maintain consistency by using `keyAttribute` to parse node key.
- (enh #63): Cache active state before save.
- (enh #66): Add Ukranian translations.
- (enh #69): Add Italian translations.
- (enh #71): Add Russian translations.
- (enh #80): Enhance boolean variable parsing in HTML5 data attributes.
- (enh #82): Change anonymous Tree behavior to a named one (`tree`).
- (enh #83): Breadcrumbs functionality and styling enhancements.
- (enh #85): Cleanup redundant code and optimize code.
- (enh #86): CSS Styling Enhancements.
- (enh #88): Correct validation for readonly and disabled nodes.
- (enh #89): Enhance ability to display and delete inactive nodes for non admin.
- (enh #90): New ajax completion jQuery events for `move`, `remove`, and `select`.

## Version 1.0.3

**Date:** 22-Jun-2015

- (enh #38): Validate `formOptions` correctly for new root creation.
- (enh #39): Expose ajax settings from `beforeSend` ajax request in events.
- (enh #40): Better dynamic styling of parent node when all children nodes are removed.
- (bug #41): Cleanup unused variables.
- (enh #42): Close tree input widget after you've selected a node.
- (enh #43): Code style and format fixes.
- (enh #44): Enhancing tree container styles for smaller device screen sizes.
- (enh #45): Refactor code for extensibility - implement `Tree` model to use a `TreeTrait`.
- (enh #46): Better styling and alignment of tree hierarchy lines across browsers.
- (bug #47): Ensure single select for TreeViewInput when `multiple` is `false`.
- (enh #51): Move properties from `TreeTrait` to `Tree` model.
- (enh #52): Better exception handling and translations.

## Version 1.0.2

**Date:** 22-May-2015

- (enh #34): Better ability to disable `treeAttribute` by setting it to `false`.
- (enh #35): Initialize variables prior to extraction.
- (enh #36): Add German translations.

## Version 1.0.1

**Date:** 11-May-2015

- (enh #18): Add new plugin events and enhance plugin event parameters.
- (enh #19): Add Russian translations and fix minor bugs in kv-tree-input.js.
- (enh #20): Encode node names feature in `Tree` model.
- (enh #21): Purify node icons feature in `Tree` model.
- (enh #22): Create jQuery helper methods for treeview input and toggle actions.
- (enh #25): New property `TreeView::showIDAttribute` to hide/show key attribute in form.
- (enh #26): Special validation for `move left` when the parent is root.
- (enh #27): Implement root node deletion.
- (enh #28): Enhance alert fade styling for deletions.
- (enh #29): Reinitialize yii active form/jquery plugins after ajax success more correctly.
- (enh #30): New property `TreeView::nodeFormOptions` to control HTML attributes for form.
- (enh #32): Better styling for inactive and invisible nodes.
- (bug #33): Fix minor bug in jquery plugin's button actions code.

## Version 1.0.0

**Date:** 21-Apr-2015

- Initial release
- (bug #2): Empty node validation on tree init.
- (enh #3): Set dependencies for Asset Bundles.
- (enh #4): Error trapping enhancements to `Tree::activateNode` and `Tree::removeNode`
- (enh #5): Different default parent icons for collapsed and opened node states.
- (enh #6): Selectively disable parent only flags for leaf nodes.
- (enh #7): Client script enhancements.
- (enh #9): Cast true & false variables in `$_POST` to boolean.
- (enh #10): Avoid duplicate URL encoding.
- (enh #13): Use Closure methods for rendering `nodeAddlViews`.
- (enh #15): Missing namespace for `Model` class in `TreeViewInput`.