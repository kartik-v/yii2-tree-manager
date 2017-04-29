Change Log: `yii2-tree-manager`
===============================

## Version 1.0.8

**Date:** 29-Apr-2017

- Chronological ordering of issues for change log.
- (enh #167): Add `alwaysDisabled` setting within `toolbar` buttons config to force disable the button (which will never become enabled).
- (enh #165): Implement tap behavior for TreeViewInput for touch based devices.
- (enh #164): Enhance search functionality and search results styling.
    - Add ability to highlight search terms within shortlisted search results
    - Ability to override styling of the search terms with CSS.
    - New boolean property `hideUnmatchedSearchItems` (defaults to true) that will intelligently hide nodes/node groups that do not have any matching nodes.
    - More faster tree node searching
- (enh #162): Update Italian Translations.
- (enh #161): Update Italian Translations.
- (bug #160): Correct key attribute usage in `TreeTrait::removeNode`.

## Version 1.0.7

**Date:** 15-Jan-2017

- (enh #158): Fire `treeview.selected` after node detail form is rendered.
- Code formatting updates.
- (bug #157): Validate request signatures correctly for invalid initial displayValue.
- (enh #156): Auto node selection and session validation enhancements.
- (enh #151): Root node creation enhancements.
- (enh #150): Modify `NodeController` actions security for console apps.
- (bug #148): Parse icons list array correctly in `NodeController::checkSignature`.
- (enh #146, #145): Enable model using TreeView behavior to run within yii console app.
- (enh #144): Add dependency for `kartik-v/yii2-dialog` in composer.
- (bug #139): New boolean property `cascadeSelectChildren` to control child nodes selection when parent node is selected.

## Version 1.0.6

**Date:** 16-Dec-2016

- (enh #142): Implement Krajee Dialog for all alerts and confirmation.
- (enh #138): Add Finnish translations.
- Update message config to include all default standard translation files.
- Add github contribution and issue/PR logging templates.
- (enh #143): Enhance security for NodeController actions using a stateless signature to prevent data tampering:
    - New property `Module::treeEncryptSalt` available to generate a stateless hashed signature.
    - If `treeEncryptSalt` is not set, it will be randomly generated and stored in a session variable.
    - Tree configuration settings will be signed and the same data via POST will be cross checked using `yii\base\Security::hashData` and `yii\base\Security::validateData`.
- (enh #137): Add Dutch translations.
- (enh #129): More correct defaulting of CSS classes.
- (bug #128): Correct asset dependency.
- (bug #127): Fix migration namespace.
- (enh #122): Add Estonian translations.
- (enh #120): Add Persian translations.
- (enh #119): Add db migrations functionality.
- (enh #118): Allow configuring order of toolbar buttons.
- (enh #113): Option to change node label.
- (enh #112): Add French translations.
- (enh #105): Generate unique `nodeSelected` session identifier for every TreeView widget instance.
- (bug #103, #104): Correct init of options and registration of assets in TreeViewInput.
- (enh #100): Add composer branch alias to allow getting latest `dev-master` updates.
- (enh #99): Add Chinese translations
- (enh #98): Add new bool property `TreeViewInput::autoCloseOnSelect`.

## Version 1.0.5

**Date:** 28-Dec-2015

- (enh #94): Enhancements and fixes to `movable` node validations.
- (enh #92): Ability to control auto loading of bootstrap plugin assets.

## Version 1.0.4

**Date:** 13-Dec-2015

- (enh #90): New ajax completion jQuery events for `move`, `remove`, and `select`.
- (enh #89): Enhance ability to display and delete inactive nodes for non admin.
- (enh #88): Correct validation for readonly and disabled nodes.
- (enh #86): CSS Styling Enhancements.
- (enh #85): Cleanup redundant code and optimize code.
- (enh #83): Breadcrumbs functionality and styling enhancements.
- (enh #82): Change anonymous Tree behavior to a named one (`tree`).
- (enh #80): Enhance boolean variable parsing in HTML5 data attributes.
- (enh #71): Add Russian translations.
- (enh #69): Add Italian translations.
- (enh #66): Add Ukranian translations.
- (enh #63): Cache active state before save.
- (bug #59): Maintain consistency by using `keyAttribute` to parse node key.
- (enh #58): Add Polish translations.
- (enh #57): Add Indonesian translations.

## Version 1.0.3

**Date:** 22-Jun-2015

- (enh #52): Better exception handling and translations.
- (enh #51): Move properties from `TreeTrait` to `Tree` model.
- (bug #47): Ensure single select for TreeViewInput when `multiple` is `false`.
- (enh #46): Better styling and alignment of tree hierarchy lines across browsers.
- (enh #45): Refactor code for extensibility - implement `Tree` model to use a `TreeTrait`.
- (enh #44): Enhancing tree container styles for smaller device screen sizes.
- (enh #43): Code style and format fixes.
- (enh #42): Close tree input widget after you've selected a node.
- (bug #41): Cleanup unused variables.
- (enh #40): Better dynamic styling of parent node when all children nodes are removed.
- (enh #39): Expose ajax settings from `beforeSend` ajax request in events.
- (enh #38): Validate `formOptions` correctly for new root creation.

## Version 1.0.2

**Date:** 22-May-2015

- (enh #36): Add German translations.
- (enh #35): Initialize variables prior to extraction.
- (enh #34): Better ability to disable `treeAttribute` by setting it to `false`.

## Version 1.0.1

**Date:** 11-May-2015

- (bug #33): Fix minor bug in jquery plugin's button actions code.
- (enh #32): Better styling for inactive and invisible nodes.
- (enh #30): New property `TreeView::nodeFormOptions` to control HTML attributes for form.
- (enh #29): Reinitialize yii active form/jquery plugins after ajax success more correctly.
- (enh #28): Enhance alert fade styling for deletions.
- (enh #27): Implement root node deletion.
- (enh #26): Special validation for `move left` when the parent is root.
- (enh #25): New property `TreeView::showIDAttribute` to hide/show key attribute in form.
- (enh #21): Purify node icons feature in `Tree` model.
- (enh #22): Create jQuery helper methods for treeview input and toggle actions.
- (enh #20): Encode node names feature in `Tree` model.
- (enh #19): Add Russian translations and fix minor bugs in kv-tree-input.js.
- (enh #18): Add new plugin events and enhance plugin event parameters.

## Version 1.0.0

**Date:** 21-Apr-2015

- (enh #15): Missing namespace for `Model` class in `TreeViewInput`.
- (enh #13): Use Closure methods for rendering `nodeAddlViews`.
- (enh #10): Avoid duplicate URL encoding.
- (enh #9): Cast true & false variables in `$_POST` to boolean.
- (enh #7): Client script enhancements.
- (enh #6): Selectively disable parent only flags for leaf nodes.
- (enh #5): Different default parent icons for collapsed and opened node states.
- (enh #4): Error trapping enhancements to `Tree::activateNode` and `Tree::removeNode`
- (enh #3): Set dependencies for Asset Bundles.
- (bug #2): Empty node validation on tree init.
- Initial release