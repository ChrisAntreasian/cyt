<?php

/****** retrieve video from the database
* private function
*/

function retrieveVideoDetails( $mysqli ) {
    $videoDetails = $mysqli->query( "SELECT * FROM `videos`" );
    
    if( !$videoDetails ) {
        echo "Video Retreval failed : " . $mysqli->error;
        return;           
    }

    return $videoDetails;
}


function retrieveVideoCommenters( $mysqli ) {
    $videoCommenters = $mysqli->query( "SELECT * FROM `commenters`" );
    if ( !$videoCommenters ) {
       echo "Commenter Retreval failed : " . $mysqli->error;
       return;           
    }

    return $videoCommenters;
}

function retrieveVideoCommentersByVideo ( $video_id, $mysqli ) {
    $videoCommenters = $mysqli->query( "SELECT * FROM commenters WHERE commenter_video = '" . $video_id . "'" );
    if ( !$videoCommenters ) {
       echo "Commenter Retreval failed : " . $mysqli->error;
       return;           
    }

    return $videoCommenters;
}

/****** save video details from youtube result
* working
* $video_details: youtube object 
*/

function saveVideoDetails( $video_item, $mysqli ) {
    $video_save_result = $mysqli->query(    
        "INSERT INTO `videos` ( `video_id`, `video_title` ) 
        VALUES ( '" . $video_item['id'] . "', '" . mysqli_real_escape_string ( $mysqli, $video_item['snippet']['title'] ) . "')"
    );

    if( !$video_save_result ) {
        echo "Video Insert failed : " . $mysqli->error;
        return;           
    }
    
    $row_id = mysqli_insert_id($mysqli);

    return array (
        'video_id' => $video_item['id'],
        'video_title' => $video_item['snippet']['title'],
        'id' => $row_id
    );
}

/****** save user comment details 
* $comment_details: youtube object
*/

function saveCommentUser ( $comment_item, $commenterIds, $video_id, $mysqli ) {
    // echo $video_id;
    $comment = $comment_item['snippet']['modelData']['topLevelComment']['snippet'];

    if( in_array( $comment['authorChannelId']['value'], $commenterIds )) {
        // echo $comment['authorDisplayName'] . " already in the databse<br />";
        return $commenterIds;
    }

    $video_save_result = $mysqli->query(    
        "INSERT INTO `commenters` ( `commenter_id`, `commenter_name`, `commenter_video` ) 
        VALUES ( '" . $comment['authorChannelId']['value'] . "', '" . $comment['authorDisplayName'] . "', '" . $video_id . "')"
    );

    if( !$video_save_result ) {
        // echo "Comment User Insert failed: " . $mysqli->error . "<br />";
        return $commenterIds;
    }
    array_push( $commenterIds, $comment['authorChannelId']['value'] );
    // echo "Comment User Insert success : " . $comment['authorDisplayName'] . "<br />";

    return $commenterIds;


}