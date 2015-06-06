angular.module('jerry', [])

.controller('MainCtrl', function($scope, $http, $timeout) {

    $scope.data = {
        filter: '',
        shows: [],
        tracks: [],
        filtered: [],
        loadingIndex: null,
        playingIndex: null,
        isPaused: false,
        selectedIndex: null,
        notification: null
    }

    $scope.soundReady = false;
    $scope.currentSound = null;

    var throttleFilter = _.debounce(function() {
        $timeout(function() {
            if ($scope.data.filter.length < 2) {
                angular.copy([], $scope.data.filtered);
                return;
            }

            var f = $scope.data.filter.toLowerCase();

            angular.copy([], $scope.data.filtered);

            for (var i in $scope.data.tracks) {
                if($scope.data.tracks[i].searchable.indexOf(f) >= 0) {
                    $scope.data.filtered.push($scope.data.tracks[i]);
                }
            }

            $scope.data.notification = null;
        }, 100)
    }, 1000)

    $scope.filter = function() {
        $scope.data.notification = 'Searching ... Just one sec';
        $scope.data.loadingIndex = null;
        $scope.data.playingIndex = null;
        throttleFilter();
    };

    $scope.clearFilter = function() {
        angular.copy([], $scope.data.filtered);
        $scope.data.filter = '';
    }

    $scope.play = function(idx) {
        $scope.data.loadingIndex = idx;
        $scope.data.playingIndex = null;

        if ($scope.currentSound) {
            $scope.currentSound.destruct();
        }

        var sm = soundManager.createSound({
            id: 'mySound',
            url: $scope.data.filtered[idx].url,
            autoLoad: true,
            autoPlay: true,
            onload: function() {
                $timeout(function() {
                    $scope.currentSound = sm;
                    $scope.data.loadingIndex = null;
                }, 100)
            },
            onplay: function() {
                $timeout(function() {
                    $scope.data.playingIndex = idx;
                    $scope.data.loadingIndex = null;
                }, 100)
            },
            onfinish: function() {
                $scope.play(idx + 1)
            },
            volume: 100
        });
    };

    $scope.pause = function() {
        $scope.currentSound.pause();
        $scope.data.isPaused = true;
    };

    $scope.unPause = function() {
        $scope.currentSound.play();
        $scope.data.isPaused = false;
    };

    $scope.next = function() {
        $scope.play($scope.data.playingIndex + 1);
    }

    $scope.last = function() {
        $scope.play($scope.data.playingIndex - 1);
    }

    $scope.init = function() {
        var t, process = function(data) {
            angular.copy(data, $scope.data.shows);

            for (var i in $scope.data.shows) {
                for (var j in $scope.data.shows[i].setlist) {
                    t = $scope.data.shows[i].setlist[j];
                    t.show = $scope.data.shows[i];
                    t.searchable = t.title.toLowerCase() + ' ' +
                    $scope.data.shows[i].date + ' ' +
                    $scope.data.shows[i].location.toLowerCase() + ' ' +
                    $scope.data.shows[i].title.toLowerCase();

                    $scope.data.tracks.push(t)
                }
            }

            $scope.data.tracks.sort($scope.trackSort)
        };

        if(window.bootstrap) {
            process(window.bootstrap);
        } else {
            $http.get('/assets/data/grateful-dead.json')
                .success(function(data) {
                    process(data);
                });
        }

        soundManager.setup({
            url: 'assets/vendor/sound/swf/',
            flashVersion: 9, // optional: shiny features (default = 8)
            // optional: ignore Flash where possible, use 100% HTML5 mode
            //preferFlash: false,
            onready: function() {
                $scope.soundReady = true;
            }
        });
    };

    $scope.trackSort = function (track1, track2){
        if(track1.show.date < track2.show.date)
            return -1;
        if(track2.show.date < track1.show.date)
            return 1;

        if(track1.order < track2.order)
            return -1;
        if(track2.order < track1.order)
            return 1;

        return 0;
    }

    $scope.init();
});

