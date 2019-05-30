<?php
namespace api\modules\v1\controllers;

// There are some minor side-effects of this solutions, because original request is made to the
// entry point, not to the target controller and action. Be careful working with Request object,
// especially when working on access restriction to the target actions. For example, you want an
// action to be reached only with GET verb only, but you do POST request to the endpoint. In that
// case you will get Internal Error because access will be denied.
use common\exceptions\InvalidBlockchainResponse;
use common\exceptions\NotEnoughBalanceException;
use common\exceptions\NotImplementedException;
use common\helpers\ThrowableModelErrors;
use common\helpers\WeiHelper;
use common\models\Partner;
use common\models\Transaction;
use common\models\User;
use common\models\Wallet;
use common\models\Withdrawal;
use georgique\yii2\jsonrpc\Action;
use Yii;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

class WalletController extends \yii\web\Controller
{
    /**
     * @param $crm_user_id
     * @param $api_key
     * @param null $callback_url
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     * @throws InvalidBlockchainResponse
     */
    public function actionCreateWallet(int $crm_user_id = null, $api_key, $callback_url = null)
    {
        // check that we have the same user_id

        $partner = new Partner();
        $partner->id = $crm_user_id;

        /*if (!$partner = Yii::$app->giftd->loginByApiKey($api_key)) {
            throw new ForbiddenHttpException('Invalid api_key');
        }

        if ($partner->id !== $crm_user_id) {
            throw new ForbiddenHttpException("{$api_key} Invalid crm_user_id: {$partner->id} !== {$crm_user_id} ");
        }*/

        if ($crm_user_id && $user = User::findByCrmId($crm_user_id)) {
            throw new BadRequestHttpException('crm user exists');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {

            $user = new User(['scenario' => $crm_user_id ? User::SCENARIO_DEFAULT : User::SCENARIO_NO_CRM_USER_ID]);
            $user->api_key = $api_key;
            $user->crm_user_id = $partner->id;
            $user->title = $partner->title;
            if (!$user->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($user));
            }

            $address = Yii::$app->eth->createWallet($user->id);

            $wallet = new Wallet();
            $wallet->address = $address;
            $wallet->withdraw_key = Yii::$app->getSecurity()->generateRandomString();
            $wallet->user_id = $user->id;
            $wallet->callback_url = $callback_url;
            if (!$wallet->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($wallet));
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        return $wallet;
    }

    /**
     * @param $address
     * @param $callback_url
     * @return Wallet
     * @throws \Exception
     */
    public function actionCreateReadonlyWallet($address, $callback_url)
    {
        return false;
        $transaction = Yii::$app->db->beginTransaction();
        try {

            $user = new User(['scenario' => User::SCENARIO_NO_CRM_USER_ID]);
            $user->title = $address;
            if (!$user->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($user));
            }

            $wallet = new Wallet(['scenario' => Wallet::SCENARIO_READONLY]);
            $wallet->address = $address;
            $wallet->user_id = $user->id;
            $wallet->callback_url = $callback_url;
            if (!$wallet->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($wallet));
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        return $wallet;
    }

    /**
     * @param $api_key
     * @throws BadRequestHttpException
     * @throws InvalidBlockchainResponse
     */
    public function actionGetBalance($api_key)
    {
        if (!$user = User::findByApiKey($api_key)) {
            throw new BadRequestHttpException('user not found with api_key:' . $api_key);
        }

        return Yii::$app->eth->getBalance($user->wallet->address);
    }

    /**
     * @param $api_key
     * @param $dest_address
     * @param string $amount
     * @param $currency
     * @param $callback_url
     * @throws BadRequestHttpException
     * @throws NotEnoughBalanceException
     * @throws InvalidBlockchainResponse
     */
    public function actionWithdraw($api_key, $withdraw_key, $dest_address, $amount, $currency = Withdrawal::CURRENCY_ETH, $callback_url = null)
    {
        if (!$user = User::findByApiKey($api_key)) {
            throw new BadRequestHttpException('user not found with api_key: ' . $api_key);
        }

        $wallet = $user->wallet;
        if (!$wallet) {
            throw new BadRequestHttpException('wallet not found');
        }

        if ($wallet->withdraw_key !== $withdraw_key) {
            throw new BadRequestHttpException('invalid withdraw_key: ' . $withdraw_key);
        }

        $withdrawal = new Withdrawal();
        $withdrawal->user_id = $user->id;
        $withdrawal->dest_address = $dest_address;
        $withdrawal->amount = WeiHelper::fromWei($amount);
        $withdrawal->currency = $currency;
        $withdrawal->callback_url = $callback_url;

        $withdrawal->status = Withdrawal::STATUS_NOT_PROCESSED;

        if (!$withdrawal->validate()) {
            throw new BadRequestHttpException(ThrowableModelErrors::jsonMessage($withdrawal));
        }

        if (!$withdrawal->save()) {
            throw new BadRequestHttpException(ThrowableModelErrors::jsonMessage($withdrawal));
        }

        return $withdrawal;
    }

    /**
     * @param $api_key
     * @return mixed
     * @throws BadRequestHttpException
     * @throws InvalidBlockchainResponse
     */
    public function actionListTransactions($api_key)
    {
        if (!$user = User::findByApiKey($api_key)) {
            throw new BadRequestHttpException('user not found');
        }

        $wallet = $user->wallet;

        $eth = Yii::$app->etherscan->listEthTransactions($wallet->address);
        $token = Yii::$app->etherscan->listTokenTransactions($wallet->address);
        $internal = Yii::$app->etherscan->listInternalTransactions($wallet->address);

        /* @var Withdrawal[] $pendingWithdrawals */
        $pendingWithdrawals = $user->getWithdrawals()
//            ->where(['not', ['status' => Withdrawal::STATUS_CONFIRMED]])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(50)
            ->all();

        $pending = [];

        foreach ($pendingWithdrawals as $withdrawal) {
            $transaction = new Transaction();

            $txData = [
                'hash' => $withdrawal->tx_id,
                'status' => Withdrawal::$STATUSES_FOR_CALLBACK[$withdrawal->status] ?? 'pending',
                'timeStamp' => $withdrawal->created_at,
                'from' => $user->wallet->address,
                'to' => $withdrawal->dest_address,
                'value' => WeiHelper::inWei($withdrawal->amount),
                'tokenSymbol' => Withdrawal::$CURRENCY_LIST[$withdrawal->currency] ?? null,
            ];

            if ($transaction->load($txData, '')) {
                $pending[] = $transaction;
            }
        }


        $hashes = array_reduce($token, function ($ids, $item) {
            array_push($ids, $item->hash);
            return $ids;
        }, []);

        $eth = array_filter($eth, function($item) use ($hashes) {
            return !in_array($item->hash, $hashes, true);
        });

        $hashes = array_reduce($eth, function ($ids, $item) {
            array_push($ids, $item->hash);
            return $ids;
        }, $hashes);

        $pending = array_filter($pending, function($item) use ($hashes) {
            return !in_array($item->hash, $hashes, true);
        });

        $transactions = array_filter(array_merge($token, $eth, $internal, $pending), function ($item) {
            return $item->value >= 0;
        });

        usort($transactions, function(Transaction $a, Transaction $b) {
            return $a->timeStamp < $b->timeStamp;
        });

        return array_slice($transactions, 0, 30);
    }

    /**
     * @param $api_key
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionTokenTransferEthFeeEstimate($api_key)
    {
        if (!$user = User::findByApiKey($api_key)) {
            throw new BadRequestHttpException('user not found');
        }

        return Yii::$app->eth->getTokenTransferEthFeeEstimate($user->id);
    }

    /**
     * @param $api_key
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionEthTransferFeeEstimate($api_key)
    {
        if (!$user = User::findByApiKey($api_key)) {
            throw new BadRequestHttpException('user not found');
        }

        return Yii::$app->eth->getEthTransferFeeEstimate($user->id);
    }
}