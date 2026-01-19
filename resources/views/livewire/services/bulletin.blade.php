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
                        <h2>{{ $element->name }}@if($element->hasContent() && $element->name != $element->content->name): {{$element->content->name }}@endif</h2>
                        @if($element->hasContent())
                        <div>
                            {!! $element->content->lyrics !!}
                        </div>
                        @endif
                    </section>
                    @break
                @case(App\Enums\LiturgyElementType::READING)
                @case(App\Enums\LiturgyElementType::PRAYER)
                    <section>
                        <h2>{{ $element->name }}@if($element->hasContent() && $element->name != $element->content->title): {{$element->content->title }}@endif</h2>
                        @if($element->hasContent())
                            {!! $element->content->text !!}
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
