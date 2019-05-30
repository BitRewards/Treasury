<?php
namespace common\services;

use common\exceptions\CallbackException;
use GuzzleHttp\Client;
use yii\base\Component;

class CallbackService extends Component
{
    /**
     * @return Client
     */
    public function getClient()
    {
        return new Client();
    }

    /**
     * @param $url
     * @param $method
     * @param $data
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function send($url, $method, $data)
    {
        $client = $this->getClient();

        $response = $client->request($method, $url, ['json' => $data]);

        return $response;
    }
}