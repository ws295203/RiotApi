<?php

namespace App\Http\Controllers;

use API\LeagueAPI\LeagueAPI;
use API\dbCall\dbCall;
use Illuminate\Http\Request;

class PagesController extends Controller
{
	public function home()
	{
		return view('welcome');
	}

	public function test()
	{
		return view('test');
	}

	public function Summoner(Request $name)
	{
		clock()->startEvent("PageControllerView", "PageControllerView");

		$summonerName = $name->get("name");

		// Init
		$lol = new LeagueAPI();
		$db = new dbCall();

		// Const?
		$region = 'eun1';
		$locale = 'en_GB';
		$version = '9.17.1';
		$limit = 2;
		
		
		// Load all static data for that specific page?
		$icons = $lol->getStaticProfileIcons($locale, $version);
		$summonerSpells = $lol->getStaticSummonerSpells($locale, $version);
		$staticChampions  =  $lol->getStaticChampions(true, $locale, $version);
		$staticItems =  $lol->getStaticItems($locale, $version);
		$staticRunes = $lol->getStaticRunesReforged($locale, $version);

		$summoner = $db->getSummoner($region, $summonerName);
		$matchlist = $db->getMatchlist($region, $summoner->accountId, $limit);


		// Load initial data
		// If we have a DB matchlist we try to get DB matchById
		if (isset($matchlist)) {
			$matchById = $db->getMatchById($region, $matchlist);
		}
		// We don't have the DB matchlist. Get it from API
		else {
			$matchlist = $lol->getMatchlist($region, $summoner->accountId, null, null, null, null, null, null, $limit);
			$db->setMatchlist($region,$matchlist,$summoner->accountId);

			$matchById = $db->getMatchById($region, $matchlist);
		}

		// find all the summonners from the games
		foreach ($matchById as $key => $value) {
			foreach ($value->participantIdentities as $key2 => $value2) {
				$summonerNameName[$key2] = $value2->player->summonerName;
			}
			$summonerNameObj[$key] = $summonerNameName;
		}
		// --------------------------------------------- // 
		$summonerLeagueTarget = $db->getLeagueSummonerSingle($region, $summoner->id);

		// $db->setLeagueBySummoner($region,$summonerLeague);		

		// 0 SOLO, 1 FLEX, 2 3v3, 3 TFT
		clock()->endEvent("PageControllerView");
		clock()->endEvent("SummonerView");
		

		
		return view('summoner')
		->with(['summoner' => $summoner])
		->with(['icons' => $icons])
		->with(['matchById' => $matchById])
		->with(['summonerSpells' => $summonerSpells])
		->with(['champions' => $staticChampions])
		->with(['items' => $staticItems])
		->with(['runes' => $staticRunes])
		->with(['summonerLeagueTarget' => $summonerLeagueTarget])
		->with(['sumonerNameObj' => $summonerNameObj]);
	}

	

	public function summonerChampions(Request $name)
	{
		$summonerName = $name->get("name");

		return view('summonerChampions')->with(['summonerName' => $summonerName]);
	}

	public function champions()
	{
		clock()->startEvent("champions", "champions list page");
		$file = file_get_contents('lolContent\data\en_GB\championFull.json');
		$file = json_decode($file, true);

		clock()->endEvent("champions");

		clock()->startEvent("view", "champions list view");
		return view('champions')->with(['champions' => $file]);
	}

	public function stats()
	{
		return view('stats');
	}

	public function leaderboards()
	{
		return view('leaderboards');
	}

	// The returned view will be dynamically created depending on the champion name selected
	public function championsStat($name)
	{
		return view('championStats')->with(['name' => $name]);
	}
}
