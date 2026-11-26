<?php
/**
 * Availability Business Logic & Validation Helpers (Application Layer)
 * Skill Swap Marketplace
 */

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

/**
 * Canonical ordered days of the week.
 *
 * @return string[]
 */
function availability_days(): array {
    return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
}

/**
 * Valid availability status definitions.
 *
 * @return array<string, array<string, string>>
 */
function availability_statuses(): array {
    return [
        'available' => [
            'key'         => 'available',
            'label'       => 'Available',
            'description' => 'Open to skill exchanges',
            'tone'        => 'success'
        ],
        'busy' => [
            'key'         => 'busy',
            'label'       => 'Busy',
            'description' => 'Limited availability',
            'tone'        => 'warning'
        ],
        'unavailable' => [
            'key'         => 'unavailable',
            'label'       => 'Not Available',
            'description' => 'Currently unavailable',
            'tone'        => 'muted'
        ]
    ];
}

/**
 * Parse a time string ("HH:MM" or "HH:MM:SS") into minutes since midnight [0..1439].
 */
function availability_time_to_minutes(string $timeStr): ?int {
    $timeStr = trim($timeStr);
    if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $timeStr, $matches)) {
        return null;
    }
    $hours = (int)$matches[1];
    $mins = (int)$matches[2];
    return ($hours * 60) + $mins;
}

/**
 * Normalize a time string ("HH:MM" or "HH:MM:SS") to "HH:MM:00" SQL TIME format.
 */
function availability_normalize_sql_time(string $timeStr): ?string {
    $mins = availability_time_to_minutes($timeStr);
    if ($mins === null) {
        return null;
    }
    return sprintf('%02d:%02d:00', intdiv($mins, 60), $mins % 60);
}

/**
 * Convert minutes since midnight to "HH:MM" (24h).
 */
function availability_minutes_to_hhmm(int $minutes): string {
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

/**
 * Format SQL TIME or "HH:MM" string into readable 12-hour display (e.g. "6:00 PM").
 */
function availability_format_time_12h(string $timeStr): string {
    $mins = availability_time_to_minutes($timeStr);
    if ($mins === null) {
        return $timeStr;
    }
    $h24 = intdiv($mins, 60);
    $m = $mins % 60;
    $suffix = ($h24 >= 12) ? 'PM' : 'AM';
    $h12 = $h24 % 12;
    if ($h12 === 0) {
        $h12 = 12;
    }
    return sprintf('%d:%02d %s', $h12, $m, $suffix);
}

/**
 * Validate availability payload and return normalized ['valid', 'status', 'slots', 'errors'].
 *
 * Accepted $schedule formats:
 * - Keyed by day: ['Monday' => [['start_time' => '18:00', 'end_time' => '21:00'], ...], ...]
 *   or ['Monday' => ['enabled' => true, 'slots' => [...]], ...]
 * - Flat list of slots: [['day_of_week' => 'Monday', 'start_time' => '18:00', 'end_time' => '21:00'], ...]
 */
function validate_availability_payload(mixed $rawStatus, mixed $rawSchedule): array {
    $errors = [];
    $validStatuses = array_keys(availability_statuses());
    $status = is_string($rawStatus) ? strtolower(trim($rawStatus)) : '';

    if (!in_array($status, $validStatuses, true)) {
        $errors[] = 'Please select a valid availability status.';
    }

    if (!is_array($rawSchedule)) {
        $errors[] = 'Invalid schedule payload.';
        return ['valid' => false, 'status' => $status, 'slots' => [], 'errors' => $errors];
    }

    $validDays = availability_days();
    $groupedByDay = array_fill_keys($validDays, []);

    // Detect keyed-by-day map vs flat list
    $isDayMap = false;
    foreach ($rawSchedule as $k => $v) {
        if (is_string($k) && in_array($k, $validDays, true)) {
            $isDayMap = true;
            break;
        }
    }

    if ($isDayMap) {
        foreach ($rawSchedule as $day => $dayEntry) {
            if (!in_array($day, $validDays, true)) {
                $errors[] = "Invalid day specified: {$day}.";
                continue;
            }
            if (!is_array($dayEntry)) {
                continue;
            }
            // Support either ['enabled' => bool, 'slots' => [...]] or direct array of slots
            if (array_key_exists('enabled', $dayEntry)) {
                if (empty($dayEntry['enabled'])) {
                    continue;
                }
                $daySlots = $dayEntry['slots'] ?? [];
            } else {
                $daySlots = $dayEntry;
            }

            if (!is_array($daySlots)) {
                $errors[] = "Invalid slots list for {$day}.";
                continue;
            }

            foreach ($daySlots as $slot) {
                if (!is_array($slot)) {
                    $errors[] = "Malformed time slot on {$day}.";
                    continue;
                }
                $groupedByDay[$day][] = $slot;
            }
        }
    } else {
        foreach ($rawSchedule as $slot) {
            if (!is_array($slot)) {
                $errors[] = 'Malformed time slot entry.';
                continue;
            }
            $day = trim((string)($slot['day_of_week'] ?? ''));
            if (!in_array($day, $validDays, true)) {
                $errors[] = 'Invalid day of week in time slot.';
                continue;
            }
            $groupedByDay[$day][] = $slot;
        }
    }

    $normalizedSlots = [];

    foreach ($validDays as $day) {
        $daySlots = $groupedByDay[$day];
        if (empty($daySlots)) {
            continue;
        }

        if (count($daySlots) > 10) {
            $errors[] = "{$day} has too many time slots (maximum 10 per day).";
            continue;
        }

        $parsedDaySlots = [];
        foreach ($daySlots as $idx => $slot) {
            $rawStart = (string)($slot['start_time'] ?? '');
            $rawEnd = (string)($slot['end_time'] ?? '');

            $startMins = availability_time_to_minutes($rawStart);
            $endMins = availability_time_to_minutes($rawEnd);

            if ($startMins === null || $endMins === null) {
                $errors[] = "{$day} slot #" . ($idx + 1) . ": Please provide valid start and end times.";
                continue;
            }

            if ($endMins <= $startMins) {
                $startLabel = availability_format_time_12h($rawStart);
                $endLabel = availability_format_time_12h($rawEnd);
                $errors[] = "{$day}: End time ({$endLabel}) must be after start time ({$startLabel}).";
                continue;
            }

            $parsedDaySlots[] = [
                'day_of_week' => $day,
                'start_mins'  => $startMins,
                'end_mins'    => $endMins,
                'start_time'  => sprintf('%02d:%02d:00', intdiv($startMins, 60), $startMins % 60),
                'end_time'    => sprintf('%02d:%02d:00', intdiv($endMins, 60), $endMins % 60)
            ];
        }

        // Sort chronologically and check for duplicate or overlapping intervals
        usort($parsedDaySlots, fn($a, $b) => ($a['start_mins'] <=> $b['start_mins']) ?: ($a['end_mins'] <=> $b['end_mins']));

        for ($i = 0; $i < count($parsedDaySlots); $i++) {
            for ($j = $i + 1; $j < count($parsedDaySlots); $j++) {
                $a = $parsedDaySlots[$i];
                $b = $parsedDaySlots[$j];
                if ($a['start_mins'] < $b['end_mins'] && $b['start_mins'] < $a['end_mins']) {
                    $rangeA = availability_format_time_12h($a['start_time']) . ' – ' . availability_format_time_12h($a['end_time']);
                    $rangeB = availability_format_time_12h($b['start_time']) . ' – ' . availability_format_time_12h($b['end_time']);
                    $errors[] = "{$day}: Overlapping time slots ({$rangeA} overlaps {$rangeB}).";
                    break 2;
                }
            }
        }

        foreach ($parsedDaySlots as $pSlot) {
            $normalizedSlots[] = [
                'day_of_week' => $pSlot['day_of_week'],
                'start_time'  => $pSlot['start_time'],
                'end_time'    => $pSlot['end_time']
            ];
        }
    }

    return [
        'valid'  => empty($errors),
        'status' => $status,
        'slots'  => $normalizedSlots,
        'errors' => $errors
    ];
}

/**
 * Format raw slot rows and user availability_status into structured response array.
 */
function format_user_availability(?string $status, array $rows): array {
    $days = availability_days();
    $statusesMeta = availability_statuses();

    $daysGrouped = [];
    foreach ($days as $day) {
        $daysGrouped[$day] = [
            'day'     => $day,
            'short'   => substr($day, 0, 3),
            'enabled' => false,
            'slots'   => []
        ];
    }

    foreach ($rows as $row) {
        $day = $row['day_of_week'] ?? '';
        if (!isset($daysGrouped[$day])) {
            continue;
        }
        $startRaw = substr((string)$row['start_time'], 0, 5);
        $endRaw = substr((string)$row['end_time'], 0, 5);
        $start12 = availability_format_time_12h($startRaw);
        $end12 = availability_format_time_12h($endRaw);

        $daysGrouped[$day]['enabled'] = true;
        $daysGrouped[$day]['slots'][] = [
            'id'         => isset($row['id']) ? (int)$row['id'] : null,
            'start_time' => $startRaw,
            'end_time'   => $endRaw,
            'start_12h'  => $start12,
            'end_12h'    => $end12,
            'formatted'  => "{$start12} – {$end12}"
        ];
    }

    $configuredDaysCount = 0;
    foreach ($daysGrouped as $d) {
        if ($d['enabled']) {
            $configuredDaysCount++;
        }
    }

    $statusKey = ($status && isset($statusesMeta[$status])) ? $status : null;
    $statusMeta = $statusKey ? $statusesMeta[$statusKey] : null;

    return [
        'configured'            => ($statusKey !== null || !empty($rows)),
        'status'                => $statusKey,
        'status_meta'           => $statusMeta,
        'total_slots'           => count($rows),
        'configured_days_count' => $configuredDaysCount,
        'days'                  => $daysGrouped
    ];
}

/**
 * Fetch a single user's availability status and weekly slots.
 */
function get_user_availability(PDO $pdo, int $userId): array {
    try {
        $uStmt = $pdo->prepare("SELECT availability_status FROM users WHERE id = :id LIMIT 1");
        $uStmt->execute([':id' => $userId]);
        $status = $uStmt->fetchColumn();
        $status = ($status !== false && $status !== null) ? (string)$status : null;

        $sStmt = $pdo->prepare("
            SELECT id, day_of_week, start_time, end_time
            FROM availability
            WHERE user_id = :uid
            ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                     start_time ASC
        ");
        $sStmt->execute([':uid' => $userId]);
        $rows = $sStmt->fetchAll(PDO::FETCH_ASSOC);

        return format_user_availability($status, $rows);
    } catch (PDOException $e) {
        error_log('[Availability Fetch Error] ' . $e->getMessage());
        return format_user_availability(null, []);
    }
}

/**
 * Fetch raw weekly availability rows grouped by user_id for a list of users.
 *
 * @param int[] $userIds
 * @return array<int, array>
 */
function get_users_availability_slots_map(PDO $pdo, array $userIds): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $userIds), fn($id) => $id > 0)));
    if (empty($ids)) {
        return [];
    }

    $map = array_fill_keys($ids, []);
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT id, user_id, day_of_week, start_time, end_time
            FROM availability
            WHERE user_id IN ({$placeholders})
            ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                     start_time ASC
        ");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $uid = (int)$row['user_id'];
            $map[$uid][] = $row;
        }
    } catch (PDOException $e) {
        error_log('[Availability Bulk Map Error] ' . $e->getMessage());
    }

    return $map;
}

/**
 * Persist a user's availability status and weekly slots inside a transaction.
 */
function save_user_availability(PDO $pdo, int $userId, string $status, array $slots): array {
    return db_transaction(function (PDO $tx) use ($userId, $status, $slots) {
        $uStmt = $tx->prepare("UPDATE users SET availability_status = :status, updated_at = NOW() WHERE id = :uid");
        $uStmt->execute([
            ':status' => $status,
            ':uid'    => $userId
        ]);

        $delStmt = $tx->prepare("DELETE FROM availability WHERE user_id = :uid");
        $delStmt->execute([':uid' => $userId]);

        if (!empty($slots)) {
            $insStmt = $tx->prepare("
                INSERT INTO availability (user_id, day_of_week, start_time, end_time)
                VALUES (:uid, :day, :start_time, :end_time)
            ");
            foreach ($slots as $slot) {
                $insStmt->execute([
                    ':uid'        => $userId,
                    ':day'        => $slot['day_of_week'],
                    ':start_time' => $slot['start_time'],
                    ':end_time'   => $slot['end_time']
                ]);
            }
        }

        return get_user_availability($tx, $userId);
    });
}

/**
 * Compute real schedule compatibility between two users based on overall status and weekly slots.
 *
 * @param string|null $myStatus
 * @param array $mySlots Raw rows from `availability`
 * @param string|null $partnerStatus
 * @param array $partnerSlots Raw rows from `availability`
 * @return array
 */
function compute_schedule_compatibility(?string $myStatus, array $mySlots, ?string $partnerStatus, array $partnerSlots): array {
    $statusesMeta = availability_statuses();
    $partnerMeta = ($partnerStatus && isset($statusesMeta[$partnerStatus])) ? $statusesMeta[$partnerStatus] : null;

    // Respect overall availability status first
    if ($myStatus === 'unavailable' || $partnerStatus === 'unavailable') {
        return [
            'is_compatible'  => false,
            'label'          => 'No Common Availability',
            'reason'         => 'unavailable',
            'partner_status' => $partnerStatus,
            'partner_meta'   => $partnerMeta,
            'overlaps'       => [],
            'overlap_count'  => 0,
            'overlap_summary'=> ''
        ];
    }

    if (empty($mySlots) || empty($partnerSlots)) {
        return [
            'is_compatible'  => false,
            'label'          => 'No Common Availability',
            'reason'         => 'no_schedule',
            'partner_status' => $partnerStatus,
            'partner_meta'   => $partnerMeta,
            'overlaps'       => [],
            'overlap_count'  => 0,
            'overlap_summary'=> ''
        ];
    }

    // Group slots by day with minute ranges
    $myByDay = [];
    foreach ($mySlots as $slot) {
        $s = availability_time_to_minutes((string)$slot['start_time']);
        $e = availability_time_to_minutes((string)$slot['end_time']);
        if ($s !== null && $e !== null && $e > $s) {
            $myByDay[$slot['day_of_week']][] = [$s, $e];
        }
    }

    $partnerByDay = [];
    foreach ($partnerSlots as $slot) {
        $s = availability_time_to_minutes((string)$slot['start_time']);
        $e = availability_time_to_minutes((string)$slot['end_time']);
        if ($s !== null && $e !== null && $e > $s) {
            $partnerByDay[$slot['day_of_week']][] = [$s, $e];
        }
    }

    $overlaps = [];
    foreach (availability_days() as $day) {
        if (empty($myByDay[$day]) || empty($partnerByDay[$day])) {
            continue;
        }

        $dayIntervals = [];
        foreach ($myByDay[$day] as [$s1, $e1]) {
            foreach ($partnerByDay[$day] as [$s2, $e2]) {
                $oStart = max($s1, $s2);
                $oEnd = min($e1, $e2);
                if ($oStart < $oEnd) {
                    $dayIntervals[] = [$oStart, $oEnd];
                }
            }
        }

        if (empty($dayIntervals)) {
            continue;
        }

        // Sort and merge any adjacent/overlapping intervals on the same day
        usort($dayIntervals, fn($a, $b) => ($a[0] <=> $b[0]) ?: ($a[1] <=> $b[1]));
        $merged = [$dayIntervals[0]];
        for ($i = 1; $i < count($dayIntervals); $i++) {
            $lastIdx = count($merged) - 1;
            if ($dayIntervals[$i][0] <= $merged[$lastIdx][1]) {
                $merged[$lastIdx][1] = max($merged[$lastIdx][1], $dayIntervals[$i][1]);
            } else {
                $merged[] = $dayIntervals[$i];
            }
        }

        foreach ($merged as [$startM, $endM]) {
            $start24 = availability_minutes_to_hhmm($startM);
            $end24 = availability_minutes_to_hhmm($endM);
            $start12 = availability_format_time_12h($start24);
            $end12 = availability_format_time_12h($end24);

            $overlaps[] = [
                'day'       => $day,
                'short_day' => substr($day, 0, 3),
                'start'     => $start24,
                'end'       => $end24,
                'formatted' => "{$start12} – {$end12}"
            ];
        }
    }

    $isCompatible = !empty($overlaps);
    $summary = '';
    if ($isCompatible) {
        $first = $overlaps[0];
        $summary = "{$first['short_day']} {$first['formatted']}";
        if (count($overlaps) > 1) {
            $summary .= ' (+' . (count($overlaps) - 1) . ' more)';
        }
    }

    return [
        'is_compatible'   => $isCompatible,
        'label'           => $isCompatible ? 'Schedule Compatible' : 'No Common Availability',
        'reason'          => $isCompatible ? 'overlap' : 'no_overlap',
        'partner_status'  => $partnerStatus,
        'partner_meta'    => $partnerMeta,
        'overlaps'        => $overlaps,
        'overlap_count'   => count($overlaps),
        'overlap_summary' => $summary
    ];
}
