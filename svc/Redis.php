<?php
declare(strict_types = 1);

namespace Apex\Svc;

use redis as redisdb;
use RedisException;

/**
 * redis Initializer
 */
class Redis
{

    /**
     * Connect
     */
    public static function connect():?\redis
    {

        // Check if connection info defined
        if (!getEnv('redis_host')) { 
            return null;
        }

        // Connect to redis
        $instance = new redisdb();
        try {
            $instance->connect(getEnv('redis_host'), (int) getEnv('redis_port'), 2);
        } catch (RedisException $e) { 
            echo "Unable to connect to redis.  We're down!";
            exit(0);
        }

        // Authenticate redis, if needed
        $password = getEnv('redis_password') ?? '';
        if ($password != '') { 

            try { 
                $instance->auth($password);
            } catch (RedisException $e) { 
                echo "Unable to authenticate into redis.  We're down!";
                exit(0);
            }
        }

        // Select redis db, if needed
    $dbindex = (int) (getEnv('redis_dbindex') ?? 0);
        if ($dbindex > 0) { 
            $instance->select((int) $dbindex);
        }

        // Return
        return $instance;
    }

}


