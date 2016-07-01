<?php

require_once 'config.php';

require_once 'Google/autoload.php';
require_once 'Google/Client.php';
require_once 'Google/Service/YouTube.php';

require_once 'initalize.php';
require_once 'connect.php';
require_once 'cytsql.php';
// require_once 'authorize.php';

// initalize the google object
$youtube = new Google_Service_YouTube($client);

/******
* working
* retrieve details from you tubee
* $videos: array
*/

function getVideoDetails( $videos, $youtube, $mysqli ) {
    foreach( $videos as $video ) {
        
        // get the query string from the url
        $query_str = parse_url( $video, PHP_URL_QUERY );
        parse_str( $query_str, $query_params );

        // get the video id from the query string
        $video_id = $query_params['v'];

        $video_details = $youtube->videos->listVideos( 'snippet',
            array(
                'id' => $video_id
            )
        );
        
        $videos = array();
        foreach ( $video_details['items'] as $video_item ) {
            $videoDetails = saveVideoDetails( $video_item, $mysqli );
            
            // print_r($videoDetails);
            array_push($videos, $videoDetails);
        }

        return $videos;
    }
}



/****** get the video commenters
* will be a public function
* update query for max 50
*/

function getVideoCommenters( $video, $commenterIds, $youtube, $mysqli) {
    // return $video;
    $commentsThread = $youtube->commentThreads->listCommentThreads( 'snippet', 
        array(
            'videoId' => $video['video_id'],
            'maxResults' => '50',
            'pageToken' => $video['nextPageToken']
        )
    );

    if( !$commentsThread ) {
        echo 'Sorry No such luck on capturing comments thread.<br/>';
        return;
    }

    $commenterNames = array();
    foreach( $commentsThread['items'] as $comment_item ) {
        array_push($commenterNames, $comment_item['snippet']['modelData']['topLevelComment']['snippet']['authorDisplayName']);
        $commenterIds = saveCommentUser( $comment_item, $commenterIds, $video['db_video_id'], $mysqli );
      
    }
    
    $nextPageToken = ($commentsThread['nextPageToken']) ? $commentsThread['nextPageToken'] : null;

    $commentData = array(
        'commenterNames' => $commenterNames,
        'nextPageToken' => $nextPageToken
    );
            
    return $commentData;
      
}


function getCommentersFromVideo( $mysqli, $youtube ) {

    $videoDetails = retrieveVideoDetails( $mysqli );
    $videoCommenters = retrieveVideoCommenters( $mysqli );

    $commenterIds = [];
    foreach( $videoCommenters as $commenter ) {
        array_push( $commenterIds, $commenter['commenter_id']);
    }

    foreach( $videoDetails as $video ) {        
        getVideoCommenters( $video, $commenterIds, null, $youtube, $mysqli );

        // echo '<pre>';
        // var_dump( $video );
        // echo '</pre>';            
    }

}


function messageCommenter( $channel_id, $message, $youtube, $client ) {

    $textOriginal = ($message) ? $message : 'This is a message posted from cyt.';
    
    if ( $client->getAccessToken() ) {
        try {
            // Insert channel comment by omitting videoId.
            // Create a comment snippet with text.
            $commentSnippet = new Google_Service_YouTube_CommentSnippet();
            $commentSnippet->setTextOriginal($textOriginal);

            // Create a top-level comment with snippet.
            $topLevelComment = new Google_Service_YouTube_Comment();
            $topLevelComment->setSnippet($commentSnippet);

            // Create a comment thread snippet with channelId and top-level comment.
            $commentThreadSnippet = new Google_Service_YouTube_CommentThreadSnippet();
            $commentThreadSnippet->setChannelId($channel_id);
            $commentThreadSnippet->setTopLevelComment($topLevelComment);

            // Create a comment thread with snippet.
            $commentThread = new Google_Service_YouTube_CommentThread();
            $commentThread->setSnippet($commentThreadSnippet);

            $commentResult = $youtube->commentThreads->insert( 'snippet', $commentThread );
        
        } catch ( Google_Service_Exception $e ) {
            return sprintf('<p>A service error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage()));
        
        } catch (Google_Exception $e) {
            return sprintf('<p>An client error occurred: <code>%s</code></p>', htmlspecialchars($e->getMessage()));
        }

        $_SESSION['token'] = $client->getAccessToken();

    } else { 
        requestAuthorization($client);
    }

}


$videos = array(
    // 'https://www.youtube.com/watch?v=8nW-IPrzM1g',
    // 'https://www.youtube.com/watch?v=HCBPmxiVMKk',
    'https://www.youtube.com/watch?v=ua3_XUOJEQE' //Abby and Jill 100 comments
);


switch($_POST['action']) {
    case 'saveVideoDetails':
    
        $videos = [ $_POST['videoUrl'] ];
        // echo $videos;
        $videoDetails =  getVideoDetails( $videos, $youtube, $mysqli );
        echo json_encode($videoDetails);
    break;
    
    case 'getVideoCommenters':

        $videoCommenters = retrieveVideoCommenters( $mysqli );
        $commenterIds = [];
        foreach( $videoCommenters as $commenter ) {
            array_push( $commenterIds, $commenter['commenter_id']);
        }

        $commenters = getVideoCommenters( $_POST, $commenterIds, $youtube, $mysqli);
        echo json_encode( $commenters );
    break;

    case 'retrieveCommentersByVideo':
    
        $video_commenters = retrieveVideoCommentersByVideo ( $_POST['videoId'], $mysqli );
        $commenters = array();
        foreach( $video_commenters as $commenter) {
            $commenterInfo = array (
                'id' => $commenter['commenter_id'],
                'name' => $commenter['commenter_name']
            );
            array_push($commenters, $commenterInfo);
        }
        echo json_encode($commenters);

    break;
    
    case 'messageCommenter':
    
        if (isset($_POST['getCode'])) {

            if (strval($_SESSION['state']) !== strval($_POST['getState'])) {
                die('The session state did not match.');
            }
            
            if( !isset($_SESSION['token'] )) {
                $client->authenticate($_POST['getCode']);
                $_SESSION['token'] = $client->getAccessToken();
            }
        }

        if (isset($_SESSION['token'])) {
            $client->setAccessToken($_SESSION['token']);
        }

        $commentResultError = false;
        $commentResultError = messageCommenter($_POST['commenterId'], $_POST['message'], $youtube, $client);
        
        $comment = array(
            'error' => $commentResultError
        );

        echo json_encode( $comment );
    break;
    case 'messageCommenters':
       
        if (isset($_POST['getCode'])) {

            if (strval($_SESSION['state']) !== strval($_POST['getState'])) {
                die('The session state did not match.');
            }
            
            if( !isset($_SESSION['token'] )) {
                $client->authenticate($_POST['getCode']);
                $_SESSION['token'] = $client->getAccessToken();
            }
        }

        if (isset($_SESSION['token'])) {
            $client->setAccessToken($_SESSION['token']);
        }

        $video_commenters = retrieveVideoCommentersByVideo ( $_POST['videoId'], $mysqli );
        $comments = array();
                
        foreach( $video_commenters as $commenter) {

            $commentResultError = false;
            $commentResultError = messageCommenter($commenter['commenter_id'], $_POST['message'], $youtube, $client);
            
            $comment = array(
                'userName' => $commenter['commenter_name'],
                'error' => $commentResultError
            );
            
            array_push( $comments, $comment );
        }
        
        echo json_encode( $comments );
    break;
}