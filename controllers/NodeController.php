<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015
 * @package yii2-tree-manager
 * @version 1.0.0
 */

namespace kartik\tree\controllers;

use Yii;
use yii\base\InvalidCallException;
use yii\helpers\Json;
use yii\web\View;
use yii\base\Event;
use kartik\tree\TreeView;

class NodeController extends \yii\web\Controller
{
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
     * View, Create or Update a tree node
     */
    public function actionManage()
    {
        static::checkValidRequest();
        $parentKey = null;
        extract($_POST);
        $node = ($id == null) ? (new $modelClass) : $modelClass::findOne($id);
        $module = TreeView::module();
        $params = $module->treeStructure + $module->dataStructure + [
                'node' => $node,
                'parentKey' => $parentKey,
                'action' => $formAction,
                'modelClass' => $modelClass,
                'currUrl' => $currUrl,
                'isAdmin' => $isAdmin,
                'iconsList' => $iconsList,
                'softDelete' => $softDelete,
                'showFormButtons' => $showFormButtons,
                'nodeView' => $nodeView,
                'nodeAddlViews' => $nodeAddlViews
            ];
        Event::on(View::className(), View::EVENT_AFTER_RENDER, function ($e) use ($module) {
            foreach ($module->unsetAjaxBundles as $bundle) {
                unset($e->sender->assetBundles[$bundle]);
            }
        });
        return Json::encode([
                'out' => $this->renderAjax($nodeView, ['params' => $params]),
                'status' => 'success'
            ]);
    }

    /**
     * Saves a node once form is submitted
     */
    public function actionSave()
    {
        static::checkValidRequest(!isset($_POST['treeNodeModify']));
        extract($_POST);
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
        Yii::$app->session->set('kvNodeId', $node->id);
        if ($treeNodeModify) {
            if ($parentKey == 'root') {
                $node->makeRoot();
            } else {
                $parent = $modelClass::findOne($parentKey);
            }
            $node->appendTo($parent);
        }
        $success = false;
        if ($node->save()) {
            if ($node->active) {
                $success = $node->activateNode();
            } else {
                $success = $node->removeNode($softDelete);
            }
        } else {
            $errorMsg = '<ul style="padding:0"><li>' . implode('</li><li>', $node->getFirstErrors()) . '</li></ul>';
        }
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
        static::checkValidRequest();
        extract($_POST);
        $node = $class::findOne($id);
        $success = $node->removeNode($softDelete);
        if ($success) {
            return Json::encode([
                'out' => Yii::t('kvtree', 'The node was removed successfully.'),
                'status' => 'success'
            ]);
        } else {
            return Json::encode([
                'out' => Yii::t('kvtree', 'Error while removing the node. Please try again later.'),
                'status' => 'error'
            ]);
        }
    }

    /**
     * Move a tree node
     */
    public function actionMove()
    {
        static::checkValidRequest();
        extract($_POST);
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
                    $nodeFrom->insertAfter($nodeTo);
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
            return Json::encode(['out' => Yii::t('kvtree', 'The node was moved successfully.'), 'status' => 'success']);
        } else {
            return Json::encode(['out' => $error, 'status' => 'error']);
        }
    }
}