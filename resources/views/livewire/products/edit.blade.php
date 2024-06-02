<?php

use App\Models\Product;
use App\Models\Material;

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use Toast, WithFileUploads;

    public Product $product;

    // You could use Livewire "form object" instead.
    #[Rule('required')]
    public string $name = '';

    #[Rule('sometimes')]
    public string $description = '';

    #[Rule('required')]
    public array $product_materials = [];

    #[Rule('required|numeric')]
    public float $price = 0;

    #[Rule('nullable|image|max:1024')]
    public $photo;

    // We also need this to fill Countries combobox on upcoming form
    public function with(): array
    {
        return [
            'materials' => Material::where('is_deleted', false)->get(),
        ];
    }

    public function mount(): void
    {
        $this->fill($this->product);

        $this->product_materials = $this->product->materials->pluck('id')->all();
    }

    public function save(): void
    {
        $isWarning = false;

        // Validate
        $data = $this->validate();

        // Update
        $this->product->update($data);

        $this->product->materials()->sync($this->product_materials);

        if ($this->photo) {
            if ($this->product->url) {
                $urlArr = explode('storage/', $this->product->url);
                $path = count($urlArr) > 1
                    ? $urlArr[1]
                    : '';
                $storageUrl = 'public/' . $path;

                Storage::exists($storageUrl)
                    ? Storage::delete($storageUrl)
                    : $isWarning = true;
            }

            $url = $this->photo->store('product', 'public');
            $this->product->update(['url' => "/storage/$url"]);
        }

        // You can toast and redirect to any route
        $isWarning
            ? $this->warning('Product updated but old file is not found. ' . $storageUrl, redirectTo: '/products/' . $this->product->id . '/edit')
            : $this->success('Продукт оновлен успішно.', redirectTo: '/products/' . $this->product->id . '/edit');
    }
}; ?>

<div>
    <x-header title="Оновити {{ $product->name }}" separator />
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="">
            <x-form wire:submit="save">
                <x-file label="Зображення" wire:model="photo" accept="image/png, image/jpeg" crop-after-change>
                    <img src="{{ $product->url ?: '/empty-product.png' }}" class="h-40 rounded-lg" />
                </x-file>

                <x-input label="Ім'я" wire:model="name" />
                <x-textarea
                    label="Опис"
                    wire:model="description"
                    placeholder="Додайте опис тут ..."
                    hint="Max 1000 chars"
                    rows="5"
                    inline />

                <x-choices-offline
                    label="Матеріали"
                    wire:model="product_materials"
                    :options="$materials"
                    searchable />

                <x-input
                    label="Ціна"
                    wire:model.defer="price"
                    suffix="грн."
                    inline
                    required
                    locale="pt-BR"
                />

                <x-slot:actions>
                    <x-button label="Відмінити" link="/users" />
                    {{-- The important thing here is `type="submit"` --}}
                    {{-- The spinner property is nice! --}}
                    <x-button label="Зберегти" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary" />
                </x-slot:actions>
            </x-form>
        </div>
        <div class="">
            <img src="" width="300" class="mx-auto" />
        </div>
    </div>
</div>

