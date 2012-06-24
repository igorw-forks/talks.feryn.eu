<?php
$pdo = new PDO('mysql:host=localhost;dbname=talks','talks','talks',array(
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
    PDO::CASE_NATURAL=>true,
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$prepareVenue = $pdo->prepare('INSERT INTO `venues`(`name`,`city`,`country`,`url`) VALUES(:name,:city,:country,:url)');
$prepareVenueId = $pdo->prepare('SELECT `id` FROM `venues` WHERE `name`=:name AND `city`=:city AND `country`=:country');
$prepareEvent = $pdo->prepare('INSERT INTO `events`(`name`,`url`,`venueId`,`startDate`,`endDate`) VALUES(:name,:url,:venueId,:startDate,:endDate)');
$prepareTalk = $pdo->prepare('INSERT INTO `talks`(`title`,`date`,`slides`,`eventId`) VALUES(:title,:date,:slides,:eventId)');
while(!feof(STDIN)){
    $input = trim(fgets(STDIN));
    if(strlen($input) == 0) continue;
    $row = preg_split('/;/',$input);
    $talk = array();
    foreach($row as $index=>$column){
        preg_match('/^"(.*)"/',$column,$matches);
        if(isset($matches[1])){
            $talk[$index] = $matches[1];
        }  else {
            $talk[$index] = null;
        }
    }
    /**
     * Insert venues
     */
    $prepareVenue->bindValue(':name',$talk[3],PDO::PARAM_STR);
    $prepareVenue->bindValue(':city',$talk[4],PDO::PARAM_STR);
    $prepareVenue->bindValue(':country',$talk[5],PDO::PARAM_STR);
    $prepareVenue->bindValue(':url',$talk[6],PDO::PARAM_STR);
    try{
        $prepareVenue->execute();
        $venueId = $pdo->lastInsertId();
    } catch (PDOException $e) {
        if($e->errorInfo[0] != '23000' || $e->errorInfo[1] != '1062'){
            echo $e->getMessage() .PHP_EOL;
            exit();
        }
        $prepareVenueId->bindValue(':name',$talk[3],PDO::PARAM_STR);
        $prepareVenueId->bindValue(':city',$talk[4],PDO::PARAM_STR);
        $prepareVenueId->bindValue(':country',$talk[5],PDO::PARAM_STR);
        $prepareVenueId->execute();
        $venueId = reset($prepareVenueId->fetch(PDO::FETCH_NUM));
    }

    /**
     * Prepare events
     */
    if(empty($talk[7])){
        $talk[7] = 'Guest lecture';
    }
    $prepareEvent->bindValue(':name',$talk[7],PDO::PARAM_STR);
    $prepareEvent->bindValue(':url',$talk[8],PDO::PARAM_STR);
    $prepareEvent->bindValue(':venueId',$venueId,PDO::PARAM_INT);
    $prepareEvent->bindValue(':startDate',$talk[1],PDO::PARAM_STR);
    $prepareEvent->bindValue(':endDate',$talk[1],PDO::PARAM_STR);
    $prepareEvent->execute();
    $eventId = $pdo->lastInsertId();

    /**
     * Prepare talks
     */
    $prepareTalk->bindValue(':title',$talk[0],PDO::PARAM_STR);
    $prepareTalk->bindValue(':date',$talk[1],PDO::PARAM_STR);
    $prepareTalk->bindValue(':slides',$talk[2],PDO::PARAM_STR);
    $prepareTalk->bindValue(':eventId',$eventId,PDO::PARAM_INT);
    $prepareTalk->execute();
}