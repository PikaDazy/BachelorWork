<?php

use App\Models\Order;

use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Statuses;

new class extends Component {
    use Toast, WithPagination;

    public bool $drawer = false;
//    public bool $drawer = true;

    public string $search = '';
    public string $statusType = '';
    public int $warningType = 0;
    public bool $receiverSearch = false;
    public int|null $receiverID = null;

    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    public function clear(): void
    {
        $this->reset();
        $this->success('Фільтри скинуті.', position: 'toast-bottom');
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'created_at', 'label' => 'Дата створення', 'class' => 'w-1'],
            ['key' => 'due_date', 'label' => 'Очікування доставки', 'class' => 'w-1'],
            ['key' => 'client_id', 'label' => 'Отримувач', 'class' => 'w-1'],
            ['key' => 'total', 'label' => 'Загалом', 'class' => 'w-1'],
            ['key' => 'status', 'label' => 'Статус', 'class' => 'w-1'],
            ['key' => 'warnings', 'label' => 'Попередження', 'class' => 'w-1',  'sortBy' => 'due_date'],
        ];
    }

    public function orders(): LengthAwarePaginator
    {
        return Order
            ::warningFilter($this->warningType)
            ->when($this->receiverSearch, fn(Builder $q) => $q->where('client_id', $this->receiverID))
            ->when($this->statusType, fn(Builder $q) => $q->where('status', $this->statusType))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    private function statuses(): array
    {
        $statuses = [];
        foreach (Statuses::cases() as $status) {
            $statuses[] = [
                'id' => $status->name,
                'status' => __($status->name)
            ];
        }

        return $statuses;
    }

    public function filterCount(): int
    {
        $count = 0;
        if ($this->search) {
            $count += 1;
        }

        if ($this->statusType) {
            $count += 1;
        }

        if ($this->warningType) {
            $count += 1;
        }

        return $count;
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'orders' => $this->orders(),
            'filterCount' => $this->filterCount(),
            'statuses' => $this->statuses()
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Замовлення" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Фільтри" @click="$wire.drawer = true" responsive icon="o-funnel"
                      badge="{{ $filterCount ?: null }}"/>
            <x-button label="Створити" link="/orders/create" responsive icon="o-plus" class="btn-primary"/>
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table
            :headers="$headers"
            :rows="$orders"
            :sort-by="$sortBy"
            with-pagination
            link="/orders/{id}/view"
        >
            @scope('cell_client_id', $order)
            {{$order->client->name}}
            @endscope

            @scope('cell_status', $order)
            <x-badge :value="__($order->status)" class="badge-primary"/>
            @endscope

            @scope('cell_warnings', $order)
                @php
                    $stamp = strtotime($order->due_date);
                    $now = now();
                    if ($order->is_finalized) {
                        $error = "закрите";
                        $badgeClass = 'badge-primary';
                    } elseif (strtotime($order->due_date) > time()) {
                        $error = "без попереджень";
                        $badgeClass = 'badge-success';
                    }  else {
                        $error = 'просрочене';
                        $badgeClass = 'badge-error';
                    }
                @endphp
                <x-badge :value="$error" class="{{ $badgeClass }}"/>
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Фільтри" right separator with-close-button class="lg:w-1/3">
        <x-select
            placeholder="Статус"
            class="mt-4"
            wire:model.live.debounce="statusType"
            :options="$statuses"
            {{--            option-value="status"--}}
            option-label="status"
            placeholder-value="0"
            @keydown.enter="$wire.drawer = false"/>

        <x-select
            placeholder="Попередження"
            class="mt-4"
            wire:model.live.debounce="warningType"
            :options="[
                ['id' => 1, 'warning' => 'Просрочені'],
                ['id' => 2, 'warning' => 'Закриті'],
                ['id' => 3, 'warning' => 'Немає попереджень'],
            ]"
            option-value="id"
            option-label="warning"
            placeholder-value="0"
            @keydown.enter="$wire.drawer = false"/>

        <x-slot:actions>
            <x-button label="Скинути" icon="o-x-mark" wire:click="clear" spinner/>
            <x-button label="Застосувати" icon="o-check" class="btn-primary" @click="$wire.drawer = false"/>
        </x-slot:actions>
    </x-drawer>
</div>
