<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->match('/', function() use ($app) {
    $sql = 'SELECT t.title, t.date, t.slides, t.video, t.joindin, v.name as venue, v.city, v.country, REPLACE(v.country,\' \',\'-\') as flag, v.url as venueUrl, v.lat, v.long, e.name, e.url, e.joindin as eventJoindin, e.hashtag, e.startDate, e.endDate
	    FROM talks t
	    LEFT JOIN `events` e ON ( e.id = t.eventId ) 
	    LEFT JOIN venues v ON ( v.id = e.venueId )
        WHERE `date`>=DATE(NOW())
        ORDER BY `date` desc';
    $upcoming = $app['db']->fetchAll($sql);
    $sql = 'SELECT t.title, t.date, t.slides,t.video, t.joindin, v.name as venue, v.city, v.country,REPLACE(v.country,\' \',\'-\') as flag, v.url as venueUrl, v.lat, v.long, e.name, e.url, e.joindin as eventJoindin, e.hashtag, e.startDate, e.endDate
	    FROM talks t
	    LEFT JOIN `events` e ON ( e.id = t.eventId ) 
	    LEFT JOIN venues v ON ( v.id = e.venueId )
    	WHERE `date`<DATE(NOW())
    	ORDER BY `date` desc';
    $pastRowSet = $app['db']->fetchAll($sql);

    $curl = curl_init($app['joindin.api'].'users/'.$app['joindin.user.id'].'/talks/');
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    $joindInOut = json_decode(curl_exec($curl));
    curl_close($curl);

    $past = array();
    $rating = 0;
    foreach($pastRowSet as $pastTalk){
        if(isset($pastTalk['joindin'])){
            foreach($joindInOut->talks as $talk){
                if(preg_match('/talks\/([0-9]+)$/',$talk->uri,$matches)){
                    if($pastTalk['joindin'] == $matches[1]){
                        $rating = $talk->average_rating;
                    }
                }
            }
        }
        $pastTalk['rating'] = $rating;
        $past[] = $pastTalk;
    }
    $output =  $app['twig']->render('layout.html.twig',array('upcoming'=>$upcoming, 'upcomingCount'=>count($upcoming),'past'=>$past,'pastCount'=>count($past)));
    return new Response($output);
})->bind('homepage');

$app->match('/geo', function() use ($app){
    $sql = 'SELECT v.id, v.name as venue, v.city, v.country, v.url as venueUrl, v.lat, v.long, e.name as event, e.url as eventUrl, t.title, t.date
            FROM venues v
            JOIN events e ON (e.venueId = v.id)
            JOIN talks t ON (t.eventId = e.id)
            ORDER BY v.id';
    $output = $app['db']->fetchAll($sql);
    $venues = array();
    $oldVenueId = null;
    $venue = null;
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    foreach($output as $row){
        $talk = new stdClass();
        $talk->title = $row['title'];
        $talk->date = $row['date'];
        $talk->event = $row['event'];
        $talk->eventUrl = $row['eventUrl'];
        if($row['id'] != $oldVenueId){
            if($venue != null){
                $venues[] = $venue;
            }
            $oldVenueId = $row['id'];
            $venue = new stdClass();
            $venue->talks = array();
            $venue->name = $row['venue'];
            $venue->city = $row['city'];
            $venue->country = $row['country'];
            $venue->url = $row['venueUrl'];
            $venue->latitude = $row['lat'];
            $venue->longitude = $row['long'];
            $venue->talks = array($talk);
            if(null == $venue->latitude || null == $venue->longitude){
                $latLong = apc_fetch(md5(json_encode($venue)));
                if($latLong !== false){
                    $latLongObj = json_decode($latLong);
                    $venue->latitude = $latLongObj->latitude;
                    $venue->longitude = $latLongObj->longitude;
                } else {
                    curl_setopt($curl,CURLOPT_URL,"http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=".$venue->name.'+'.$venue->city.'+'.$venue->country);
                    $json = curl_exec($curl);
                    $geo = json_decode($json);
                    if(isset($geo->results[0]->geometry->location->lat) && isset($geo->results[0]->geometry->location->lng)){
                        $venue->latitude = $geo->results[0]->geometry->location->lat;
                        $venue->longitude = $geo->results[0]->geometry->location->lng;
                        $latLongObj = new stdClass();
                        $latLongObj->latitude = $venue->latitude;
                        $latLongObj->longitude = $venue->longitude;
                        apc_store(md5(json_encode($venue)),json_encode($latLongObj));
                    }
                }
            }
        } else {
            $venue->talks[] = $talk;
        }
    }
    curl_close($curl);
    return $app->json($venues);
})->bind('geo');
$app->match('/map', function() use ($app){
    $output =  $app['twig']->render('map.html.twig');
    return new Response($output);
})->bind('map');
return $app;
