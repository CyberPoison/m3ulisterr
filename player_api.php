<?php

require_once 'config.php';
require_once 'generate_live_playlist.php';
require_once 'aio_availability.php';

set_time_limit(0);

// The full movie/series playlist is tens of MB of JSON (and only grows as the
// TMDB catalog does) - get_vod_streams/get_series below load it, then run a
// preg_replace over the whole thing for any non-English account (to append
// &lang=... to every stream URL - see injectStreamLang() in config.php).
// Confirmed directly: on a stock 128M memory_limit, that preg_replace call
// fatals with "Allowed memory size exhausted" - silently, since production
// runs with error_reporting(0) - so a non-English account's client gets an
// HTTP 200 with a completely empty body and no movies/shows at all, while an
// English account (which skips the regex entirely - see injectStreamLang())
// never hits it. Setting this here guarantees enough headroom for this
// script's own known-heavy operation regardless of the host's php.ini
// default or whether an external memory_limit override (e.g.
// docker-entrypoint.sh's PHP_MEMORY_LIMIT) is actually in effect.
@ini_set('memory_limit', '1024M');

accessLog();


if (!$GLOBALS['DEBUG']) {
	error_reporting(0);
}


$BasePath = locateBaseURL();
$urlComponents = parse_url($BasePath);
$scheme = $urlComponents['scheme'];
$host = $urlComponents['host'];
$domain = $scheme . '://' . $host;

// The account tied to the "username" query param (see $xcAccounts in config.php).
// Its default language, if any, gets baked into every VOD/series stream link below.
$xcAccount = getXcAccount();

//Publicly exposed private API key for YouTube.
$yt_api_key = 'AIzaSyA-dlBUjVQeuc4a6ZN4RkNUYDFddrVLxrA';


// Removed global JSON header to allow other actions to set their own content type.

//Get and setup Live playlist and return json.
if (isset($_GET['action']) && $_GET['action'] == 'get_live_streams') {

	if (shouldUpdateLiveStreams()) {
		runLivePlaylistGenerate();
	}

	if ($_GET['type'] == 'm3u' || $_GET['type'] == 'm3u8') {

		$m3uCreate = file_get_contents('channels/live_playlist.m3u8');
		header('Content-Type: audio/x-mpegurl');
		echo $m3uCreate;

	} else {
		header('Content-Type: application/json');
		$jsonCreate = file_get_contents('channels/live_playlist.json');
		echo $jsonCreate;
	}
	exit();
}

//Setup live categories and return json.
if (isset($_GET['action']) && $_GET['action'] == 'get_live_categories') {

	if (shouldUpdateLiveStreams()) {
		runLivePlaylistGenerate();
	}

	header('Content-Type: application/json');
	echo file_get_contents('channels/get_live_categories.json');
	exit();
}

//Get movie categories from TMDB and return json.
if (isset($_GET['action']) && $_GET['action'] == 'get_vod_categories') {

	$genresUrl = "https://api.themoviedb.org/3/genre/movie/list?api_key=$apiKey&include_adult=false&language=$language";
	$fetchGenres = file_get_contents($genresUrl);
	$genresArray = json_decode($fetchGenres, true);

	$output = [
		[
			'category_id' => "999992",
			'category_name' => 'Now Playing',
			'parent_id' => 0
		],
		[
			'category_id' => "999991",
			'category_name' => 'Popular',
			'parent_id' => 0
		]
	];

	// Then append the genres from the loop
	foreach ($genresArray['genres'] as $genre) {
		$output[] = [
			'category_id' => (string) $genre['id'],
			'category_name' => $genre['name'],
			'parent_id' => 0
		];
	}

	if ($GLOBALS['INCLUDE_ADULT_VOD']) {
		$output[] = [
			'category_id' => "999993",
			'category_name' => 'XXX Adult Movies',
			'parent_id' => 0
		];

	}

	header('Content-Type: application/json');
	echo json_encode($output);
	exit();
}

//Get tv categories from TMDB and return json.
if (isset($_GET['action']) && $_GET['action'] == 'get_series_categories') {
	$genresUrl = "https://api.themoviedb.org/3/genre/tv/list?api_key=$apiKey&include_adult=false&language=$language";
	$fetchGenres = file_get_contents($genresUrl);
	$genresArray = json_decode($fetchGenres, true);

	$output = [];

	// Setup a top level category for the networks. 

	$tvNetworks = [
		"Apple Tv" => 2552,
		"Discovery" => 64,
		"Disney+" => 2739,
		"HBO" => 49,
		"History" => 65,
		"Hulu" => 453,
		"Investigation" => 244,
		"Lifetime" => 34,
		"Netflix" => 213,
		"Oxygen" => 132
	];

	$output[] = [
		'category_id' => "88883",
		'category_name' => 'On The Air',
		'parent_id' => 0
	];

	$output[] = [
		'category_id' => "88882",
		'category_name' => 'Top Rated',
		'parent_id' => 0
	];

	$output[] = [
		'category_id' => "88881",
		'category_name' => 'Popular',
		'parent_id' => 0
	];

	foreach ($tvNetworks as $networkName => $networkId) {
		$output[] = [
			'category_id' => "99999" . $networkId,
			'category_name' => $networkName,
			'parent_id' => 0
		];
	}

	// Then append the genres from the loop
	foreach ($genresArray['genres'] as $genre) {
		$output[] = [
			'category_id' => (string) $genre['id'],
			'category_name' => $genre['name'],
			'parent_id' => 0
		];
	}

	header('Content-Type: application/json');
	echo json_encode($output);
	exit();
}


// Send the request to the playlist.
if (isset($_GET['action']) && $_GET['action'] == 'get_vod_streams') {


	if ($GLOBALS['INCLUDE_ADULT_VOD'] && !$GLOBALS['userCreatePlaylist']) {
		// Only fetch from GitHub if the file doesn't exist locally (so we don't overwrite generated posters)
		if (!file_exists('adult-movies.json')) {
			$jsonUrl = "https://raw.githubusercontent.com/gogetta69/public-files/main/adult-movies.json";
			$jsonContent = file_get_contents($jsonUrl);

			if ($jsonContent !== false) {
				$BasePath = rtrim($BasePath, '/');
				$jsonContent = str_replace('[[SERVER_URL]]', $BasePath, $jsonContent);

				if (file_put_contents('adult-movies.json', $jsonContent) === false) {
					echo "Failed to save the modified JSON file.";
					exit;
				}
			} else {
				echo "Failed to load the JSON file.";
				exit;
			}
		}
	}

	if (!$GLOBALS['userCreatePlaylist']) {
		$jsonUrl = "https://raw.githubusercontent.com/gogetta69/public-files/main/playlist.json";
		$jsonContent = file_get_contents($jsonUrl);

		if ($jsonContent !== false) {
			$BasePath = rtrim($BasePath, '/');
			$jsonContent = str_replace('[[SERVER_URL]]', $BasePath, $jsonContent);

			if (file_put_contents('playlist.json', $jsonContent) === false) {
				echo "Failed to save the modified JSON file.";
				exit;
			}
		} else {
			echo "Failed to load the JSON file.";
			exit;
		}

		$m3u8Url = "https://raw.githubusercontent.com/gogetta69/public-files/main/playlist.m3u8";
		$m3u8Content = file_get_contents($m3u8Url);
		$m3u8Content = str_replace('[[SERVER_URL]]', $BasePath, $m3u8Content);

		if (file_put_contents('playlist.m3u8', $m3u8Content) === false) {
			echo "Failed to save the modified M3U8 file.";
			exit;
		}
	}

	if ($GLOBALS['INCLUDE_ADULT_VOD']) {
		$playlistJson = file_get_contents('playlist.json');
		$playlist = json_decode($playlistJson, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			echo json_encode(["error" => "JSON decoding error in playlist.json: " . json_last_error_msg()]);
			exit;
		}

		$adultJsonUrl = "adult-movies.json";
		$adultJsonContent = file_get_contents($adultJsonUrl);

		if ($adultJsonContent !== false) {
			$adultMovies = json_decode($adultJsonContent, true);

			if (json_last_error() === JSON_ERROR_NONE) {
				// Merge adult movies into the playlist
				$playlist = array_merge($playlist, $adultMovies);

				// Output the combined JSON
				header('Content-Type: application/json');
				echo injectStreamLang(json_encode($playlist), $xcAccount['lang']);
				exit();
			} else {
				echo json_encode(["error" => "Failed to decode adult-movies.json: " . json_last_error_msg()]);
				exit();
			}
		} else {
			echo json_encode(["error" => "Failed to load adult-movies.json."]);
			exit();
		}
	}

	if (isset($_GET['type']) && ($_GET['type'] == 'm3u' || $_GET['type'] == 'm3u8')) {
		// Reuse the content already in memory from the download above rather
		// than reading the same tens-of-MB file straight back off disk again
		// (a second full copy in memory, needlessly closer to memory_limit) -
		// only actually re-read when userCreatePlaylist means it was never
		// downloaded in this request at all.
		$m3u8Out = $m3u8Content ?? (file_exists('playlist.m3u8') ? file_get_contents('playlist.m3u8') : null);
		if ($m3u8Out !== null) {
			header('Content-Type: audio/x-mpegurl');
			echo injectStreamLang($m3u8Out, $xcAccount['lang']);
		} else {
			header('HTTP/1.1 404 Not Found');
			echo "Playlist file not found. Please run create_playlist.php first.";
		}
	} else {
		$jsonOut = $jsonContent ?? (file_exists('playlist.json') ? file_get_contents('playlist.json') : null);
		if ($jsonOut !== null) {
			header('Content-Type: application/json');
			echo injectStreamLang($jsonOut, $xcAccount['lang']);
		} else {
			header('HTTP/1.1 404 Not Found');
			echo json_encode(["error" => "Playlist file not found. Please run create_playlist.php first."]);
		}
	}
	exit();
}

//Send the request to the playlist.
if (isset($_GET['action']) && $_GET['action'] == 'get_series') {

	if (!$GLOBALS['userCreatePlaylist']) {

		$jsonUrl = "https://raw.githubusercontent.com/gogetta69/public-files/main/tv_playlist.json";
		$jsonContent = file_get_contents($jsonUrl);

		if ($jsonContent !== false) {

			$BasePath = rtrim($BasePath, '/');
			$jsonContent = str_replace('[[SERVER_URL]]', $BasePath, $jsonContent);

			if (file_put_contents('tv_playlist.json', $jsonContent) === false) {
				echo "Failed to save the modified JSON file.";
				exit;
			}
		} else {
			echo "Failed to load the JSON file.";
			exit;
		}
	}

	if (isset($_GET['type']) && ($_GET['type'] == 'm3u' || $_GET['type'] == 'm3u8')) {
		if (file_exists('tv_playlist.m3u8')) {
			header('Content-Type: audio/x-mpegurl');
			echo injectStreamLang(file_get_contents('tv_playlist.m3u8'), $xcAccount['lang']);
		} else {
			header('HTTP/1.1 404 Not Found');
			echo "Series playlist file not found. Please run create_tv_playlist.php first.";
		}
	} else {
		// See the matching comment in get_vod_streams above - reuse the
		// content already downloaded into memory instead of reading the same
		// large file back off disk again.
		$jsonOut = $jsonContent ?? (file_exists('tv_playlist.json') ? file_get_contents('tv_playlist.json') : null);
		if ($jsonOut !== null) {
			header('Content-Type: application/json');
			echo injectStreamLang($jsonOut, $xcAccount['lang']);
		} else {
			header('HTTP/1.1 404 Not Found');
			echo json_encode(["error" => "Series playlist file not found. Please run create_tv_playlist.php first."]);
		}
	}
	exit();
}

// Cheap "does this movie have any cached candidate?" check for one TMDB id,
// for the requesting account's language - see aio_availability.php. Read-only:
// never resolves or probes a stream. available=null (HTTP 502/200 with an
// "error") means unknown, not "nothing available".
if (isset($_GET['action']) && $_GET['action'] == 'get_vod_availability') {
	header('Content-Type: application/json');
	if (!isset($_GET['vod_id']) || intval($_GET['vod_id']) <= 0) {
		http_response_code(400);
		echo json_encode(['error' => 'missing_or_invalid_vod_id']);
		exit();
	}
	$availLang = $streamLanguageNames[getRequestedStreamLang()] ?? 'English';
	list($availStatus, $availBody) = aioAvailabilityLookup($_GET['vod_id'], $availLang, isset($_GET['refresh']), preg_match('/^\d{4}$/', $_GET['year'] ?? '') ? $_GET['year'] : '');
	http_response_code($availStatus);
	if (isset($availBody['retry_after'])) {
		header('Retry-After: ' . intval($availBody['retry_after']));
	}
	echo json_encode($availBody);
	exit();
}

// Many titles at once: type=movie|series&items=<tmdb id>[:<year>],... (max
// AIO_AVAILABILITY_MAX_BATCH). Always HTTP 200 with a per-title
// available true|false|null; null = unknown, ask again after retry_after.
if (isset($_GET['action']) && $_GET['action'] == 'get_availability_batch') {
	header('Content-Type: application/json');
	$availKind = ($_GET['type'] ?? 'movie') === 'series' ? 'series' : 'movie';
	$availItems = [];
	foreach (explode(',', (string) ($_GET['items'] ?? '')) as $part) {
		$bits = explode(':', trim($part));
		$availId = intval($bits[0]);
		if ($availId > 0 && !isset($availItems[$availId])) {
			$availItems[$availId] = ['id' => $availId, 'year' => preg_match('/^\d{4}$/', $bits[1] ?? '') ? $bits[1] : ''];
		}
	}
	if (!$availItems || count($availItems) > AIO_AVAILABILITY_MAX_BATCH) {
		http_response_code(400);
		echo json_encode(['error' => 'items must contain 1-' . AIO_AVAILABILITY_MAX_BATCH . ' tmdb ids']);
		exit();
	}
	$availLang = $streamLanguageNames[getRequestedStreamLang()] ?? 'English';
	$availOut = aioAvailabilityBatch(array_values($availItems), $availKind, $availLang, isset($_GET['refresh']));
	if ($availOut['retry_after'] !== null) {
		header('Retry-After: ' . intval($availOut['retry_after']));
	}
	echo json_encode($availOut);
	exit();
}

//Look up the movie info on TMDB and return json.
if (isset($_GET['action']) && $_GET['action'] == 'get_vod_info') {
	if (!isset($_GET['vod_id'])) {
		echo 'Missing the vod_id parameter.';
		exit();
	}
	$vodId = $_GET['vod_id'];
	// If $vodId is greater than 10000000 the movie type is adult.
	if (intval($vodId) > 10000000) {
		getAdultInfo($vodId);
	}

	$infoUrl = "https://api.themoviedb.org/3/movie/{$vodId}?api_key={$apiKey}&append_to_response=credits,watch/providers&include_adult=false&language={$language}";
	// Confirmed directly: this specific TMDB call (movie details with
	// credits+watch/providers) via file_get_contents() fataled silently in
	// production (PHP 8.2.33) - HTTP 200 with a completely empty body, same
	// symptom as the memory_limit bug above - while play.php's own TMDB
	// movie-detail lookup, which already goes through this curl-based
	// helper, works correctly in production. Using it here too instead of
	// chasing the exact file_get_contents failure mode further.
	$fetchDetails = makeGetRequest($infoUrl);
	$details = json_decode($fetchDetails, true);

	$output = [];

	$runtimeMinutes = $details['runtime'];

	// Calculate hours, minutes, and seconds
	$hours = floor($runtimeMinutes / 60);
	$minutes = $runtimeMinutes % 60;
	$seconds = 0;

	// Format the time as HH:MM:SS
	$formattedRuntime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);


	if (isset($details['release_date'])) {
		$dateParts = explode("-", $details['release_date']);
		$year = $dateParts[0];
	} else {
		$year = '';
	}

	//Try and get a trailer from TMDB, if not try Youtube.
	$ytId = getTMDBTrailer($vodId, 'movie');

	/* 	if ($ytId == false){
		//Run Youtube trailer search.
		$ytId = getYoutubeTrailer($details['original_title'].' '.$year);
		} */
	if ($ytId == false) {
		$ytId = '';
	}

	// Ensure that 'genres' key exists and is an array
	if (isset($details['genres']) && is_array($details['genres'])) {
		// Extract the 'name' values
		$genreNames = array_map(function ($genre) {
			return $genre['name'];
		}, $details['genres']);

		// Convert the names array to a comma-separated string
		$genresString = implode(', ', $genreNames);
	} else {
		$genresString = '';
	}

	$actorsString = '';

	if (isset($details['credits']['cast']) && is_array($details['credits']['cast'])) {
		$cast = $details['credits']['cast'];

		$actorNames = array_map(function ($actor) {
			return (isset($actor['known_for_department']) && $actor['known_for_department'] == 'Acting' && isset($actor['name'])) ? $actor['name'] : null;
		}, $cast);

		// Remove null values from the list
		$actorNames = array_filter($actorNames);

		// Take only the first 4 actor names
		$actorNames = array_slice($actorNames, 0, 4);

		// Concatenate the names with a comma separator
		$actorsString = implode(', ', $actorNames);
	}

	$directors = [];

	if (isset($details['credits']['crew']) && is_array($details['credits']['crew'])) {
		$crew = $details['credits']['crew'];

		$directorNames = array_filter(array_map(function ($member) {
			return (isset($member['job']) && $member['job'] == 'Director' && isset($member['name'])) ? $member['name'] : null;
		}, $crew));

		// Since we want only up to 2 directors, take the first two (if they exist)
		$directors = array_slice($directorNames, 0, 2);
	}

	$directorsString = implode(', ', $directors);

	$output = [
		"info" => [
			"movie_image" => "https://image.tmdb.org/t/p/original" . $details['poster_path'],
			"tmdb_id" => $vodId,
			"youtube_trailer" => $ytId,
			"genre" => $genresString,
			"director" => $directorsString,
			"plot" => $details['overview'],
			"rating" => round($details['vote_average'], 1),
			"releasedate" => $details['release_date'],
			"duration_secs" => $details['runtime'] * 60,
			"duration" => $formattedRuntime,
			"cast" => $actorsString,
			// Production country names (TMDB "production_countries") and
			// streaming platform names for the requesting account's own
			// region (TMDB "watch/providers", "flatrate" = subscription
			// streaming, closest match to "which platform is this on" -
			// excludes rent/buy-only offers on purpose since those aren't a
			// meaningful "platform" grouping). Both new, additive fields -
			// existing clients that don't read them are unaffected.
			"country" => tmdbCountryNames($details),
			"platform" => tmdbPlatformNames($details, tmdbRegionForLang($xcAccount['lang'] ?? '')),
			"video" => [],
			"audio" => [],
			"bitrate" => 0
		],
		"movie_data" => [
			"stream_id" => intval($_GET['vod_id']),
			"name" => $details['original_title'],
			"added" => 1696275436,
			"category_id" => "22",
			"container_extension" => "mkv",
			"custom_sid" => null,
			"direct_source" => ""
		]
	];

	header('Content-Type: application/json');
	echo json_encode($output);
	exit();
}

//Look up the series info on TMDB and return json.
if (isset($_GET['action']) && $_GET['action'] == 'get_series_info') {
	if (!isset($_GET['series_id'])) {
		echo 'Missing the series_id parameter.';
		exit();
	}


	$vodId = $_GET['series_id'];
	// First, get the details of the series
	$infoUrl = "https://api.themoviedb.org/3/tv/{$vodId}?api_key={$apiKey}&include_adult=false&append_to_response=external_ids,credits,watch/providers&language={$language}";
	// See the matching comment in get_vod_info - this file_get_contents()
	// call fataled silently in production; makeGetRequest() (curl) is the
	// proven-working path for this same kind of TMDB call.
	$fetchDetails = makeGetRequest($infoUrl);
	$details = json_decode($fetchDetails, true);

	$actorsString = '';

	if (isset($details['credits']['cast']) && is_array($details['credits']['cast'])) {
		$cast = $details['credits']['cast'];

		$actorNames = array_map(function ($actor) {
			return (isset($actor['known_for_department']) && $actor['known_for_department'] == 'Acting' && isset($actor['name'])) ? $actor['name'] : null;
		}, $cast);

		// Remove null values from the list
		$actorNames = array_filter($actorNames);

		// Take only the first 4 actor names
		$actorNames = array_slice($actorNames, 0, 4);

		// Concatenate the names with a comma separator
		$actorsString = implode(', ', $actorNames);
	}

	// Number of seasons in the series
	$totalSeasons = $details['number_of_seasons'];

	// Array to store all the details including individual seasons
	$fullDetails = [];

	// If number of seasons is less than or equal to 20, just make one call
	if ($totalSeasons <= 20) {
		$seasons = range(1, $totalSeasons);
		$seasonsToFetch = implode(',', array_map(function ($season) {
			return "season/{$season}";
		}, $seasons));

		$url = "https://api.themoviedb.org/3/tv/{$vodId}?api_key={$apiKey}&append_to_response={$seasonsToFetch}";
		$fullDetails = json_decode(makeGetRequest($url), true);
	} else {
		// If more than 20, loop and fetch in batches of 20
		$batches = ceil($totalSeasons / 20);
		$fullDetails = $details;  // start with the basic series details

		for ($i = 0; $i < $batches; $i++) {
			$start = ($i * 20) + 1;
			$end = min($start + 19, $totalSeasons);
			$seasons = range($start, $end);

			$seasonsToFetch = implode(',', array_map(function ($season) {
				return "season/{$season}";
			}, $seasons));

			$url = "https://api.themoviedb.org/3/tv/{$vodId}?api_key={$apiKey}&append_to_response={$seasonsToFetch}";
			$batchDetails = json_decode(makeGetRequest($url), true);

			// Merge the seasons details with the main details array
			foreach ($seasons as $season) {
				$fullDetails["season/{$season}"] = $batchDetails["season/{$season}"];
			}
		}
	}


	if (isset($fullDetails['first_air_date'])) {
		$dateParts = explode("-", $fullDetails['first_air_date']);
		$year = $dateParts[0];
		$date = $fullDetails['first_air_date'];
		$timestamp = strtotime($date);
		$lastAirdate = strtotime($fullDetails['last_air_date']);
	} else {
		$date = '1970-01-01';
		$year = '1970'; //Set to 1970 since its unknown.
		$timestamp = '24034884';
		$lastAirdate = '24034884';
	}

	//Try and get a trailer from TMDB, if not try Youtube.
	$ytId = getTMDBTrailer($vodId, 'tv');
	/* 	if ($ytId == false){
			//Run Youtube trailer search.
		$ytId = getYoutubeTrailer($fullDetails['name'].' '.$year);
		} */
	if ($ytId == false) {
		$ytId = '';
	}

	// Ensure that 'genres' key exists and is an array
	if (isset($fullDetails['genres']) && is_array($fullDetails['genres'])) {
		// Extract the 'name' values
		$genreNames = array_map(function ($genre) {
			return $genre['name'];
		}, $fullDetails['genres']);

		// Convert the names array to a comma-separated string
		$genresString = implode(', ', $genreNames);
	} else {
		$genresString = '';
	}

	// Construct the array
	$result = [
		"seasons" => [],
		"info" => [
			"name" => $fullDetails['name'],
			"cover" => "https://image.tmdb.org/t/p/original" . $fullDetails['poster_path'],
			"plot" => $fullDetails['overview'],
			"cast" => $actorsString,
			"director" => isset($fullDetails['created_by'][0]['name']) ? $fullDetails['created_by'][0]['name'] : '',
			// Needed by any external client that wants to build a real
			// per-episode play.php URL itself: the "data" param each episode
			// carries below is imdbId:tmdbId/season/N/episode/M, base64'd -
			// exposing imdb_id directly here means a client can construct
			// that same string without parsing it back out of an episode's
			// own container_extension field.
			"imdb_id" => $details['external_ids']['imdb_id'] ?? '',
			"genre" => $genresString,
			"releaseDate" => $date,
			"last_modified" => $lastAirdate,
			"rating" => isset($fullDetails['vote_average']) ? round($fullDetails['vote_average'], 1) : 0,
			"rating_5based" => isset($fullDetails['vote_average']) ? (round($fullDetails['vote_average'], 1) / 2) : 0,
			"backdrop_path" => [
				"https://image.tmdb.org/t/p/original" . $fullDetails['backdrop_path']
			],
			"youtube_trailer" => $ytId,
			"episode_run_time" => isset($fullDetails['episode_run_time'][0]) ? $fullDetails['episode_run_time'][0] : 0,
			"category_id" => "",
			// See the matching comment in get_vod_info - $details (not
			// $fullDetails) is used here since it's the one call in this
			// handler that always carries watch/providers/production
			// countries regardless of the season-count branch taken above.
			"country" => tmdbCountryNames($details),
			"platform" => tmdbPlatformNames($details, tmdbRegionForLang($xcAccount['lang'] ?? ''))
		],
		"episodes" => []
	];


	// Add seasons
	foreach (range(1, $totalSeasons) as $seasonNumber) {
		if (isset($fullDetails["season/{$seasonNumber}"]) && is_array($fullDetails["season/{$seasonNumber}"])) {
			$seasonData = $fullDetails["season/{$seasonNumber}"];

			// Assuming 'episodes' is always an array, but still checking to be safe
			$episodeCount = isset($seasonData['episodes']) && is_array($seasonData['episodes']) ? count($seasonData['episodes']) : 0;

			$result["seasons"][] = [
				"air_date" => $seasonData['air_date'] ?? '',
				"episode_count" => $episodeCount,
				"id" => $seasonData['_id'] ?? '',
				"name" => "Season " . $seasonNumber,
				"overview" => $seasonData['overview'] ?? '',
				"season_number" => $seasonData['season_number'] ?? '',
				"backdrop_path" => "https://image.tmdb.org/t/p/original" . $fullDetails['backdrop_path'],
				"cover" => "https://image.tmdb.org/t/p/original" . $fullDetails['poster_path'],
				"cover_big" => "https://image.tmdb.org/t/p/original" . $fullDetails['poster_path']
			];

			if (isset($seasonData['episodes']) && is_array($seasonData['episodes'])) {
				foreach ($seasonData['episodes'] as $episode) {
					// Check if the episode has aired yet
					$airDate = new DateTime($episode['air_date']);
					$today = new DateTime();

					if ($airDate > $today) {
						continue; // If the episode's air date is in the future, skip it
					}


					$result["episodes"][$seasonNumber][] = [
						"id" => $episode['id'],
						"episode_num" => $episode['episode_number'],
						"title" => "S" . str_pad($seasonNumber, 2, '0', STR_PAD_LEFT) . "E" . str_pad($episode['episode_number'], 2, '0', STR_PAD_LEFT) . ' - ' . $episode['name'],
						//Had to use container extension to send the episode data through Tivimate.
						"container_extension" => base64_encode($details['external_ids']['imdb_id'] . ':' . $vodId . "/season/" . $seasonNumber . "/episode/" . $episode['episode_number']),
						"custom_sid" => "",
						"added" => "",
						"season" => $seasonNumber,
						"direct_source" => "",
						"info" => [
							"tmdb_id" => $vodId,
							"name" => $episode['name'],
							"cover_big" => "https://image.tmdb.org/t/p/original" . (isset($episode['still_path']) && !empty($episode['still_path']) ? $episode['still_path'] : $fullDetails['backdrop_path']),
							"plot" => $episode['overview'],
							"movie_image" => "https://image.tmdb.org/t/p/original" . (isset($episode['still_path']) && !empty($episode['still_path']) ? $episode['still_path'] : $fullDetails['backdrop_path'])
						]
					];
				}
			}
		}
	}

	header('Content-Type: application/json');
	echo json_encode($result);
	exit();
}

//All unhandled action requests should return dummy user info. Need to change this.
//Set the url in the user info json or Smarters Pro can't locate the streams.

$port = parse_url($domain, PHP_URL_PORT);

if (!$port) {
	$port = 80;
}
//Changing "allowed_output_formats" to m3u8 allowed NexTV to work correctly.
//This change should fix the other apps like XCIPTV, so be sure to test it.

header('Content-Type: application/json');
echo '{"user_info":{"username":"' . $xcAccount['username'] . '","password":"' . $xcAccount['password'] . '","message":"","auth":1,"status":"Active","exp_date":"4095101905","is_trial":"0","active_cons":"0","created_at":"1684851647","max_connections":"1000","allowed_output_formats":["m3u8",""]},"server_info":{"url":"' . parse_url($domain, PHP_URL_HOST) . '","port":"' . $port . '","https_port":"","server_protocol":"http","rtmp_port":"","timezone":"America\/New_York","timestamp_now":' . time() . ',"time_now":"' . date("Y-m-d H:i:s", time()) . '"}}';
exit();

function getYoutubeTrailer($keyword)
{
	global $yt_api_key;

	// Fetch the JSON data
	$json = @file_get_contents("https://www.googleapis.com/youtube/v3/search?part=snippet&maxResults=20&q=" . urlencode($keyword) . "%20AND%20intitle%3A%22Trailer&type=video&key=" . $yt_api_key);

	if ($json === false) {
		// Failed to fetch data from the API
		return false;
	}

	$data = json_decode($json, true);

	// Check if 'items' key exists and is an array
	if (!isset($data['items']) || !is_array($data['items'])) {
		return false;
	}

	$videoId = null;

	// Loop through the items
	foreach ($data['items'] as $item) {
		// Check if the necessary keys exist
		if (isset($item['snippet']['title']) && isset($item['id']['videoId'])) {
			$title = $item['snippet']['title'];

			// Check if the title contains the word "Official" (case-insensitive)
			if (stripos($title, 'Official') !== false) {
				$videoId = $item['id']['videoId'];
				break;
			}
		}
	}

	// If none of the videos contains the word "Official", grab the first videoId
	if (!$videoId && isset($data['items'][0]['id']['videoId'])) {
		$videoId = $data['items'][0]['id']['videoId'];
	}

	// Return the videoId or false if not found
	return $videoId ? $videoId : false;
}

function getTMDBTrailer($movieId, $type)
{
	global $apiKey;

	$url = "https://api.themoviedb.org/3/$type/$movieId/videos?language=en-US&site=YouTube&api_key=$apiKey";

	// Fetch and decode the JSON data - see the comment in get_vod_info for
	// why this uses makeGetRequest() (curl) rather than file_get_contents().
	$data = makeGetRequest($url);
	if ($data === false) {
		// Failed to fetch data from the API
		return false;
	}

	$jsonData = json_decode($data, true);

	// Check if 'results' key exists
	if (!isset($jsonData['results']) || !is_array($jsonData['results'])) {
		return false;
	}

	// Filter for trailers
	$trailers = array_filter($jsonData['results'], function ($video) {
		return isset($video['type']) && $video['type'] == 'Trailer';
	});

	if (empty($trailers)) {
		return false;
	}

	// Look for an official trailer
	foreach ($trailers as $trailer) {
		if (isset($trailer['official']) && $trailer['official'] == true) {
			return $trailer['key'];
		}
	}

	// If none are official, return the key of the first trailer
	return reset($trailers)['key'];  // Use reset to get the first element without relying on indexes
}

function shouldUpdateLiveStreams()
{
	$lastUpdatedFile = "channels/last_updated_channels.txt";

	if (!file_exists($lastUpdatedFile)) {

		file_put_contents($lastUpdatedFile, '0');
	}

	if (file_exists($lastUpdatedFile)) {
		$lastUpdatedContent = file_get_contents($lastUpdatedFile);
		$lastUpdatedTimestamp = (int) $lastUpdatedContent;
		$currentTime = time();
		$timeDifference = $currentTime - $lastUpdatedTimestamp;

		if ($timeDifference > 60) {
			return true;
		}
	}

	return false;
}

function getAdultInfo($vodId)
{
	// Read the contents of the JSON file
	$fetchDetails = @file_get_contents('adult-movies.json');
	$movies = json_decode($fetchDetails, true);

	$index = array_search($vodId, array_column($movies, 'stream_id'));

	// Check if the calculated index is within bounds
	if (!isset($movies[$index])) {
		echo json_encode(["error" => "Movie not found"]);
		exit();
	}

	$details = $movies[$index];

	// Prepare the output
	$output = [
		"info" => [
			"movie_image" => $details['stream_icon'],
			"tmdb_id" => '',
			"youtube_trailer" => '',
			"genre" => $details['genres'],
			"director" => $details['director'],
			"plot" => $details['plot'],
			//"rating" => 0,
			//"releasedate" => 0,
			//"duration_secs" => 0,
			//"duration" => 0,
			"cast" => $details['cast'],
			"video" => [],
			"audio" => [],
			"bitrate" => 0
		],
		"movie_data" => [
			"stream_id" => intval($vodId),
			"name" => $details['name'],
			"added" => $details['added'],
			"category_id" => $details['category_id'],
			"container_extension" => $details['container_extension'],
			"custom_sid" => $details['custom_sid'],
			"direct_source" => $details['direct_source']
		]
	];

	// Output the JSON
	header('Content-Type: application/json');
	echo json_encode($output);
	exit();
}

?>