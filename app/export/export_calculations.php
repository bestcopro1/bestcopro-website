<?php

function bestcopro_export_month_ranges($dateDebut, $count = 12)
{
    $start = new DateTimeImmutable((string) $dateDebut);
    $ranges = [];
    for ($i = 0; $i < (int) $count; $i++) {
        $from = $start->modify("+" . $i . " months");
        $toExclusive = $start->modify("+" . ($i + 1) . " months");
        $ranges[] = [
            "from" => $from->format("Y-m-d"),
            "toExclusive" => $toExclusive->format("Y-m-d"),
        ];
    }
    return $ranges;
}

function bestcopro_export_calculate_period_cells($periodRows, $dueFlags, $periodCount)
{
    $cells = [];
    $reste = 0.0;
    for ($i = 0; $i < (int) $periodCount; $i++) {
        $row = isset($periodRows[$i])
            ? $periodRows[$i]
            : ["montantDu" => 0, "montantPaye" => 0];
        $due = max(0, (float) $row["montantDu"]);
        $paid = min($due, max(0, (float) $row["montantPaye"]));
        if (!empty($dueFlags[$i])) {
            $reste += max(0, $due - $paid);
        }
        $cells[] = [
            "montantDu" => $due,
            "montantPaye" => $paid,
            "display" => $paid > 0.00001 ? $paid : null,
            "due" => !empty($dueFlags[$i]),
        ];
    }

    return ["cells" => $cells, "reste" => $reste];
}

function bestcopro_export_rebucket_period_rows($periodRows, $desiredCount)
{
    $desiredCount = (int) $desiredCount;
    $actualCount = count($periodRows);
    if ($desiredCount <= 0 || $actualCount === 0) {
        return [];
    }
    if ($desiredCount === $actualCount) {
        return array_values($periodRows);
    }
    if (12 % $desiredCount !== 0 || 12 % $actualCount !== 0) {
        return array_slice(array_values($periodRows), 0, $desiredCount);
    }

    $monthly = array_fill(0, 12, ["montantDu" => 0.0, "montantPaye" => 0.0]);
    $monthsPerActualPeriod = (int) (12 / $actualCount);
    foreach (array_values($periodRows) as $index => $row) {
        $due = max(0, (float) $row["montantDu"]);
        $paidRemaining = min($due, max(0, (float) $row["montantPaye"]));
        $monthlyDue = $monthsPerActualPeriod > 0 ? $due / $monthsPerActualPeriod : 0;
        for ($month = 0; $month < $monthsPerActualPeriod; $month++) {
            $monthIndex = $index * $monthsPerActualPeriod + $month;
            $monthlyPaid = min($monthlyDue, $paidRemaining);
            $monthly[$monthIndex] = [
                "montantDu" => $monthlyDue,
                "montantPaye" => $monthlyPaid,
            ];
            $paidRemaining -= $monthlyPaid;
        }
    }

    $result = [];
    $monthsPerDesiredPeriod = (int) (12 / $desiredCount);
    for ($period = 0; $period < $desiredCount; $period++) {
        $due = 0.0;
        $paid = 0.0;
        for ($month = 0; $month < $monthsPerDesiredPeriod; $month++) {
            $monthIndex = $period * $monthsPerDesiredPeriod + $month;
            $due += $monthly[$monthIndex]["montantDu"];
            $paid += $monthly[$monthIndex]["montantPaye"];
        }
        $result[] = ["montantDu" => $due, "montantPaye" => $paid];
    }
    return $result;
}

function bestcopro_export_build_reconciliation_plan($payments, $periods)
{
    $allocations = [];
    $unresolved = 0.0;
    $paymentIndex = 0;
    $payments = array_values($payments);

    foreach ($periods as $period) {
        $missing = max(0, (float) $period["missing"]);
        while ($missing > 0.009 && isset($payments[$paymentIndex])) {
            $remaining = max(0, (float) $payments[$paymentIndex]["remaining"]);
            if ($remaining <= 0.009) {
                $paymentIndex++;
                continue;
            }
            $amount = min($missing, $remaining);
            $allocations[] = [
                "id_rel" => (int) $period["id_rel"],
                "id_paiement" => (int) $payments[$paymentIndex]["id"],
                "amount" => $amount,
            ];
            $missing -= $amount;
            $payments[$paymentIndex]["remaining"] -= $amount;
        }
        if ($missing > 0.009) {
            $unresolved += $missing;
        }
    }

    return ["allocations" => $allocations, "unresolved" => $unresolved];
}
