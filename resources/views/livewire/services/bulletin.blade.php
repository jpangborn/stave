<?php

use App\Models\Service;
use Livewire\Attributes\Reactive;
use Livewire\Component;

new class extends Component {
    #[Reactive]
    public int $serviceId;

    public function getServiceProperty()
    {
        return Service::with(
            "liturgyElements",
            "liturgyElements.content"
        )->find($this->serviceId);
    }
};
?>

<article class="w-full space-y-6 **:[p+p]:mt-3 **:[p+h3]:mt-3 **:[header>h1]:text-4xl **:[header>p]:text-sm **:[h2]:text-2xl **:[h2]:mb-2 **:[h3]:font-semibold">
    @if($this->service->liturgyElements->isEmpty())
        <flux:heading size="xl">No Service Elements</flux:heading>
    @else
        @foreach($this->service->liturgyElements as $element)
            @switch($element->type)
                @case(App\Enums\LiturgyElementType::SECTION)
                    <header class="space-y-1">
                        <h1>{{ $element->name }}</h1>
                        <p>{{ $element->description }}</p>
                        <hr>
                    </header>
                    @break
                @case(App\Enums\LiturgyElementType::SONG)
                    <section>
                        <div class="flex items-start justify-between gap-2">
                            <h2>{{ $element->getDisplayTitle() }}</h2>
                            @if($element->hasContent())
                                <x-copy-buttons
                                    :content="$element->getContentText()"
                                    class="shrink-0 mt-1"
                                />
                            @endif
                        </div>
                        @if($element->hasContent())
                        <div>
                            {!! $element->getContentText() !!}
                        </div>
                        @endif
                    </section>
                    @break
                @case(App\Enums\LiturgyElementType::READING)
                @case(App\Enums\LiturgyElementType::PRAYER)
                    <section>
                        <div class="flex items-start justify-between gap-2">
                            <h2>{{ $element->getDisplayTitle() }}</h2>
                            @if($element->hasContent())
                                <x-copy-buttons
                                    :content="$element->getContentText()"
                                    class="shrink-0 mt-1"
                                />
                            @endif
                        </div>
                        @if($element->hasContent())
                            {!! $element->getContentText() !!}
                        @endif
                    </section>
                    @break
                @case(App\Enums\LiturgyElementType::SERMON)
                    <section>
                        <h2>{{ $element->name }}@if($element->description): {{$element->description }}@endif</h2>
                    </section>
                    @break
                @default
                    <section>
                        <h2>{{ $element->name }}@if($element->description): {{$element->description }}@endif</h2>
                    </section>
                    @break
            @endswitch
        @endforeach
    @endif
</article>
