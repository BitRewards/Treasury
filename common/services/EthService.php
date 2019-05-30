<?php
namespace common\services;

use common\exceptions\InvalidBlockchainResponse;
use common\exceptions\NodeException;
use common\helpers\WeiHelper;
use common\models\TokenTransfer;
use common\traits\Web3Methods;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\helpers\Json;

class EthService extends Component
{
    use Web3Methods;

    public $node = '';
    public $contractAddress;
    public $debug = true;

    public $jsonOutput = false;

    /**
     * @return $this
     */
    public function expectJSON($expect = true)
    {
        $this->jsonOutput = $expect;
        return $this;
    }

    /**
     * @param string $name
     * @param array $params
     * @return mixed|null
     * @throws InvalidBlockchainResponse
     */
    public function __call($name, $params)
    {
        return $this->web3query($name, $params);
    }

    /**
     * @param string $method
     * @param array $params
     * @return null
     * @throws InvalidBlockchainResponse
     */
    public function web3query(string $method, array $params = [])
    {
        $workingDir = \Yii::getAlias('@console') . '/node/';
        $tmpFile = tempnam(sys_get_temp_dir(), 'treasury_');

        $cmd = 'cd ' . escapeshellarg($workingDir) . ' && '
            . '/usr/local/bin/node '
            . 'treasury-operations.js '
            . escapeshellarg($method)
            . array_reduce($params, function ($str, $param) {
                    $str .= ' ' . escapeshellarg($param);
                    return $str;
                }, '')
            . (' > ' . escapeshellarg($tmpFile))
            . ($this->debug ?  '' : ' 2>/dev/null');



        try {

            $process = new Process($cmd);
            $process->setTimeout(60 * 5);
            $process->run();

            if (!$process->isSuccessful()) {

                throw new ProcessFailedException($process);
            }

            if (file_exists($tmpFile) && is_readable($tmpFile)) {

                $output = file_get_contents($tmpFile);
            } else {
                throw new NodeException('No tmp file');
            }

            //$output = $process->getOutput();

            return $this->jsonOutput ? Json::decode($output) : trim($output);
            //return count($output) === 1 ? ($this->jsonOutput ? Json::decode($output[0]) : $output[0]) : implode("\n", $output);

        } catch (NodeException $e) {
            // notify about Node problems
            // ...

            throw new InvalidBlockchainResponse($e->getMessage());
        } catch (\Throwable $e) {
            // possible fatal errors
            // log them
            $this->expectJSON(false);
            throw $e;
        } finally {
            if (file_exists($tmpFile) && is_readable($tmpFile)) {
                @unlink($tmpFile);
            }
        }

        //throw new InvalidBlockchainResponse('unhandled');
    }

    /**
     * @param $index
     * @return mixed
     * @throws InvalidBlockchainResponse
     */
    public function createWallet($index)
    {
        return $this->getAddress($index);
    }

    /**
     * @param $address
     * @return array
     * @throws InvalidBlockchainResponse
     */
    public function getBalance($address)
    {
        return [
            'address' => $address,
            'balanceEth' => WeiHelper::formatValue(WeiHelper::fromWei($this->getEthBalance($address))),
            'balanceBIT' => WeiHelper::formatValue(WeiHelper::fromWei($this->getTokenBalance($address)))
        ];
    }

    /**
     * @param $block
     * @throws InvalidBlockchainResponse
     */
    public function getBlock($block)
    {
        return $this->expectJSON()->getBlockTransactions($block);
    }

    /**
     * @param $block
     * @throws InvalidBlockchainResponse
     */
    public function getEthTransactions($block)
    {
        return $this->expectJSON()->getBlockTransactions($block);
    }

    /**
     * @param $block
     * @return mixed
     * @throws InvalidBlockchainResponse
     */
    public function getTokenTransactions($fromBlock, $toBlock = null, $event = TokenTransfer::TOKEN_EVENT_TRANSFER)
    {
        return $toBlock ? $this->expectJSON()->getLogs($fromBlock, $toBlock, $event) : $this->expectJSON()->getSingleBlockLogs($fromBlock, $event);
    }

    /**
     * @throws InvalidBlockchainResponse
     */
    public function getCurrentBlockNumber()
    {
        $blockNumber = (int)$this->getBlockNumber();
        if (!$blockNumber) {
            throw new InvalidBlockchainResponse("Invalid block number");
        }

        return $blockNumber;
    }

    /**
     * @param $hash
     * @return mixed
     * @throws InvalidBlockchainResponse
     */
    public function getTransactionByHash($hash)
    {
        return $this->expectJSON()->getTransaction($hash);
    }
}