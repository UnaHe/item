<?php
class CacheBase extends \Phalcon\Cache\Backend implements \Phalcon\Cache\BackendInterface {
	/**
	 * Default Values
	 */
	const CLEANING_MODE_ALL = 1;
	const CLEANING_MODE_TAG = 2;

	/**
	 * @param $prefix
	 * @param string $params
	 * @return string
	 */

	public static function makeTag($prefix, $params = '') {
		return md5 ( $prefix . serialize($params) );
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
	public function save($keyName = null, $content = null, $lifetime = 864000, $stopBuffer = null, $tags = array()) {}
	/**
	 * Returns a cached content
	 *
	 * @param int|string $keyName
	 * @param long $lifetime
	 * @return mixed
	 */
	public function get($keyName, $lifetime = NULL) {}

	/**
	 * Remove a cache record
	 *
	 * @param string $id
	 *        	Cache id
	 * @return boolean True if no problem
	 */
	public function delete($id) {}

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
	public function clean($mode = self::CLEANING_MODE_ALL, $tags = array()) {}

	/**
	 * Immediately invalidates all existing items.
	 *
	 * @return boolean
	 */
	public function flush() {}
	/**
	 * Query the existing cached keys
	 *
	 * @param string $prefix
	 * @return array
	 */
	public function queryKeys($prefix = null) {
	}

	/**
	 * Checks if cache exists and it hasn't expired
	 *
	 * @param string $keyName
	 * @param long $lifetime
	 * @return boolean
	 */
	public function exists($keyName = null, $lifetime = null) {}
}