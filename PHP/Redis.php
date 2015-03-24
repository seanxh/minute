<?php
if(!class_exists('Redis')){
class Redis
{
	/**
	 * @var string hostname to use for connecting to the redis server. Defaults to 'localhost'.
	 */
	public $hostname='localhost';
	/**
	 * @var int the port to use for connecting to the redis server. Default port is 6379.
	 */
	public $port=6379;
	/**
	 * @var string the password to use to authenticate with the redis server. If not set, no AUTH command will be sent.
	 */
	public $password;
	/**
	 * @var int the redis database to use. This is an integer value starting from 0. Defaults to 0.
	 */
	public $database=0;
	/**
	 * @var float timeout to use for connection to redis. If not set the timeout set in php.ini will be used: ini_get("default_socket_timeout")
	 */
	public $timeout=null;
	/**
	 * @var resource redis socket connection
	 */
	private $_socket;
	
	public static $AFTER = 1;
	public static $BEFORE = 0;
	
	public static $REDIS_STRING =  'String';
	public static $REDIS_SET = 'Set';
	public static $REDIS_LIST = 'List';
	public static $REDIS_ZSET ='Sorted set';
	public static $REDIS_HASH = 'Hash';
	public static $REDIS_NOT_FOUND = 'Not found / other';
	 

	/**
	 * Establishes a connection to the redis server.
	 * It does nothing if the connection has already been established.
	 * @throws CException if connecting fails
	 */
	public function connect($hostname,$port,$password=null,$database=0)
	{

		$this->hostname = $hostname;
		$this->port = $port;
		$this->password = $password;
		$this->database = $database;

		$this->_socket=@stream_socket_client(
			$this->hostname.':'.$this->port,
			$errorNumber,
			$errorDescription,
			$this->timeout ? $this->timeout : ini_get("default_socket_timeout")
		);
		if ($this->_socket)
		{
			if($this->password!==null)
				$this->executeCommand('AUTH',array($this->password));
			$this->executeCommand('SELECT',array($this->database));
		}
		else
			throw new MRedisException('Failed to connect to redis: '.$errorDescription,(int)$errorNumber);
	}
	
	/**
	 * @todo Change the selected database for the current connection.
	 * @param int $dbindex INTEGER: dbindex, the database number to switch to.
	 * @return TRUE in case of success, FALSE in case of failure.
	 */
	public function select($database){
		return (bool)$this->executeCommand('SELECT',array($dbindex));
	}
	
	/**
	 * @todo Disconnects from the Redis instance, except when pconnect is used.
	 * @return boolean
	 */
	public function close(){
		return fclose($this->_socket);
	}
	
	/**
	 * Executes a redis command.
	 * For a list of available commands and their parameters see {@link http://redis.io/commands}.
	 *
	 * @param string $name the name of the command
	 * @param array $params list of parameters for the command
	 * @return array|bool|null|string Dependend on the executed command this method
	 * will return different data types:
	 * <ul>
	 *   <li><code>true</code> for commands that return "status reply".</li>
	 *   <li><code>string</code> for commands that return "integer reply"
	 *       as the value is in the range of a signed 64 bit integer.</li>
	 *   <li><code>string</code> or <code>null</code> for commands that return "bulk reply".</li>
	 *   <li><code>array</code> for commands that return "Multi-bulk replies".</li>
	 * </ul>
	 * See {@link http://redis.io/topics/protocol redis protocol description}
	 * for details on the mentioned reply types.
	 * @trows CException for commands that return {@link http://redis.io/topics/protocol#error-reply error reply}.
	 */
	public function executeCommand($name,$params=array())
	{
		if($this->_socket===null)
			$this->connect();
	
		array_unshift($params,$name);
		$command='*'.count($params)."\r\n";
		foreach($params as $arg)
			$command.='$'.strlen($arg)."\r\n".$arg."\r\n";
	
		fwrite($this->_socket,$command);
		return $this->parseResponse(implode(' ',$params));
	}
	
	/**
	 * Reads the result from socket and parses it
	 * @return array|bool|null|string
	 * @throws CException socket or data problems
	 */
	private function parseResponse()
	{
		if(($line=fgets($this->_socket))===false)
			throw new MRedisException('Failed reading data from redis connection socket.');
		$type=$line[0];
		$line=substr($line,1,-2);
		switch($type)
		{
			case '+': // Status reply
				return true;
			case '-': // Error reply
				throw new MRedisException('Redis error: '.$line);
			case ':': // Integer reply
				// no cast to int as it is in the range of a signed 64 bit integer
				return $line;
			case '$': // Bulk replies
				if($line=='-1')
					return null;
				$length=$line+2;
				$data='';
				while($length>0)
				{
					if(($block=fread($this->_socket,$length))===false)
						throw new MRedisException('Failed reading data from redis connection socket.');
					$data.=$block;
					$length-=(function_exists('mb_strlen') ? mb_strlen($block,'8bit') : strlen($block));
				}
				return substr($data,0,-2);
			case '*': // Multi-bulk replies
				$count=(int)$line;
				$data=array();
				for($i=0;$i<$count;$i++)
					$data[]=$this->parseResponse();
					return $data;
					default:
						throw new MRedisException('Unable to parse data received from redis.');
		}
	}
	
	/**
	 * @todo Check the current connection status
	 * @return TRUE in case of success, FALSE in case of failure.
	 */
	public function ping(){
		return (bool)$this->executeCommand('PING');
	}

	/**
	 *  echo commnad
	 */
	public function __call($name,$arguments) {
		if($name=='echo'){
			return $this->executeCommand('ECHO',$arguments);
		}
	}
	
	//####################
	//Server COMMANDS
	//####################
	
	/**
	 * @todo Start the background rewrite of AOF (Append-Only File)
	 * @return BOOL: TRUE in case of success, FALSE in case of failure.
	 * @example $redis->bgrewriteaof();
	 */
	public function bgrewriteaof(){
		return $this->executeCommand('BGREWRITEAOF');
	}
	
	/**
	 * @todo Asynchronously save the dataset to disk (in background)
	 * @return BOOL: TRUE in case of success, FALSE in case of failure. If a save is already running, this command will fail and return FALSE.
	 * @example $redis->bgSave();
	 */
	public function bgsave(){
		return $this->executeCommand('BGSAVE');
	}
	
	/**
	 * @todo Get or Set the Redis server configuration parameters.
	 * @param string $operation either GET or SET
	 * @param string $key key string for SET, glob-pattern for GET. See http://redis.io/commands/config-get for examples.
	 * @param string $value optional string (only for SET)
	 * @return Associative array for GET, key -> value. bool for SET
	 * @example
	 * $redis->config("GET", "*max-*-entries*");
	 * $redis->config("SET", "dir", "/var/run/redis/dumps/");
	 */
	public function config($operation,$key,$value=NULL){
		if( $value !== NULL){
			return $this->executeCommand('CONFIG',array('SET',$key,$value));
		}else {
			return $this->executeCommand('CONFIG',array('GET',$key));
		}
	}
	
	/**
	 * @todo Return the number of keys in selected database.
	 * @return INTEGER: DB size, in number of keys.
	 * @example $count = $redis->dbSize();
	 */
	public function dbSize(){
		return (int)$this->executeCommand('DBSIZE');
	}
	
	/**
	 * @todo Remove all keys from all databases.
	 * @return BOOL: Always TRUE.
	 * @example $redis->flushAll();
	 */
	public function flushAll(){
		return (int)$this->executeCommand('FLUSHALL');
	}
	
	/**
	 * @todo Remove all keys from the current database.
	 * @return BOOL: Always TRUE.
	 * @example $redis->flushDB();
	 */
	public function flushDB()
	{
		return $this->executeCommand('FLUSHDB');
	}
	
	/**
	 * @todo Get information and statistics about the server
	 * @param string option The option to provide redis (e.g. "COMMANDSTATS", "CPU") . You can pass a variety of options to INFO (http://redis.io/commands/info), which will modify what is returned.
	 * @return Returns an associative array that provides information about the server. Passing no arguments to INFO will call the standard REDIS INFO command, which returns information such as the following:
	 * redis_version,arch_bits,uptime_in_seconds,uptime_in_days,connected_clients,connected_slaves,used_memory,changes_since_last_save,bgsave_in_progress,last_save_time,total_connections_received,total_commands_processed,role
	 * @example 
	 * $redis->info(); //standard redis INFO command
	 * redis->info("COMMANDSTATS");//Information on the commands that have been run (>=2.6 only)
	 * $redis->info("CPU"); //just CPU information from Redis INFO
	 */
	public function info($option=''){
		return $this->executeCommand('INFO',array($option));
	}
	
	/**
	 * @todo Returns the timestamp of the last disk save.
	 * @return INT: timestamp.
	 * @example $redis->lastSave();
	 */
	public function lastSave(){
		return $this->executeCommand('LASTSAVE');
	}
	
	/**
	 * @todo Reset the stats returned by info method.
	 * These are the counters that are reset:
	 * 		Keyspace hits
	 * 		Keyspace misses
	 * 		Number of commands processed
	 * 		Number of connections received
	 * 		Number of expired keys
	 * @return BOOL: TRUE in case of success, FALSE in case of failure.
	 * @example $redis->resetStat();
	 */
	public function resetStat(){
		return $this->executeCommand('CONFIG',array('RESETSTAT'));
	}
	
	/**
	 * @todo Synchronously save the dataset to disk (wait to complete)
	 * @return BOOL: TRUE in case of success, FALSE in case of failure. If a save is already running, this command will fail and return FALSE.
	 * @example  $redis->save();
	 */
	public function save(){
		return $this->executeCommand('SAVE');
	}
	
	/**
	 * @todo Changes the slave status
	 * @param string $host
	 * @param int $port 
	 * @example
	 * Either host (string) and port (int), or no parameter to stop being a slave.
	 * $redis->slaveof('10.0.1.7', 6379);
	 * $redis->slaveof();
	 */
	public function slaveof($host=NULL,$port=0){
		if($host === NULL){
			return $this->executeCommand('SLAVEOF',array('NO','ONE'));
		}else{
			return $this->executeCommand('SLAVEOF',array($host,$port));
		}
	}
	
	/**
	 * @todo Return the current server time.
	 * @return If successfull, the time will come back as an associative array with element zero being the unix timestamp, and element one being microseconds.
	 * @example $redis->time();
	 */
	public function time(){
		return $this->executeCommand('TIME');
	}
	
	/**
	 * @todo  Access the Redis slowlogs
	 * @param string $operation This can be either GET, LEN, or RESET
	 * @param int $length optional: If executing a SLOWLOG GET command, you can pass an optional length.
	 * @return The return value of SLOWLOG will depend on which operation was performed. SLOWLOG GET: Array of slowlog entries,
	 * 					 as provided by Redis SLOGLOG LEN: Integer, the length of the slowlog 
	 * 					SLOWLOG RESET: Boolean, depending on success
	 * @example 
	 * // Get ten slowlog entries
	 * $redis->slowlog('get', 10); 
	 * //Get the default number of slowlog entries
	 * $redis->slowlog('get');
	 * // Reset our slowlog
	 * $redis->slowlog('reset');
	 * // Retrieve slowlog length
	 * $redis->slowlog('len');
	 */
	public function slowlog($operation,$length=0){
		if($length > 0){
			return $this->executeCommand('SLOWLOG',array($operation,$length));
		}else{
			return $this->executeCommand('SLOWLOG',array($operation));
		}
	}
	
	//####################
	//Strings COMMANDS
	//####################
	
	
	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key a unique key identifying the cached value
	 * @return string|boolean the value stored in cache, false if the value is not in the cache or expired.
	 */
	public function get($key)
	{
		return $this->executeCommand('GET',array($key));
	}
	
	/**
	 * Stores a value identified by a key in cache.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function set($key,$value,$expire=0)
	{
		if ($expire==0)
			return (bool)$this->executeCommand('SET',array($key,$value));
		return (bool)$this->executeCommand('SETEX',array($key,$expire,$value));
	}
	
	/**
	 * @todo Set the string value in argument as value of the key, with a time to live. PSETEX uses a TTL in milliseconds.
	 * @param string $key
	 * @param int $ttl
	 * @param string $value
	 * @return Bool TRUE if the command is successful.
	 * @example
	 * $redis->setex('key', 3600, 'value'); // sets key → value, with 1h TTL.
	 */
	public function setex($key,$ttl,$value){
		return $this->executeCommand('SETEX',array($key,$ttl,$value));
	}
	
	/**
	 * @todo Set the string value in argument as value of the key, with a time to live. PSETEX uses a TTL in milliseconds.
	 * @param string $key
	 * @param int $ttl
	 * @param string $value
	 * @return Bool TRUE if the command is successful.
	 * @example
	 * $redis->psetex('key', 100, 'value'); // sets key → value, with 0.1 sec TTL.
	 */
	public function psetex($key,$ttl,$value){
		return $this->executeCommand('PSETEX',array($key,$ttl,$value));
	}
	
	/**
	 * @todo Sets multiple key-value pairs in one atomic command. MSETNX only returns TRUE if all the keys were set (see SETNX).
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 * @example
	 * $redis->setnx('key', 'value'); // return TRUE
	 * $redis->setnx('key', 'value'); // return FALSE
	 */
	public function setnx($key,$value){
		return (bool)$this->executeCommand('SETNX',array($key,$value));
	}
	
	

	//####################
	//Keys COMMANDS
	//####################
	
	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	public function del($key)
	{
		return (bool)$this->executeCommand('DEL',array($key));
	}
	
	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	public function delete($key)
	{
		return (bool)$this->executeCommand('DEL',array($key));
	}

	/**
	 * @todo Verify if the specified key exists.
	 * @param string $key 
	 * @return BOOL: If the key exists, return TRUE, otherwise return FALSE.
	 * @example
	 * $redis->set('key', 'value');
	 * $redis->exists('key'); //  TRUE
	 * $redis->exists('NonExistingKey'); // FALSE
	 */
	public function exists($key){
		return (bool)$this->executeCommand('EXISTS',array($key));
	}
	
	/**
	 * @todo  Increment the number stored at key by one. If the second argument is filled, it will be used as the integer value of the increment.
	 * @param string $key
	 * @return INT the new value
	 * @example
	 * $redis->incr('key1'); // key1 didn't exists, set to 0 before the increment 
	 * $redis->incr('key1'); // 2
	 */
	public function incr($key){
		return (int)$this->executeCommand('INCR',array($key));
	}
	
	/**
	 * @todo  Increment the number stored at key by one. If the second argument is filled, it will be used as the integer value of the increment.
	 * @param string $key
	 * @param int $value value that will be added to key
	 * @return INT the new value
	 * @example
	 * $redis->incr('key1'); // key1 didn't exists, set to 0 before the increment
	 * $redis->incrBy('key1',2); // 3
	 */
	public function incrBy($key,$value){
		return (int)$this->executeCommand('INCRBY',array($key,$value));
	}
	
	/**
	 * @todo Increment the key with floating point precision.
	 * @param string $key
	 * @param float $value (float) value that will be added to the key
	 * @return  FLOAT the new value
	 * @example 
	 * $redis->incrByFloat('key1', 1.5); //key1 didn't exist, so it will now be 1.5 
	 * $redis->incrByFloat('key1', 1.5); //3 
	 * $redis->incrByFloat('key1', -1.5); //1.5 
	 */
	public function incrByFloat($key,$value){
		return (float)$this->executeCommand('INCRBYFLOAT',array($key,$value));
	}
	
	/**
	 * @todo  Decrement the number stored at key by one. If the second argument is filled, it will be used as the integer value of the decrement.
	 * @param string $key
	 * @return INT the new value
	 * @example
	 * $redis->decr('key1'); // key1 didn't exists, set to 0 before the increment
	 * $redis->decr('key1'); // -2 
	 */
	public function decr($key){
		return (int)$this->executeCommand('DECR',array($key));
	}
	
	/**
	 * @todo  Decrement the number stored at key by one. If the second argument is filled, it will be used as the integer value of the decrement.
	 * @param string $key
	 * @param string $value value that will be substracted to key
	 * @return INT the new value
	 * @example
	 * $redis->decr('key1'); // key1 didn't exists, set to 0 before the increment
	 * $redis->decr('key1'); // -2
	 */
	public function decrBy($key,$value){
		return (int)$this->executeCommand('DECRBY',array($key,$value));
	}
	
	/**
	 * Retrieves multiple values from cache with the specified keys.
	 * @param array $keys a list of keys identifying the cached values
	 * @return array a list of cached values indexed by the keys
	 */
	public function mGet($keys)
	{
		$response=$this->executeCommand('MGET',$keys);
		$result=array();
		$i=0;
		foreach($keys as $key)
			$result[$key]=$response[$i++];
		return $result;
	}
	
	public function getMultiple($keys){
		return $this->mGet($keys); 
	}
	
	/**
	 * @todo Sets a value and returns the previous entry at that key.
	 * @param string $key 
	 * @param string $value
	 * @return A string, the previous value located at this key.
	 * @example 
	 * $redis->set('x', '42');
	 * $exValue = $redis->getSet('x', 'lol');  // return '42', replaces x by 'lol'
	 * $newValue = $redis->get('x')'       // return 'lol'
	 */
	public function getSet($key,$value){
		return $this->executeCommand('GETSET',array($key,$value));
	}
	
	/**
	 * @todo  Returns a random key.
	 * @param None
	 * @return STRING: an existing key in redis.
	 * @example 
	 * $key = $redis->randomKey();
	 * $surprise = $redis->get($key);  // who knows what's in there.
	 */
	public function randomKey(){
		return $this->executeCommand('RANDOMKEY');
	}
	
	/**
	 * @todo Moves a key to a different database.
	 * @param string $key
	 * @param int $dbindex  the database number to move the key to.
	 * @return BOOL: TRUE in case of success, FALSE in case of failure.
	 * @example
	 * $redis->select(0);  // switch to DB 0
	 * $redis->set('x', '42'); // write 42 to x
	 * $redis->move('x', 1);   // move to DB 1
	 * $redis->select(1);  // switch to DB 1
	 * $redis->get('x');   // will return 42
	 */
	public function move($key,$dbindex){
		return $this->executeCommand('MOVE',array($key,$dbindex));
	}
	
	/**
	 * @todo Renames a key.
	 * @param string $srckey the key to rename.
	 * @param string $dstkey the new name for the key.
	 * @return BOOL: TRUE in case of success, FALSE in case of failure.
	 * @example
	 * $redis->set('x', '42');
	 * $redis->rename('x', 'y');
	 * $redis->get('y');   // → 42
	 * $redis->get('x');   // → `FALSE`
	 */
	public function rename($srckey,$dstkey){
		return $this->executeCommand('RENAME',array($srckey,$dstkey));
	}
	
	public function renameKey($srckey,$dstkey){
		return $this->rename($srckey, $dstkey);
	}
	
	/**
	 * @todo Same as rename, but will not replace a key if the destination already exists. This is the same behaviour as setNx.
	 * 	@param string $srckey the key to rename.
	 * @param string $dstkey the new name for the key.
	 * @return BOOL: TRUE in case of success, FALSE in case of failure.
	 * @example
	 * $redis->set('x', '42');
	 * $redis->set('y', '43');
	 * $redis->renameNx('x', 'y');
	 * $redis->get('y');   // → 43
	 * $redis->get('x');   // → 42
	 */
	public function renameNx($srckey,$dstkey){
		return $this->executeCommand('RENAMENX',array($srckey,$dstkey));
	}

	/**
	 * @todo Sets an expiration date (a timeout) on an item. pexpire requires a TTL in milliseconds.
	 * @param string $key
	 * @param string $ttl
	 * @return BOOL: TRUE in case of success, FALSE in case of failure.
	 * @example 
	 * $redis->set('x', '42');
	 * $redis->expire('x', 3); // x will disappear in 3 seconds.
	 * sleep(5);               // wait 5 seconds
	 * $redis->get('x');       // will return `FALSE`, as 'x' has expired.
	 */
	public function expire($key,$ttl){
		return (bool)$this->executeCommand('EXPIRE',array($key,$ttl));
	}
	
	public function setTimeout($key,$ttl){
		return $this->expire($key,$ttl);
	}
	
	/**
	 * @todo Sets an expiration date (a timeout) on an item. pexpire requires a TTL in milliseconds.
	 * @param string $key
	 * @param string $ttl
	 * @return BOOL: TRUE in case of success, FALSE in case of failure.
	 * @example
	 * $redis->set('x', '42');
	 * $redis->pexpire('x', 3000); // x will disappear in 3 seconds.
	 * sleep(5);               // wait 5 seconds
	 * $redis->get('x');       // will return `FALSE`, as 'x' has expired.
	 */
	public function pexpire($key,$ttl){
		return (bool)$this->executeCommand('PEXPIRE',array($key,$ttl));
	}
	
	/**
	 * @todo Sets an expiration date (a timestamp) on an item. pexpireAt requires a timestamp in milliseconds.
	 * @param string $key The key that will disappear.
	 * @param int $unix_time  Unix timestamp. The key's date of death, in seconds from Epoch time.
	 * @return BOOL: TRUE in case of success, FALSE in case of failure.
	 * @example  
	 * $redis->set('x', '42');
	 * $now = time(NULL); // current timestamp
	 * $redis->expireAt('x', $now + 3);    // x will disappear in 3 seconds.
	 * sleep(5);               // wait 5 seconds
	 * $redis->get('x');       // will return `FALSE`, as 'x' has expired.
	 */
	public function expireAt($key,$unix_time){
		return (bool)$this->executeCommand('EXPIREAT',array($key,$unix_time));
	}
	
	/**
	 * @todo Sets an expiration date (a timestamp) on an item. pexpireAt requires a timestamp in milliseconds.
	 * @param string $key The key that will disappear.
	 * @param int $unix_time  Unix timestamp. The key's date of death, in seconds from Epoch time.
	 * @return BOOL: TRUE in case of success, FALSE in case of failure.
	 */
	public function pexpireAt($key,$unix_time){
		return (bool)$this->executeCommand('PEXPIREAT',array($key,$unix_time));
	}
	
	/**
	 * @todo Returns the keys that match a certain pattern.
	 * @param string  $pattern using '*' as a wildcard.
	 * @return Array of STRING: The keys that match a certain pattern.
	 * @example
	 * $allKeys = $redis->keys('*');   // all keys will match this.
	 * $keyWithUserPrefix = $redis->keys('user*');
	 */
	public function keys($pattern){
		return $this->executeCommand('KEYS',array($pattern));
	}
	
	public function getKeys($pattern){
		return $this->keys($pattern);
	}
	
	/**
	 * @todo Describes the object pointed to by a key.
	 * @param string $retrieve The information to retrieve (string). Info can be one of the following:encoding,refcount,idletime
	 * @param string $key 
	 * @return STRING for "encoding", LONG for "refcount" and "idletime", FALSE if the key doesn't exist.
	 * @example
	 * $redis->object("encoding", "l"); // → ziplist
	 * $redis->object("refcount", "l"); // → 1
	 * $redis->object("idletime", "l"); // → 400 (in seconds, with a precision of 10 seconds).
	 */
	public function object($retrieve,$key){
		return $this->executeCommand('OBJECT',array($retrieve,$key));
	}
	
	/**
	 * @todo Returns the type of data pointed by a given key.
	 * @param string $key
	 * @return Depending on the type of the data pointed by the key, this method will return the following value:
	 * string: Redis::REDIS_STRING
	 * set: Redis::REDIS_SET
	 * list: Redis::REDIS_LIST
	 * zset: Redis::REDIS_ZSET
	 * hash: Redis::REDIS_HASH
	 * other: Redis::REDIS_NOT_FOUND
	 * @example $redis->type('key');
	 */
	public function type($key){
		return $this->executeCommand('TYPE',array($key));
	}
	
	/**
	 * @todo Append specified string to the string stored in specified key.
	 * @param string $key
	 * @param string $value
	 * @return INTEGER: Size of the value after the append
	 * @example
	 * $redis->set('key', 'value1');
	 * $redis->append('key', 'value2'); //12
	 */
	public function append($key,$value){
		return (int)$this->executeCommand('APPEND',array($key,$value));
	}
	
	/**
	 * @todo Return a substring of a larger string
	 * @param string $key
	 * @param int $start
	 * @param int $end
	 * @return STRING: the substring
	 * @example
	 * $redis->set('key', 'string value');
	 * $redis->getRange('key', 0, 5); //'string'
	 * $redis->getRange('key', -5, -1); // 'value'
	 */
	public function getRange($key,$start,$end){
		return $this->executeCommand('GETRANGE',array($key,$start,$end));
	}
	
	/**
	 * @todo Changes a substring of a larger string.
	 * @param string $key
	 * @param int $start
	 * @param int $value
	 * @return INTEGER: the length of the string after it was modified.
	 * @example
	 * $redis->set('key', 'Hello world');
	 * $redis->setRange('key', 6, "redis"); // returns 11
	 * $redis->get('key'); // "Hello redis"
	 */
	public function setRange($key,$start,$value){
		return (int)$this->executeCommand('SETRANGE',array($key,$start,$value));
	}
	
	/**
	 * @param  Get the length of a string value.
	 * @param string  $key
	 * @return INTEGER
	 * @example
	 * $redis->set('key', 'value');
	 * $redis->strlen('key'); //5
	 */
	public function strlen($key){
		return (int)$this->executeCommand('STRLEN',array($key));
	}
	
	/**
	 * @todo Return a single bit out of a larger string
	 * @param string $key key
	 * @param int $offset
	 * @return LONG: the bit value (0 or 1)
	 * @example
	 * $redis->set('key', "\x7f"); // this is 0111 1111
	 * $redis->getBit('key', 0); //0
	 * $redis->getBit('key', 1); // 1
	 */
	public function getBit($key,$offset){
		return (int)$this->executeCommand('GETBIT',array($key,$offset));
	}
	
	/**
	 * @todo  Changes a single bit of a string.
	 * @param string $key key
	 * @param int $offset
	 * @param bool $value  bool or int (1 or 0)
	 * @return LONG: 0 or 1, the value of the bit before it was set.
	 * @example
	 * $redis->set('key', "*");    // ord("*") = 42 = 0x2f = "0010 1010"
	 * $redis->setBit('key', 5, 1); //returns 0
	 * $redis->setBit('key', 7, 1); // returns 0
	 * $redis->get('key'); // chr(0x2f) = "/" = b("0010 1111")
	 */
	public function setBit($key,$offset,$value){
		return (int)$this->executeCommand('SETBIT',array($key,$offset,$value));
	}

	/**
	 * @todo Bitwise operation on multiple keys.
	 * @param string $operation either "AND", "OR", "NOT", "XOR"
	 * @param string $ret_key return key
	 * @param string $key1,$key2.....
	 * @return LONG: The size of the string stored in the destination key.
	 */
	public function bitop($operation,$ret_key,$key){
		$keys = array_slice(func_get_args(), 2);
		return (int)$this->executeCommand('BITOP',array_merge(array($operation,$ret_key),$keys));
	}
	
	/**
	 * @todo Count bits in a string.
	 * @param string $key
	 * @return LONG: The number of bits set to 1 in the value behind the input key.
	 */
	public function bitcount($key){
		return (int)$this->executeCommand('BITCOUNT',array($key));
	}
	
	/**
	 * @todo Sort the elements in a list, set or sorted set.
	 * @param string  $key key
	 * @param array $options: array(key => value, ...) - optional, with the following keys and values:
	 *  'by' => 'some_pattern_*',
	 *  'limit' => array(0, 1),
	 *  'get' => 'some_other_pattern_*' or an array of patterns,
	 *  'sort' => 'asc' or 'desc',
	 *  'alpha' => TRUE,
	 *  'store' => 'external-key'
	 *  @return An array of values, or a number corresponding to the number of elements stored if that was used.
	 *  @example
	 *  $redis->delete('s');
	 *  $redis->sadd('s', 5);
	 *  $redis->sadd('s', 4);
	 *  $redis->sadd('s', 2);
	 *  $redis->sadd('s', 1);
	 *  $redis->sadd('s', 3);
	 *  var_dump($redis->sort('s')); // 1,2,3,4,5
	 *  var_dump($redis->sort('s', array('sort' => 'desc'))); // 5,4,3,2,1
	 *  var_dump($redis->sort('s', array('sort' => 'desc', 'store' => 'out'))); // (int)5
	 */
	public function sort($key,$options=array()){
	}
	

	/**
	 * @todo Returns the time to live left for a given key in seconds (ttl), or milliseconds (pttl).
	 * @param string $key key
	 * @return LONG: The time to live in seconds. If the key has no ttl, -1 will be returned, and -2 if the key doesn't exist.
	 *  @example $redis->ttl('key');
	 */
	public function ttl($key){
		return (int) $this->executeCommand('TTL',array($key));
	}
	
	/**
	 * @todo Returns the time to live left for a given key in seconds (ttl), or milliseconds (pttl).
	 * @param string $key key
	 * @return LONG: The time to live in seconds. If the key has no ttl, -1 will be returned, and -2 if the key doesn't exist.
	 *  @example $redis->pttl('key');
	 */
	public function pttl($key){
		return (int) $this->executeCommand('PTTL',array($key));
	}
	
	/**
	 * @todo  Remove the expiration timer from a key.
	 * @param string $key key
	 * @return BOOL: TRUE if a timeout was removed, FALSE if the key didn’t exist or didn’t have an expiration timer.
	 * @example $redis->persist('key');
	 */
	public function persist($key){
		return (int) $this->executeCommand('PERSIST',array($key));
	}
	
	/**
	 * @todo Sets multiple key-value pairs in one atomic command.
	 * @param array $values array(key => value, ...)
	 * @return Bool TRUE in case of success, FALSE in case of failure
	 * @example $redis->mset(array('key0' => 'value0', 'key1' => 'value1'));
	 */
	public function mset($values){
		$parameters = array();
		foreach ($values as $key=>$value){
			array_push($parameters,$key,$value);
		}
		return (int) $this->executeCommand('MSET',$parameters);
	}
	
	/**
	 * @todo MSETNX only returns TRUE if all the keys were set (see SETNX).
	 * @param array $values array(key => value, ...)
	 * @return Bool TRUE in case of success, FALSE in case of failure
	 *  @example $redis->msetnx(array('key0' => 'value0', 'key1' => 'value1'));
	 */
	public function msetnx(){
		$parameters = array();
		foreach ($values as $key=>$value){
			array_push($parameters,$key,$value);
		}
		return (int) $this->executeCommand('MSETNX',$parameters);
	}
	
	/**
	 * @todo  Dump a key out of a redis database, the value of which can later be passed into redis using the RESTORE command. The data that comes out of DUMP is a binary representation of the key as Redis stores it.
	 * @param string $key
	 * @return The Redis encoded value of the key, or FALSE if the key doesn't exist
	 * @example
	 * $redis->set('foo', 'bar');
	 * $val = $redis->dump('foo'); // $val will be the Redis encoded key value
	 */
	public function dump($key){
		return $this->executeCommand('DUMP',array($key));
	} 
	
	/**
	 * @todo Restore a key from the result of a DUMP operation.
	 * @param string $key The key name
	 * @param int $ttl How long the key should live (if zero, no expire will be set on the key)
	 * @param string $value The Redis encoded key value (from DUMP)
	 * @example
	 * $redis->set('foo', 'bar');
	 * $val = $redis->dump('foo');
	 * $redis->restore('bar', 0, $val); // The key 'bar', will now be equal to the key 'foo'
	 */
	public function restore($key,$ttl,$value){
		return $this->executeCommand('RESTORE',array($key,$ttl,$value));
	}

	/**
	 * @todo Migrates a key to a different Redis instance.
	 * @param string $host The destination host
	 * @param int $port The TCP port to connect to.
	 * @param string $key The key to migrate.
	 * @param int $destination_db The target DB.
	 * @param int $timeout The maximum amount of time given to this transfer.
	 * @example
	 * $redis->migrate('backup', 6379, 'foo', 0, 3600);
	 */
	public function migrate($host,$port,$key,$destination_db,$timeout){
		return $this->executeCommand('MIGRATE',array($host,$port,$key,$destination_db,$timeout));
	}
	
	
	//####################
	//HASH TABLE COMMANDS
	//####################
	
	/**
	 * @todo Removes a value from the hash stored at key. If the hash table doesn't exist, or the key doesn't exist, FALSE is returned. 
	 * @param string $key
	 * @param string $hashKey
	 * @return BOOL TRUE in case of success, FALSE in case of failures
	 */
	public function hDel($key,$hashKey){
		return (bool)$this->executeCommand('HDEL',array($key,$hashKey));
	}
	
	/**
	 * @todo Verify if the specified member exists in a key.
	 * @param string $key 
	 * @param string $memberKey
	 * @return BOOL: If the member exists in the hash table, return TRUE, otherwise return FALSE.
	 * @example
	 * $redis->hSet('h', 'a', 'x');
	 * $redis->hExists('h', 'a'); //  TRUE
	 * $redis->hExists('h', 'NonExistingKey'); //FALSE 
	 */
	public function hExists($key,$memberKey){
		return (bool)$this->executeCommand('HEXISTS',array($key,$memberKey));
	}
	
	
	/**
	 * @todo Gets a value from the hash stored at key. If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
	 * @param string $key
	 * @param string $hashKey
	 * @return STRING The value, if the command executed successfully BOOL FALSE in case of failure
	 */
	public function hGet($key,$hashKey){
		return $this->executeCommand('HGET',array($key,$hashKey));
	}
	
	/**
	 * @todo  Returns the whole hash, as an array of strings indexed by strings.
	 * @param string $key
	 * @return An array of elements, the contents of the hash.
	 * @example
	 * $redis->hSet('h', 'a', 'x');
	 * $redis->hSet('h', 'b', 'y');
	 * $redis->hSet('h', 'c', 'z');
	 * $redis->hSet('h', 'd', 't');
	 * var_dump($redis->hGetAll('h'));
	 * //output
	 * array(4) {
	 *   ["a"]=>
	 *     string(1) "x"
	 *   ["b"]=>
	 *    	string(1) "y"
	 *   ["c"]=>
	 *   	string(1) "z"
	 *   ["d"]=>
	 *   	string(1) "t"
	 *   }
	 */
	public function hGetAll($key){
		return $this->executeCommand('HGETALL',array($key));
	}

	/**
	 * @todo Increments the value of a member from a hash by a given amount.
	 * @param string $key
	 * @param string $member
	 * @param int $value  (integer) value that will be added to the member's value
	 * @return LONG the new value
	 * @example
	 * $redis->delete('h');
	 * $redis->hIncrBy('h', 'x', 2); // returns 2: h[x] = 2 now.
	 * $redis->hIncrBy('h', 'x', 1); // h[x] ← 2 + 1. Returns 3
	 */
	public function hIncrBy($key,$member,$value){
		return (int)$this->executeCommand('HINCRBY',array($key,$member,$value));
	}
	
	/**
	 * @todo Increments the value of a hash member by the provided float value
	 * @param string$key
	 * @param string $member
	 * @param float $value
	 * @return FLOAT the new value
	 * @example
	 * $redis->delete('h');
	 * $redis->hIncrByFloat('h','x', 1.5); // returns 1.5: h[x] = 1.5 now
	 * $redis->hIncrByFLoat('h', 'x', 1.5); // returns 3.0: h[x] = 3.0 now
	 * $redis->hIncrByFloat('h', 'x', -3.0); // returns 0.0: h[x] = 0.0 now
	 */
	public function hIncrByFloat($key,$member,$value){
		return (float)$this->executeCommand('HINCRBYFLOAT',array($key,$member,$value));
	}
	
	/**
	 * @todo Returns the keys in a hash, as an array of strings.
	 * @param string $key
	 * @return An array of elements, the keys of the hash. This works like PHP's array_keys().
	  * @example
	 * $redis->hSet('h', 'a', 'x');
	 * $redis->hSet('h', 'b', 'y');
	 * $redis->hSet('h', 'c', 'z');
	 * $redis->hSet('h', 'd', 't');
	 * var_dump($redis->hKeys('h'));
	 * //output
	 * array(4) {
	 *   ["0"]=>
	 *     string(1) "a"
	 *   ["1"]=>
	 *    	string(1) "b"
	 *   ["2"]=>
	 *   	string(1) "c"
	 *   ["3"]=>
	 *   	string(1) "d"
	 *   }
	 */
	public function hKeys($key){
		return $this->executeCommand('HKEYS',array($key));
	}
	
	/**
	 * @todo Returns the length of a hash, in number of items
	 * @param string $key
	 * @return LONG the number of items in a hash, FALSE if the key doesn't exist or isn't a hash.
	 * @example
	 * $redis->delete('h')
	 * $redis->hSet('h', 'key1', 'hello');
	 * $redis->hSet('h', 'key2', 'plop');
	 * $redis->hLen('h'); // returns 2
	 */
	public function hLen($key){
		return $this->executeCommand('HLEN',array($key));
	}
	
	/**
	 * @todo Retrieve the values associated to the specified fields in the hash.
	 * @param string $key
	 * @param array $memberKeys
	 * @return Array An array of elements, the values of the specified fields in the hash, with the hash keys as array keys.
	 * @example
	 * $redis->delete('h');
	 * $redis->hSet('h', 'field1', 'value1');
	 * $redis->hSet('h', 'field2', 'value2');
	 * $redis->hmGet('h', array('field1', 'field2'));//returns array('field1' => 'value1', 'field2' => 'value2')
	 */
	public function hMGet($key,$memberKeys){
		return $this->executeCommand('HMGET',array_merge(array($key),$memberKeys));
	}
	
	/**
	 * @todo  Fills in a whole hash. Non-string values are converted to string, using the standard (string) cast. NULL values are stored as empty strings.
	 * @param string $key
	 * @param array $memberKeys key → value array
	 * @return BOOL
	 * @example
	 * $redis->delete('user:1');$redis->hMset('user:1', array('name' => 'Joe', 'salary' => 2000));
	 * $redis->hIncrBy('user:1', 'salary', 100); // Joe earns 100 more now.
	 */
	public function hMSet($key,$memberKeys){
		$args = array();
		foreach ($memberKeys as $k=>$v){
			$args[] = $k;
			$args[] = $v;
		}
		return (bool)$this->executeCommand('HMSET',array_merge(array($key),$args));
	}
	
	/**
	 * @todo Adds a value to the hash stored at key. If this value is already in the hash, FALSE is returned.
	 * @param string $key
	 * @param string $hashKey
	 * @param string $value
	 * @return LONG 1 if value didn't exist and was added successfully, 0 if the value was already present and was replaced, FALSE if there was an error.
	 * @example
	 * $redis->delete('h')
	 * $redis->hSet('h', 'key1', 'hello'); // 1, 'key1' => 'hello' in the hash at "h"
	 * $redis->hGet('h', 'key1'); // returns "hello"
	 * $redis->hSet('h', 'key1', 'plop'); // 0, value was replaced.
	 * $redis->hGet('h', 'key1'); // returns "plop"
	 */
	public function hSet($key,$hashKey,$value){
		return (int)$this->executeCommand('HSET',array($key,$hashKey,$value));
	}
	
	/**
	 * @todo Adds a value to the hash stored at key only if this field isn't already in the hash.
	 * @param string $key
	 * @param string $hashKey
	 * @param string $value
	 * @return BOOL TRUE if the field was set, FALSE if it was already present.
	 * @example
	 * $redis->delete('h')
	 * $redis->hSetNx('h', 'key1', 'hello'); // TRUE, 'key1' => 'hello' in the hash at "h" 
	 * $redis->hSetNx('h', 'key1', 'world'); // FALSE, 'key1' => 'hello' in the hash at "h". No change since the field wasn't replaced. 
	 */
	public function hSetNx($key,$hashKey,$value){
		return (bool)$this->executeCommand('HSETNX',array($key,$hashKey,$value));
	}
	
	/**
	 * @todo Returns the values in a hash, as an array of strings.
	 * @param string $key
	 * @return An array of elements, the values of the hash. This works like PHP's array_values().
	 * @example
	 * $redis->hSet('h', 'a', 'x');
	 * $redis->hSet('h', 'b', 'y');
	 * $redis->hSet('h', 'c', 'z');
	 * $redis->hSet('h', 'd', 't');
	 * var_dump($redis->hVals('h'));
	 * //output
	 * array(4) {
	 *   ["0"]=>
	 *     string(1) "x"
	 *   ["1"]=>
	 *    	string(1) "y"
	 *   ["2"]=>
	 *   	string(1) "z"
	 *   ["3"]=>
	 *   	string(1) "t"
	 *   }
	 */
	public function hVals($key){
		return $this->executeCommand('HVALS',array($key));
	}
	
	//####################
	//LIST COMMANDS
	//####################
	
	/**
	 * @todo  Is a blocking lPop(rPop) primitive. If at least one of the lists contains at least one element, the element will be popped from the head of the list and returned to the caller. Il all the list identified by the keys passed in arguments are empty, blPop will block during the specified timeout until an element is pushed to one of those lists. This element will be popped.
	 * @param ARRAY Array containing the keys of the lists 
	 * @param INTEGER Timeout Or STRING Key1 STRING Key2 STRING Key3 ... STRING Keyn 
	 * @param INTEGER Timeout
	 * @return ARRAY array('listName', 'element')
	 * @example
	 * //Non blocking feature 
	 * $redis->lPush('key1', 'A');
	 * $redis->delete('key2');
	 * $redis->blPop('key1', 'key2', 10); //array('key1', 'A') 
	 * //OR 
	 * $redis->blPop(array('key1', 'key2'), 10); //array('key1', 'A') 
	 * $redis->brPop('key1', 'key2', 10); //array('key1', 'A') 
	 * //OR 
	 * $redis->brPop(array('key1', 'key2'), 10); //array('key1', 'A') 
	 * //Blocking feature 
	 * //process 1 
	 * $redis->delete('key1');
	 * $redis->blPop('key1', 10);
	 * //blocking for 10 seconds 
	 * //process 2 
	 * $redis->lPush('key1', 'A');
	 * //process 1 
	 * //array('key1', 'A') is returned
	 */
	public function blPop(){
		$args = func_get_args();
		$argc = func_num_args();
		if( $argc <2 )
			throw new MRedisException('blPop parameters error. eg: $redis->blPop(\'key1\', 10);');
				
		$timeout = array_pop($args);
		if( !is_int($timeout) ) throw new MRedisException('blPop parameters error. eg: $redis->blPop(\'key1\', 10);');
		
		if( !is_array( $args[0]) )  {
			array_push($args, $timeout);
			return $this->executeCommand('BLPOP',$args);
		}else{
			array_push($args[0], $timeout);
			return $this->executeCommand('BLPOP',$args[0]);
		}
	}
	
	/**
	 * @todo reference blPop()
	 */
	public function brPop(){
		$args = func_get_args();
		$argc = func_num_args();
		if( $argc <2 )
			throw new MRedisException('brPop parameters error. eg: $redis->brPop(\'key1\', 10);');
		
		$timeout = array_pop($args);
		if( !is_int($timeout) ) throw new MRedisException('brPop parameters error. eg: $redis->brPop(\'key1\', 10);');
		
		if( !is_array( $args[0]) )  {
			array_push($args, $timeout);
			return $this->executeCommand('BRPOP',$args);
		}else{
			array_push($args[0], $timeout);
			return $this->executeCommand('BRPOP',$args[0]);
		}
	}
	
	/**
	 * @todo  A blocking version of rpoplpush, with an integral timeout in the third parameter.
	 * @param string $srckey
	 * @param string $dstkey
	 * @param int $timeout
	 * @return STRING The element that was moved in case of success, FALSE in case of timeout.
	 */
	public function  brpoplpush($srckey,$dstkey,$timeout){
		return $this->executeCommand('BRPOPLPUSH',array($srckey,$dstkey,$timeout));
	}
	
	/**
	 * @todo Return the specified element of the list stored at the specified key. 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ... Return FALSE in case of a bad index or a key that doesn't point to a list.
	 * @param string $key
	 * @param int $index
	 * @return String the element at this index. Bool FALSE if the key identifies a non-string data type, or no value corresponds to this index in the list Key.
	 * @example 
	 * $redis->rPush('key1', 'A');
	 * $redis->rPush('key1', 'B');
	 * $redis->rPush('key1', 'C'); //key1 => [ 'A', 'B', 'C' ]
	 * $redis->lGet('key1', 0); //'A'
	 * $redis->lGet('key1', -1); //'C'
	 * $redis->lGet('key1', 10); //`FALSE`
	 */
	public function lIndex($key,$index){
		return $this->executeCommand('LINDEX',array($key,$index));
	}
	
	/**
	 * @todo reference lIndex()
	 */
	public function lGet($key,$index){
		return $this->lIndex($key,$index);
	}
	
	/**
	 * @todo  Insert value in the list before or after the pivot value.The parameter options specify the position of the insert (before or after). If the list didn't exists, or the pivot didn't exists, the value is not inserted.
	 * @param string $key
	 * @param STRING $position Redis::$BEFORE | Redis::$AFTER
	 * @param int $pivot
	 * @param string $value
	 * @return The number of the elements in the list, -1 if the pivot didn't exists.
	 * @example
	 * $redis->delete('key1');
	 * $redis->lInsert('key1', Redis::AFTER, 'A', 'X'); // 0
	 * $redis->lPush('key1', 'A');
	 * $redis->lPush('key1', 'B');
	 * $redis->lPush('key1', 'C');
	 * $redis->lInsert('key1', Redis::BEFORE, 'C', 'X'); // 4
	 * $redis->lRange('key1', 0, -1); // array('A', 'B', 'X', 'C')
	 * 
	 * $redis->lInsert('key1', Redis::AFTER, 'C', 'Y'); // 5
	 * $redis->lRange('key1', 0, -1); // array('A', 'B', 'X', 'C', 'Y')
	 * $redis->lInsert('key1', Redis::AFTER, 'W', 'value'); // -1
	 */
	public function lInsert($key,$position,$pivot,$value){
			return $this->executeCommand('LINDEX',array($key,$position,$pivot,$value));
	}
	
	/**
	 * @todo Returns the size of a list identified by Key.If the list didn't exist or is empty, the command returns 0. If the data type identified by Key is not a list, the command return FALSE.
	 * @param string $key
	 * @return LONG The size of the list identified by Key exists. BOOL FALSE if the data type identified by Key is not list
	 * @example 
	 * $redis->rPush('key1', 'A');
	 * $redis->rPush('key1', 'B');
	 * $redis->rPush('key1', 'C'); // key1 => [ 'A', 'B', 'C' ] 
	 * $redis->lSize('key1');// 3 
	 * $redis->rPop('key1'); 
	 * $redis->lSize('key1');// 2 
	 */
	public function lLen($key){
		return (int)$this->executeCommand('LLEN',array($key));
	}
	
	/**
	 * @todo reference lLen()
	 */
	public function lSize($key){
		return $this->lLen($key);
	}
	
	/**
	 * @return  Return and remove the first element of the list.
	 * @param string $key 
	 * @return STRING if command executed successfully BOOL FALSE in case of failure (empty list)
	 * @example 
	 * $redis->rPush('key1', 'A');
	 * $redis->rPush('key1', 'B');
	 * $redis->rPush('key1', 'C'); // key1 => [ 'A', 'B', 'C' ] 
	 * $redis->lPop('key1'); // key1 => [ 'B', 'C' ] 
	 */
	public function lPop($key){
		return $this->executeCommand('LPOP',array($key));
	}
	
	/**
	 * @todo Adds the string value to the head (left) of the list. Creates the list if the key didn't exist. If the key exists and is not a list, FALSE is returned.
	 * @param string $key
	 * @param string $value
	 * @return LONG The new length of the list in case of success, FALSE in case of Failure.
	 * @example
	 * $redis->delete('key1');
	 * $redis->lPush('key1', 'C'); // returns 1
	 * $redis->lPush('key1', 'B'); // returns 2
	 * $redis->lPush('key1', 'A'); // returns 3
	 * // key1 now points to the following list: [ 'A', 'B', 'C' ] 
	 */
	public function lPush($key,$value){
		return (int)$this->executeCommand('LPUSH',array($key,$value));
	}

	/**
	 * @todo Adds the string value to the head (left) of the list if the list exists.
	 * @param string $key
	 * @param string $value value to push in key
	 * @return LONG The new length of the list in case of success, FALSE in case of Failure.
	 * @example
	 * $redis->delete('key1');
	 * $redis->lPushx('key1', 'A'); // returns 0
	 * $redis->lPush('key1', 'A'); // returns 1
	 * $redis->lPushx('key1', 'B'); // returns 2
	 * $redis->lPushx('key1', 'C'); // returns 3
	 * // key1 now points to the following list: [ 'A', 'B', 'C' ]
	 */
	public function lPushx($key,$value){
		return (int)$this->executeCommand('LPUSHX',array($key,$value));
	}
	
	/**
	 * @todo Returns the specified elements of the list stored at the specified key in the range [start, end]. start and stop are interpretated as indices: 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
	 * @param string $key
	 * @param int $start
	 * @param int $end
	 * @return Array containing the values in specified range.
	 * @example
	 * $redis->rPush('key1', 'A');
	 * $redis->rPush('key1', 'B');
	 * $redis->rPush('key1', 'C');
	 * $redis->lRange('key1', 0, -1); // array('A', 'B', 'C')
	 */
	public function lRange($key,$start,$end){
		return $this->executeCommand('LRANGE',array($key,$start,$end));
	}
	
	/**
	 * @todo reference lRange()
	 */
	public function lGetRange($key,$start,$end){
		return $this->lRange( $key,$start,$end);
	}
	
	/**
	 * @todo Removes the first count occurences of the value element from the list. If count is zero, all the matching elements are removed. If count is negative, elements are removed from tail to head. The argument order is not the same as in the Redis documentation. This difference is kept for compatibility reasons. 
	 * @param string $key
	 * @param string $value
	 * @param int $count
	 * @return LONG the number of elements to remove. BOOL FALSE if the value identified by key is not a list.
	 * @example
	 * $redis->lPush('key1', 'A');
	 * $redis->lPush('key1', 'B');
	 * $redis->lPush('key1', 'C'); 
	 * $redis->lPush('key1', 'A'); 
	 * $redis->lPush('key1', 'A'); 
	 * $redis->lRange('key1', 0, -1); //array('A', 'A', 'C', 'B', 'A') 
	 * $redis->lRem('key1', 'A', 2); //2 
	 * $redis->lRange('key1', 0, -1); //array('C', 'B', 'A') 
	 */
	public function lRem($key,$value,$count){
		return (int)$this->executeCommand('LREM',array($key,$value,$count));
	}
	
	/**
	 * @todo reference lRem()
	 */
	public function lRemove($key,$value,$count){
		return $this->lRem($key,$value,$count);
	}
	
	/**
	 * @todo Set the list at index with the new value.
	 * @param string $key
	 * @param int $index
	 * @param string $value
	 * @return BOOL TRUE if the new value is setted. FALSE if the index is out of range, or data type identified by key is not a list.
	 * @example
	 * $redis->rPush('key1', 'A');
	 * $redis->rPush('key1', 'B');
	 * $redis->rPush('key1', 'C'); //key1 => [ 'A', 'B', 'C' ] 
	 * $redis->lGet('key1', 0); //'A' 
	 * $redis->lSet('key1', 0, 'X');
	 * $redis->lGet('key1', 0); //'X'  
	 */
	public function lSet($key,$index,$value){
		return (bool)$this->executeCommand('LSET',array($key,$index,$value));
	}
	
	/**
	 * @todo Trims an existing list so that it will contain only a specified range of elements.
	 * @param string $key
	 * @param int $start
	 * @param int $stop
	 * @return Array Bool return FALSE if the key identify a non-list value.
	 * @example
	 * $redis->rPush('key1', 'A');
	 * $redis->rPush('key1', 'B');
	 * $redis->rPush('key1', 'C');
	 * $redis->lRange('key1', 0, -1); // array('A', 'B', 'C') 
	 * $redis->lTrim('key1', 0, 1);
	 * $redis->lRange('key1', 0, -1); // array('A', 'B') 
	 */
	public function lTrim($key,$start,$stop){
		return $this->executeCommand('LTRIM',array($key,$start,$stop));
	}
	
	/**
	 * @todo reference lTrim() 
	 */
	public function listTrim($key,$start,$stop){
		return $this->lTrim($key,$start,$stop);
	}
	
	/**
	 * @todo Returns and removes the last element of the list.
	 * @param string $key
	 * @return STRING if command executed successfully BOOL FALSE in case of failure (empty list)
	 * @example
	 * $redis->rPush('key1', 'A');
	 * $redis->rPush('key1', 'B');
	 * $redis->rPush('key1', 'C'); // key1 => [ 'A', 'B', 'C' ] 
	 * $redis->rPop('key1'); // key1 => [ 'A', 'B' ] 
	 */
	public function rPop($key){
		return $this->executeCommand('RPOP',array($key));
	}
	
	/**
	 * @todo  Pops a value from the tail of a list, and pushes it to the front of another list. Also return this value. (redis >= 1.1)
	 * @param string $srckey
	 * @param string $dstkey
	 * @return STRING The element that was moved in case of success, FALSE in case of failure.
	 * @example
	 * $redis->delete('x', 'y');
	 * 
	 * $redis->lPush('x', 'abc');
	 * $redis->lPush('x', 'def');
	 * $redis->lPush('y', '123');
	 * $redis->lPush('y', '456');
	 * // move the last of x to the front of y.
	 * var_dump($redis->rpoplpush('x', 'y'));
	 * var_dump($redis->lRange('x', 0, -1));
	 * var_dump($redis->lRange('y', 0, -1));
	 * //Output
	 * string(3) "abc"
	 * array(1) {
	 *   [0]=>
	 *     string(3) "def"
	 *  }
	 * array(3) {
	 * [0]=>
	 * 		string(3) "abc"
	 * [1]=>
	 * 		string(3) "456"
	 * [2]=>
	 * 		string(3) "123"
	 * }
	 * 
	 */
	public function rpoplpush($srckey,$dstkey){
		return $this->executeCommand('RPOPLPUSH',array($srckey,$dstkey));
	}
	
	/**
	 * @todo Adds the string value to the tail (right) of the list. Creates the list if the key didn't exist. If the key exists and is not a list, FALSE is returned.
	 * @param string $key 
	 * @param string $value
	 * @return LONG The new length of the list in case of success, FALSE in case of Failure.
	 * @example
	 * $redis->delete('key1');
	 * $redis->rPush('key1', 'A'); // returns 1
	 * $redis->rPush('key1', 'B'); // returns 2
	 * $redis->rPush('key1', 'C'); // returns 3
	 * //key1 now points to the following list: [ 'A', 'B', 'C' ] 
	 */
	public function rPush($key,$value){
		return $this->executeCommand('RPUSH',array($key,$value));
	}
	
	/**
	 * @todo  Adds the string value to the tail (right) of the list if the ist exists. FALSE in case of Failure.
	 * @param string $key
	 * @param string $value
	 * @return LONG The new length of the list in case of success, FALSE in case of Failure.
	 * @example
	 * $redis->delete('key1');
	 * $redis->rPushx('key1', 'A'); // returns 0
	 * $redis->rPush('key1', 'A'); // returns 1
	 * $redis->rPushx('key1', 'B'); // returns 2
	 * $redis->rPushx('key1', 'C'); // returns 3
	 * //key1 now points to the following list: [ 'A', 'B', 'C' ]
	 */
	public function rPushx($key,$value){
		return $this->executeCommand('RPUSHX',array($key,$value));
	}
	
	
	//####################
	//Sets COMMANDS
	//####################
	
	/**
	 * @todo Adds a value to the set value stored at key. If this value is already in the set, FALSE is returned.
	 * @param string $key
	 * @param string $value
	 * @return LONG the number of elements added to the set.
	 * @example
	 * $redis->sAdd('key1' , 'member1'); // 1, 'key1' => {'member1'} 
	 * $redis->sAdd('key1' , 'member2', 'member3'); // 2, 'key1' => {'member1', 'member2', 'member3'}
	 * $redis->sAdd('key1' , 'member2'); // 0, 'key1' => {'member1', 'member2', 'member3'}
	 */
	public function sAdd($key,$value){
		
	}
	
	/**
	 * @todo Returns the cardinality of the set identified by key.
	 * @param string $key
	 * @return LONG the cardinality of the set identified by key, 0 if the set doesn't exist.
	 * @example
	 * $redis->sAdd('key1' , 'member1');
	 * $redis->sAdd('key1' , 'member2');
	 * $redis->sAdd('key1' , 'member3'); // 'key1' => {'member1', 'member2', 'member3'}
	 * $redis->sCard('key1'); //3
	 * $redis->sCard('keyX'); // 0 
	 */
	public function sCard($key){
		
	}
	
	/**
	 * @todo reference sCard()
	 */
	public function sSize(){}
	
	/**
	 * @todo Performs the difference between N sets and returns it.
	 * @param string Keys: key1, key2, ... , keyN: Any number of keys corresponding to sets in redis.
	 * @return Array of strings: The difference of the first set will all the others.
	 * @example
	 * $redis->delete('s0', 's1', 's2');
	 * $redis->sAdd('s0', '1');
	 * $redis->sAdd('s0', '2');
	 * $redis->sAdd('s0', '3');
	 * $redis->sAdd('s0', '4');
	 * 
	 * $redis->sAdd('s1', '1');
	 * $redis->sAdd('s2', '3');
	 * var_dump($redis->sDiff('s0', 's1', 's2'));
	 * //Return value: all elements of s0 that are neither in s1 nor in s2.
	 * array(2) {
	 *   [0]=>
	 *     string(1) "4"
	 *   [1]=>
	 *     string(1) "2"
	 * }
	 */
	public function sDiff(){
		
	}
	
	public function sDiffStore(){}
	
	public function sInter(){}
	
	public function sInterStore(){}
	
	public function sIsMember(){}
	
	public function sContains(){}
	
	public function sMembers(){}
	
	public function sGetMembers(){}
	
	public function sMove(){}
	
	public function sPop(){}
	
	public function sRandMember(){}
	
	public function sRem(){}
	
	public function sRemove(){}
	
	public function sUnion(){}
	
	public function sUnionStore(){}
	
	
	//####################
	//Sorted sets COMMANDS
	//####################
	
	public function zAdd(){}
	public function zCard(){}
	public function zSize(){}
	public function zCount(){}
	public function zIncrBy(){}
	public function zInter(){}
	public function zRange(){}
	public function zRangeByScore(){}
	public function zRevRangeByScore(){}
	public function zRank(){}
	public function zRem(){}
	public function zDelete(){}
	public function zRemRangeByRank(){}
	public function zDeleteRangeByRank(){}
	public function zRemRangeByScore(){}
	public function zRevRange(){}
	public function zScore(){}
	public function zUnion(){}
	
	//####################
	//Sorted sets COMMANDS
	//####################
	public function psubscribe(){}
	public function publish(){}
	public function subscribe(){}
	
	
	//####################
	//Transactions COMMANDS
	//####################
	public function multi(){}
	public function exec(){}
	public function discard(){}
	public function watch(){}
	public function unwatch(){}
	
	//####################
	//Scripting COMMANDS
	//####################
	public function eval1(){}
	public function evalSha(){}
	public function script(){}
	public function client(){}
	public function getLastError(){}
	public function clearLastError(){}
	
	public function _prefix(){}
	
	public function _unserialize(){}
	
	
	//####################
	//Introspection Functions
	//####################
	public function isConnected(){}
	
	public function getHost(){}
	
	public function getPort(){}
	
	public function getDBNum(){}
	
	public function GetTimeout(){}
	
	public function GetReadTimeout(){}
	
	public function GetPersistentID(){}
	
	public function GetAuth(){}
}

class RedisException extends Exception{}

}

try{
	$redis = new Redis();
	$redis->connect('127.0.0.1', 6379);
	// $redis->select(2);
// 	echo $redis->ping();
// echo $redis->echo('hello world');
// echo $redis->bgrewriteaof();
	// $redis->set('test','value');
	// echo $redis->get('test');
	// $redis->del('test');
// 	var_dump( $redis->append('key','value') );
// echo $redis->getRange('key',0,-1);
// 	echo $redis->setRange('key',0,'hello');
// 	echo $redis->strlen('key');
// echo $redis->ttl('esy');
	$redis->lPush('key1', 'A');
	var_dump( $redis->blPop('key1', 'key2', 10) );
}catch (RedisException $e){
	echo $e->getMessage().' Error Code:' .$e->getCode().' File:'.$e->getFile().' At Line:'.$e->getLine();
}


