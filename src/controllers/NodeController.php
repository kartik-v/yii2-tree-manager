<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2018
 * @package   yii2-tree-manager
 * @version   1.0.9
 */

namespace kartik\tree\controllers;

use Closure;
use Exception;
use kartik\tree\Module;
use kartik\tree\models\Tree;
use kartik\tree\TreeView;
use kartik\tree\TreeSecurity;
use Yii;
use yii\base\ErrorException;
use yii\base\Event;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\Exception as DbException;
use yii\helpers\ArrayHelper;
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
        'showNameAttribute',
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
     * Saves a node once form is submitted
     */
    public function actionSave()
    {
        /**
         * @var Module $module
         * @var Tree $node
         * @var Tree $parent
         * @var \yii\web\Session $session
         */
        $post = Yii::$app->request->post();
        static::checkValidRequest(false, !isset($post['treeNodeModify']));
        $data = static::getPostData();
        $parentKey = ArrayHelper::getValue($data, 'parentKey', null);
        $treeNodeModify = ArrayHelper::getValue($data, 'treeNodeModify', null);
        $currUrl = ArrayHelper::getValue($data, 'currUrl', '');
        $treeClass = TreeSecurity::getModelClass($data);
        $module = TreeView::module();
        $keyAttr = $module->dataStructure['keyAttribute'];
        $nodeTitles = TreeSecurity::getNodeTitles($data);

        if ($treeNodeModify) {
            $node = new $treeClass;
            $successMsg = Yii::t('kvtree', 'The {node} was successfully created.', $nodeTitles);
            $errorMsg = Yii::t('kvtree', 'Error while creating the {node}. Please try again later.', $nodeTitles);
        } else {
            $tag = explode("\\", $treeClass);
            $tag = array_pop($tag);
            $id = $post[$tag][$keyAttr];
            $node = $treeClass::findOne($id);
            $successMsg = Yii::t('kvtree', 'Saved the {node} details successfully.', $nodeTitles);
            $errorMsg = Yii::t('kvtree', 'Error while saving the {node}. Please try again later.', $nodeTitles);
        }
        $node->activeOrig = $node->active;
        $isNewRecord = $node->isNewRecord;
        $node->load($post);
        $errors = $success = false;
        if (Yii::$app->has('session')) {
            $session = Yii::$app->session;
        }
        if ($treeNodeModify) {
            if ($parentKey == TreeView::ROOT_KEY) {
                $node->makeRoot();
            } else {
                $parent = $treeClass::findOne($parentKey);
                if ($parent->isChildAllowed()) {
                    $node->appendTo($parent);
                } else {
                    $errorMsg = Yii::t('kvtree', 'You cannot add children under this {node}.', $nodeTitles);
                    if (Yii::$app->has('session')) {
                        $session->setFlash('error', $errorMsg);
                    } else {
                        throw new ErrorException("Error saving {node}!\n{$errorMsg}", $nodeTitles);
                    }
                    return $this->redirect($currUrl);
                }
            }
        }
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
                    $errorMsg .= "<li>" . Yii::t('kvtree', "{node} # {id} - '{name}': {error}",
                            $err + $nodeTitles) . "</li>\n";
                }
                $errorMsg .= "</ul>";
            }
        } else {
            $errorMsg = '<ul style="margin:0"><li>' . implode('</li><li>', $node->getFirstErrors()) . '</li></ul>';
        }
        if (Yii::$app->has('session')) {
            $session->set(ArrayHelper::getValue($post, 'nodeSelected', 'kvNodeId'), $node->{$keyAttr});
            if ($success) {
                $session->setFlash('success', $successMsg);
            } else {
                $session->setFlash('error', $errorMsg);
            }
        } elseif (!$success) {
            throw new ErrorException("Error saving {node}!\n{$errorMsg}", $nodeTitles);
        }
        return $this->redirect($currUrl);
    }

    /**
     * View, create, or update a tree node via ajax
     *
     * @return mixed json encoded response
     */
    public function actionManage()
    {
        static::checkValidRequest();
        $data = static::getPostData();
        $nodeTitles = TreeSecurity::getNodeTitles($data);
        $callback = function () use ($data, $nodeTitles) {
            $id = ArrayHelper::getValue($data, 'id', null);
            $parentKey = ArrayHelper::getValue($data, 'parentKey', null);
            $parsedData = TreeSecurity::parseManageData($data);
            $out = $parsedData['out'];
            $oldHash = $parsedData['oldHash'];
            $newHash = $parsedData['newHash'];
            /**
             * @var Module $module
             * @var Tree $treeClass
             * @var Tree $node
             */
            $treeClass = $out['treeClass'];
            if (!isset($id) || empty($id)) {
                $node = new $treeClass;
                $node->initDefaults();
            } else {
                $node = $treeClass::findOne($id);
            }
            $module = TreeView::module();
            $params = $module->treeStructure + $module->dataStructure + [
                    'node' => $node,
                    'parentKey' => $parentKey,
                    'treeManageHash' => $newHash,
                    'treeRemoveHash' => ArrayHelper::getValue($data, 'treeRemoveHash', ''),
                    'treeMoveHash' => ArrayHelper::getValue($data, 'treeMoveHash', ''),
                ] + $out;
            if (!empty($module->unsetAjaxBundles)) {
                $cb = function ($e) use ($module) {
                    foreach ($module->unsetAjaxBundles as $bundle) {
                        unset($e->sender->assetBundles[$bundle]);
                    }
                };
                Event::on(View::class, View::EVENT_AFTER_RENDER, $cb);
            }
            TreeSecurity::checkSignature('manage', $oldHash, $newHash);
            return $this->renderAjax($out['nodeView'], ['params' => $params]);
        };
        return self::process(
            $callback,
            Yii::t('kvtree', 'Error while viewing the {node}. Please try again later.', $nodeTitles),
            null
        );
    }

    /**
     * Remove a tree node
     */
    public function actionRemove()
    {
        static::checkValidRequest();
        $data = static::getPostData();
        $nodeTitles = TreeSecurity::getNodeTitles($data);
        $callback = function () use ($data) {
            $id = ArrayHelper::getValue($data, 'id', null);
            $parsedData = TreeSecurity::parseRemoveData($data);
            $out = $parsedData['out'];
            $oldHash = $parsedData['oldHash'];
            $newHash = $parsedData['newHash'];
            /**
             * @var Tree $treeClass
             * @var Tree $node
             */
            $treeClass = $out['treeClass'];
            TreeSecurity::checkSignature('remove', $oldHash, $newHash);
            /**
             * @var Tree $node
             */
            $node = $treeClass::findOne($id);
            return $node->removeNode($out['softDelete']);
        };
        return self::process(
            $callback,
            Yii::t('kvtree', 'Error removing the {node}. Please try again later.', $nodeTitles),
            Yii::t('kvtree', 'The {node} was removed successfully.', $nodeTitles)
        );
    }

    /**
     * Move a tree node
     */
    public function actionMove()
    {
        static::checkValidRequest();
        $data = static::getPostData();
        $dir = ArrayHelper::getValue($data, 'dir', null);
        $idFrom = ArrayHelper::getValue($data, 'idFrom', null);
        $idTo = ArrayHelper::getValue($data, 'idTo', null);
        $parsedData = TreeSecurity::parseMoveData($data);
        /**
         * @var Tree $treeClass
         * @var Tree $node
         */
        $treeClass = $parsedData['out']['treeClass'];
        $nodeTitles = TreeSecurity::getNodeTitles($data);
        /**
         * @var Tree $nodeFrom
         * @var Tree $nodeTo
         */
        $nodeFrom = $treeClass::findOne($idFrom);
        $nodeTo = $treeClass::findOne($idTo);
        $isMovable = $nodeFrom->isMovable($dir);
        $errorMsg = $isMovable ?
            Yii::t('kvtree', 'Error while moving the {node}. Please try again later.', $nodeTitles) :
            Yii::t('kvtree', 'The selected {node} cannot be moved.', $nodeTitles);
        $callback = function () use ($dir, $parsedData, $isMovable, $nodeFrom, $nodeTo, $nodeTitles) {
            $out = $parsedData['out'];
            $oldHash = $parsedData['oldHash'];
            $newHash = $parsedData['newHash'];
            if (!empty($nodeFrom) && !empty($nodeTo)) {
                TreeSecurity::checkSignature('move', $oldHash, $newHash);
                if (!$isMovable || ($dir !== 'u' && $dir !== 'd' && $dir !== 'l' && $dir !== 'r')) {
                    return false;
                }
                if ($dir === 'r') {
                    $nodeFrom->appendTo($nodeTo);
                } else {
                    if ($dir === 'l' && $nodeTo->isRoot() && $out['allowNewRoots']) {
                        $nodeFrom->makeRoot();
                    } elseif ($nodeTo->isRoot()) {
                        throw new Exception(Yii::t('kvtree',
                            'Cannot move root level {nodes} before or after other root level {nodes}.', $nodeTitles));
                    } elseif ($dir == 'u') {
                        $nodeFrom->insertBefore($nodeTo);
                    } else {
                        $nodeFrom->insertAfter($nodeTo);
                    }
                }
                return $nodeFrom->save();
            }
            return true;
        };
        return self::process($callback, $errorMsg, Yii::t('kvtree', 'The {node} was moved successfully.', $nodeTitles));
    }
}
