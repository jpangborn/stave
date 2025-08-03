<?php

use App\Http\Controllers\Controller;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Livewire\Form;

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();

arch("globals")
    ->expect(["sleep"])
    ->toBeUsedInNothing();

arch("controllers")
    ->expect("App\Controllers")
    ->toBeClasses()
    ->toExtend(Controller::class);

arch("livewire forms")
    ->expect("App\Livewire\Forms")
    ->toBeClasses()
    ->toExtend(Form::class);
