<?php
/**
 * EXPLORE PAGE DATA (Member 2)
 *
 * PURPOSE: collect everything the Explore page needs about each venue
 * and return it as one clean PHP array.
 *
 * WHY A NEW FILE: Member 4 owns includes/functions.php. Putting our code
 * here means we never edit their file, so there are no merge conflicts.
 */

// Load Member 4's shared functions (getDB, getAllVenues).
// require_once = load the file, but only once, even if included twice.
require_once __DIR__ . '/functions.php';

// Safety check: only define these functions if they don't exist yet.
// This avoids the "cannot redeclare function" error if two files define the same name.
if (!function_exists('getExploreVenues')) {

    /**
     * Decide which of the 5 Explore groups a category belongs to.
     * Example: the categories 'academic' and 'dining' both belong to 'universities'.
     * The "?string" means the slug can be text or null.
     */
    function exploreGroupForCategory(?string $slug): string
    {
        // A lookup table (associative array): category slug => Explore group.
        $map = [
            'academic'   => 'universities',
            'dining'     => 'universities',
            'sports'     => 'universities',
            'residence'  => 'universities',
            'campus'     => 'universities',
            'restaurant' => 'restaurants',
            'heritage'   => 'tourist-sites',   // slug 'heritage' is displayed as "Tourist Sites"
            'recreation' => 'recreation',
            'airports'   => 'airports',
        ];

        // "??" means: if the key is missing, use 'other' instead of crashing.
        return $map[$slug ?? ''] ?? 'other';
    }

    /**
     * Build the full venue list for the Explore page.
     * Each venue comes back with its score, rating, region, prototype email,
     * and the STATUS of every accessibility feature.
     */
    function getExploreVenues(): array
    {
        // One shared database connection (PDO) created by Member 4's config.
        $db = getDB();

        // Member 4's function: venues with score, average rating and review count.
        // It uses prepared statements internally, which protects against SQL injection.
        $venues = getAllVenues(['sort' => 'name']);

        // Region and email are the columns we added in migration-explore-2.sql.
        // We store them in an array keyed by venue id so we can look them up fast.
        $extra = [];
        foreach ($db->query('SELECT id, region, contact_email FROM venues') as $row) {
            $extra[(int) $row['id']] = $row;
        }

        // Get the status of EVERY feature for EVERY venue in one query
        // (JOIN links venue_features to features so we get the feature_key name).
        // Result shape: $statuses[venue id]['ramps'] = 'available' | 'unavailable' | 'unknown'
        $statuses = [];
        $sql = 'SELECT vf.venue_id, f.feature_key, vf.status
                  FROM venue_features vf
                  JOIN features f ON f.id = vf.feature_id';
        foreach ($db->query($sql) as $row) {
            $statuses[(int) $row['venue_id']][$row['feature_key']] = $row['status'];
        }
        // These two queries have no user input in them, so they are safe without placeholders.

        // Combine everything into the shape the JavaScript expects.
        $result = [];
        foreach ($venues as $v) {
            $id = (int) $v['id'];
            $result[] = [
                'id'          => $id,
                'name'        => $v['name'],
                'slug'        => $v['slug'],
                'parentId'    => $v['parent_id'] !== null ? (int) $v['parent_id'] : null,
                'parentVenue' => $v['parent_name'] ?? '',          // e.g. "Ashesi University Campus"
                'group'       => exploreGroupForCategory($v['category_slug'] ?? null),
                'category'    => $v['category_name'] ?? '',
                'location'    => $v['city'],
                'region'      => $extra[$id]['region'] ?? '',
                'email'       => $extra[$id]['contact_email'] ?? '',
                'score'       => $v['accessibility_score'],         // 0-100, from Member 4/3's scoring
                'rating'      => $v['avg_rating'],                  // average review rating, can be null
                'reviewCount' => $v['review_count'],
                'features'    => $statuses[$id] ?? [],              // the three-state statuses
            ];
        }
        return $result;
    }
}