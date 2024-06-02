<?php

use App\Models\Manufacture;
use App\Models\Material;
use App\Models\Product;

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use Toast, WithFileUploads;

    public Manufacture $manufacture;

    // You could use Livewire "form object" instead.
    #[Rule('required')]
    public string $name = '';

    #[Rule('required')]
    public string $address = '';

    #[Rule('sometimes')]
    public array $manufactureMaterials = [];

    #[Validate([
        'materialsPrice.*' => 'required',
    ])]
    public array $materialsPrice = [];

    // We also need this to fill Countries combobox on upcoming form
    public function with(): array
    {
        return [
            'materials' => Material::where('is_deleted', false)->get(),
            'products' => Product::all(),
        ];
    }

    public function mount(): void
    {
        $this->fill($this->manufacture);

        $materials = $this->manufacture->materials()->get();

        $manufactureMaterials = [];
        $materialsPrice = [];
        foreach ($materials as $material) {
            $manufactureMaterials[] = $material->id;

            $materialsPrice[$material->id] = $material->pivot->price;
        }

        $this->fill([
            'materialsPrice' => $materialsPrice,
            'manufactureMaterials' => $manufactureMaterials
        ]);
    }

    public function save()
    {

        $this->validate();

        if(!$this->validatePrices()) {
            return;
        }

        // save manufacture data
        $manufacture = [
            'name' => $this->name,
            'address' => $this->address,
        ];

        $relatedMaterials = [];

        foreach ($this->manufactureMaterials as $materialId) {
            $relatedMaterials[$materialId] = [
                'price' => (float) $this->materialsPrice[$materialId]
            ];
        }

        //save manufacture
        $this->manufacture->update($manufacture);
        // link materials
        $this->manufacture->materials()->sync($relatedMaterials);

        $this->success('Виробник оновлен успішно.', timeout: 60000, redirectTo: '/manufacture/' . $this->manufacture->id . '/edit');
    }


    private function validatePrices(): bool
    {
        $success = true;

        if (!empty($this->manufactureMaterials) && empty($this->materialsPrice)) {
            foreach ($this->manufactureMaterials as $materialId) {
                $this->addError(
                    'materialsPrice.' . $materialId,
                    'Ціна матеріала обов\'язкова'
                );
            }

            $success = false;
        }

        foreach ($this->materialsPrice as $materialId => $price) {
            if (is_null($price)) {
                $this->addError(
                    'materialsPrice.' . $materialId,
                    'Material price is required'
                );
                $success = false;
            }
            if (!is_numeric($price)) {
                $this->addError(
                    'materialsPrice.' . $materialId,
                    'Material price  must be a number'
                );
                $success = false;
            }
        }

        return $success;
    }
}; ?>

<div>
    <x-header title="Редагувати Виробника" separator/>
    <div class="grid gap-8 lg:grid-cols-2">
        <div class="">
            <x-form wire:submit="save">
                <x-input label="Ім'я" wire:model="name"/>
                <x-input label="Адреса" wire:model="address" />

                <x-choices-offline
                    label="Матеріали"
                    wire:model.live="manufactureMaterials"
                    :options="$materials"
                    searchable/>

                <x-slot:actions>
                    <x-button label="Відмінити" link="/users"/>
                    {{-- The important thing here is `type="submit"` --}}
                    {{-- The spinner property is nice! --}}
                    <x-button label="Зберегти" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary"/>
                </x-slot:actions>

                @if($manufactureMaterials)
                    @php
                        $addedMaterials = Material::whereIn('id', $this->manufactureMaterials)->get();
                    @endphp

                    <x-header title="Додати вартість матеріала" size="text-xl" class="mt-8" separator />
                    @foreach($addedMaterials as $addedMaterial)
                        <div class="flex flex-row content-center space-x-2 items-center justify-items-stretch">
                            <div class="flex-initial w-64 text-lg font-extrabold">
                                {{$addedMaterial->name}}
                            </div>
                            <div class="flex-initial w-80">
                                <x-input
                                    label="Ціна"
                                    wire:model.defer="materialsPrice.{{$addedMaterial->id}}"
                                    suffix="грн."
                                    inline
                                    required
                                    locale="pt-BR"
                                />
                            </div>
                        </div>
                    @endforeach
                @endif
            </x-form>
        </div>
        <div class="">
        </div>
    </div>
</div>
