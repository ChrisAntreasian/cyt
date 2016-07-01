<?php

require 'config.php';
require_once 'connect.php';
require_once 'cytsql.php';

// set up options
$videos = retrieveVideoDetails( $mysqli );
$optionsNode = '<option value="0">Select a Video</option>';
foreach($videos as $video) {
    $optionsNode .= '<option value="' . $video['id'] . '" >'  . $video['video_title'] . '</option>';
}

?>

<html>
<head>
    <script   src="https://code.jquery.com/jquery-3.0.0.min.js"
    integrity="sha256-JmvOoLtYsmqlsWxa7mDSLMwa6dZ9rrIdtrrVYRnDRH0=" 
    crossorigin="anonymous"></script>
    
    <style type="text/css">
        html,
        body {
            width: 100%;
            height: 100%;
        }
        .cyt__actions {
            float: left;
            width: 40%;
        }
        .cyt__feedback {
            width: 54%;
            overflow-y: scroll;
            float: right;
            height: 100%;
            border-left: 1px solid lightgrey;
            padding-left: 20px;
            position: fixed;
            top: 0;
            right: 0;
        }
        label,
        input,
        select,
        textarea {
            display: block;
            margin: 5px;
        }
        form {
            border-top: 1px solid lightgrey;
            padding-top: 20px;
        }
        .cyt__input,
        textarea,
        select {
            width: 100%;
        }
        textarea {
            height: 100px;
        }
    </style>
    
    <script language="javascript">
            

        $( document ).ready( function(){
            var getUrlParameter = function getUrlParameter(sParam) {
                var sPageURL = decodeURIComponent(window.location.search.substring(1)),
                    sURLVariables = sPageURL.split('&'),
                    sParameterName,
                    i;

                for (i = 0; i < sURLVariables.length; i++) {
                    sParameterName = sURLVariables[i].split('=');

                    if (sParameterName[0] === sParam) {
                        return sParameterName[1] === undefined ? true : sParameterName[1];
                    }
                }
            };
            function getCommenters (video_id, nextPageToken, db_video_id) {
                
                $.ajax({
                    url: 'cyt.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        'action': 'getVideoCommenters',
                        'video_id': video_id,
                        'nextPageToken': nextPageToken,
                        'db_video_id': db_video_id
                    }, 
                    success: function( commentersData ){
                        console.log( commentersData );
                        // need video id for commenter table
                        var uniqueNames = [];
                        $.each(commentersData.commenterNames, function(i, e) {
                            if ($.inArray(e, uniqueNames) == -1) uniqueNames.push(e);
                        });

                        var cytResponse = '<div><h5>' + uniqueNames.length + ' commenters saved!</h5><ul>';
                        for( var i = 0; uniqueNames.length > i; i++ ){
                            cytResponse += '<li>' + uniqueNames[i] + '</li>';
                        }

                        if(!commentersData.nextPageToken) {
                            cytResponse += '<div>All comenters collected for the video.</div>'
                            $('.cyt__feedback').append(cytResponse);
                            return;
                        }

                        cytResponse += '<div>Wait there are more commenters.</div>'
                        $('.cyt__feedback').append(cytResponse);
                        
                        getCommenters (video_id, commentersData.nextPageToken, db_video_id)
                    }
                })
            }
            $('#cyt__capture-details').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    url: 'cyt.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        'action': 'saveVideoDetails',
                        'videoUrl' : $("input[name=videoUrl]").val()
                    },
                    success: function(video) {                      
                        for( var i = 0; video.length > i; i++ ) {
                            console.log(video);
                            var videoOption = '<option value="' + video[i].video_id + '">' + video[i].video_title + '</option>';
                            var cytResponse = '<div><h5>' + video[i].video_title + ' saved!</h5></div>';

                            $('#video__list').append(videoOption);
                            $('.cyt__feedback').append(cytResponse);
                            console.log (video[i]);
                            getCommenters(video[i].video_id, null, video[i].id);
                        }
                    }
                });
            });
            
            // $('#cyt__message-commenters').submit(function(e) {
            //     e.preventDefault();

            //     $.ajax({
            //         url: 'cyt.php',
            //         type: 'POST',
            //         dataType: 'json',
            //         data: {
            //             'action': 'messageCommenters',
            //             'getState': getUrlParameter('state'),
            //             'getCode': getUrlParameter('code'),
            //             'videoId': $("select[name=videoId]").val(),
            //             'message': $("textarea[name=message] ").val()
            //         }, 
            //         success: function(commentResult) {
            //             // console.log('comments sending?')
            //             console.log(commentResult)
            //         }
            //     });
            // });
            $('#cyt__message-commenters').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'cyt.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        'action': 'retrieveCommentersByVideo',
                        'videoId': $("select[name=videoId]").val()
                    }, 
                    success: function(commenters) {
                        for( var i = 0; commenters.length > i; i++) {
                            $.ajax({
                                url: 'cyt.php',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    'action': 'messageCommenter',
                                    'getState': getUrlParameter('state'),
                                    'getCode': getUrlParameter('code'),
                                    'commenterId': commenters[i].id,
                                    'message': $("textarea[name=message] ").val()
                                }, 
                                success: function(commentResult) {
                                    var cytResponse = '<div>user messaged</div>';
                                    if(commentResult.error) {
                                        cytResponse = '<div>' + commentResult.error + 'messaged</div>';
                                    }
                                    $('.cyt__feedback').append(cytResponse);
                                }
                            });
                        }
                    }
                });
            });
        });
    </script>

</head>

<body>
    <h1>Welcome to CYT</h1>
    <p>https://www.youtube.com/watch?v=XKLn0n2fR_0</p>
    
    <div class="cyt__actions">
        <form id="cyt__capture-details">
            <label>Video Url</label>
            <input class="cyt__input" type="text" name="videoUrl" placeholder="http://www.youtube.com?v=videoId"/>
            <input type="submit">
        </form>
        <form id="cyt__message-commenters">
            <label>Select a video to message video commenters</label>
            <select id="video__list" name="videoId">
                <?php
                    echo $optionsNode;
                ?>
            </select>
            <textarea name="message"></textarea>
            <input type="submit">

        </form>
    </div>
    <div class="cyt__feedback">

    </div>
</body>

</html>