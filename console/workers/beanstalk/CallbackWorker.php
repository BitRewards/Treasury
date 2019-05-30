<?php
namespace console\workers\beanstalk;

use common\exceptions\CallbackException;
use common\helpers\ThrowableModelErrors;
use common\models\Callback;
use common\services\CallbackService;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Pheanstalk\Job;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class CallbackWorker extends \console\workers\beanstalk\BeanstalkController
{
    public function listenTubes()
    {
        return ['callback'];
    }

    public function actionCallback(Job $job)
    {
        /* @var \stdClass $data */
        $data = $job->getData();

        /**
         * default beanstalk action
         */
        $action = self::DELETE;

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            /* @var \common\models\Callback $clb */
            $clb = Callback::findBySql('SELECT * FROM {{%callback}} WHERE id = :id AND (status = :status OR status = :retry_status OR status = :initiated_status) FOR UPDATE',
                [
                    ':id' => $data->id,
                    ':status' => Callback::STATUS_NOT_PROCESSED,
                    ':retry_status' => Callback::STATUS_WAITING_RETRY,
                    ':initiated_status' => Callback::STATUS_INITIATED
                ])->one();

            if (!$clb) {
                throw new InvalidArgumentException("Pending callback #{$data->id} not found");
            }

            $clb->status = Callback::STATUS_INITIATED;


            if (!$clb->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($clb));
            }

            $transaction->commit();

            // once again retrieve record with extra lock
            $transaction = \Yii::$app->db->beginTransaction();
            $clb = Callback::findBySql('SELECT * FROM {{%callback}} WHERE id = :id AND status = :status FOR UPDATE',
                [
                    ':id' => $data->id,
                    ':status' => Callback::STATUS_INITIATED
                ])->one();

            if (!$clb) {
                throw new InvalidArgumentException('Initiated callback not found');
            }

            $service = new CallbackService();

            // append User's api_key to request
            $params = ArrayHelper::merge(
                $clb->user ? ['api_token' => $clb->user->api_key] : [],
                $clb->params ?? []
            );

            $response = $service->send($clb->url, $clb->getMethodName(), $params);

            if ($response->getStatusCode() !== 200) {
                $clb->response = $response->getBody()->getContents();
                throw new CallbackException('invalid response');
            }

            $clb->response = Json::decode($response->getBody()->getContents());

            if (empty($clb->response['status']) || $clb->response['status'] !== 'ok') {
                throw new CallbackException("Invalid response");
            }


            $clb->status = Callback::STATUS_PROCESSED;
            if (!$clb->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($clb));
            }

            $action = self::DELETE;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $clb->attempts_count++;
            if ($e instanceof RequestException) {
                $response = $e->getResponse();
                $clb->response = $response ? $response->getBody()->getContents() : $e->getMessage();
            } else {
                $clb->response = $e->getMessage();
            }

            if ($clb->attempts_count < Callback::MAX_CALLBACK_ATTEMPTS) {
                $clb->status = Callback::STATUS_WAITING_RETRY;
                $clb->save();
            } else {
                $clb->status = Callback::STATUS_FAILED;
                $clb->save();
            }
            \Yii::error(['message' => $e->getMessage(), 'object' => $clb->attributes], GuzzleException::class);
            $action = self::DELETE;
        } catch (CallbackException $e) {
            $clb->attempts_count++;

            if ($clb->attempts_count < Callback::MAX_CALLBACK_ATTEMPTS) {
                $clb->status = Callback::STATUS_WAITING_RETRY;
                $clb->save();
            } else {
                $clb->status = Callback::STATUS_FAILED;
                $clb->save();
            }
            \Yii::error(['message' => "Callback {$clb->id}: " . $e->getMessage(), 'object' => $clb->attributes], CallbackException::class);
            $action = self::DELETE;
        } catch (\Throwable $e) {
            if ($clb instanceof Callback) {
                $clb->attempts_count++;

                if ($clb->attempts_count < Callback::MAX_CALLBACK_ATTEMPTS) {
                    $clb->status = Callback::STATUS_WAITING_RETRY;
                } else {
                    $clb->status = Callback::STATUS_FAILED;
                }

                if (isset($response)) {
                    $clb->response = $response->getBody()->getContents();
                }

                $clb->save();
                \Yii::error(['message' => $e->getMessage(), 'object' => $clb->attributes, 'stack' => $e->getTrace()]);
            } else {
                \Yii::error($e->getMessage());
            }
            $action = self::DELETE;
        }

        /* commit here to release lock and update transaction status whatever it was set to  */
        $transaction->commit();
        return $action;
    }

}