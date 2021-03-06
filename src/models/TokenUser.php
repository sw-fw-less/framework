<?php

namespace SwFwLess\models;

use SwFwLess\components\auth\token\UserProviderContract;
use SwFwLess\components\redis\RedisWrapper;
use SwFwLess\facades\RedisPool;

class TokenUser extends AbstractMysqlModel implements UserProviderContract
{
    /**
     * @param $authToken
     * @return bool
     * @throws \Throwable
     */
    public function retrieveByToken($authToken)
    {
        /** @var \Redis|RedisWrapper $redis */
        $redis = RedisPool::pick();
        try {
            if ($id = $redis->get('auth:token:' . $authToken)) {
                $this->id = $id;
                return true;
            }
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            RedisPool::release($redis);
        }

        return false;
    }
}
