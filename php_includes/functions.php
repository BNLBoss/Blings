<?php
function timeAgo($datetime, $full = false) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [];
    $units = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    foreach ($units as $key => &$val) {
        if ($diff->$key) {
            $string[] = $diff->$key . ' ' . $val . ($diff->$key > 1 ? 's' : '');
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

$iconMapping = [
    'suggestion' => 'fa-comment',
    'poll' => 'fa-pie-chart',
    'project' => 'fa-building',
    'townhall' => 'fa-users',
    'issue' => 'fa-comment', // Change this as needed
];
?>