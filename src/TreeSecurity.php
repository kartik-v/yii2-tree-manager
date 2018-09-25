<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2018
 * @package   yii2-tree-manager
 * @version   1.0.9
 */

namespace kartik\tree;

use Closure;
use kartik\tree\models\Tree;
use Yii;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\console\Application;

/**
 * Tree data security and data hashing helper class
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since  1.0
 */
class TreeSecurity
{
    /**
     * Parses tree manage data and returns processed manage data signatures for the tree
     * @param array $data
     * @return array
     * @throws InvalidConfigException
     */
    public static function parseManageData($data = [])
    {
        $nodeTitles = static::getNodeTitles($data);
        $defaults = [
            'modelClass' => '',
            'defaultBtnCss' => '',
            'formAction' => '',
            'currUrl' => '',
            'nodeView' => '',
            'nodeSelected' => '',
            'nodeTitle' => $nodeTitles['node'],
            'nodeTitlePlural' => $nodeTitles['nodes'],
            'noNodesMessage' => '',
            'isAdmin' => false,
            'softDelete' => true,
            'showFormButtons' => true,
            'showIDAttribute' => true,
            'showNameAttribute' => true,
            'allowNewRoots' => true,
            'formOptions' => [],
            'nodeAddlViews' => [],
            'nodeViewButtonLabels' => [],
            'icons' => [],
            'iconsList' => [],
            'breadcrumbs' => [],
        ];
        $out = static::getParsedData($defaults, $data, function ($type, $key, $value) {
            if ($type === 'array' && $key === 'iconsList' && is_array($value)) {
                $out = isset($value[0]) ? $value[0] : '';
                return count($value) === 1 && ($out === 'none' || $out === 'text') ? $out : array_values($value);
            }
            return $value;
        });
        $oldHash = ArrayHelper::getValue($data, 'treeManageHash', '');
        return ['out' => $out['data'], 'oldHash' => $oldHash, 'newHash' => $out['hash']];
    }

    /**
     * Parses  tree remove data and returns processed remove data signatures for the tree
     * @param array $data
     * @return array
     * @throws InvalidConfigException
     */
    public static function parseRemoveData($data = [])
    {
        $defaults = [
            'modelClass' => '',
            'softDelete' => true,
        ];
        $out = static::getParsedData($defaults, $data);
        $oldHash = ArrayHelper::getValue($data, 'treeRemoveHash', '');
        return ['out' => $out['data'], 'oldHash' => $oldHash, 'newHash' => $out['hash']];
    }

    /**
     * Parses tree move data and returns processed move data signatures for the tree
     * @param array $data
     * @return array
     * @throws InvalidConfigException
     */
    public static function parseMoveData($data = [])
    {
        $defaults = [
            'modelClass' => '',
            'allowNewRoots' => true,
        ];
        $out = static::getParsedData($defaults, $data);
        $oldHash = ArrayHelper::getValue($data, 'treeMoveHash', '');
        return ['out' => $out['data'], 'oldHash' => $oldHash, 'newHash' => $out['hash']];
    }

    /**
     * Gets parsed output and hash data for a tree action data source
     * @param array $defaults the default data
     * @param array $data the source data
     * @param Closure|null $callback the callback to process specific data keys
     * @return array the parsed data
     * @throws InvalidConfigException
     */
    protected static function getParsedData($defaults, $data, $callback = null)
    {
        $out = [];
        $hash = '';
        foreach ($defaults as $key => $val) {
            $value = isset($data[$key]) ? $data[$key] : $val;
            $type = 'string';
            if (is_bool($val)) {
                $value = (bool)$value;
                $type = 'bool';
            } elseif (is_array($val)) {
                $value = empty($value) ? [] : (array)$value;
                $type = 'array';
            }
            if (is_callable($callback)) {
                $value = $callback($type, $key, $value);
            }
            $out[$key] = $value;
            $hash .= $type === 'array' ? Json::encode($value) : $value;
        }
        $out['treeClass'] = ArrayHelper::getValue($out, 'modelClass', Tree::class);
        /**
         * @var Module $module
         */
        $module = TreeView::module();
        return ['data' => $out, 'hash' => Yii::$app->security->hashData($hash, $module->treeEncryptSalt)];
    }

    /**
     * Gets the node singular and plural titles
     * @param array $data the source data
     * @return array
     */
    public static function getNodeTitles($data)
    {
        return [
            'node' => ArrayHelper::getValue($data, 'nodeTitle', 'node'),
            'nodes' => ArrayHelper::getValue($data, 'nodeTitlePlural', 'nodes'),
        ];
    }

    /**
     * Gets the model class name key from the data array
     * @param array $data
     * @return mixed
     */
    public static function getModelClass($data = [])
    {
        return ArrayHelper::getValue($data, 'modelClass', Tree::class);
    }

    /**
     * Checks signature of posted data for ensuring security against data tampering.
     *
     * @param string $action the name of the action
     * @param string $oldHash the old hashed data
     * @param string $newHash the new hashed data
     *
     * @throws InvalidCallException
     * @throws InvalidConfigException
     */
    public static function checkSignature($action, $oldHash = '', $newHash = null)
    {
        if (Yii::$app instanceof Application) {
            return; // skip hash signature validation for console apps
        }
        /**
         * @var Module $module
         */
        $module = TreeView::module();
        if (Yii::$app->security->validateData($oldHash, $module->treeEncryptSalt) && $oldHash === $newHash) {
            return;
        }
        $params = YII_DEBUG ? '<pre>OLD HASH:<br>' . $oldHash . '<br>NEW HASH:<br>' . $newHash . '</pre>' : '';
        $message = Yii::t(
            'kvtree',
            '<h4>Operation Disallowed</h4><hr>Invalid request signature detected during tree data <b>{action}</b> action! Please refresh the page and retry.{params}',
            ['action' => $action, 'params' => $params]
        );
        throw new InvalidCallException($message);
    }
}
