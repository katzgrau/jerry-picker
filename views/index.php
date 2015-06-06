<html>
    <head>
        <title>Jerry Picker</title>
        <link href="assets/app.css" media="screen" rel="stylesheet" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.css" media="screen" rel="stylesheet" />
    </head>
    <body>
        <div id="main" ng-app="jerry" ng-cloak>
            <div id="left">
                <div class="title-wrap">
                    <div class="title">Jerry Picker</div>
                    <div class="sub">A dumb hack for sifting through the best of live dead songs</div>
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
                            <td class="title">{{track.title}}</td>
                            <td class="date">{{track.show.date}}</td>
                            <td class="location">{{track.show.location}}</td>
                        </tr>
                    </table>
                </div>
                <div class="player">
                    <span class="player-icon" ng-click="last()"><i class="fa fa-fast-backward"></i></span>
                    <span class="player-icon" ng-click="next()"><i class="fa fa-fast-forward"></i></span>
                    <span class="player-icon" ng-click="pause()" ng-show="!data.isPaused" ng-click="pause()"><i class="fa fa-pause"></i></span>
                    <span class="player-icon" ng-click="unPause()" ng-show="data.isPaused"><i class="fa fa-play"></i></span>
                    <span class="player-icon" ng-click="stop()" ng-show="data.playingIndex"><i class="fa fa-stop"></i></span>
                </div>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.3.15/angular.js"></script>
        <script src="assets/vendor/sound/script/soundmanager2-nodebug-jsmin.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js"></script>
        <?php if(index_config('is_build')): ?>
        <script>window.bootstrap = <?php readfile('assets/data/grateful-dead.json') ?></script>
        <?php endif; ?>
        <script src="assets/app.js"></script>
    </body>
</html>