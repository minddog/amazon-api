<?php
/*
 * AmazonSearchAPI
 *
 * Copyright(c) 2009 Adam Ballai <adam@blackacid.org>
 */

class AmazonSearchAPIError extends Exception {}

class AmazonSearchAPI {
    private static $curl = null;
    
    public function __construct($access_key,
                                $associate_tag,
                                $cache_config = NULL)
    {
        if(empty($access_key) || empty($associate_tag))
            throw new AmazonSearchAPIError("Access key or associate tag missing");
        $this->access_key = $access_key;
        $this->associate_tag = $associate_tag;
        if(!empty($cache_config)) {
            $this->setup_memcache($cache_config['memcache_servers'],
                                  $cache_config['memcache_port'],
                                  $cache_config['key_prefix']);
        }
    }

    public function setup_memcache($memcache_servers, $memcache_port, $key_prefix) {
        $this->memcache = new Memcache();
        foreach ($memcache_servers as $memcache_server) {
            $this->memcache->addServer($memcache_server, $memcache_port);
        }
        $this->key_prefix = $key_prefix;
    }


    public function build_key($url, $req_per_hour=1) {
        $stamp = intval(time() * ($req_per_hour / 3600));
        return $this->key_prefix . ':' . $stamp . ':' . $url;
    }

    function fetch($url, $req_per_hour=1) {
        if(!$this->memcache) {
            return $this->perform_request($url);
        }
        
        $key = $this->build_key($url, $req_per_hour);
        $value = $this->memcache->get($key);
        if (!$value) {
            $value = $this->perform_request($url);
            $value = json_encode($value);
            $this->memcache->set($key, $value);
        }
        if (!$value) return null;
        return json_decode($value, true);
    }

    public function perform_request($url) {
        // Send the HTTP request.
        curl_setopt(self::$curl, CURLOPT_URL, $url);
        curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec(self::$curl);

        // Throw an exception on connection failure.
        if (!$response) throw new AmazonSearchAPIError('Connection failed');
        
        // Deserialize the response string and store the result.
        $result = json_decode($response, true);
        
        return $result;
    }
    
    public function __call($method, $args) {
        static $api_cumulative_time = 0;
        $time = microtime(true);
        
        // Initialize CURL if called for the first time.
        if (is_null(self::$curl)) {
            self::$curl = curl_init();
        }

        $args = $args[0];
        $args['token'] = $this->api_key;
        
        $url = ('http://ecs.amazonaws.com/onca/xml?Service=AWSECommerceService'
                . '&Operation='.$method
                . '&AssociateTag='.$this->associate_tag
                . '&AWSAccessKeyId='.$this->access_key
                . '&' . http_build_query($args));
        $result = $this->fetch($url);

        // If the result is a hash containing a key called 'error', assume
        // that an error occurred on the other end and throw an exception.
        return $result;        
    }

}

?>