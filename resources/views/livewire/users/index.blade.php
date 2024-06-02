<?php

use App\Models\User;

use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Roles;

new class extends Component {
    use Toast;
    use WithPagination;

    public string $search = '';

    public bool $drawer = false;

    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    // Clear filters
    public function clear(): void
    {
        $this->reset();
        $this->success('Фільтри скинуті.', position: 'toast-bottom');
    }

    // Delete action
    public function delete(User $user): void
    {
        $user->delete();
        $this->warning("$user->name deleted", 'Good bye!', position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'І\'мя', 'class' => 'w-64'],
            ['key' => 'email', 'label' => 'E-mail'],
        ];
    }

    /**
     * For demo purpose, this is a static collection.
     *
     * On real projects you do it with Eloquent collections.
     * Please, refer to maryUI docs to see the eloquent examples.
     */
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->search, fn(Builder $q) => $q->where('name', 'like', "%$this->search%"))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(5); // No more `->get()`
    }

    public function updated($property): void
    {
        if (! is_array($property) && $property != "") {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        return [
            'users' => $this->users(),
            'headers' => $this->headers(),
            'filterCount' => $this->filterCount(),
            'adminRole' => Roles::admin->name,
            'user' => auth()->user(),
        ];
    }

    public function filterCount(): int
    {
        $count = 0;
        if ($this->search) {
            $count += 1;
        }

        return $count;
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Робітники" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Пошук..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Фільтри" @click="$wire.drawer = true" responsive icon="o-funnel" badge="{{ $filterCount ?: null }}" />
            @if($user->role === $adminRole)
            <x-button label="Створити" link="/users/create" responsive icon="o-plus" class="btn-primary" />
            @endif
        </x-slot:actions>
    </x-header>

    @php
    $link = $user->role === $adminRole
        ? "users/{id}/edit"
        : "";
    @endphp
    <x-card>
        <x-table
            :headers="$headers"
            :rows="$users"
            :sort-by="$sortBy"
            with-pagination
            link="{{$link}}"
        >
            @scope('cell_name', $user)
                @php
                $badgeType = 'badge-primary';

                switch ($user->role) {
                    case Roles::admin->name:
                        $badgeType = 'badge-accent badge-outline';
                        break;
                    case Roles::user->name:
                       $badgeType = 'badge-primary badge-outline';
                }
                @endphp
                <span class="mr-1">
                    {{$user->name}}
                </span>
                <x-badge value="{{ $user->role }}" class="{{ $badgeType }}" />
            @endscope

            @if(auth()->user()->role === $adminRole)
                @scope('actions', $user)
                <x-button icon="o-trash" wire:click="delete({{ $user['id'] }})" wire:confirm="Ви впевнені?" spinner class="btn-ghost btn-sm text-red-500" />
                @endscope
            @endif
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Фільтри" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Пошук..." wire:model.live.debounce="search" icon="o-magnifying-glass" @keydown.enter="$wire.drawer = false" />

        <x-slot:actions>
            <x-button label="Скинути" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Застосувати" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
