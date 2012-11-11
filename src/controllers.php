<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->match('/', function() use ($app) {
    $sql = 'SELECT t.title, t.date, t.slides, t.joindin, v.name as venue, v.city, v.country, REPLACE(v.country,\' \',\'-\') as flag, v.url as venueUrl, v.lat, v.long, e.name, e.url, e.joindin as eventJoindin, e.hashtag, e.startDate, e.endDate
	    FROM talks t
	    LEFT JOIN `events` e ON ( e.id = t.eventId ) 
	    LEFT JOIN venues v ON ( v.id = e.venueId )
        WHERE `date`>=DATE(NOW())
        ORDER BY `date` desc';
    $upcoming = $app['db']->fetchAll($sql);
    $sql = 'SELECT t.title, t.date, t.slides, t.joindin, v.name as venue, v.city, v.country,REPLACE(v.country,\' \',\'-\') as flag, v.url as venueUrl, v.lat, v.long, e.name, e.url, e.joindin as eventJoindin, e.hashtag, e.startDate, e.endDate
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
    $sql = 'SELECT t.title, t.date, v.name as venue, v.city, v.country, v.lat, v.long, e.name as `event`, e.url
	    FROM talks t
	    LEFT JOIN `events` e ON ( e.id = t.eventId )
	    LEFT JOIN venues v ON ( v.id = e.venueId )';
    $output = $app['db']->fetchAll($sql);
    $events = array();
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    foreach($output as $row){
        $event = new stdClass();
        $event->title = $row['title'];
        $event->date = $row['date'];
        $event->venue = $row['venue'];
        $event->city = $row['city'];
        $event->country = $row['country'];
        $event->latitude = $row['lat'];
        $event->longitude = $row['long'];
        if(null == $event->lat || null == $event->long){
            curl_setopt($curl,CURLOPT_URL,"http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=".$event->venue.'+'.$event->city.'+'.$event->country);
            $json = curl_exec($curl);
            $geo = json_decode($json);
            if(isset($geo->results[0]->geometry->location->lat) && isset($geo->results[0]->geometry->location->lng)){
                $latitude = $geo->results[0]->geometry->location->lat;
                $longitude = $geo->results[0]->geometry->location->lng;
            }
            $event->latitude = $latitude;
            $event->longitude = $longitude;
        }
        $event->event = $row['event'];
        $event->url = $row['urls'];
        $events[] = $event;
    }
    curl_close($curl);
    return $app->json($events);
})->bind('geo');
$app->match('/map', function() use ($app){
    $output =  $app['twig']->render('map.html.twig');
    return new Response($output);
})->bind('map');
return $app;
