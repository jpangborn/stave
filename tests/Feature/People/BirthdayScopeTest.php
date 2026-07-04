<?php

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('birthday inside the window is returned', function (): void {
    $this->travelTo(now()->setDate(2026, 1, 1));

    $person = Person::factory()->create(['birth_date' => '1990-01-15']);

    expect(Person::query()->birthdayWithin(30)->pluck('id'))->toContain($person->id);
});

test('birthday today is returned', function (): void {
    $this->travelTo(now()->setDate(2026, 1, 1));

    $person = Person::factory()->create(['birth_date' => '1990-01-01']);

    expect(Person::query()->birthdayWithin(30)->pluck('id'))->toContain($person->id);
});

test('birthday exactly days out is returned', function (): void {
    $this->travelTo(now()->setDate(2026, 1, 1));

    $person = Person::factory()->create(['birth_date' => '1990-01-31']);

    expect(Person::query()->birthdayWithin(30)->pluck('id'))->toContain($person->id);
});

test('birthday one day past the window is excluded', function (): void {
    $this->travelTo(now()->setDate(2026, 1, 1));

    $person = Person::factory()->create(['birth_date' => '1990-02-01']);

    expect(Person::query()->birthdayWithin(30)->pluck('id'))->not->toContain($person->id);
});

test('birth_date null is excluded', function (): void {
    $person = Person::factory()->create(['birth_date' => null]);

    expect(Person::query()->birthdayWithin(30)->pluck('id'))->not->toContain($person->id);
});

test('year wraparound includes january birthday but excludes february birthday', function (): void {
    $this->travelTo(now()->setDate(2025, 12, 20));

    $january = Person::factory()->create(['birth_date' => '1980-01-05']);
    $february = Person::factory()->create(['birth_date' => '1980-02-15']);

    $ids = Person::query()->birthdayWithin(30)->pluck('id');

    expect($ids)->toContain($january->id);
    expect($ids)->not->toContain($february->id);
});

test('birth year is irrelevant to the match', function (): void {
    $this->travelTo(now()->setDate(2026, 1, 1));

    $person = Person::factory()->create(['birth_date' => '1950-01-08']);

    expect(Person::query()->birthdayWithin(30)->pluck('id'))->toContain($person->id);
});

test('birthdayWithin uses MySQL DATE_FORMAT syntax on a MySQL connection', function (): void {
    config(['database.connections.test_mysql' => ['driver' => 'mysql', 'database' => 'testing']]);

    $sql = Person::on('test_mysql')->birthdayWithin(30)->toSql();

    expect($sql)->toContain("DATE_FORMAT(birth_date, '%m-%d')");
});

test('birthdayWithin throws for an unsupported database driver', function (): void {
    config(['database.connections.test_pgsql' => ['driver' => 'pgsql', 'database' => 'testing']]);

    expect(fn () => Person::on('test_pgsql')->birthdayWithin(30)->toSql())
        ->toThrow(LogicException::class, 'birthdayWithin() has no month-day expression for the [pgsql] database driver.');
});
