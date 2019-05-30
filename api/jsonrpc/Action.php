<?php

namespace api\jsonrpc;

use georgique\yii2\jsonrpc\JsonRpcException;
use \georgique\yii2\jsonrpc\Action as BaseAction;

use const georgique\yii2\jsonrpc\{JSON_RPC_ERROR_INTERNAL, JSON_RPC_ERROR_REQUEST_INVALID};
use georgique\yii2\jsonrpc\JsonRpcRequest;
use yii\base\Exception;

class Action extends BaseAction
{
    protected function renderError($data, $id = null) {

        if ($data instanceof JsonRpcException && $data->getCode() === JSON_RPC_ERROR_INTERNAL) {
            if (($previous = $data->getPrevious()) instanceof Exception) {
                \Yii::error($previous->getMessage(), get_class($previous));
            }
        }

        return [
            'jsonrpc' => '2.0',
            'error' => ($data instanceof \Exception) ? $this->renderException($data) : $data,
            'id' => $id,
        ];
    }

    /**
     * Parses request (parsed JSON object) and prepares JsonRpcRequest object.
     * @param $request
     * @return JsonRpcRequest
     * @throws JsonRpcException
     * @throws \yii\base\InvalidConfigException
     */
    public function parseRequest($request) {
        if (!isset($request->id)) {
            throw new JsonRpcException(null, "The JSON sent is not a correct JSON-RPC request - incorrect id.", JSON_RPC_ERROR_REQUEST_INVALID);
        }
        elseif (!is_int($request->id) && !ctype_digit($request->id)) {
            throw new JsonRpcException(null, "The JSON sent is not a correct JSON-RPC request - incorrect id.", JSON_RPC_ERROR_REQUEST_INVALID);
        }

        if (!isset($request->jsonrpc) || $request->jsonrpc !== '2.0') {
            throw new JsonRpcException($request->id, "The JSON sent is not a correct JSON-RPC request - missing or incorrect version.", JSON_RPC_ERROR_REQUEST_INVALID);
        }

        if (!isset($request->method) || !is_string($request->method) || (!$route = $this->parseMethod($request->method))) {
            throw new JsonRpcException($request->id, "The JSON sent is not a correct JSON-RPC request - missing or incorrect method.", JSON_RPC_ERROR_REQUEST_INVALID);
        }

        $params = [];
        if (isset($request->params)) {
            $params = (array) $request->params;
        }

        return \Yii::createObject([
            'class' => JsonRpcRequest::class,
            'id' => $request->id,
            'route' => $route,
            'params' => $params,
        ]);
    }
}