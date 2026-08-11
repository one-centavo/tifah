<?php

use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guests are redirected to login page when accessing dashboard', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('authenticated users can access dashboard', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Panel de Control');
});

test('it renders dashboard component with default state and can switch periods', function () {
    $this->actingAs($this->user);

    Volt::test('dashboard.index')
        ->assertSet('period', 'month')
        ->assertSet('activeAlertTab', 'expiring_lots')
        ->assertSet('chartDays', 15)
        ->call('setPeriod', 'week')
        ->assertSet('period', 'week')
        ->call('setActiveAlertTab', 'low_stock')
        ->assertSet('activeAlertTab', 'low_stock')
        ->call('setChartDays', 30)
        ->assertSet('chartDays', 30)
        ->assertHasNoErrors();
});
