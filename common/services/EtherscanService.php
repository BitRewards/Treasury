<?php
namespace common\services;

use common\exceptions\InvalidBlockchainResponse;
use common\models\Transaction;
use GuzzleHttp\Client;
use yii\base\Component;
use GuzzleHttp\Exception\GuzzleException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class EtherscanService extends Component
{
    public $base_uri;
    public $api_key;

    public function getClient()
    {
        return new Client(['base_uri' => $this->base_uri]);
    }

    public function getTransactionsParams($params)
    {
        $default = [
            'module' => 'account',
//            'action' => 'txlistinternal',
//            'address' => $address,
            'startblock' => 0,
            'endblock' => '99999999',
            'sort' => 'desc',
            'page' => 1,
            'offset' => 50,
            'apikey' => $this->api_key
        ];

        return ArrayHelper::merge($default, $params);
    }

    /**
     * @param $params
     * @return array
     * @throws InvalidBlockchainResponse
     */
    public function fetchTransactions($params)
    {
        try {
            $response = $this->getClient()->request('GET', 'api', [
                'query' => $this->getTransactionsParams($params)
            ]);


            $encoded = $response->getBody()->getContents();
            $data = Json::decode($encoded);

            if (!isset($data['status'])) {
                throw new InvalidBlockchainResponse('Something bas came out of etherscan: ' . $encoded);
            }

            $transactions = [];

            if (!empty($data['result'])) {

                foreach ($data['result'] as $txData) {
                    $transaction = new Transaction();

                    if ($transaction->load($txData, '') && $transaction->validate()) {
                        $transactions[] = $transaction;
                    }
                }
            }

            return $transactions;
        } catch (GuzzleException $e) {
            \Yii::error(['message' => $e->getMessage(), 'params' => $params], GuzzleException::class);

            throw new InvalidBlockchainResponse($e->getMessage());
        }
    }

    /**
     * @param $address
     * @throws InvalidBlockchainResponse
     */
    public function listInternalTransactions($address)
    {
        $params = [
            'action' => 'txlistinternal',
            'address' => $address,
        ];

        return $this->fetchTransactions($params);
    }

    /**
     * @param $address
     * @throws InvalidBlockchainResponse
     */
    public function listEthTransactions($address)
    {
        $params = [
            'action' => 'txlist',
            'address' => $address,
        ];

        return $this->fetchTransactions($params);
    }

    /**
     * @param $address
     * @throws InvalidBlockchainResponse
     */
    public function listTokenTransactions($address)
    {
        $params = [
            'action' => 'tokentx',
            'address' => $address,
        ];

        return $this->fetchTransactions($params);
    }
}