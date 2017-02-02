<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2017
 * @package   yii2-tree-manager
 * @version   1.0.8
 */

namespace kartik\tree\controllers;

use Closure;
use Exception;
use kartik\tree\models\Tree;
use kartik\tree\TreeView;
use Yii;
use yii\base\ErrorException;
use yii\base\Event;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\console\Application;
use yii\db\Exception as DbException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\Response;
use yii\web\View;

/**
 * The `NodeController` class manages all the manipulation actions for each tree node. It includes security
 * validations to ensure the actions are accessible only via `ajax` or `post` requests. In addition, it includes
 * stateless signature token validation to cross check data is not tampered before the request is sent via POST.
 */
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
        'allowNewRoots',
    ];

    /**
     * Processes a code block and catches exceptions
     *
     * @param Closure $callback the function to execute (this returns a valid `$success`)
     * @param string $msgError the default error message to return
     * @param string $msgSuccess the default success error message to return
     *
     * @return array outcome of the code consisting of following keys:
     * - `out`: _string_, the output content
     * - `status`: _string_, success or error
     */
    public static function process($callback, $msgError, $msgSuccess)
    {
        $error = $msgError;
        try {
            $success = call_user_func($callback);
        } catch (DbException $e) {
            $success = false;
            $error = $e->getMessage();
        } catch (NotSupportedException $e) {
            $success = false;
            $error = $e->getMessage();
        } catch (InvalidParamException $e) {
            $success = false;
            $error = $e->getMessage();
        } catch (InvalidConfigException $e) {
            $success = false;
            $error = $e->getMessage();
        } catch (InvalidCallException $e) {
            $success = false;
            $error = $e->getMessage();
        } catch (Exception $e) {
            $success = false;
            $error = $e->getMessage();
        }
        if ($success !== false) {
            $out = $msgSuccess === null ? $success : $msgSuccess;
            return ['out' => $out, 'status' => 'success'];
        } else {
            return ['out' => $error, 'status' => 'error'];
        }
    }

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
     * @param boolean $isJsonResponse whether the action response is of JSON format
     * @param boolean $isInvalid whether the request is invalid
     *
     * @throws InvalidCallException
     */
    protected static function checkValidRequest($isJsonResponse = true, $isInvalid = null)
    {
        $app = Yii::$app;
        if ($isJsonResponse) {
            $app->response->format = Response::FORMAT_JSON;
        }
        if ($isInvalid === null) {
            $isInvalid = !$app->request->isAjax || !$app->request->isPost;
        }
        if ($isInvalid) {
            throw new InvalidCallException(Yii::t('kvtree', 'This operation is not allowed.'));
        }
    }

    /**
     * Checks signature of posted data for ensuring security against data tampering.
     *
     * @param string $action the controller action for which post data signature will be verified
     * @param array $data the posted data
     *
     * @throws InvalidCallException
     */
    protected static function checkSignature($action, $data = [])
    {
        if (Yii::$app instanceof Application) {
            return; // skip hash signature validation for console apps
        }
        $module = TreeView::module();
        $security = Yii::$app->security;
        $modelClass = '\kartik\tree\models\Tree';
        $validate = function ($act, $oldHash, $newHashData) use ($module, $security) {
            $salt = $module->treeEncryptSalt;
            $newHash = $security->hashData($newHashData, $salt);
            if ($security->validateData($oldHash, $salt) && $oldHash === $newHash) {
                return;
            }
            $params = YII_DEBUG ? '<pre>OLD HASH:<br>' . $oldHash . '<br>NEW HASH:<br>' . $newHash . '</pre>' : '';
            $message = Yii::t(
                'kvtree',
                '<h4>Operation Disallowed</h4><hr>Invalid request signature detected during tree data <b>{action}</b> action! Please refresh the page and retry.{params}',
                ['action' => $act, 'params' => $params]
            );
            throw new InvalidCallException($message);
        };
        switch ($action) {
            case 'save':
                $treeNodeModify = $currUrl = $treeSaveHash = null;
                extract($data);
                $dataToHash = !!$treeNodeModify . $currUrl . $modelClass;
                $validate($action, $treeSaveHash, $dataToHash);
                break;
            case 'manage':
                $treeManageHash = null;
                $isAdmin = $softDelete = $showFormButtons = $showIDAttribute = false;
                $currUrl = $nodeView = $formAction = $nodeSelected = '';
                $formOptions = $iconsList = $nodeAddlViews = $breadcrumbs = [];
                extract($data);
                $icons = is_array($iconsList) ? array_values($iconsList) : $iconsList;
                $dataToHash = $modelClass . !!$isAdmin . !!$softDelete . !!$showFormButtons .
                    !!$showIDAttribute . $currUrl . $nodeView . $nodeSelected . Json::encode($formOptions) .
                    Json::encode($nodeAddlViews) . Json::encode($icons) . Json::encode($breadcrumbs);
                $validate($action, $treeManageHash, $dataToHash);
                break;
            case 'remove':
                $treeRemoveHash = null;
                $softDelete = false;
                extract($data);
                $dataToHash = $modelClass . $softDelete;
                $validate($action, $treeRemoveHash, $dataToHash);
                break;
            case 'move':
                $treeMoveHash = $allowNewRoots = null;
                extract($data);
                $dataToHash = $modelClass . $allowNewRoots;
                $validate($action, $treeMoveHash, $dataToHash);
                break;
            default:
                break;
        }
    }

    /**
     * Saves a node once form is submitted
     */
    public function actionSave()
    {
        $post = Yii::$app->request->post();
        static::checkValidRequest(false, !isset($post['treeNodeModify']));
        $treeNodeModify = $parentKey = $currUrl = $treeSaveHash = null;
        $modelClass = '\kartik\tree\models\Tree';
        $data = static::getPostData();
        extract($data);
        $module = TreeView::module();
        $keyAttr = $module->dataStructure['keyAttribute'];
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
            if ($parentKey == TreeView::ROOT_KEY) {
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
        if (Yii::$app->has('session')) {
            $session = Yii::$app->session;
            $session->set(ArrayHelper::getValue($post, 'nodeSelected', 'kvNodeId'), $node->{$keyAttr});
            if ($success) {
                $session->setFlash('success', $successMsg);
            } else {
                $session->setFlash('error', $errorMsg);
            }
        } elseif (!$success) {
            throw new ErrorException("Error saving node!\n{$errorMsg}");
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
        static::checkValidRequest();
        $callback = function () {
            $parentKey = null;
            $modelClass = '\kartik\tree\models\Tree';
            $isAdmin = $softDelete = $showFormButtons = $showIDAttribute = $allowNewRoots = false;
            $currUrl = $nodeView = $formAction = $nodeSelected = '';
            $formOptions = $iconsList = $nodeAddlViews = $breadcrumbs = [];
            $data = static::getPostData();
            extract($data);
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
                    'allowNewRoots' => $allowNewRoots,
                    'nodeView' => $nodeView,
                    'nodeAddlViews' => $nodeAddlViews,
                    'nodeSelected' => $nodeSelected,
                    'breadcrumbs' => empty($breadcrumbs) ? [] : $breadcrumbs,
                    'noNodesMessage' => ''
                ];
            if (!empty($module->unsetAjaxBundles)) {
                Event::on(
                    View::className(), View::EVENT_AFTER_RENDER, function ($e) use ($module) {
                    foreach ($module->unsetAjaxBundles as $bundle) {
                        unset($e->sender->assetBundles[$bundle]);
                    }
                }
                );
            }
            static::checkSignature('manage', $data);
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
        static::checkValidRequest();
        $callback = function () {
            /**
             * @var Tree $modelClass
             * @var Tree $node
             */
            $id = null;
            $modelClass = '\kartik\tree\models\Tree';
            $softDelete = false;
            $data = static::getPostData();
            static::checkSignature('remove', $data);
            extract($data);
            $node = $modelClass::findOne($id);
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
         * @var Tree $modelClass
         * @var Tree $nodeFrom
         * @var Tree $nodeTo
         */
        static::checkValidRequest();
        $dir = $idFrom = $idTo = $treeMoveHash = null;
        $modelClass = '\kartik\tree\models\Tree';
        $allowNewRoots = false;
        $data = static::getPostData();
        extract($data);
        $nodeFrom = $modelClass::findOne($idFrom);
        $nodeTo = $modelClass::findOne($idTo);
        $isMovable = $nodeFrom->isMovable($dir);
        $errorMsg = $isMovable ? Yii::t('kvtree', 'Error while moving the node. Please try again later.') :
            Yii::t('kvtree', 'The selected node cannot be moved.');
        $callback = function () use ($dir, $nodeFrom, $nodeTo, $allowNewRoots, $treeMoveHash, $isMovable, $data) {
            if (!empty($nodeFrom) && !empty($nodeTo)) {
                static::checkSignature('move', $data);
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
}
