<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2016
 * @package   yii2-tree-manager
 * @version   1.0.6
 */

namespace kartik\tree\controllers;

use Yii;
use Closure;
use Exception;
use yii\db\Exception as DbException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;
use yii\base\InvalidCallException;
use yii\web\View;
use yii\base\Event;
use kartik\tree\TreeView;
use kartik\tree\models\Tree;

class NodeController extends Controller
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
     * Saves a node once form is submitted
     */
    public function actionSave()
    {
        $post = Yii::$app->request->post();
        static::checkValidRequest(!isset($post['treeNodeModify']));
        $treeNodeModify = $parentKey = $currUrl = null;
        $modelClass = '\kartik\tree\models\Tree';
        extract(static::getPostData());
        $module = TreeView::module();
        $keyAttr = $module->dataStructure['keyAttribute'];
        $session = Yii::$app->session;
        /**
         * @var Tree $modelClass
         * @var Tree $node
         * @var Tree $parent
         */
        if ($treeNodeModify) {
            $node = new $modelClass;
            $successMsg = Yii::t('kvtree', 'The node was successfully created.');
            $errorMsg = Yii::t('kvtree', 'Error while creating the node. Please try again later.');
        } else {
            $tag = explode("\\", $modelClass);
            $tag = array_pop($tag);
            $id = $post[$tag][$keyAttr];
            $node = $modelClass::findOne($id);
            $successMsg = Yii::t('kvtree', 'Saved the node details successfully.');
            $errorMsg = Yii::t('kvtree', 'Error while saving the node. Please try again later.');
        }
        $node->activeOrig = $node->active;
        $isNewRecord = $node->isNewRecord;
        $node->load($post);
        if ($treeNodeModify) {
            if ($parentKey == 'root') {
                $node->makeRoot();
            } else {
                $parent = $modelClass::findOne($parentKey);
                $node->appendTo($parent);
            }
        }
        $errors = $success = false;
        if ($node->save()) {
            // check if active status was changed
            if (!$isNewRecord && $node->activeOrig != $node->active) {
                if ($node->active) {
                    $success = $node->activateNode(false);
                    $errors = $node->nodeActivationErrors;
                } else {
                    $success = $node->removeNode(true, false); // only deactivate the node(s)
                    $errors = $node->nodeRemovalErrors;
                }
            } else {
                $success = true;
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

        $session->set(ArrayHelper::getValue($post, 'nodeSelected', 'kvNodeId'), $node->$keyAttr);
        if ($success) {
            $session->setFlash('success', $successMsg);
        } else {
            $session->setFlash('error', $errorMsg);
        }
        return $this->redirect($currUrl);
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
        $parentKey = $action = null;
        $modelClass = '\kartik\tree\models\Tree';
        $isAdmin = $softDelete = $showFormButtons = $showIDAttribute = false;
        $currUrl = $nodeView = $formOptions = $formAction = $breadCrumbs = $nodeSelected = '';
        $iconsList = $nodeAddlViews = [];
        extract(static::getPostData());
        /**
         * @var Tree $modelClass
         * @var Tree $node
         */
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
                'formOptions' => empty($formOptions) ? [] : $formOptions,
                'modelClass' => $modelClass,
                'currUrl' => $currUrl,
                'isAdmin' => $isAdmin,
                'iconsList' => $iconsList,
                'softDelete' => $softDelete,
                'showFormButtons' => $showFormButtons,
                'showIDAttribute' => $showIDAttribute,
                'nodeView' => $nodeView,
                'nodeAddlViews' => $nodeAddlViews,
                'nodeSelected' => $nodeSelected,
                'breadcrumbs' => empty($breadcrumbs) ? [] :$breadcrumbs,
            ];
        if (!empty($module->unsetAjaxBundles)) {
            Event::on(View::className(), View::EVENT_AFTER_RENDER, function ($e) use ($module) {
                foreach ($module->unsetAjaxBundles as $bundle) {
                    unset($e->sender->assetBundles[$bundle]);
                }
            });
        }
        $callback = function () use ($nodeView, $params) {
            return $this->renderAjax($nodeView, ['params' => $params]);
        };
        return self::process(
            $callback,
            Yii::t('kvtree', 'Error while viewing the node. Please try again later.'),
            null
        );
    }

    /**
     * Remove a tree node
     */
    public function actionRemove()
    {
        /**
         * @var Tree $class
         * @var Tree $node
         */
        Yii::$app->response->format = Response::FORMAT_JSON;
        static::checkValidRequest();
        $id = null;
        $class = '\kartik\tree\models\Tree';
        $softDelete = false;
        extract(static::getPostData());
        $node = $class::findOne($id);
        $callback = function () use ($node, $softDelete) {
            return $node->removeNode($softDelete);
        };
        return self::process(
            $callback,
            Yii::t('kvtree', 'Error removing the node. Please try again later.'),
            Yii::t('kvtree', 'The node was removed successfully.')
        );
    }

    /**
     * Move a tree node
     */
    public function actionMove()
    {
        /**
         * @var Tree $class
         * @var Tree $nodeFrom
         * @var Tree $nodeTo
         */
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
        $isMovable = $nodeFrom->isMovable($dir);
        $errorMsg = $isMovable ? Yii::t('kvtree', 'Error while moving the node. Please try again later.') :
            Yii::t('kvtree', 'The selected node cannot be moved.');
        $callback = function () use ($dir, $nodeFrom, $nodeTo, $allowNewRoots, $isMovable) {
            if (!empty($nodeFrom) && !empty($nodeTo)) {
                if (!$isMovable) {
                    return false;
                }
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
                return $nodeFrom->save();
            }
            return true;
        };
        return self::process($callback, $errorMsg, Yii::t('kvtree', 'The node was moved successfully.'));
    }

    /**
     * Processes a code block and catches exceptions
     *
     * @param Closure $callback   the function to execute (this returns a valid `$success`)
     * @param string  $msgError   the default error message to return
     * @param string  $msgSuccess the default success error message to return
     *
     * @return array outcome of the code consisting of following keys:
     * - 'out': string, the output content
     * - 'status': string, success or error
     */
    public static function process($callback, $msgError, $msgSuccess)
    {
        $error = $msgError;
        $success = false;
        try {
            $success = call_user_func($callback);
        } catch (DbException $e) {
            $error = $e->getMessage();
        } catch (NotSupportedException $e) {
            $error = $e->getMessage();
        } catch (InvalidParamException $e) {
            $error = $e->getMessage();
        } catch (InvalidConfigException $e) {
            $error = $e->getMessage();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        if ($success !== false) {
            $out = $msgSuccess === null ? $success : $msgSuccess;
            return ['out' => $out, 'status' => 'success'];
        } else {
            return ['out' => $error, 'status' => 'error'];
        }
    }
}
