<?php

use App\Models\Order;
use App\Enums\Statuses;
use App\Models\Material;
use App\Models\Product;

use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Rule;

new class extends Component {
    use Toast, WithPagination;

    public Order $order;
    public $orderItems;
    public string $status;
    public bool $showDrawer = false;

    #[Rule('required|numeric')]
    public int $itemId;

    #[Rule('required|numeric')]
    public int $quantity;

    public function headers(): array
    {
        return [
            ['key' => 'url', 'label' => '', 'class' => 'w-14'],
            ['key' => 'name', 'label' => 'Ім\'я.', 'class' => ''],
            ['key' => 'count', 'label' => 'Кількість', 'class' => ''],
            ['key' => 'price', 'label' => 'Ціна', 'class' => ''],
            ['key' => 'total', 'label' => 'Загалом', 'class' => ''],

        ];
    }

    public function updateQuantity($itemId, int $count): void
    {
        $this->js("console.log($count);");

        $item = $this->order->getOrderItems()->where('id', $itemId)->first()->orderItems;
        $item->count = $count;
        $item->total = $count * $item->price;

        $item->save();

        $this->order->updateOrderTotal();
    }

    public function delete($itemId): void
    {
        $item = $this->order->getOrderItems()->where('id', $itemId)->first()->orderItems;
        $item->delete();
        $this->order->updateOrderTotal();

        $this->js('window.location.reload()');
    }

    public function deleteOrder()
    {
        $this->order->products()->detach();
        $this->order->materials()->detach();
        $this->order->delete();

        $this->warning('Замовлення успішно видалене', redirectTo: '/orders/list');

    }

    public function save(): void
    {
        //syncWithoutDetaching
        $this->validate();

        if ($this->order->client_id !== 1) {
            $dataType = 'product';
            $item = Product::where('id', $this->itemId)->first();
            $price = $item->price;
        } else {
            $dataType = 'material';
            $price = $this->order
                ->manufacture
                ->materials()->where('id', $this->itemId)->first()
                ->pivot->price;
        }

        $data = [
            $dataType . '_id' => $this->itemId,
            'count' => $this->quantity,
            'price' => $price,
            'total' => $price * $this->quantity,
        ];

        $dataType .= 's';
        $this->order->$dataType()->syncWithoutDetaching([$data]);
        $this->showDrawer = false;
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function changeStatus(bool $isNext = true): void
    {
        $statuses = Statuses::cases();
        $statusesCount = count($statuses);

        $i = 0;
        while ($i < $statusesCount) {
            if ($this->order->status === $statuses[$i]->name) {
                // validate status change action
                if ($isNext && $i === $statusesCount - 1) {
                    return;
                } elseif (!$isNext && $i === 0) {
                    return;
                }

                // update order status
                $this->order->status = $isNext
                    ? $statuses[$i + 1]->name
                    : $statuses[$i - 1]->name;

                $this->order->save();

                return;
            }

            $i++;
        }
    }

    public function finalize(): void
    {
        $status = $this->order->client_id === 1
        ? $this->order->finalizeMaterials()
        : $this->order->finalizeProducts();

        if ($status) {
            $this->error($status);
        }
    }

    public function getAvailableItems(): Collection
    {
//        dd($this->order->manufacture);
        return $this->order->client_id !== 1
            ? Product::whereNotIn('id', $this->order->products()->pluck('id'))->get()
            : $this->order->manufacture
                ->materials()
                ->whereNotIn('id', $this->order->materials()
                    ->pluck('id'))->get();
    }

    public function with(): array
    {
        if ($this->order->client_id === 1) {
            $this->orderItems = $this->order->materials;
            $link = '/materials/';
        } else {
            $this->orderItems = $this->order->products;
            $link = '/products/';
        }

        return [
            'order' => $this->order,
            'orderItems' => $this->orderItems,
            'headers' => $this->headers(),
            'itemsList' => $this->getAvailableItems()
        ];
    }
}; ?>

<div>
    <x-header title="Замовлення #{{$order->id}}" separator progress-indicator>
        <x-slot:actions>
            @if(!$order->is_finalized)
                <x-button label="Видалити" wire:click="deleteOrder()" wire:confirm="Ви впевнені? \nВи не зможете відмінити цю дію" responsive icon="o-trash" class="btn-error"/>
            @endif
        </x-slot:actions>
    </x-header>
    <div class="grid lg:grid-cols-2 gap-8">
        <x-card title="Отримувач" shadow separator>

            <x-card title="{{$order->client->name}}" class="!p-0">
                <x-slot:subtitle class="text-gray-500 flex flex-col gap-2 mt-2 pl-2">
                    <x-icon name="o-envelope" label="{{$order->client->email}}"/>
                    <x-icon name="o-phone" label="{{$order->client->phone}}"/>
                    <x-icon name="o-map-pin" label="{{$order->client->address}}"/>
                </x-slot:subtitle>
            </x-card>
        </x-card>
        <x-card title="Данні" shadow separator>
            <x-slot:menu>
                <x-badge value="{{__($order->status)}}" class="bg-purple-500/20"/>
            </x-slot:menu>

            <div class="grid gap-2">
                <div class="flex gap-3 justify-between items-baseline pl-2 pr-5">
                    <div>Елементи</div>
                    <div class="border-b border-b-gray-400 border-dashed flex-1"></div>
                    <div class="font-black">({{count($orderItems)}})</div>
                </div>
                <div class="flex gap-3 justify-between items-baseline pl-2 pr-5">
                    <div>Загалом</div>
                    <div class="border-b border-b-gray-400 border-dashed flex-1"></div>
                    <div class="font-black">{{$order->total}} грн.</div>
                </div>
            </div>

            <div class="px-2 mt-5">
                @php
                    $pending = false;
                    $background = 'bg-primary';
                @endphp
                <ol class="items-center sm:flex mt-5">
                    @foreach(Statuses::cases() as $status)
                        <li class="relative mb-6 sm:mb-0">
                            <div class="flex items-center">
                                <div
                                    class="z-10 flex items-center justify-center w-7 h-7 {{$pending ? 'bg-base-300' : 'bg-primary'}} rounded-full ring-0 ring-white dark:bg-blue-900 sm:ring-8 dark:ring-gray-900 shrink-0">
                                    <x-icon name="{{__($status->value)}}" class="{{$pending ? '' : 'text-base-100'}}"/>
                                </div>
                                @if(!$loop->last)
                                    <div
                                        class="hidden sm:flex w-full {{$pending ? 'bg-gray-200' : 'bg-primary'}} h-0.5 dark:bg-gray-700"></div>
                                @endif
                            </div>
                            <div class="mt-3 sm:pe-6 min-w-24">
                                <div class="font-bold mb-1">{{__($status->name)}}</div>
                            </div>
                        </li>
                        @php
                            if (!$pending) {
                                $pending = $order->status == $status->name;
                            }
                        @endphp
                    @endforeach
                </ol>
            </div>

            <x-slot:actions>
                @if($order->is_finalized)
                    <x-alert icon="o-exclamation-triangle" class="alert-success">
                        Замовлення закрите
                    </x-alert>
                @else
                    @if($order->status !== Statuses::placed->name)
                        <x-button label="Попередній статус" wire:click="changeStatus(false)" class=""/>
                    @endif
                    @if($order->status !== Statuses::delivered->name)
                        <x-button label="Наступний статус" wire:click="changeStatus()" class=""/>
                    @else
                        <x-button
                            label="Закрити замовлення"
                            wire:click="finalize()"
                            wire:confirm="Ви впевнені? \nYou can't undo that action"
                            class=""/>
                    @endif
                @endif

            </x-slot:actions>
        </x-card>
    </div>

    <x-card title="Елементи" class="mt-5" shadow separator>
{{--        <x-slot:menu>--}}
{{--            <x-button label="Add" icon="o-plus" wire:click="$toggle('showDrawer')"/>--}}
{{--        </x-slot:menu>--}}

        <x-table
            :headers="$headers"
            :rows="$orderItems"
        >
            @scope('cell_url', $orderItem)
            <x-avatar :image="$orderItem->url ?: '/empty-product.png'" class="!w-14 !rounded-lg"/>
            @endscope

            @scope('cell_count', $orderItem)
                {{$orderItem->orderItems->count}}
{{--                <x-input class="!max-w-24" type="number"--}}
{{--                         wire:change="updateQuantity({{$orderItem->id}}, $event.target.value)"--}}
{{--                         value="{{$orderItem->orderItems->count}}" placeholder="item count"/>--}}
            @endscope

            @scope('cell_price', $orderItem)
            {{ $orderItem->orderItems->price }} грн.
            @endscope

            @scope('cell_total', $orderItem)
            {{ $orderItem->orderItems->total }} грн.
            @endscope

{{--            @scope('actions', $orderItem)--}}
{{--            <x-button icon="o-trash" wire:click="delete({{ $orderItem->id }})" wire:confirm="Ви впевнені?" spinner--}}
{{--                      class="btn-ghost btn-sm text-red-500"/>--}}
{{--            @endscope--}}
        </x-table>
    </x-card>

    <x-drawer wire:model="showDrawer" title="Add item" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="save" class="grid grid-flow-row auto-rows-min gap-3">
            <x-choices-offline
                label="{{ $order->client_id === 1 ? 'Materials' : 'Products' }}"
                wire:model="itemId"
                :options="$itemsList"
                icon="o-magnifying-glass"
                height="max-h-96"
                hint="Search for product name"
                single
                searchable
            >
                @scope('item', $item)
                    <x-list-item :item="$item" no-hover>
                        <x-slot:avatar>
                            <x-avatar :image="$item->url ?: '/empty-product.png'" class="!w-14 !rounded-lg" />
                        </x-slot:avatar>
                        <x-slot:value>
                            {{ $item->name }}
                        </x-slot:value>
                        <x-slot:actions>
                            <x-badge :value="$item->price . 'грн.'" />
                        </x-slot:actions>
                    </x-list-item>
                @endscope
            </x-choices-offline>

            <x-input label="Кількість" placeholder="0" wire:model="quantity" />

            <x-slot:actions>
                <x-button label="Відмінити" class="btn-outline btn-error" />
                <x-button label="Add" class="btn-outline btn-success" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
