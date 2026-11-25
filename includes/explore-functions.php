<?php
/**
 * Explore page data (Member 2).
 * Returns venues with a three-state status for every feature
 * (available / unavailable / unknown). Uses Member 4's getDB() and getAllVenues().
 */
require_once __DIR__ . '/functions.php';

if (!function_exists('getExploreVenues')) {

    /** Which Explore group a category belongs to. */
    function exploreGroupForCategory(?string $slug): string
    {
        $map = [
            'academic'   => 'universities',
            'dining'     => 'universities',
            'sports'     => 'universities',
            'residence'  => 'universities',
            'campus'     => 'universities',
            'restaurant' => 'restaurants',
            'heritage'   => 'tourist-sites',
            'recreation' => 'recreation',
            'airports'   => 'airports',
        ];
        return $map[$slug ?? ''] ?? 'other';
    }

    function getExploreVenues(): array
    {
        $db     = getDB();
        $venues = getAllVenues(['sort' => 'name']);

        // Region and prototype email for each venue
        $extra = [];
        foreach ($db->query('SELECT id, region, contact_email FROM venues') as $row) {
            $extra[(int) $row['id']] = $row;
        }

        // Status of every feature for every venue
        $statuses = [];
        $sql = 'SELECT vf.venue_id, f.feature_key, vf.status
                  FROM venue_features vf
                  JOIN features f ON f.id = vf.feature_id';
        foreach ($db->query($sql) as $row) {
            $statuses[(int) $row['venue_id']][$row['feature_key']] = $row['status'];
        }

        $result = [];
        foreach ($venues as $v) {
            $id = (int) $v['id'];
            $result[] = [
                'id'          => $id,
                'name'        => $v['name'],
                'slug'        => $v['slug'],
                'parentId'    => $v['parent_id'] !== null ? (int) $v['parent_id'] : null,
                'parentVenue' => $v['parent_name'] ?? '',
                'group'       => exploreGroupForCategory($v['category_slug'] ?? null),
                'category'    => $v['category_name'] ?? '',
                'location'    => $v['city'],
                'region'      => $extra[$id]['region'] ?? '',
                'email'       => $extra[$id]['contact_email'] ?? '',
                'score'       => $v['accessibility_score'],
                'rating'      => $v['avg_rating'],
                'reviewCount' => $v['review_count'],
                'features'    => $statuses[$id] ?? [],
            ];
        }
        return $result;
    }
}