<?php
namespace api\jsonrpc;

use georgique\yii2\jsonrpc\Controller;

class EntryPointController extends Controller {

    public function actions()
    {
        return [
            'index' => [
                'class' => \api\jsonrpc\Action::class,
                'paramsPassMethod' => $this->paramsPassMethod
            ]
        ];
    }
}
