<?php
/**
 * Skill Matchmaking Engine API Endpoint (Business Layer)
 * GET /api/matches/find.php
 */

define('APP_INIT', true);

// Resolve root directory path relative to this file's location
$rootDir = dirname(__DIR__, 2);

require_once $rootDir . '/config/db.php';
require_once $rootDir . '/includes/auth.php';
require_once $rootDir . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_auth();

$userId = current_user_id();

try {
    $pdo = get_db();

    // 1. Fetch current user's skills
    $mySkillsStmt = $pdo->prepare("
        SELECT us.skill_id, us.skill_type, us.proficiency, s.name, s.category
        FROM user_skills us
        INNER JOIN skills s ON us.skill_id = s.id
        WHERE us.user_id = :uid
    ");
    $mySkillsStmt->execute([':uid' => $userId]);
    $mySkills = $mySkillsStmt->fetchAll(PDO::FETCH_ASSOC);

    $myOffered = [];
    $myWanted = [];

    foreach ($mySkills as $sk) {
        if ($sk['skill_type'] === 'OFFER') {
            $myOffered[$sk['skill_id']] = $sk;
        } else {
            $myWanted[$sk['skill_id']] = $sk;
        }
    }

    $myOfferedIds = array_keys($myOffered);
    $myWantedIds = array_keys($myWanted);

    if (empty($myOfferedIds) && empty($myWantedIds)) {
        json_response(true, 'No skills registered yet.', [
            'has_skills'       => false,
            'two_way_matches'  => [],
            'one_way_matches'  => [],
            'my_offered_count' => 0,
            'my_wanted_count'  => 0
        ]);
    }

    // 2. Preload active swap requests (Using distinct parameters to prevent HY093)
    $reqStmt = $pdo->prepare("
        SELECT id, sender_id, receiver_id, status
        FROM swap_requests
        WHERE (sender_id = :uid1 OR receiver_id = :uid2)
          AND status IN ('pending', 'accepted')
    ");
    $reqStmt->execute([
        ':uid1' => $userId,
        ':uid2' => $userId
    ]);
    $existingRequests = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

    $requestMap = [];
    foreach ($existingRequests as $req) {
        $otherId = ($req['sender_id'] == $userId) ? $req['receiver_id'] : $req['sender_id'];
        $requestMap[$otherId] = [
            'request_id' => $req['id'],
            'is_sender'  => ($req['sender_id'] == $userId),
            'status'     => $req['status']
        ];
    }

    // 3. Bulk query candidates AND their skills in ONE single SQL call
    $candidatesStmt = $pdo->prepare("
        SELECT 
            u.id AS user_id, u.name, u.username, u.bio, u.location, u.profile_image,
            us.skill_id, us.skill_type, us.proficiency,
            s.name AS skill_name, s.category AS skill_category
        FROM users u
        INNER JOIN user_skills us ON u.id = us.user_id
        INNER JOIN skills s ON us.skill_id = s.id
        WHERE u.id != :uid AND u.status = 'active' AND u.role != 'admin'
    ");
    $candidatesStmt->execute([':uid' => $userId]);
    $rows = $candidatesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Group user records and skills array in memory
    $candidates = [];
    foreach ($rows as $row) {
        $cId = (int)$row['user_id'];
        if (!isset($candidates[$cId])) {
            $candidates[$cId] = [
                'info' => [
                    'id'            => $row['user_id'],
                    'name'          => $row['name'],
                    'username'      => $row['username'],
                    'bio'           => $row['bio'],
                    'location'      => $row['location'],
                    'avatar_url'    => get_avatar_url($row['profile_image']),
                    'swap_status'   => $requestMap[$cId] ?? null
                ],
                'offers' => [],
                'wants'  => []
            ];
        }

        $skillData = [
            'skill_id'    => $row['skill_id'],
            'skill_type'  => $row['skill_type'],
            'proficiency' => $row['proficiency'],
            'name'        => $row['skill_name'],
            'category'    => $row['skill_category']
        ];

        if ($row['skill_type'] === 'OFFER') {
            $candidates[$cId]['offers'][$row['skill_id']] = $skillData;
        } else {
            $candidates[$cId]['wants'][$row['skill_id']] = $skillData;
        }
    }

    // 4. Compute matches
    $twoWayMatches = [];
    $oneWayMatches = [];

    foreach ($candidates as $candId => $candidate) {
        $theyOfferWhatIWant = [];
        foreach ($myWantedIds as $wId) {
            if (isset($candidate['offers'][$wId])) {
                $theyOfferWhatIWant[] = $candidate['offers'][$wId];
            }
        }

        $iOfferWhatTheyWant = [];
        foreach ($myOfferedIds as $oId) {
            if (isset($candidate['wants'][$oId])) {
                $iOfferWhatTheyWant[] = $myOffered[$oId];
            }
        }

        if (!empty($theyOfferWhatIWant) && !empty($iOfferWhatTheyWant)) {
            $twoWayMatches[] = array_merge($candidate['info'], [
                'match_type'          => 'two_way',
                'compatibility_score' => 100,
                'offered_to_you'      => $theyOfferWhatIWant,
                'wanted_from_you'     => $iOfferWhatTheyWant,
                'primary_exchange'    => [
                    'you_learn' => $theyOfferWhatIWant[0]['name'],
                    'you_teach' => $iOfferWhatTheyWant[0]['name']
                ],
                'explanation'         => "Perfect Reciprocal Match! {$candidate['info']['name']} can teach you {$theyOfferWhatIWant[0]['name']} and wants to learn {$iOfferWhatTheyWant[0]['name']} from you."
            ]);
        } elseif (!empty($theyOfferWhatIWant)) {
            $oneWayMatches[] = array_merge($candidate['info'], [
                'match_type'          => 'one_way',
                'compatibility_score' => 70,
                'offered_to_you'      => $theyOfferWhatIWant,
                'wanted_from_you'     => [],
                'explanation'         => "Offers {$theyOfferWhatIWant[0]['name']}, which you want to learn."
            ]);
        }
    }

    // Sort Two-Way matches by total overlapping skills count
    usort($twoWayMatches, function ($a, $b) {
        $countA = count($a['offered_to_you']) + count($a['wanted_from_you']);
        $countB = count($b['offered_to_you']) + count($b['wanted_from_you']);
        return $countB <=> $countA;
    });

    json_response(true, 'Skill matches calculated.', [
        'has_skills'       => true,
        'my_offered'       => array_values($myOffered),
        'my_wanted'        => array_values($myWanted),
        'two_way_matches'  => $twoWayMatches,
        'one_way_matches'  => $oneWayMatches,
        'two_way_count'    => count($twoWayMatches),
        'one_way_count'    => count($oneWayMatches)
    ]);

} catch (Exception $e) {
    error_log('[Skill Matching Error] ' . $e->getMessage());
    json_response(false, 'Unable to compute matches: ' . $e->getMessage(), null, 500);
}