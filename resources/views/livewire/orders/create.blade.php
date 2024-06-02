<?php

use App\Models\Product;
use App\Models\Material;
use App\Models\Client;
use App\Models\Order;
use App\Models\Manufacture;
use App\Enums\Statuses;

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Livewire\Attributes\Rule;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Validate;

new class extends Component {
    use Toast, WithFileUploads;

    /*
     TODO: add
        due date
        receiver
        materials/products list
     */
    // You could use Livewire "form object" instead.
    #[Rule('required')]
    public string $dueDate = '';

    #[Rule('required|numeric')]
    public ?int $receiverId = null;

    public ?int $manufactureId = 0;

    public array $items = [];

    public array $itemIds = [];

    #[Validate([
        'itemsCount.*' =>  'required|numeric',
    ])]
    public array $itemsCount = [];

    // We also need this to fill Countries combobox on upcoming form
    public function with(): array
    {
        if (!is_null($this->receiverId)) {
            $listItems = $this->receiverId === 0
                ? Material::all()
                : Product::all();
        }

        $itemsData = $this->getItemsData();

        return [
            'listItems' => $listItems ?? null,
            'clients' => Client::where('is_deleted', false)->get(),
            'receiverId' => $this->receiverId,
            'manufactures' => $this->receiverId === 1 ? Manufacture::where('is_deleted', false)->get() : [],
            'manufactureId' => $this->manufactureId,
            'products' => Product::where('is_deleted', false)->get(),
            'materials' => $this->getManufactureMaterials(),
            'items' => $this->getOrderableItems(),
            'itemsData' => $itemsData,
            'total' => $this->countTotal($this->itemsCount, $itemsData)
        ];
    }

    private function getManufactureMaterials(): Collection|array
    {
        if (!$this->manufactureId) {
            return [];
        }

        return Manufacture::where('id', $this->manufactureId)->first()
            ->materials;
    }

    private function getOrderableItems(): Collection|null
    {
        if (!$this->manufactureId) {
            return null;
        }

        if ($this->receiverId === 1) {
            return Manufacture::where('id', $this->manufactureId)->first()->materials;

        }

        return Product::whereIn('id', $this->itemIds)->get();
    }

    private function getItemsData(): Collection|array
    {
        if (empty($this->itemIds)) {
            return [];
        }

        if ($this->receiverId === 1) {
            $manufacture = Manufacture::where('id', $this->manufactureId)->first();
            if ($manufacture) {
                return $manufacture->materials()->whereIn('id', $this->itemIds)->get();
            }
            return [];
        }

        return Product::whereIn('id', $this->itemIds)->get();
    }

    private function countTotal($countData, Collection|array $items): float
    {
        if (empty($items)) {
            return 0;
        }

        $total = 0;
        foreach ($countData as $id => $count) {
            $item = $items->where('id', $id)->first();
            if (!$item || !$count) {
                return 0;
            }
//            $total += ($item->price * $count) ?: ($item->pivot->price * $count);
            $total += $this->receiverId === 1
                ? ($item->pivot->price * $count)
                : ($item->price * $count);
        }

        return $total;
    }

    public function save(): void
    {
        $this->validate();
        if (!$this->validateItems()) {
            return;
        }
        $itemsData = $this->getItemsData();

        $order = new Order();
        $order->due_date = $this->dueDate;
        $order->client_id = $this->receiverId;
        $order->status = Statuses::placed->name;
        $order->total = $this->countTotal($this->itemsCount, $itemsData);

        if ($this->receiverId === 1) {
            $order->manufacture_id = $this->manufactureId;
            $itemName = 'material';
        } else {
            $itemName = 'product';
        }

        $order->is_finalized = false;

        $syncData = [];
        foreach ($this->itemIds as $itemId) {
            $count = $this->itemsCount[$itemId];

            $item = $itemsData->where('id', $itemId)->first();
            $price = $item->price ?: $item->pivot->price;

            $syncData[$itemId] = [
                'count' => $count,
                'price' => $price,
                'total' => $count * (float)$price
            ];
        }

        $itemName .= 's';
        $order->save();
        $order->$itemName()->sync($syncData);

        $this->success('Order created with success.', redirectTo: '/orders/' . $order->id . '/view');
    }

    public function updatingReceiverId($value)
    {
        if (
            $this->receiverId === 1 && $value !== 1 ||
            $value === 1
        ) {
            $this->fill([
                'itemIds' => [],
                'itemsCount' => [],
            ]);
        }
    }

    private function validateItems(): bool
    {
        $errors = true;
        foreach ($this->itemIds as $itemId) {
            if (!isset($this->itemsCount[$itemId]) || !$this->itemsCount[$itemId]) {
                $this->addError(
                    'itemsCount.' . $itemId,
                    'Необхідно ввести кількість'
                );

                $errors = false;
            }
        }

        return $errors;
    }
}; ?>

<div>
    <x-header title="Створити Замовлення" separator/>
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="">
            <x-form wire:submit="save">
                <x-datetime
                    label="Очікуванна дата доставки"
                    wire:model="dueDate"
                    icon="o-calendar"
                    type="datetime-local"
                    min="{{ date('Y-m-d', (time() + 24 * 3600)) . 'T00:00' }}"
                />

                <x-choices
                    label="Отримувач"
                    wire:model.live="receiverId"
                    wire:change="resetForm($event.target.value)"
                    :options="$clients"
                    single
                />

                @if($receiverId === 1)
                    <x-choices-offline
                        label="Виробник"
                        wire:model.live="manufactureId"
                        :options="$manufactures"
                        height="max-h-96"
                        hint="Search for product name"
                        searchable
                        single
                    >
                        @scope('item', $item)
                        <x-list-item :item="$item" no-hover>
                            <x-slot:sub-value>
                                @foreach($item->materials as $material)
                                    <x-badge :value="$material->name" class="badge-primary m-1" />
                                @endforeach
                            </x-slot:sub-value>
                        </x-list-item>
                        @endscope
                    </x-choices-offline>

                    @if($manufactureId)
                        <x-choices-offline
                            label="Матеріали"
                            wire:model.live="itemIds"
                            :options="$materials"
                            height="max-h-96"
                            hint="Search for product name"
                            searchable
                        >
                            @scope('item', $item)
                            <x-list-item :item="$item" no-hover>
                                <x-slot:avatar>
                                    <x-avatar :image="$item->url ?: '/empty-product.png'" class="!w-14 !rounded-lg"/>
                                </x-slot:avatar>
                                <x-slot:value>
                                    {{ $item->name }}
                                </x-slot:value>
                                <x-slot:actions>
                                    <x-badge :value="$item->pivot->price . 'грн.'"/>
                                </x-slot:actions>
                            </x-list-item>
                            @endscope

                            @scope('selection', $item)
                            {{ $item->name }} ({{ $item->pivot->price }}грн.)
                            @endscope
                        </x-choices-offline>
                    @endif
                @endif

                @if($receiverId > 1)
                    <x-choices-offline
                        label="Продукти"
                        wire:model.live="itemIds"
                        :options="$products"
                        height="max-h-96"
                        hint="Search for product name"
                        searchable
                    >
                        @scope('item', $item)
                        <x-list-item :item="$item" no-hover>
                            <x-slot:avatar>
                                <x-avatar :image="$item->url ?: '/empty-product.png'" class="!w-14 !rounded-lg"/>
                            </x-slot:avatar>
                            <x-slot:value>
                                {{ $item->name }}
                            </x-slot:value>
                            <x-slot:actions>
                                <x-badge :value="$item->price . 'грн.'"/>
                            </x-slot:actions>
                        </x-list-item>
                        @endscope

                        @scope('selection', $item)
                        {{ $item->name }} ({{ $item->price }}грн.)
                        @endscope
                    </x-choices-offline>
                @endif

                @if($itemsData)
                    @foreach($itemsData as $item)
                        <x-list-item :item="$item" no-hover>
                            <x-slot:avatar>
                                <x-avatar :image="$item->url ?: '/empty-product.png'" class="!w-14 !rounded-lg" />
                            </x-slot:avatar>
                            <x-slot:value>
                                {{ $item->name }}
                            </x-slot:value>
                            <x-slot:actions>
                                <x-input
                                    placeholder="кількість"
                                    wire:model.live="itemsCount.{{$item->id}}"
                                    type="number"
                                />
                            </x-slot:actions>
                        </x-list-item>
                    @endforeach
                @endif

                <x-slot:actions>
                    <x-button label="Відмінити" link="/users"/>
                    {{-- The important thing here is `type="submit"` --}}
                    {{-- The spinner property is nice! --}}
                    <x-button label="Зберегти" icon="o-paper-airplane" spinner="save" type="submit" class="btn-primary"/>
                </x-slot:actions>
            </x-form>
        </div>
        <div class="">
            <div class="grid gap-2">
                <div class="flex gap-3 justify-between items-baseline px-10">
                    <div>Елементи</div>
                    <div class="border-b border-b-gray-400 border-dashed flex-1"></div>
                    <div class="font-black">({{count($itemsData)}})</div>
                </div>
                <div class="flex gap-3 justify-between items-baseline px-10">
                    <div>Загалом</div>
                    <div class="border-b border-b-gray-400 border-dashed flex-1"></div>
                    <div class="font-black">{{$total}} грн.</div>
                </div>
            </div>
        </div>
    </div>
</div>
