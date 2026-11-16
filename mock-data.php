<?php
/**
 * MOCK DATA (temporary). Same venues as Member 2's mock-venues.js so the demo is consistent.
 * Status string order: wheelchair_entrance, ramps, elevator, accessible_toilet,
 * accessible_parking, wide_doorways, tactile_guidance, braille_signage
 * a = available, u = unavailable, ? = unknown (tactile/braille are not collected yet).
 * Delete this file once the database is wired in.
 */
$MOCK_FEATURES = [
    ['feature_key' => 'wheelchair_entrance', 'label' => 'Wheelchair Entrance', 'weight' => 20],
    ['feature_key' => 'ramps',               'label' => 'Ramps',               'weight' => 15],
    ['feature_key' => 'elevator',            'label' => 'Elevator',            'weight' => 15],
    ['feature_key' => 'accessible_toilet',   'label' => 'Accessible Toilet',   'weight' => 15],
    ['feature_key' => 'accessible_parking',  'label' => 'Accessible Parking',  'weight' => 10],
    ['feature_key' => 'wide_doorways',       'label' => 'Wide Doorways',       'weight' => 10],
    ['feature_key' => 'tactile_guidance',    'label' => 'Tactile Guidance',    'weight' => 8],
    ['feature_key' => 'braille_signage',     'label' => 'Braille Signage',     'weight' => 7],
];

$MOCK_VENUES = [
    1 => ['name' => 'Library',               'cat' => 'Academic',   'city' => 'Berekuso', 'addr' => 'Ashesi University', 's' => 'aauaaa??', 'desc' => 'The campus library with study spaces and reading areas.'],
    2 => ['name' => 'Engineering Building',  'cat' => 'Academic',   'city' => 'Berekuso', 'addr' => 'Ashesi University', 's' => 'aaauaa??', 'desc' => 'Lecture rooms and labs for engineering students.'],
    3 => ['name' => 'Cafeteria',             'cat' => 'Dining',     'city' => 'Berekuso', 'addr' => 'Ashesi University', 's' => 'aauuua??', 'desc' => 'The main campus dining hall.'],
    4 => ['name' => 'Amphitheatre',          'cat' => 'Events',     'city' => 'Berekuso', 'addr' => 'Ashesi University', 's' => 'uauuau??', 'desc' => 'Outdoor venue for talks, performances and events.'],
    5 => ['name' => 'Sports Field',          'cat' => 'Sports',     'city' => 'Berekuso', 'addr' => 'Ashesi University', 's' => 'uuuuau??', 'desc' => 'Open sports ground used for matches and training.'],
    6 => ['name' => 'On-Campus Housing',    'cat' => 'Residence',  'city' => 'Berekuso', 'addr' => 'Ashesi University', 's' => 'aauaaa??', 'desc' => 'Student residences on campus.'],
    7 => ['name' => 'Off-Campus Housing',   'cat' => 'Residence',  'city' => 'Berekuso', 'addr' => 'Berekuso',          's' => 'uuuuaa??', 'desc' => 'Private student accommodation near campus.'],
    8 => ['name' => 'STEM Building',         'cat' => 'Academic',   'city' => 'Berekuso', 'addr' => 'Ashesi University', 's' => 'aaaaaa??', 'desc' => 'Science, technology, engineering and maths facilities.'],
    9 => ['name' => 'The Hive',              'cat' => 'Innovation', 'city' => 'Berekuso', 'addr' => 'Ashesi University', 's' => 'aauauu??', 'desc' => 'Innovation and collaboration space.'],
];

function mockGetVenue(int $id): ?array {
    global $MOCK_VENUES;
    if (!isset($MOCK_VENUES[$id])) return null;
    $v = $MOCK_VENUES[$id];
    return ['id' => $id, 'name' => $v['name'], 'category_name' => $v['cat'], 'city' => $v['city'], 'address' => $v['addr'], 'description' => $v['desc']];
}

function mockAllVenues(): array {
    global $MOCK_VENUES;
    $out = [];
    foreach ($MOCK_VENUES as $id => $v) $out[] = ['id' => $id, 'name' => $v['name']];
    return $out;
}

function mockScore(int $id): array {
    global $MOCK_FEATURES, $MOCK_VENUES;
    $map = ['a' => 'available', 'u' => 'unavailable', '?' => 'unknown'];
    $rows = [];
    foreach ($MOCK_FEATURES as $i => $f) {
        $f['status'] = $map[$MOCK_VENUES[$id]['s'][$i] ?? '?'] ?? 'unknown';
        $rows[] = $f;
    }
    return scoreFromRows($rows);
}

function mockReviews(int $id): array {
    return [
        ['full_name' => 'Ama', 'rating' => 5, 'title' => 'Step-free and welcoming', 'comment' => 'Easy to get in, and staff were helpful.', 'created_at' => '2026-11-02'],
        ['full_name' => 'Kofi', 'rating' => 3, 'title' => 'Mostly good', 'comment' => 'The lift was out of service on my visit.', 'created_at' => '2026-10-27'],
    ];
}
