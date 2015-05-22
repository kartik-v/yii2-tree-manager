<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2015
 * @package yii2-tree-manager
 * @version 1.0.2
 */

namespace kartik\tree\controllers;

use Yii;
use yii\helpers\Json;
use yii\web\Response;
use yii\base\InvalidCallException;
use yii\web\View;
use yii\base\Event;
use kartik\tree\TreeView;

class NodeController extends \yii\web\Controller
{
    /**
     * @var array the list of keys in $_POST which must be cast as boolean
     */
    public static $boolKeys = [
        'isAdmin',
        'softDelete',
        'showFormButtons',
        'showIDAttribute',
        'multiple',
        'treeNodeModify',
        'allowNewRoots'
    ];

    /**
     * Gets the data from $_POST after parsing boolean values
     *
     * @return array
     */
    protected static function getPostData()
    {
        if (empty($_POST)) {
            return [];
        }
        $out = [];
        foreach ($_POST as $key => $value) {
            $out[$key] = in_array($key, static::$boolKeys) ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : $value;
        }
        return $out;
    }

    /**
     * Checks if request is valid and throws exception if invalid condition is true
     *
     * @param bool $isInvalid whether the request is invalid
     *
     * @throws InvalidCallException
     *
     * @return void
     */
    protected static function checkValidRequest($isInvalid = null)
    {
        if ($isInvalid === null) {
            $isInvalid = !Yii::$app->request->isAjax || !Yii::$app->request->isPost;
        }
        if ($isInvalid) {
            throw new InvalidCallException(Yii::t('kvtree', 'This operation is not allowed.'));
        }
    }

    /**
     * View, create, or update a tree node via ajax
     *
     * @return string json encoded response
     */
    public function actionManage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        static::checkValidRequest();
        $parentKey = null;
        $action = null;
        $formOptions = [];
        $modelClass = '\kartik\tree\models\Tree';
        $currUrl = '';
        $isAdmin = false;
        $iconsList = [];
        $softDelete = false;
        $showFormButtons = false;
        $showIDAttribute = false;
        $nodeView = '';
        $nodeAddlViews = [];
        extract(static::getPostData());
        if (!isset($id) || empty($id)) {
            $node = new $modelClass;
            $node->initDefaults();
        } else {
            $node = $modelClass::findOne($id);
        }
        $module = TreeView::module();
        $params = $module->treeStructure + $module->dataStructure + [
                'node' => $node,
                'parentKey' => $parentKey,
                'action' => $formAction,
                'formOptions' => Json::decode($formOptions),
                'modelClass' => $modelClass,
                'currUrl' => $currUrl,
                'isAdmin' => $isAdmin,
                'iconsList' => $iconsList,
                'softDelete' => $softDelete,
                'showFormButtons' => $showFormButtons,
                'showIDAttribute' => $showIDAttribute,
                'nodeView' => $nodeView,
                'nodeAddlViews' => $nodeAddlViews
            ];
        if (!empty($module->unsetAjaxBundles)) {
            Event::on(View::className(), View::EVENT_AFTER_RENDER, function ($e) use ($module) {
                foreach ($module->unsetAjaxBundles as $bundle) {
                    unset($e->sender->assetBundles[$bundle]);
                }
            });
        }
        return [
            'out' => $this->renderAjax($nodeView, ['params' => $params]),
            'status' => 'success'
        ];
    }

    /**
     * Saves a node once form is submitted
     */
    public function actionSave()
    {
        static::checkValidRequest(!isset($_POST['treeNodeModify']));
        $treeNodeModify = null;
        $parentKey = null;
        $modelClass = '\kartik\tree\models\Tree';
        extract(static::getPostData());
        $module = TreeView::module();
        /**
         * @var \kartik\tree\models\Tree $node
         */
        if ($treeNodeModify) {
            $node = new $modelClass;
            $successMsg = Yii::t('kvtree', 'The node was successfully created.');
            $errorMsg = Yii::t('kvtree', 'Error while creating the node. Try again later.');
        } else {
            $idAttr = $module->dataStructure['keyAttribute'];
            $tag = explode("\\", $modelClass);
            $tag = array_pop($tag);
            $id = $_POST[$tag][$idAttr];
            $node = $modelClass::findOne($id);
            $successMsg = Yii::t('kvtree', 'Saved the node details successfully.');
            $errorMsg = Yii::t('kvtree', 'Error while saving the node. Try again later.');
        }
        $node->load($_POST);
        if ($treeNodeModify) {
            if ($parentKey == 'root') {
                $node->makeRoot();
            } else {
                $parent = $modelClass::findOne($parentKey);
                $node->appendTo($parent);
            }
        }
        $success = false;
        if ($node->save()) {
            if ($node->active) {
                $success = $node->activateNode(false);
                $errors = $node->nodeActivationErrors;
            } else {
                $success = $node->removeNode($softDelete, false);
                $errors = $node->nodeRemovalErrors;
            }
            if (!empty($errors)) {
                $success = false;
                $errorMsg = "<ul style='padding:0'>\n";
                foreach ($errors as $err) {
                    $errorMsg .= "<li>" . Yii::t('kvtree', "Node # {id} - '{name}': {error}", $err) . "</li>\n";
                }
                $errorMsg .= "</ul>";
            }
        } else {
            $errorMsg = '<ul style="margin:0"><li>' . implode('</li><li>', $node->getFirstErrors()) . '</li></ul>';
        }
        Yii::$app->session->set('kvNodeId', $node->id);
        if ($success) {
            Yii::$app->session->setFlash('success', $successMsg);
        } else {
            Yii::$app->session->setFlash('error', $errorMsg);
        }
        return $this->redirect($currUrl);
    }

    /**
     * Remove a tree node
     */
    public function actionRemove()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        static::checkValidRequest();
        $id = null;
        $class = '\kartik\tree\models\Tree';
        $softDelete = false;
        extract(static::getPostData());
        $node = $class::findOne($id);
        $success = $node->removeNode($softDelete);
        if ($success) {
            return [
                'out' => Yii::t('kvtree', 'The node was removed successfully.'),
                'status' => 'success'
            ];
        } else {
            return [
                'out' => Yii::t('kvtree', 'Error while removing the node. Please try again later.'),
                'status' => 'error'
            ];
        }
    }

    /**
     * Move a tree node
     */
    public function actionMove()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        static::checkValidRequest();
        $dir = null;
        $idFrom = null;
        $idTo = null;
        $class = '\kartik\tree\models\Tree';
        $allowNewRoots = false;
        extract(static::getPostData());
        $nodeFrom = $class::findOne($idFrom);
        $nodeTo = $class::findOne($idTo);
        $success = false;
        $error = Yii::t('kvtree', 'Error moving the node. Please try again later.');
        try {
            if (!empty($nodeFrom) && !empty($nodeTo)) {
                if ($dir == 'u') {
                    $nodeFrom->insertBefore($nodeTo);
                } elseif ($dir == 'd') {
                    $nodeFrom->insertAfter($nodeTo);
                } elseif ($dir == 'l') {
                    if ($nodeTo->isRoot() && $allowNewRoots) {
                        $nodeFrom->makeRoot();
                    } else {
                        $nodeFrom->insertAfter($nodeTo);
                    }
                } elseif ($dir == 'r') {
                    $nodeFrom->appendTo($nodeTo);
                }
                $success = $nodeFrom->save();
            }
        } catch (yii\db\Exception $e) {
            $error = $e->getMessage();
        } catch (yii\base\NotSupportedException $e) {
            $error = $e->getMessage();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        if ($success) {
            return ['out' => Yii::t('kvtree', 'The node was moved successfully.'), 'status' => 'success'];
        } else {
            return ['out' => $error, 'status' => 'error'];
        }
    }
}