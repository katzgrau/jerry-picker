<?php
    $artist_slug = str_replace('/', '', index_config('request'));
    if (!$artist_slug) $artist_slug = index_config('default_artist_slug');
?>
<html>
    <head>
        <title>Search and Listen to Live Grateful Dead | Jerry Picker</title>
        <meta name="description" content="Search over and listen to a huge collection of live Grateful Dead recordings">
        <style>
            <?php readfile("assets/app.css") ?>
        </style>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.css" media="screen" rel="stylesheet" />
        <meta property="og:title" content="Jerry Picker: Search and listen to live Grateful Dead shows" />
        <meta property="og:site_name" content="Jerry Picker"/>
        <meta property="og:url" content="https://katzgrau.github.io/jerry-picker/"/>
        <meta property="og:image" content="https://katzgrau.github.io/jerry-picker/assets/jerry-square.jpg" />
        <link rel="icon" type="image/x-icon" href="https://katzgrau.github.io/jerry-picker/assets/favicon.ico" />
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/gh/greghub/green-audio-player/dist/css/green-audio-player.min.css">
        <script src="https://cdn.jsdelivr.net/gh/greghub/green-audio-player/dist/js/green-audio-player.min.js"></script>
    </head>
    <body>
        <div id="main" ng-app="jerry" ng-cloak>
            <div id="left">
                <div class="artist-picker">

                </div>
                <div class="title-wrap">
                    <div class="title">Jerry Picker</div>
                    <div class="sub">A hack for sifting through the best of live dead songs</div>
                </div>
            </div>
            <div id="right" ng-controller="MainCtrl">
                <div class="search-pane">
                    <input type="text" ng-change="filter()" placeholder="search by year, song title, venue" value="" ng-model="data.filter" />
                    <span class="results-count" ng-click="clearFilter()">
                        <span class="results-count-number" ng-click="clearFilter()">{{ data.filtered.length }} {{ data.filtered.length == 1 ? 'Result' : 'Results' }}</span>
                    </span>
                </div>
                <div class="notification" ng-show="data.notification">
                    {{data.notification}}
                </div>
                <div class="results" ng-hide="data.notification">
                    <table>
                        <tr ng-repeat="track in data.filtered" ng-class="{'highlight': $index == data.selectedIndex}" ng-click="data.selectedIndex = $index">
                            <td class="play">
                                <span class="play-sprite" ng-show="data.loadingIndex != $index && data.playingIndex != $index" ng-click="play($index)"><i class="fa fa-play-circle-o"></i></span>
                                <span class="play-sprite" ng-show="data.loadingIndex == $index"><i class="fa fa-spinner"></i></span>
                                <span class="play-sprite active" ng-show="data.playingIndex == $index && !data.isPaused" ng-click="pause()"><i class="fa fa-pause"></i></span>
                                <span class="play-sprite active" ng-show="data.playingIndex == $index && data.isPaused" ng-click="unPause()"><i class="fa fa-play-circle-o"></i></span>
                            </td>
                            <td class="title"><span class="quick-filter" ng-click="setFilter(track.title)">{{track.title}}</span></td>
                            <td class="date"><span class="quick-filter" ng-click="setFilter(track.show.date)">{{track.show.date}}</span></td>
                            <td class="location"><span class="quick-filter" ng-click="setFilter(track.show.location)">{{track.show.location}}</span></td>
                        </tr>
                    </table>
                </div>
                <div class="player">
                    <audio>
                        <source type="audio/mpeg">
                    </audio>
                </div>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.3.15/angular.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js"></script>
        <?php if(index_config('is_build')): ?>
        <script>/*fi*/ window.bootstrap = <?php readfile("assets/data/{$artist_slug}.json") ?></script>
        <?php endif; ?>
        <script>window.artist_slug = <?php echo json_encode($artist_slug) ?></script>
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/github-fork-ribbon-css/0.1.1/gh-fork-ribbon.min.css" />
        <!--[if lt IE 9]>
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/github-fork-ribbon-css/0.1.1/gh-fork-ribbon.ie.min.css" />
        <![endif]-->
        <script>
            <?php readfile("assets/vendor/script/english-stemmer.js") ?>
        </script>
        <script>
            <?php readfile("assets/app.js") ?>
        </script>
        <style>
            .left .github-fork-ribbon {
                background-color: #444;
            }
        </style>
        <div class="github-fork-ribbon-wrapper left">
            <div class="github-fork-ribbon">
                <a target="_blank" href="https://github.com/katzgrau/jerry-picker">Fork me on GitHub</a>
            </div>
        </div>
    </body>
</html>