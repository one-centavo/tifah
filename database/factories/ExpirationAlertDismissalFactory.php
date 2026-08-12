<?php

namespace Database\Factories;

use App\Models\ExpirationAlertDismissal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpirationAlertDismissal>
 */
class ExpirationAlertDismissalFactory extends Factory
{
    protected $model = ExpirationAlertDismissal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'dismissed_date' => Carbon::today()->format('Y-m-d'),
        ];
    }
}
