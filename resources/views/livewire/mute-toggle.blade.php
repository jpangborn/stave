<?php

use App\Models\Conversation;
use App\Models\MutedCommentable;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Model $commentable;

    public string $noun = '';

    public function mount(Model $commentable, ?string $noun = null): void
    {
        $this->commentable = $commentable;
        $this->noun = $noun ?? Str::lower(class_basename($commentable));
    }

    #[Computed]
    public function isMuted(): bool
    {
        if (! ($this->commentable instanceof Conversation || $this->commentable instanceof Service)) {
            return false;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        return $user->hasMuted($this->commentable);
    }

    public function toggle(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        if (! ($this->commentable instanceof Conversation || $this->commentable instanceof Service)) {
            return;
        }

        $attributes = [
            'user_id' => $user->id,
            'commentable_type' => $this->commentable::class,
            'commentable_id' => $this->commentable->getKey(),
        ];

        $existing = MutedCommentable::query()->where($attributes)->first();

        if ($existing !== null) {
            $existing->delete();
        } else {
            MutedCommentable::query()->create($attributes);
        }

        unset($this->isMuted);
    }
}; ?>

<div>
    @if (Auth::check())
        <flux:dropdown align="end">
            <flux:tooltip content="{{ $this->isMuted ? __('Notifications muted') : __('Notifications on') }}">
                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="{{ $this->isMuted ? 'bell-slash' : 'bell' }}"
                    square
                    data-test="mute-toggle"
                    data-test-muted="{{ $this->isMuted ? 'true' : 'false' }}"
                />
            </flux:tooltip>
            <flux:menu>
                <flux:menu.item
                    wire:click="toggle"
                    icon="{{ $this->isMuted ? 'bell' : 'bell-slash' }}"
                    data-test="mute-toggle-action"
                >
                    {{ $this->isMuted
                        ? __('Unmute :noun', ['noun' => $noun])
                        : __('Mute :noun', ['noun' => $noun]) }}
                </flux:menu.item>
                <div class="px-3 py-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('You\'ll still get notifications when @mentioned.') }}
                </div>
            </flux:menu>
        </flux:dropdown>
    @endif
</div>
