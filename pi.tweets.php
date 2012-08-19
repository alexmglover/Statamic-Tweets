<?php

class Plugin_tweets extends Plugin {

	var $meta = array(
		'name'       => 'Tweets',
		'version'    => '1.0',
		'author'     => 'Alex Glover',
		'author_url' => 'http://alex-glover.com'
	);

	public $username;
	public $count;
	public $include_retweets;
	public $exclude_replies;
	public $include_entities;

	/**
	 * Displays the tweets
	 * 
	 * <code>
	 * {{ tweets: display username="{{username}}" count="5" include_rewtweets="false" date_format="Y-m-d" }}
	 * {{ /tweets:display }}
	 * </code>
	 */
	public function display()
	{
		$this->username   		= $this->fetch_param('username');
		$this->count      		= $this->fetch_param('count', 3, 'is_numeric'); # defaults to 3, validates as numeric
		$this->include_retweets = $this->fetch_param('include_retweets', true, false, true); # defaults to true, always returns boolean
		$this->exclude_replies  = $this->fetch_param('exclude_replies', false, false, true); #defaults to false, always returns boolean
		$this->include_entities = $this->fetch_param('include_entities', true, false, true); # defaults to true, always returns boolean
		$cache 			  		= $this->fetch_param('cache', false, false, true); # defaults to false
		$cache_expire			= $this->fetch_param('cache_expire', 10, 'is_numeric');

		// get the tagdata
		$content = $this->content;

		// if username is empty, bail out
		if(!$this->username)
		{
			// @todo better bailing out
			return false;
		}

		if($cache)
		{
			// check for the cache
			if(file_exists('_cache/twitter_cache'))
			{
				// if cache is expired
				$cache_expire_time = filemtime('_cache/twitter_cache') + ((int) $cache_expire * 60);

				if(time() > $cache_expire_time)
				{
					$tweets = $this->get_tweets();
				}
				else
				{
					// get the tweets from the file
					$tweets = json_decode(file_get_contents('_cache/twitter_cache'), true);
				}
			}
			else
			{
				$this->get_tweets();
			}

		}
		else
		{
			$tweets = $this->get_tweets();
		}

		// is this hacky?  I am just reformatting some of the data
		foreach($tweets as $index => $tweet)
		{
			$tweets[$index]['time_ago'] = $this->time_ago($tweet['created_at']);
			$tweets[$index]['text'] = $this->convert_links($tweet['text']);

			//$content = $this->parse_loop($content, $tweet['user']);
		}

		return $this->parse_loop($content, $tweets);
	}

	private function get_tweets()
	{
		// create twitter REST API url
		// @link https://dev.twitter.com/docs/api/1/get/statuses/user_timeline

		$uri = "https://api.twitter.com/1/statuses/user_timeline.json";

		$config = array(
			'screen_name'      => $this->username,
			'count'            => $this->count,
			'include_rts'      => $this->include_retweets,
			'exclude_replies'  => $this->exclude_replies,
			'include_entities' => $this->include_entities,
		);

		// create the request uri for twitter
		$request_uri = $uri . '?' . http_build_query($config);

		$twitter_json = file_get_contents($request_uri);

		$tweets = json_decode($twitter_json, true);

		file_put_contents('_cache/twitter_cache', $twitter_json);

		return $tweets;
	}

	/**
	 * Calculates the time ago
	 *
	 * @return String $timeago
	 */
	private function time_ago($date, $granularity = 2) 
	{
	    $date = strtotime($date);
	    $difference = time() - $date;
	    $return = "";

	    $periods = array(
	    	'decade' 	=> 315360000,
	        'year' 		=> 31536000,
	        'month' 	=> 2628000,
	        'week' 		=> 604800, 
	        'day' 		=> 86400,
	        'hour' 		=> 3600,
	        'minute' 	=> 60,
	        'second' 	=> 1
	    );
	                                 
	    foreach ($periods as $key => $value) 
	    {
	        if ($difference >= $value) 
	        {
	            $time = floor($difference/$value);
	            $difference %= $value;
	            $return .= ($return ? ' ' : '') . $time . ' ';
	            $return .= (($time > 1) ? $key.'s' : $key);
	            $granularity--;
	        }
	        if ($granularity == '0') { break; }
	    }
	    return $return;
	}

	/**
	 * Converts any twitter links into clickable links
	 */
	private function convert_links($text)
	{
		$text = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $text);
		$text = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $text);
		$text = preg_replace("/@(\w+)/", "<a href=\"http://www.twitter.com/\\1\" target=\"_blank\">@\\1</a>", $text);
		$text = preg_replace("/#(\w+)/", "<a href=\"http://search.twitter.com/search?q=\\1\" target=\"_blank\">#\\1</a>", $text);
		return $text;		
	}
}