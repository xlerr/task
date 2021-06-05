<?php

namespace xlerr\task;

use Carbon\Carbon;
use xlerr\task\models\Task;

class TaskHelper
{
    /**
     * 根据运行次数获取下次运行时间
     *
     * @param int $runTimes
     * @param int $lowLevelRunTimes
     * @param int $highLevelRunTimes
     * @param int $totalRunTimes
     *
     * @return bool|string
     */
    public static function getNextRunDate($runTimes, $lowLevelRunTimes, $highLevelRunTimes, $totalRunTimes)
    {
        if ($runTimes >= $totalRunTimes) {
            return false;
        } elseif ($runTimes >= $highLevelRunTimes) {
            $minutes = ($runTimes - $highLevelRunTimes + 1) * 15;
        } elseif ($runTimes >= $lowLevelRunTimes) {
            $minutes = 5;
        } else {
            $minutes = 2;
        }

        return Carbon::parse()->addMinutes($minutes)->toDateTimeString();
    }

    /**
     * 根据时间比较获取下次运行时间
     *
     * @param string $datetime
     * @param int    $maxHours
     * @param int    $addHours
     * @param int    $addMinutes
     *
     * @return string
     */
    public static function getNextRunTime(string $datetime, $maxHours = 24, $addHours = 1, $addMinutes = 2): string
    {
        $now   = Carbon::now();
        $hours = Carbon::parse($datetime)->diffInHours($now, false);
        if ($hours >= $maxHours) {
            $nextRunTime = $now->addHours($addHours);
        } else {
            $nextRunTime = $now->addMinutes($addMinutes);
        }

        return $nextRunTime->toDateTimeString();
    }

    /**
     * 根据运行次数获取下次运行时间
     *
     * @param     $runTimes
     * @param     $totalRunTimes
     * @param int $hours
     *
     * @return string|boolean
     */
    public static function getNextRunTimeAfterHours($runTimes, $totalRunTimes, $hours = 12)
    {
        if ($runTimes >= $totalRunTimes) {
            return false;
        }

        return Carbon::now()->addMinutes(60 * $hours + 10)->toDateTimeString();
    }
}
