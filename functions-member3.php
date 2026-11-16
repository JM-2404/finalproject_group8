<?php
/**
 * MEMBER 3 FUNCTIONS (scoring + matching)
 *
 * Load AFTER Member 4's includes/functions.php. Every DB-facing function is
 * wrapped in function_exists() so nothing collides with Member 4's file.
 * Convention (matches Member 4's pages): functions take the id only, no $pdo.
 *
 * VERIFY against database/accessable.sql: the SQL in getAccessibilityBreakdown()
 * assumes tables features(feature_key, name, weight) and
 * venue_features(venue_id, feature_id, status).
 */

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

/** Find the shared PDO connection. Adjust ONE line here if Member 4 names it differently. */
if (!function_exists('m3_pdo')) {
    function m3_pdo(): PDO {
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) return $GLOBALS['pdo'];
        foreach (['getPDO', 'getDB', 'getConnection', 'db'] as $fn) {
            if (function_exists($fn)) { $c = $fn(); if ($c instanceof PDO) return $c; }
        }
        throw new RuntimeException('No PDO connection found. Check config/database.php for the variable or function name.');
    }
}

/** Pure scoring: only 'available' earns points; 'unknown' = 0 but is counted separately. */
function scoreFromRows(array $rows): array {
    $score = 0; $verified = 0; $breakdown = [];
    foreach ($rows as $row) {
        $status = $row['status'] ?? 'unknown';
        $weight = (int)$row['weight'];
        $earned = ($status === 'available') ? $weight : 0;
        if ($status !== 'unknown') $verified++;
        $score += $earned;
        $breakdown[$row['feature_key']] = [
            'label' => $row['label'], 'weight' => $weight,
            'status' => $status, 'earned' => $earned,
        ];
    }
    return ['score' => $score, 'verified' => $verified, 'total' => count($rows), 'breakdown' => $breakdown];
}

/** Full breakdown from the database. LEFT JOIN: a venue with no rows still lists every feature as unknown. */
if (!function_exists('getAccessibilityBreakdown')) {
    function getAccessibilityBreakdown(int $venueId): array {
        $stmt = m3_pdo()->prepare(
            "SELECT f.feature_key, f.name AS label, f.weight,
                    COALESCE(vf.status, 'unknown') AS status
             FROM features f
             LEFT JOIN venue_features vf ON vf.feature_id = f.id AND vf.venue_id = :vid
             ORDER BY f.weight DESC, f.name ASC");
        $stmt->execute([':vid' => $venueId]);
        return scoreFromRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

/** The shared function from the plan: returns the 0-100 integer, for Members 2, 4 and 5. */
if (!function_exists('calculateAccessibilityScore')) {
    function calculateAccessibilityScore(int $venueId): int {
        return getAccessibilityBreakdown($venueId)['score'];
    }
}

/** Requirement matching: which selected needs are met / missing / unverified. */
function matchRequirements(array $breakdown, array $needs): array {
    $met = $missing = $unknown = [];
    foreach ($needs as $key) {
        if (!isset($breakdown[$key])) continue;
        switch ($breakdown[$key]['status']) {
            case 'available':   $met[] = $key; break;
            case 'unavailable': $missing[] = $key; break;
            default:            $unknown[] = $key;
        }
    }
    return ['met' => $met, 'missing' => $missing, 'unknown' => $unknown];
}

/** Icon + text + css class. Never rely on colour alone. */
function statusMeta(string $status): array {
    switch ($status) {
        case 'available':   return ['icon' => '✓', 'text' => 'Available',   'class' => 'is-available'];
        case 'unavailable': return ['icon' => '✗', 'text' => 'Unavailable', 'class' => 'is-unavailable'];
        default:            return ['icon' => '?', 'text' => 'Unknown',     'class' => 'is-unknown'];
    }
}

function scoreBand(int $score): string {
    if ($score >= 80) return 'Highly accessible';
    if ($score >= 50) return 'Partly accessible';
    return 'Limited accessibility';
}
