<?php
namespace common\services\Fiat\Tickers;

use common\models\TickerData;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use yii\base\Component;

abstract class AbstractTicker extends Component
{
    public $baseUrl;

    public function queryApi($endpoint, $method = 'GET', $params = [])
    {
        $client = new Client(['base_uri' => $this->baseUrl]);

        $data = null;

        try {
            /* @var Response $response*/
            $response = $client->request($method, $endpoint, ['params' => $params]);

            if ($response->getStatusCode() !== 200) {
                throw new InvalidTickerResponse("Invalid response status (".$response->getStatusCode().").");
            }

            $data = $response->getBody()->getContents();
        } catch (InvalidTickerResponse $e) {
            \Yii::error([$e->getMessage(), $endpoint, $method, $params, $response->getBody()->getContents()]);
        } catch (\Throwable $e) {
            \Yii::error([$e->getMessage(), $endpoint, $method, $params, isset($response) ? $response->getBody()->getContents() : null]);
        }

        return $data;
    }

    abstract public function requestTickerData($coin);
    abstract public function convertResponse($response, $coin) : TickerData;

    /**
     * @param $coin
     * @return mixed|null
     * @throws InvalidTickerResponse
     */
    public function getTicker($coin)
    {
        $response = $this->requestTickerData($coin);

        if (empty($response)) {
            throw new InvalidTickerResponse('Empty response from ticker');
        }

        return $this->convertResponse($response, $coin);
    }
}