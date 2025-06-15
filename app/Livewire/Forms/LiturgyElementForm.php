<?php

namespace App\Livewire\Forms;

use App\Enums\LiturgyElementType;
use App\Models\LiturgyElement;
use App\Models\Service;
use App\Models\Template;
use Exception;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LiturgyElementForm extends Form
{
    public ?LiturgyElement $element;
    public Template|Service|null $parent;

    #[Validate]
    public string $name;

    #[Validate]
    public ?string $description = null;

    #[Validate]
    public string $type;

    #[Validate]
    public int $order = 0;
    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            "name" => "required|string|max:255",
            "description" => "nullable|string|max:255",
            "type" => [
                "required",
                "string",
                new Enum(LiturgyElementType::class),
            ],
            "order" => "integer|min:0|max:1000",
        ];
    }

    /**
     * @return void
     */
    public function setLiturgyElement(LiturgyElement $element): void
    {
        $this->element = $element;
        $this->parent = $element->parent;

        $this->name = $element->name;
        $this->description = $element->description;
        $this->type = $element?->type?->value ?? "";
        $this->order = $element->order;
    }
    /**
     * @return void
     */
    public function setParent(Template|Service $parent): void
    {
        $this->parent = $parent;
    }
    /**
     * @return void
     */
    public function store(): void
    {
        $this->validate();

        if (!$this->parent) {
            throw new Exception("Parent not set");
        }

        $this->parent
            ->liturgyElements()
            ->create($this->only(["name", "description", "type", "order"]));
    }
    /**
     * @return void
     */
    public function update(): void
    {
        $this->validate();

        $this->element->update(
            $this->only(["name", "description", "type", "order"])
        );
    }
}
