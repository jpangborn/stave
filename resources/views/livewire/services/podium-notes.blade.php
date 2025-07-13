<?php

use App\Enums\LiturgyElementType;
use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\Template;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class extends Component {
    #[Reactive]
    public int $serviceId;

    public function getServiceProperty()
    {
        return Service::with([
            "liturgyElements" => function ($query) {
                $query
                    ->whereIn("type", [
                        LiturgyElementType::READING->value,
                        LiturgyElementType::PRAYER->value,
                    ])
                    ->whereNotNull("content_id")
                    ->with("content");
            },
        ])->find($this->serviceId);
    }
};
?>

<article class="w-full space-y-6 **:[p+p]:mt-3 **:[p+h3]:mt-3 **:[header>h1]:text-4xl **:[header>p]:text-sm **:[h2]:text-2xl **:[h2]:mb-2 **:[h3]:font-semibold">
    @if($this->service->liturgyElements->isEmpty())
        <flux:heading size="xl">No Service Elements</flux:heading>
    @else
        @foreach($this->service->liturgyElements as $element)
            <section>
                <h2>{{ $element->name }}
                    @if($element->hasContent() && $element->name != $element->content->title)
                        : {{$element->content->title }}
                    @endif
                </h2>
                @if($element->hasContent())
                    {!! $element->content->text !!}
                @endif
            </section>
        @endforeach
    @endif
</article>
