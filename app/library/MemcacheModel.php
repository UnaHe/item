<?php
class MemcacheModel extends \Phalcon\Cache\Backend\Memcache {
	/**
	 * Default Values
	 */
	const DEFAULT_HOST = '127.0.0.1';
	const DEFAULT_PORT = 11211;
	const DEFAULT_PERSISTENT = true;
	const DEFAULT_WEIGHT = 1;
	const DEFAULT_TIMEOUT = 1;
	const DEFAULT_RETRY_INTERVAL = 15;
	const DEFAULT_STATUS = true;
	const DEFAULT_FAILURE_CALLBACK = null;
	const TAG_PREFIX = 'MemcacheModel_';
	
	/**
	 * Available options
	 *
	 * =====> (array) servers :
	 * an array of memcached server ; each memcached server is described by an associative array :
	 * 'host' => (string) : the name of the memcached server
	 * 'port' => (int) : the port of the memcached server
	 * 'persistent' => (bool) : use or not persistent connections to this memcached server
	 * 'weight' => (int) : number of buckets to create for this server which in turn control its
	 * probability of it being selected. The probability is relative to the total
	 * weight of all servers.
	 * 'timeout' => (int) : value in seconds which will be used for connecting to the daemon. Think twice
	 * before changing the default value of 1 second - you can lose all the
	 * advantages of caching if your connection is too slow.
	 * 'retry_interval' => (int) : controls how often a failed server will be retried, the default value
	 * is 15 seconds. Setting this parameter to -1 disables automatic retry.
	 * 'status' => (bool) : controls if the server should be flagged as online.
	 * 'failure_callback' => (callback) : Allows the user to specify a callback function to run upon
	 * encountering an error. The callback is run before failover
	 * is attempted. The function takes two parameters, the hostname
	 * and port of the failed server.
	 *
	 * =====> (boolean) compression :
	 * true if you want to use on-the-fly compression
	 *
	 * =====> (boolean) compatibility :
	 * true if you use old memcache server or extension
	 *
	 * @var array available options
	 */
	protected $__options = array (
			'servers' => array (
					array (
							'host' => self::DEFAULT_HOST,
							'port' => self::DEFAULT_PORT,
							'persistent' => self::DEFAULT_PERSISTENT 
					) 
			),
			
			// 'weight' => self::DEFAULT_WEIGHT,
			// 'timeout' => self::DEFAULT_TIMEOUT,
			// 'retry_interval' => self::DEFAULT_RETRY_INTERVAL,
			// 'status' => self::DEFAULT_STATUS,
			// 'failure_callback' => self::DEFAULT_FAILURE_CALLBACK
			
			'compression' => false,
			'compatibility' => false 
	);
	/**
	 * Constructor
	 *
	 * @param array $options
	 *        	associative array of options
	 * @throws Zend_Cache_Exception
	 * @return void
	 */
	public function __construct($frontend, $options = null) {
		if (! extension_loaded ( 'memcache' )) {
			throw new \Phalcon\Exception ( 'The memcache extension must be loaded for using this backend !' );
		}
		
		if (isset ( $options ['servers'] )) {
			parent::__construct ( $frontend, $options ['servers'] [0] );
			$this->_connect ();
			unset ( $options ['servers'] [0] );
			if (! empty ( $options ['servers'] )) {
				foreach ( $options ['servers'] as $server ) {
					if (! array_key_exists ( 'port', $server )) {
						$server ['port'] = self::DEFAULT_PORT;
					}
					if (! array_key_exists ( 'persistent', $server )) {
						$server ['persistent'] = self::DEFAULT_PERSISTENT;
					}
					$this->_memcache->addServer ( $server ['host'], $server ['port'], $server ['persistent'] );
				}
			}
		}
	}
		
	/**
	 * Save some string datas into a cache record
	 *
	 * Note : $data is always "string" (serialization is done by the
	 * core not by the backend)
	 *
	 * @param string $data
	 *        	Datas to cache
	 * @param string $id
	 *        	Cache id
	 * @param array $tags
	 *        	Array of strings, the cache record will be tagged by each string entry
	 * @param int $specificLifetime
	 *        	If != false, set a specific lifetime for this cache record (null => infinite lifetime)
	 * @return boolean True if no problem
	 */
	public function save($keyName = null, $content = null, $lifetime = 864000, $stopBuffer = null, $tags = array()) {
		// if ($this->__options ['compression']) {
		$flag = MEMCACHE_COMPRESSED;
		// } else {
// 		$flag = 0;
		// }
		
		// ZF-8856: using set because add needs a second request if item already exists
		$result = @$this->_memcache->set ( $keyName, $content , $flag, $lifetime );
		
		if (! empty ( $tags )) {
			foreach ( $tags as $v ) {
				$_tag = self::TAG_PREFIX.$v;
				$_caches = $this->get ( $_tag );
				if (! $_caches) {
					$_caches = array ();
				} else
					$_caches = unserialize ( $_caches );
				
				if (! in_array ( $keyName, $_caches ))
					$_caches [] = $keyName;
				$_caches = serialize ( $_caches );
				$this->_memcache->set ( $_tag, $_caches, $flag, $lifetime );
			}
		}
		
		return $result;
	}
	
	/**
	 * Returns a cached content
	 *
	 * @param int|string $keyName
	 * @param long $lifetime
	 * @return mixed
	 */
	public function get($keyName, $lifetime = null) {
		return $this->_memcache->get($keyName);
	}
	
	/**
	 * Remove a cache record
	 *
	 * @param string $id
	 *        	Cache id
	 * @return boolean True if no problem
	 */
	public function delete($id) {
		return $this->_memcache->delete ( $id, 0 );
	}
	
	/**
	 * Clean some cache records
	 *
	 * Available modes are :
	 * 'all' (default) => remove all cache entries ($tags is not used)
	 * 'old' => unsupported
	 * 'matchingTag' => unsupported
	 * 'notMatchingTag' => unsupported
	 * 'matchingAnyTag' => unsupported
	 *
	 * @param string $mode
	 *        	Clean mode
	 * @param array $tags
	 *        	Array of tags
	 * @throws Zend_Cache_Exception
	 * @return boolean True if no problem
	 */
	public function clean($mode = CacheBase::CLEANING_MODE_ALL, $tags = array()) {
		switch ($mode) {
			case CacheBase::CLEANING_MODE_ALL :
				return $this->_memcache->flush ();
				break;
			case CacheBase::CLEANING_MODE_TAG:
				foreach ( $tags as $v ) {
					$_tag = self::TAG_PREFIX.$v;
					$_caches = $this->get ( $_tag );
					if (! $_caches)
						continue;
					$_caches = unserialize ( $_caches );
					foreach ( $_caches as $cv ) {
						if ($cv == '')
							continue;
						$this->delete ( $cv );
					}
					$this->delete ( $_tag );
				}
				// $this->_log(self::TAGS_UNSUPPORTED_BY_CLEAN_OF_MEMCACHED_BACKEND);
				break;
			default :
				throw new \Phalcon\Exception ( 'Invalid mode for clean() method' );
				break;
		}
	}
	
	/**
	 * Immediately invalidates all existing items.
	 *
	 * @return boolean
	 */
	public function flush() {
		return $this->clean ();
	}
	/**
	 * Immediately invalidates all existing items.
	 *
	 * @return boolean
	 */
	public function getVersion() {
		return $this->_memcache->getVersion();
	}
}