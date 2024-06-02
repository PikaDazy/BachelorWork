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

    // You could use Livewire "form object" instead.
    #[Rule('required')]
    public string $name = '';

    #[Rule('sometimes')]
    public string $description = '';

    #[Rule('required')]
    public array $product_materials = [];

    #[Rule('nullable|image|max:1024')]
    public $photo;

    #[Rule('required|numeric')]
    public float $price = 0;

    // We also need this to fill Countries combobox on upcoming form
    public function with(): array
    {
        return [
            'materials' => Material::where('is_deleted', false)->get(),
        ];
    }

    public function save(): void
    {
        // Validate
        $data = $this->validate();

        if ($this->_isProductExists($data['name'])) {
            $this->error('Product already exists.', redirectTo: '/products/create');
            return;
        }

        $product = new Product();
        $product->name = $data['name'];
        $product->description = $data['description'];
        $product->price = $data['price'];
        $product->save();

        $product->materials()->sync($this->product_materials);

        if ($this->photo) {
            if ($product->url) {
                $urlArr = explode('storage/', $product->url);
                $path = count($urlArr) > 1
                    ? $urlArr[1]
                    : '';
                $storageUrl = 'public/' . $path;

                Storage::exists($storageUrl)
                    ? Storage::delete($storageUrl)
                    : $isWarning = true;
            }

            $url = $this->photo->store('product', 'public');
            $product->update(['url' => "/storage/$url"]);
        }

        // You can toast and redirect to any route
        $this->success('Продукт оновлен успішно.', redirectTo: '/products/' . $product->id . '/edit');
    }

    private function _isProductExists(string $name): bool
    {
        return (bool)Product::query()->where('name', $name)->first();
    }
}; ?>

<div>
    <x-header title="Створити продукт" separator/>
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="">
            <x-form wire:submit="save">
                <x-file label="Зображення" wire:model="photo" accept="image/png, image/jpeg" crop-after-change>
                    <img src="/empty-product.png" class="h-40 rounded-lg"/>
                </x-file>

                <x-input label="Ім'я" wire:model="name"/>
                <x-textarea
                    label="Опис"
                    wire:model="description"
                    placeholder="Додайте опис тут ..."
                    hint="Max 1000 chars"
                    rows="5"
                    inline/>

                <x-choices-offline
                    label="Матеріали"
                    wire:model="product_materials"
                    :options="$materials"
                    searchable/>

                <x-input
                    label="Ціна"
                    wire:model.defer="price"
                    suffix="грн."
                    inline
                    required
                    locale="pt-BR"
                />

                <x-slot:actions>
                    <x-button label="Відмінити" link="/users"/>
                    {{-- The important thing here is `type="submit"` --}}
                    {{-- The spinner property is nice! --}}
                    <x-button label="Зберегти" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary"/>
                </x-slot:actions>
            </x-form>
        </div>
        <div class="">
            <img src="" width="300" class="mx-auto"/>
        </div>
    </div>
</div>
