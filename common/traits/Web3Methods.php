<?php
namespace common\traits;

trait Web3Methods
{
    /**
     * @param int $index
     * @return mixed
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function getAddress(int $index)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $address
     * @return mixed
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function getEthBalance(string $address)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $address
     * @return mixed
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function getTokenBalance(string $address)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param int $index
     * @param string $destAddress
     * @param string $amountInWei
     * @return mixed
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function sendEther(int $user_id, string $destAddress, string $amountInWei)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param int $user_id
     * @param string $destAddress
     * @param string $amountInWei
     * @return mixed
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function sendToken(int $user_id, string $destAddress, string $amountInWei)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param int $block
     * @return mixed
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function getBlockTransactions(int $block)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param int $block
     * @param string $event
     * @return mixed
     *
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function getSingleBlockLogs(int $block, string $event)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param int $fromBlock
     * @param int $toBlock
     * @param string $event
     * @return mixed
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function getLogs(int $fromBlock, int $toBlock, string $event)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return mixed
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function getBlockNumber()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param $hash
     * @return mixed
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function getTransaction($hash)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}