<?php
namespace Spicespirit\Redis;

class Redis
{

    protected $_redis = null;
    protected $_config = null;
    protected $_select = 0;


    /**
     * 类构造方法
     *
     * @param int   默认一库
     */
    public function __construct($select = 0)
    {

        if (!is_null($this->_redis)) {
            return $this->_redis;
        }

        //获取redis配置
        $file = APPLICATION_PATH . '/../conf/custom/data.yml';
        if (!is_file($file)) {
            throw new \AtsException(\AtsMessages::PARAMS_INVALID, [$file]);
        }

        $data = yaml_parse_file($file);
        $this->_config = $data['redis'];

        $this->_select = $select;

        $this->_init_redis();

    }


    /**
     * 初始化连接Redis服务器
     */
    private function _init_redis()
    {

        try {
            $this->_redis = new \Redis();
            $this->_redis->connect($this->_config['host'], $this->_config['port'], 0);
            $this->_redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            $this->_redis->auth($this->_config['password']);
            $this->_redis->select($this->_select);

            return $this->_redis;

        } catch (\Exception $e) {
            throw new \Exception('redis server can not connect');
        }

    }

// ----------------------------------------- String ----------------------------------------------------
    /**
     * 根据key获取对应value
     *
     * 支持单个key获取value，同时支持通过key的数组获取value数组
     *
     * @param string /array
     * @return string/array
     */
    public function gets($key)
    {
        if (!is_array($key)) {
            return $this->_redis->get($key);
        } else {
            return $this->_redis->getMultiple($key);
        }
    }

    /**
     * 增加一条名为key的value的数据
     *
     * 支持对key设置生存周期
     *
     * @param string 键名
     * @param string 值
     * @param int 生存时间
     * @return boolean
     */
    public function save($key, $value, $ttl = NULL)
    {
        return ($ttl)
            ? $this->_redis->setex($key, $ttl, $value)
            : $this->_redis->set($key, $value);
    }

    /**
     * 添加多条出现在参数数组中的键值对
     *
     * 例：
     * $obj->msave(array('key0' => 'value0', 'key1' => 'value1'));
     *
     * @param array 数组
     * @return boolean
     */
    public function msave($arr)
    {
        return $this->_redis->mset($arr);
    }

    /**
     * 对key对应的value做增量操作
     *
     * @param string 键名
     * @param int 增量步长
     * @return boolean
     */
    public function incr($key, $incr = 1)
    {
        return $this->_redis->incrby($key, $incr);
    }

    /**
     * 删除指定key的值
     *
     * @param string 键名
     * @return boolean
     */
    public function delete($key)
    {
        return ($this->_redis->delete($key) === 1);
    }

    /**
     * 对指定key设置过期时间
     *
     * @param string 键名
     * @return boolean
     */
    public function expire($key, $sec)
    {
        return ($this->_redis->expire($key, $sec) === 1);
    }

    /**
     * 判断key是否存在
     *
     * @param string 键名
     * @return boolean
     */
    public function exists($key)
    {
        return $this->_redis->exists($key);
    }

    // ----------------------------------------- List ----------------------------------------------------
    /**
     * list增加value操作
     *
     * $direction='left' 向list头部push值
     * $direction='right' 向list尾部push值
     *
     * @param string Cache key identifier
     * @param mixed Data to push
     * @param string Direction to insert
     * @return mixed|boolean
     */
    public function push($key, $value, $direction = 'left')
    {
        if ($direction == 'right') {
            return $this->_redis->rpush($key, $value);
        }
        return $this->_redis->lpush($key, $value);
    }

    /**
     * list弹出value操作
     *
     * $direction='left' 移除并返回列表key的头元素
     * $direction='right' 移除并返回列表key的尾元素
     *
     * @param string Cache key identifier
     * @param string Direction to pop
     * @return mixed|null
     */
    public function pop($key, $direction = 'left')
    {
        if ($direction == 'right') {
            return $this->_redis->rpop($key);
        }
        return $this->_redis->lpop($key);
    }

    /**
     * 返回列表key中指定区间内的元素，区间以偏移量start和stop指定。
     *
     * 下标(index)参数start和stop都以0为底，也就是说，以0表示列表的第一个元素，以1表示列表的第二个元素，以此类推。
     * 可以使用负数下标，以-1表示列表的最后一个元素，-2表示列表的倒数第二个元素，以此类推。
     *
     * @param string Cache key identifier
     * @param string Direction to pop
     * @return mixed|null
     */
    public function lrange($key, $start = 0, $end = -1)
    {
        return $this->_redis->lRange($key, $start, $end);
    }

    /**
     * 根据参数count的值，移除列表中与参数value相等的元素。
     *
     * count的值可以是以下几种：
     * count > 0: 从表头开始向表尾搜索，移除与value相等的元素，数量为count。
     * count < 0: 从表尾开始向表头搜索，移除与value相等的元素，数量为count的绝对值。
     * count = 0: 移除表中所有与value相等的值。
     *
     * @param string Cache key identifier
     * @param string Direction to pop
     * @return int The number of removed elements
     */
    public function lRem($key, $value, $count = 0)
    {
        return $this->_redis->lRem($key, $value, $count);
    }

    /**
     * 让列表只保留指定区间内的元素，不在指定区间之内的元素都将被删除
     *
     * 下标(index)参数start和stop都以0为底，也就是说，以0表示列表的第一个元素，以1表示列表的第二个元素，以此类推。
     * 可以使用负数下标，以-1表示列表的最后一个元素，-2表示列表的倒数第二个元素，以此类推。
     *
     * @param string Cache key identifier
     * @param string Direction to pop
     * @return int The number of removed elements
     */
    public function ltrim($key, $start, $stop)
    {
        return $this->_redis->ltrim($key, $start, $stop);
    }

    /**
     * 将列表key下标为index的元素的值设置为value
     *
     * 下标(index)参数start和stop都以0为底，也就是说，以0表示列表的第一个元素，以1表示列表的第二个元素，以此类推。
     * 可以使用负数下标，以-1表示列表的最后一个元素，-2表示列表的倒数第二个元素，以此类推。
     *
     * @param string Cache key identifier
     * @param int The index of the element
     * @param string Value of the element
     * @return boolen
     */
    public function lset($key, $index, $value)
    {
        return $this->_redis->lset($key, $index, $value);
    }

    /**
     * 返回list类型列表总长度
     *
     *
     * @param string 键名
     * @return int
     */
    public function lsize($key)
    {
        return $this->_redis->lSize($key);
    }

    /**
     * Clean cache
     *
     * @return    bool
     * @see        Redis::flushDB()
     */
    public function clean()
    {
        return $this->_redis->flushDB();
    }

    /**
     * Get cache driver info
     *
     * @param    string    Not supported in Redis.
     *            Only included in order to offer a
     *            consistent cache API.
     * @return    array
     * @see        Redis::info()
     */
    public function cache_info($type = NULL)
    {
        return $this->_redis->info();
    }

    /**
     * Get cache metadata
     *
     * @param    string    Cache key
     * @return    array
     */
    public function get_metadata($key)
    {
        $value = $this->get($key);

        if ($value) {
            return array(
                'expire' => time() + $this->_redis->ttl($key),
                'data' => $value
            );
        }
        return FALSE;
    }


    /**
     * 向名称为key的hash中批量添加元素
     */
    public function hMset($key, $value)
    {
        return $this->_redis->hMset($key, $value);
    }


    /**
     * 返回名称为h的hash中field1,field2对应的value
     */
    public function hMGet($key, $attribute = array())
    {
        return $this->_redis->hMGet($key, $attribute);
    }


    /**
     *
     */
    public function hGetAll($key)
    {
        return $this->_redis->hGetAll($key);
    }

    /**
     * $redis->hMSet('h',
     *               array(
     *                    'f1' => 'v1',
     *                    'f2' => 'v2',
     *                    'f3' => 'v3',
     *                    'f4' => 'v4',
     *               ));
     *
     * var_dump( $redis->hDel('h', 'f1') );        // int(1)
     * var_dump( $redis->hDel('h', 'f2', 'f3') );  // int(2)
     * s
     * var_dump( $redis->hGetAll('h') );
     * //// Output:
     * //  array(1) {
     * //    ["f4"]=> string(2) "v4"
     * //  }
     * </pre>
     */
    public function hDel( $key, $hashKey1, $hashKey2 = null, $hashKeyN = null ) {
        return $this->_redis->hDel($key, $hashKey1, $hashKey2, $hashKeyN);
    }

    /**
     * @param       key
     * @param       value       value to push in key
     */
    public function lPush($key, $value)
    {
        return $this->_redis->lPush($key, $value);
    }

    public function hSet($key, $field, $value)
    {
        return $this->_redis->hSet($key, $field, $value);
    }

    public function hGet($key, $field)
    {
        return $this->_redis->hGet($key, $field);
    }

    public function hIncrBy($key, $field, $increment)
    {
        return $this->_redis->hIncrBy($key, $field, $increment);
    }


    /**
     * 带生存时间的写入值
     *
     * @param    string $key
     * @param    int $time
     * @param    mix $value
     */
    public function setex($key, $time, $value)
    {
        return $this->_redis->setex($key, $time, $value);
    }


    /**
     * 获取指定Key 的值
     */
    public function get($key)
    {
        return $this->_redis->get($key);
    }


    /**
     *  获取指定多个Key 的值
     *
     * var_dump($redis->mget(array('x', 'y', 'z', 'h')));
     * // Output:
     * // array(3) {
     * // [0]=>
     * // string(1) "a"
     * // [1]=>
     * // string(1) "b"
     * // [2]=>
     * // string(1) "c"
     * // [3]=>
     * // bool(false)
     * // }
     * </pre>
     */
    public function mget( $array = array() ) {
        return $this->_redis->mget($array);
    }

    /**
     * @param   string  $key
     * @param   string  $value
     * @example $redis->set('key', 'value');
     */
    public function set( $key, $value, $timeout = 0 ) {
        return $this->_redis->set($key, $value, $timeout);
    }

    /**
     * $redis->mset(array('key0' => 'value0', 'key1' => 'value1'));
     * var_dump($redis->get('key0'));
     * var_dump($redis->get('key1'));
     * // Output:
     * // string(6) "value0"
     * // string(6) "value1"
     * </pre>
     */
    public function mset( array $array ) {
        return $this->_redis->mset($array);
    }

    /**
     * 只有不存在的时候才设置
     * $redis->setnx('key', 'value');   // return TRUE
     */
    public function setnx( $key, $value ) {
        return $this->_redis->setnx( $key, $value);
    }

    /**
     * $redis->delete('key1', 'key2');          // return 2
     * $redis->delete(array('key3', 'key4'));   // return 2
     */
    public function del( $key = array() ) {
        return $this->_redis->del( $key);
    }


//    /**
//     * 获取锁
//     * @param  String $key 锁标识
//     * @param  Int $expire 锁过期时间
//     * @return Boolean
//     */
//    public function lock($key, $expire = 5)
//    {
//        $is_lock = $this->setnx($key, time() + $expire);
//
//        // 不能获取锁
//        if (!$is_lock) {
//
//            // 判断锁是否过期
//            $lock_time = $this->get($key);
//
//            // 锁已过期，删除锁，重新获取
//            if (time() > $lock_time) {
//                $this->unlock($key);
//                $is_lock = $this->setnx($key, time() + $expire);
//            }
//        }
//
//        return $is_lock ? true : false;
//    }
//
//    /**
//     * 释放锁
//     * @param  String $key 锁标识
//     * @return Boolean
//     */
//    public function unlock($key)
//    {
//        return $this->del($key);
//    }


}