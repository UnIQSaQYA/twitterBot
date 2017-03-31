<?php

namespace App\Http\Controllers;

use Codebird\Codebird;
use Illuminate\Http\Request;

class BotController extends Controller
{

	/**
	 * It authenticates us to our twitter account with the help of consumer key and api key provided
	 * by twitter.
	 * Keys are placed in .env file for security proposes
	 */
	private function AuthenticateTwitter()
	{
		# Codebird static method setConsumerKey used to set the consumer key, 
		Codebird::setConsumerKey($_ENV['TWITTER_CONSUMER_KEY'], $_ENV['TWITTER_CONSUMER_SECRET']);
    	$cb = Codebird::getInstance();
    	# Set the return format to array
    	$cb->setReturnFormat(CODEBIRD_RETURNFORMAT_ARRAY);
    	#set token to authenticate
    	$cb->setToken($_ENV['TWITTER_APP_KEY'], $_ENV['TWITTER_APP_SECRET']);

    	return $cb;
	}

	/**
	 * After successfull authentication this method retrives post that mentions us
	 */
	public function getMentionData()
	{	
		$mentions = $this->AuthenticateTwitter()->statuses_mentionsTimeline();
		if(!isset($mentions[0]))
		{
			return;
		}
		return $mentions;	
	}

	public function AnalyzeMentionData()
	{
		$tweets = [];
		foreach($this->getMentionData() as $index => $mention) {
			if(isset($mention['id'])) {
				$tweets[] = [
					'id' => $mention['id'],
					'user_screen_name' => $mention['user'],
					'text' => $mention['text'],
				];
			}
		}

		$tweetsText = array_map(function($tweet) {
			return $tweet['text'];
		}, $tweets);
		dd($tweetsText);
	}

    public function getBot()
    {
    	$this->AnalyzeMentionData();
    	return view('bot');
    }
}
