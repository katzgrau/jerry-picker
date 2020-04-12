angular.module('jerry', [])

.controller('MainCtrl', function($scope, $http, $timeout, kucene, bench) {

    $scope.data = {
        filter: '',
        shows: [],
        tracks: [],
        filtered: [],
        loadingIndex: null,
        playingIndex: null,
        isPaused: false,
        selectedIndex: null,
        notification: null,
        progress: '50'
    }

    $scope.player = null;

    $scope.soundReady = false;
    $scope.currentSound = null;

    // Search-related
    $scope.searchIndexName = window.artist_slug;
    $scope.searchIndexFields = ['title', 'show.location', 'show.title', 'show.date'];
    $scope.searchIndex = null;

    var throttleFilter = _.debounce(function() {
        $timeout(function() {
            if ($scope.data.filter.length < 2 || !$scope.searchIndex) {
                angular.copy([], $scope.data.filtered);
                return;
            }

            var results;
            if ($scope.data.filter.indexOf('>') > 0) {
                console.log('Transition search: ', $scope.data.filter);
                results = $scope.transitionSearch($scope.data.filter);
            } else {
                console.log('Index search: ', $scope.data.filter);
                results = $scope.searchIndex.search($scope.data.filter);
            }

            angular.copy(results, $scope.data.filtered);
            $scope.data.notification = null;
        }, 100)
    }, 1000)

    $scope.filter = function() {
        $scope.data.notification = 'Searching ' + $scope.data.tracks.length + ' tracks ... Just one sec';
        $scope.data.loadingIndex = null;
        $scope.data.playingIndex = null;
        throttleFilter();
    };

    $scope.setFilter = function(filter) {
        $scope.data.filter = filter;
        $scope.filter();
    }

    $scope.transitionSearch = function(phrase) {
        var timer = bench.timer('Transition search on ' + phrase);
        var songs = phrase.toLowerCase().split('>'), match = true, results = [], seen = {};

        for (var i = 0; i < songs.length; i++) {
            songs[i] = songs[i].trim().toLowerCase();
        }

        songs = songs.filter(function(s) {
            return s.length > 0;
        });

        for (var t = 0; t < $scope.data.tracks.length; t++) {
            if ($scope.data.tracks[t].title.toLowerCase().indexOf(songs[0]) > -1) {
                for (var s = 1; s < songs.length; s++) {
                    if ($scope.data.tracks[t + s].title.toLowerCase().indexOf(songs[s]) < 0) {
                        match = false;
                        continue;
                    }
                }
            } else {
                match = false;
            }

            if (match) {
                for(var i = 0; i < songs.length; i++) {
                    if (!seen[$scope.data.tracks[t + i].url])
                        results.push($scope.data.tracks[t + i]);
                    seen[$scope.data.tracks[t + i].url] = true;
                }
            }

            match = true;
        }

        timer.stop();

        return results;
    }

    $scope.clearFilter = function() {
        angular.copy([], $scope.data.filtered);
        $scope.data.filter = '';
    }

    $scope.pause = function() {
        $scope.player.pause();
        $scope.data.isPaused = true;
    };

    $scope.unPause = function() {
        $scope.player.play();
        $scope.data.isPaused = false;
    };


    $scope.play = function(idx) {
        $scope.data.loadingIndex = idx;
        $scope.data.playingIndex = null;

        $scope.player.src = $scope.data.filtered[idx].url;
        $scope.player.onended = function () {
            console.log('hey');
            $scope.play(idx + 1);
        }
        $scope.player.play();
        $timeout(function() {
            $scope.data.playingIndex = idx;
            $scope.data.loadingIndex = null;
        }, 100)
    };

    $scope.init = function() {
        $scope.data.notification = 'Indexing tracks, give it a few seconds to complete ...';
        var t, seen = {}, process = function(data) {
            angular.copy(data, $scope.data.shows);

            for (var i in $scope.data.shows) {
                // Remove band title from show names - its assumed
                $scope.data.shows[i].title = $scope.data.shows[i].title.replace(/^(the)?\s*grateful dead\s+/gi, '');
                for (var j in $scope.data.shows[i].setlist) {
                    t = $scope.data.shows[i].setlist[j];
                    t.show = $scope.data.shows[i];
                    if (!seen[t.url]) {
                        $scope.data.tracks.push(t);
                    }
                    seen[t.url] = true;
                }
            }

            $scope.data.tracks.sort($scope.trackSort);
            $scope.searchIndex = kucene.index($scope.searchIndexName, $scope.data.tracks, $scope.searchIndexFields);
            $scope.data.notification = null;
        };

        if (window.bootstrap) {
            process(window.bootstrap);
        } else {
            $http.get('/assets/data/' + window.artist_slug + '.json')
                .success(function(data) {
                    process(data);
                });
        }

        GreenAudioPlayer.init({
            selector: '.player',
            stopOthersOnPlay: true,
            enableKeystrokes: true,
            showTooltips: true,
            showDownloadButton: true
        });

        document.querySelector('a.download__link').target = '_blank';

        window.p = $scope.player = document.querySelector('.player audio');
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
})

.service('bench', function() {
    var _bench = this;

    _bench.timer = function(name) {
        var start = new Date();
        return {
            stop: function() {
                var end  = new Date();
                var time = end.getTime() - start.getTime();
                console.log('Timer:', name, 'finished in', time, 'ms');
            }
        }
    };

    return _bench;
})

/**
 * A lazy man's lucene search engine, based on my limited knowledge of Lucene's internal workings
 * Should probably be moved to the backend soon
 */
.service('kucene', function(bench) {
    var _kucene = this;

    var indexes = {}; // easier to spell than indeces, don't care what y'all think

    _kucene.index = function(indexName, data, fields) {
        var idx = indexes[indexName] = {},
            t = null,
            term = null;

        var timer = bench.timer('Indexing');

        // build fields. d = document; f = field; t = token;
        // output: token string => document id
        for (var d = 0; d < data.length; d++) {
            for (var f = 0; f < fields.length; f++) {
                t = tokenize(grabField(fields[f], data[d]));
                for (var x = 0; x < t.length; x++) {
                    term = analyze(t[x]);
                    if (!idx[term]) {
                        idx[term] = [];
                    }
                    idx[term].push(d);
                }
            }
        }

        timer.stop();

        return {
            name: indexName,
            search: function(query) {
                return _kucene.search(indexName, query, data);
            }
        }
    };

    var stopWords = ["i", "me", "my", "myself", "we", "our", "ours", "ourselves", "you", "your", "yours", "yourself", "yourselves", "he", "him", "his", "himself", "she", "her", "hers", "herself", "it", "its", "itself", "they", "them", "their", "theirs", "themselves", "what", "which", "who", "whom", "this", "that", "these", "those", "am", "is", "are", "was", "were", "be", "been", "being", "have", "has", "had", "having", "do", "does", "did", "doing", "a", "an", "the", "and", "but", "if", "or", "because", "as", "until", "while", "of", "at", "by", "for", "with", "about", "against", "between", "into", "through", "during", "before", "after", "above", "below", "to", "from", "up", "down", "in", "out", "on", "off", "over", "under", "again", "further", "then", "once", "here", "there", "when", "where", "why", "how", "all", "any", "both", "each", "few", "more", "most", "other", "some", "such", "no", "nor", "not", "only", "own", "same", "so", "than", "too", "very", "s", "t", "can", "will", "just", "don", "should", "now"]
        .reduce((m, el) => { m[el] = true; return m; }, {}); // turn into a hash of el => true

    var stopFilter = function(tokens) {
        return tokens; // tempoarily disabled because the search failed for "further on down the road" (jack johnson) which happened to be a stop word
        return tokens.filter(function(token) {
            return !stopWords[token];
        });
    }

    var tokenize = function(string) {
        var m = string.toLowerCase().match(/[\d\w]+/g);
        if (m && m.length) {
            return stopFilter(m);
        } else {
            return [];
        }
    }

    var analyze = function(term) {
        return stemmer(term);
    }

    var grabField = function(fieldName, obj) {
        var f = fieldName.split('.'), prop = null;

        if (f.length === 0)
            return obj[fieldName];

        prop = obj;
        for (var i = 0; i < f.length; i++) {
            try {
                prop = prop[f[i]];
            } catch(e) {
                console.log('Error', obj)
            }
        }

        return prop;
    }

    /**
     * Return the intersection of multiple arrays
     * @param arrays
     * @returns {*}
     */
    var intersection = function(arrays) {
        if(!arrays.length) return [];
        return arrays.shift().reduce(function(res, v) {
            if (res.indexOf(v) === -1 && arrays.every(function(a) {
                    return a.indexOf(v) !== -1;
                })) res.push(v);
            return res;
        }, []);
    }

    _kucene.search = function(indexName, phrase, data) {
        var terms = tokenize(phrase), term = null, matches = [], idx = indexes[indexName];
        var timer = bench.timer('Searching on ' + phrase);

        for (var i = 0; i < terms.length; i++) {
            term = analyze(terms[i]);
            if (idx[term]) {
                matches.push(idx[term]);
            }
        }

        var winners = intersection(matches);
        timer.stop()

        if (!data) {
            return winners;
        } else {
            var results = [];
            for(var i = 0; i < winners.length; i++) {
                results.push(data[winners[i]]);
            }
            return results;
        }
    }

    return _kucene;
});

