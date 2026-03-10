<?php

namespace App\Livewire\Forms;

use App\Enums\GroupMessaging;
use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Enums\MembershipStatus;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class GroupForm extends Form
{
    public ?Group $group = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public ?string $description = null;

    public string $visibility = GroupVisibility::PUBLIC->value;

    public string $messaging = GroupMessaging::OFF->value;

    public function rules(): array
    {
        return [
            'visibility' => ['required', Rule::enum(GroupVisibility::class)],
            'messaging' => ['required', Rule::enum(GroupMessaging::class)],
        ];
    }

    public function setGroup(Group $group): void
    {
        $this->group = $group;

        $this->name = $group->name;
        $this->description = $group->description;
        $this->visibility = $group->visibility->value;
        $this->messaging = $group->messaging->value;
    }

    public function store(?string $imagePath = null): void
    {
        $this->validate();

        $group = Group::create($this->data($imagePath));

        $group->allUsers()->attach(Auth::id(), [
            'role' => GroupRole::LEADER,
            'status' => MembershipStatus::ACTIVE,
        ]);
    }

    public function update(?string $imagePath = null): void
    {
        $this->validate();

        $this->group->update($this->data($imagePath));
    }

    /** @return array<string, mixed> */
    private function data(?string $imagePath = null): array
    {
        $data = $this->only(['name', 'description', 'visibility', 'messaging']);

        if ($imagePath) {
            $data['image'] = $imagePath;
        }

        return $data;
    }
}
