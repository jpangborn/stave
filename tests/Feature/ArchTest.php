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
    ->expect(["dd", "ddd", "die", "dump", "ray", "sleep", "eval", "env"])
    ->toBeUsedInNothing();

arch("models")->expect("App\Models")->toExtend(Model::class);

arch("controllers")
    ->expect("App\Controllers")
    ->toBeClasses()
    ->toExtend(Controller::class);

arch("livewire forms")
    ->expect("App\Livewire\Forms")
    ->toBeClasses()
    ->toExtend(Form::class);

arch("notifications")
    ->expect("App\Notifications")
    ->toBeClasses()
    ->toExtend(Notification::class);

arch("enums")->expect("App\Enums")->toBeEnums();

arch("commands")
    ->expect("App\Console\Commands")
    ->toBeClasses()
    ->toExtend(Command::class);

arch("jobs")
    ->expect("App\Jobs")
    ->toBeClasses()
    ->toImplement(ShouldQueue::class)
    ->toHaveMethod("handle");
