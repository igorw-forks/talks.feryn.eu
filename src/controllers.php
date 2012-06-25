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
    foreach($pastRowSet as $pastTalk){
        if(isset($pastTalk['joindin'])){
            foreach($joindInOut->talks as $talk){
                if(preg_match('/talks\/([0-9]+)$/',$talk->uri,$matches)){
                    if($pastTalk['joindin'] == $matches[1]){
                        $rating = $talk->average_rating;
                    }
                }
            }
        } else {
            $rating = 0;
        }
        $pastTalk['rating'] = $rating;
        $past[] = $pastTalk;
    }
    return $app['twig']->render('layout.html.twig',array('upcoming'=>$upcoming, 'upcomingCount'=>count($upcoming),'past'=>$past,'pastCount'=>count($past)));
})->bind('homepage');

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'We are sorry, but something went terribly wrong.';
    }

    return new Response($message, $code);
});

return $app;
