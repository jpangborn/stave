<?php

use App\Http\Controllers\Controller;
use Livewire\Form;

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();

arch('globals')
    ->expect(['sleep'])
    ->toBeUsedInNothing();

arch('controllers')
    ->expect("App\Controllers")
    ->toBeClasses()
    ->toExtend(Controller::class);

arch('livewire forms')
    ->expect("App\Livewire\Forms")
    ->toBeClasses()
    ->toExtend(Form::class);
