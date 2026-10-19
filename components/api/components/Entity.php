<?php

namespace app\components\api\components;

use app\models\Season;
use app\models\Tariff;
use app\models\TariffMonth;
use app\models\User;
use Yii;
use yii\base\Model;

/**
 * Entity serializers
 */
class Entity
{
    /**
     * API response
     *
     * @param  array  $payload response payload
     * @param  array  $errors request errors
     * @param  int  $code response code
     * @return array
     */
    public static function apiResponse($payload, $errors = [], int $code = 200)
    {
        $data = [];
        $data['request_time'] = date('c');

        if (empty($errors)) {
            $data['payload'] = $payload;
            $data['errors'] = null;
        } else {
            $data['payload'] = null;
            $data['errors'] = $errors;
            $code = $code == 200 ? 400 : $code;
        }

        Yii::$app->response->statusCode = $code;
        return $data;
    }

    /**
     * API error
     *
     * @param  string  $error error description
     * @param  int  $code response code
     * @return array
     */
    public static function apiError($error, int $code = 400)
    {
        Yii::$app->response->statusCode = $code;
        return [
            'message' => $error
        ];
    }

    /**
     * User
     *
     * Note: the full project resolves the company currency and time zone from
     * the system-settings dictionary, and the document-recognition flag from
     * the ChatGPT API key stored there. Those dictionaries are out of scope
     * for this excerpt, so the affected fields fall back to defaults.
     *
     * @param User $user
     * @return array
     */
    public static function user(User $user)
    {
        $data = [
            'login' => $user->login,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];

        return $data;
    }

    /**
     * Daily tariff
     *
     * @param Tariff $tariff
     * @return array
     */
    public static function tariffDaily(Tariff $tariff)
    {
        $data = [
            'id' => $tariff->idTariff,
            'vehicleCode' => $tariff->vehicle->urlCode,
            'season' => $tariff->season_idSeason,
            'seasonName' => $tariff->season->name,
            'minDays' => (int)$tariff->minDays,
            'maxDays' => (int)$tariff->maxDays,
            'price' => (float)$tariff->price,
        ];

        return $data;
    }

    /**
     * Monthly tariff
     *
     * @param TariffMonth $tariff
     * @return array
     */
    public static function tariffMonth(TariffMonth $tariff)
    {
        $data = [
            'id' => $tariff->idTariffMonth,
            'vehicleCode' => $tariff->vehicle->urlCode,
            'season' => $tariff->season_idSeason,
            'seasonName' => $tariff->season->name,
            'price' => (float)$tariff->price,
            'useForCalculate' => (int)$tariff->useForCalculate
        ];

        return $data;
    }

    /**
     * Season
     *
     * @param Season $season
     * @param string $currentDate
     * @return array
     */
    public static function season(Season $season, string $currentDate)
    {
        $data = [
            'id' => $season->idSeason,
            'name' => $season->name,
            'vehicleType' => $season->vehicletype_idVehicleType,
            'isCurrent' => 0,
            'period' => []
        ];

        foreach ($season->seasonRanges as $seasonRange) {
            $data['period'][] = $seasonRange->humanFormated();
            if (!$data['isCurrent']) {
                if ($seasonRange->startDate<=$currentDate && $seasonRange->endDate>=$currentDate) {
                    $data['isCurrent'] = 1;
                }
            }
        }

        return $data;
    }
}