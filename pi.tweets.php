<?php

class Plugin_tweets extends Plugin {

	var $meta = array(
		'name'       => 'Tweets',
		'version'    => '1.0',
		'author'     => 'Alex Glover',
		'author_url' => 'http://alex-glover.com'
	);

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
		$username = $this->fetch_param('username');
		$count = ($this->fetch_param('count')) ? $this->fetch_param('count') : 3;
		$include_retweets = ($this->fetch_param('include_retweets')) ? $this->fetch_param('include_retweets') : true;
		$exclude_replies = ($this->fetch_param('exclude_replies')) ? $this->fetch_param('exclude_replies') : false;
		$include_entities = ($this->fetch_param('include_entities')) ? $this->fetch_param('include_entities') : true;

		$date_format = ($this->fetch_param('date_format')) ? $this->fetch_param('date_format') : 'F d, Y';

		// get the tagdata
		$content = $this->content;

		// if username is empty, bail out
		if(!$username)
		{
			// @todo better bailing out
			return false;
		}

		// create twitter REST API url
		// @link https://dev.twitter.com/docs/api/1/get/statuses/user_timeline

		$uri = "https://api.twitter.com/1/statuses/user_timeline.json";

		$config = array(
			'screen_name' => $username,
			'count' => $count,
			'include_rts' => $include_retweets,
			'exclude_replies' => $exclude_replies,
			'include_entities' => $include_entities,
		);

		// create the request uri for twitter
		$request_uri = $uri . '?' . http_build_query($config);

		$twitter_json = file_get_contents($request_uri);

		$tweets = json_decode($twitter_json, true);

		// is this hacky?  I am just reformatting some of the data
		foreach($tweets as $index => $tweet)
		{
			$tweets[$index]['time_ago'] = $this->time_ago($tweet['created_at']);

			if($this->fetch_param('date_format'))
			{
				$tweets[$index]['created_at'] = date($date_format, strtotime($tweet['created_at'])); 				
			}
			
			$tweets[$index]['text'] = $this->convert_links($tweet['text']);

			//$content = $this->parse_loop($content, $tweet['user']);
		}

		return $this->parse_loop($content, $tweets);
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