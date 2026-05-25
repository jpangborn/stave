<?php

namespace App\Livewire\Forms;

use App\Enums\Gender;
use App\Enums\MembershipStatus;
use App\Enums\TerminationReason;
use App\Models\Person;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class PersonForm extends Form
{
    public ?Person $person = null;

    #[Validate('required|string|max:255')]
    public string $first_name = '';

    #[Validate('required|string|max:255')]
    public string $last_name = '';

    #[Validate('nullable|email|max:255')]
    public ?string $email = null;

    #[Validate('nullable|string|max:32')]
    public ?string $phone = null;

    #[Validate('nullable|string|max:255')]
    public ?string $address_line1 = null;

    #[Validate('nullable|string|max:255')]
    public ?string $address_city = null;

    #[Validate('nullable|string|size:2')]
    public ?string $address_state = null;

    #[Validate('nullable|string|max:16')]
    public ?string $address_zip = null;

    public ?string $gender = null;

    public string $membership_status = MembershipStatus::VISITOR->value;

    public ?string $membership_since = null;

    public ?string $termination_reason = null;

    public ?int $pastoral_care_elder_id = null;

    public function rules(): array
    {
        return [
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'membership_status' => ['required', Rule::enum(MembershipStatus::class)],
            'membership_since' => ['nullable', 'date'],
            'termination_reason' => ['nullable', Rule::enum(TerminationReason::class)],
            'pastoral_care_elder_id' => ['nullable', 'integer', Rule::exists('people', 'id')],
        ];
    }

    public function setPerson(Person $person): void
    {
        $this->person = $person;

        $this->first_name = $person->first_name;
        $this->last_name = $person->last_name;
        $this->email = $person->email;
        $this->phone = $person->phone;
        $this->address_line1 = $person->address_line1;
        $this->address_city = $person->address_city;
        $this->address_state = $person->address_state;
        $this->address_zip = $person->address_zip;
        $this->gender = $person->gender?->value;
        $this->membership_status = $person->membership_status->value;
        $this->membership_since = $person->membership_since?->toDateString();
        $this->termination_reason = $person->termination_reason?->value;
        $this->pastoral_care_elder_id = $person->pastoral_care_elder_id;
    }

    public function store(): Person
    {
        $this->validate();

        return Person::create($this->data());
    }

    public function update(): void
    {
        $this->validate();

        $this->person->update($this->data());
    }

    /** @return array<string, mixed> */
    private function data(): array
    {
        $data = $this->only([
            'first_name',
            'last_name',
            'email',
            'phone',
            'address_line1',
            'address_city',
            'address_state',
            'address_zip',
            'gender',
            'membership_status',
            'membership_since',
            'pastoral_care_elder_id',
        ]);

        $data['termination_reason'] = $this->membership_status === MembershipStatus::TERMINATED->value
            ? $this->termination_reason
            : null;

        return $data;
    }
}
