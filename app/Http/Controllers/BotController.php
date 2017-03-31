<?php

namespace App\Http\Controllers;

use MonkeyLearn\Client as MonkeyLearn;
use Codebird\Codebird;
use Illuminate\Http\Request;
use App\Constants;

class BotController extends Controller
{
	private function AuthenticateTwitter()
	{
		Codebird::setConsumerKey('14GrWnkv2iiEheyDSuFsdhHAE', 'AvqfpEoCUi0xcX425iHXdP7roalU3r3lgqMpd97LIhLR7EbNcq');
    	$cb = Codebird::getInstance();
    	$cb->setReturnFormat(CODEBIRD_RETURNFORMAT_ARRAY);
    	$cb->setToken('844439493168021508-aIz8EdYvnL0SsWDZdcY7PR17uI1TyWL', 'sjUdzX0nHLr2aR7PaBdhpr8JDkOl3HfQA6gJGY9hdRvJX');

    	return $cb;
	}

	public function getMentionData()
	{	
		$mentions = $this->AuthenticateTwitter()->statuses_mentionsTimeline();
		if(!isset($mentions[0]))
		{
			return '';
		}
		return $mentions;	
	}

	public function AnalyzeMentionData()
	{
		$ml = new MonkeyLearn('3f091775337e6417988a19fd068e40ebf000b551');
		$tweets = [];
		$analyzeData = [];
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
		$analysis = $ml->classifiers->classify('cl_qkjxv9Ly', $tweetsText, true);
		$analyzeData = ['tweets' => $tweets, 'analysis' => $analysis];
		return $analyzeData;
	}

	public function postReply()
	{
		foreach($this->AnalyzeMentionData()['tweets'] as $index => $tweet) {
			switch(strtolower($this->AnalyzeMentionData()['analysis']->result[$index][0]['label'])) {
				case 'positive':
					$emojiset = Constants::$happyEmojis;
					break;
				case 'neutral':
					$emojiset = Constants::$neutralEmojis;
					break;
				case 'negative':
					$emojiset = Constants::$negativeEmojis;
					break;
				dd($emojiset);
			}
		}
	} 

    public function getBot()
    {
    	$this->postReply();
    	exit();
    	$this->AnalyzeMentionData();
    	return view('bot');
    }
}
