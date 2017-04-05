<?php

namespace App\Http\Controllers;

use App\Tracking;
use MonkeyLearn\Client as MonkeyLearn;
use Codebird\Codebird;
use Illuminate\Http\Request;
use App\Constants;

class BotController extends Controller
{
	private function AuthenticateTwitter()
	{
		Codebird::setConsumerKey(env('TWITTER_CONSUMER_KEY'), env('TWITTER_CONSUMER_SECRET'));
    	$cb = Codebird::getInstance();
    	$cb->setReturnFormat(CODEBIRD_RETURNFORMAT_ARRAY);
    	$cb->setToken(env('TWITTER_APP_KEY'), env('TWITTER_APP_SECRET'));

    	return $cb;
	}

	public function getMentionData()
	{	
		$mentions = $this->AuthenticateTwitter()->statuses_mentionsTimeline();
		return $mentions;	
	}

	public function AnalyzeMentionData()
	{
		$ml = new MonkeyLearn(env('MONKEY_LEARN_APP'));
		$tweets = [];
		$analyzeData = [];
		if($this->getMentionData()){
			foreach($this->getMentionData() as $index => $mention) {
				if(isset($mention['id'])) {
					$tweets[] = [
						'id' => $mention['id'],
						'user_screen_name' => $mention['user']['screen_name'],
						'text' => $mention['text'],
					];
				}
			}
		}

		$tweetsText = array_map(function($tweet) {
			return $tweet['text'];
		}, $tweets);
		$analysis = $ml->classifiers->classify(env('MONKEY_LEARN_ANALYSIS_KEY'), $tweetsText, true);
		$analyzeData = ['tweets' => $tweets, 'analysis' => $analysis];
		return $analyzeData;
	}

	public function TrackMentionData($twitter_id=null)
	{
		Tracking::Create([
				'twitter_id' => $twitter_id,
		]);	
	}

	public function postReply()
	{
		foreach($this->AnalyzeMentionData()['tweets'] as $index => $tweet) {
			switch(strtolower($this->AnalyzeMentionData()['analysis']->result[$index][0]['label'])) {
				case 'positive':
					$emojiSet = Constants::$happyEmojis;
					break;
				case 'neutral':
					$emojiSet = Constants::$neutralEmojis;
					break;
				case 'negative':
					$emojiSet = Constants::$negativeEmojis;
					break;
			}
			//dd($tweet['user_screen_name']);
			// dd(html_entity_decode($emojiSet[rand(0, count($emojiSet)-1)]));
			$this->AuthenticateTwitter()->statuses_update([
				'status' => '@' . $tweet['user_screen_name'].' '.html_entity_decode($emojiSet[rand(0, count($emojiSet)-1)],0, 'UTF-8'),
				'in_reply_to_status_id' => $tweet['id'],
			]);
			$this->TrackMentionData($tweet['id']);
		}
	} 

    public function getBot()
    {
       	$this->postReply();
    	return view('bot');
    }
}
