<?php

namespace App\Livewire\Forms;

use App\Enums\LiturgyElementType;
use App\Enums\ReadingType;
use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\Template;
use Exception;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LiturgyElementForm extends Form
{
    public ?LiturgyElement $element = null;

    public Template|Service|null $parent = null;

    #[Validate]
    public string $name;

    #[Validate]
    public ?string $description = null;

    #[Validate]
    public string $type;

    #[Validate]
    public ?string $reading_type = null;

    #[Validate]
    public int $order = 0;

    #[Validate]
    public ?int $assignee_id = null;

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'type' => [
                'required',
                'string',
                new Enum(LiturgyElementType::class),
            ],
            'reading_type' => [
                'exclude_unless:type,'.LiturgyElementType::READING->value,
                'required',
                'string',
                new Enum(ReadingType::class),
            ],
            'assignee_id' => 'nullable|integer|exists:users,id',
            'order' => 'integer|min:0|max:1000',
        ];
    }

    public function setLiturgyElement(LiturgyElement $element): void
    {
        $this->element = $element;
        $this->parent = $element->liturgy;

        $this->name = $element->name;
        $this->description = $element->description;
        $this->type = $element?->type?->value ?? '';
        $this->reading_type = $element?->reading_type?->value ?? null;
        $this->order = $element->order;
        $this->assignee_id = $element->assignee_id;
    }

    public function setParent(Template|Service $parent): void
    {
        $this->parent = $parent;
    }

    public function store(): void
    {
        $this->validate();

        if (! $this->parent) {
            throw new Exception('Parent not set');
        }

        $this->parent
            ->liturgyElements()
            ->create(
                $this->only([
                    'name',
                    'description',
                    'type',
                    'reading_type',
                    'assignee_id',
                    'order',
                ])
            );
    }

    public function update(): void
    {
        $this->validate();

        $data = $this->only([
            'name',
            'description',
            'type',
            'reading_type',
            'assignee_id',
            'order',
        ]);

        if (($data['type'] ?? null) !== LiturgyElementType::READING->value) {
            $data['reading_type'] = null;
        }

        $this->element->update($data);
    }
}
