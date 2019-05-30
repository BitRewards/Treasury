<?php
namespace common\services;

use common\helpers\ThrowableModelErrors;
use common\models\Partner;
use GuzzleHttp\Client;
use function GuzzleHttp\Psr7\uri_for;
use GuzzleHttp\Psr7\UriResolver;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\helpers\Json;

class GiftdService extends Component
{
    public $api_base_url;

    /**
     * @return Client
     */
    protected function getClient()
    {
        $client = new Client(['base_uri' => $this->api_base_url]);

        return $client;
    }

    /**
     * @return string
     */
    public function getBalanceNotificationEndpoint()
    {
        $uri = UriResolver::resolve(uri_for($this->api_base_url), uri_for('treasury/balance-callback'));
        return $uri->__toString();
    }

    public function getEthTransferCallbackEndpoint()
    {
        $uri = UriResolver::resolve(uri_for($this->api_base_url), uri_for('treasury/eth-transfer'));
        return $uri->__toString();
    }

    /**
     * @param $api_key
     * @return bool|Partner
     */
    public function loginByApiKey ($api_key)
    {
        try {
            $response = $this->getClient()->request('GET', 'partner', ['query' => ['api_token' => $api_key]]);
            $body = Json::decode($response->getBody());

            if (isset($body['data'])) {
                // we are ok
                $partner = new Partner();
                if ($partner->load($body['data'], '') && $partner->validate()) {
                    return $partner;
                } else {
                    throw new InvalidArgumentException(ThrowableModelErrors::jsonMessage($partner));
                }
            }

        } catch (InvalidArgumentException $e) {
            // invalid json response meaning crm issue
            throw $e;
        } catch (\Exception $e) {
            // invalid api key most probably
            throw $e;
        }


        return false;
    }
}
