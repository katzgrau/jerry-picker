<?php
// Keep in mind that I wouldn't ever build a production webapp in
//  spaghetti php. I'm just being lazy since this is a hack and I
//  probably shouldn't be spending time on on it while I let more
//  important things wait

$search_term = 'Grateful Dead';
$rows   = 1000;
$sleep_time = .2 * 1000000;


if($argc > 1) {
    $search_term = urlencode($argv[1]);
}

if($argc > 2) {
    $rows = intval($argv[2]);
}

$q = "https://archive.org/advancedsearch.php?q=creator%3A%22{$search_term}%22&output=json&rows=$rows";
$output_file = preg_replace('/[^\w]+/', '-', strtolower($search_term));

$payload = file_get_contents($q);
$payload = json_decode($payload);

$shows = $payload->response->docs;
$list = [];

$track_count = 0;
$show_count  = 0;
$drop_count  = 0;

foreach ($shows as $show) {
    $item = array ();

    // filter out cabbage
    if (!property_exists($show, 'title')) continue;
    if (!property_exists($show, 'type')) continue;
    if (!property_exists($show, 'downloads')) continue;

    if ($show->type != 'sound') continue;
    if ($show->downloads < 100) continue;

    $item['title'] = $show->title;

    if (property_exists($show, 'date')) {
        $item['date'] = date('Y-m-d', strtotime($show->date));
    } else {
        $matches = null;
        if (preg_match('/\d{4}\-d{1,2}\-d{1,2}/', $show->title, $matches)) {
            $item['date'] = date('Y-m-d', date('Y-m-d', strtotime($matches[1])));
        }
    }

    if (property_exists($show, 'coverage')
        && is_array($show->coverage)
        && count($show->coverage) > 0) {
        $item['location'] = $show->coverage[0];
    } else {
        $item['location'] = 'Unknown Location';
    }

    $item['id'] = $show->identifier;

    $q = "http://archive.org/metadata/{$item['id']}";
    $payload = file_get_contents($q);
    $payload = json_decode($payload);

    if (!property_exists($payload, 'files')) continue;

    $set = $payload->files;
    $tracks = [];

    if(count($set) == 0) continue;

    foreach ($set as $song) {

        $track = array();
        if (!property_exists($song, 'title')) continue;
        if (!property_exists($song, 'track')) continue;
        if (!stristr($song->format, 'mp3')) continue;

        $track['title'] = $song->title;
        $track['length'] = $song->length;
        $track['file'] = $song->name;
        $track['order'] = $song->track;
        $track['url'] = "https://archive.org/download/{$item['id']}/{$song->name}";

        $tracks[] = $track;

        $track_count++;
    }

    $item['setlist'] = $tracks;

    // Only add it if there are playable tracks
    if(count($tracks)) {
        $list[] = $item;
        $show_count++;
        echo "Imported $show_count of $rows: " . $list[count($list) - 1]['title'] . ", $drop_count dropped\n";
    } else {
        $drop_count++;
    }

    usleep($sleep_time);
}

echo "Imported $show_count shows, $track_count tracks total\n";
file_put_contents(dirname(__FILE__) . "/../assets/data/{$output_file}.json", json_encode($list));

