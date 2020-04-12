# Jerry Picker

Jerry Picker is a tool for combing through and playing Grateful Dead tracks
hosted on Archive.org. A handful of other artists are supported too, and you
can add your own.

## Running Locally

`php -S 0.0.0.0:8080 # requires php 5+`

Then open a browser to `http://localhost:8080` and you'll see a rough
approximation of a Spotify player (even "rough approximation" is putting it very nicely).

## Adding Artists/Track Data

You can add support for new artists (or more tracks for existing artists)
by running the seed script. To add "Tedeschi Trucks Band" for example, you can
just run:

`php lib/seed "Tedeschi Trucks Band"`

And it'll import track data and store them in `assets/data/tedeschi-trucks-band.json`

By default, it'll pull the information for up to 1000 shows, which for most
bands exceeds the show count available on Archive.org anyway. But for the
Grateful Dead, you might need to raise that limit with and optional second
parameter:

`php lib/seed "Tedeschi Trucks Band" 2000`

Once this is done, you can start the server (see 'Running Locally' above)
and just visit:

`http://localhost:8080/tedeschi-trucks-band`

The server basically just scans the `assets/data` directory and makes all band data
available as a subdirectory with the "slugified" version of the band's name.

## Building a Static Version

You can build a static version of the site which doesn't require PHP (which is
how it's hosted on Github Pages).

For that, just run: `php index.php build`

That will place a static/html version of the site in the `build/` directory with
the timestamp of the build in its name.

## Horn Tooting

The project uses two things that I think are pretty cool:

1. `index.php`: index.php is a script/single-file framework for quickly
building sites that can be served dynamically (eg, via a webserver like Apache)
or build to static HTML. Yes, there are many static site generators out there,
but this one is my own and I like it. It's somewhat like a single-file CodeIgniter
without any data layer ability built in. It supports plugins ("bottlenecks") too.

2. `kucene`: Kenny-Lucene is a javascript search algorithm for efficiently searching
through thousands of tracks. Complete with a `StandardAnalyzer`, it's a
reverse-index based full text search algorithm based on the Java Lucene project,
which I worked with a while back. You'll find that in angular.js service form in
`assets/app.js`

## Changelog

### 2020: Easter-quarantine-induced-boredom-inspired-change series

* Added 5 years' worth the new tracks from Archive.org, which includes an absurd number of Grateful Dead Tracks
* Pushed the Slightly Stoopid, Jack Johnson, and Phish datasets to production.
* Added an English word stemmer for better matches on tracks names (eg, a search for "vision of johanna" should match "visions of johanna")
* Filtered English stop words out of searches
* Realized I like this hack a lot more than I remember
* Use newer / improved HTML audio player

Special thanks:

* Porter word stemmer: https://github.com/kristopolous/Porter-Stemmer
* Audio player: https://github.com/greghub/green-audio-player

The media playing ability is currently attrocious and I plan to resolve that.
Options for for portable audio playing were limited in 2015. 2020 of progress
should yield some better and perhaps native options.

### 2015/2016:

Wrote most of it.
