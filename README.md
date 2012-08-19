# Statamic Tweets Plugin

Here is an example:

	<h2>Tweets</h2>
	<ul>
		{{ tweets:display username="{{ twitter_name }}" exclude_replies="false" cache="yes" cache_expire="10" }}

			<li>
				{{ text }}<br />
				<span class="tweet-date">posted {{ time_ago }} ago</span>
			</li>

		{{ /tweets:display }}
	</ul>

## Parameters

- username
- count
- exclude_replies
- include_retweets
- include_entities
- cache
- cache_expire

## Installation

Drop the `tweets` folder into the `_addons` directory.