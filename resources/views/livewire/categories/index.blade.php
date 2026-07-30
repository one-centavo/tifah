<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Category;
use App\Services\CategoryService;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    /**
     * Delete a category.
     */
    public function deleteCategory(int $id, CategoryService $categoryService): void
    {
        $category = Category::findOrFail($id);
        $categoryService->delete($category);

        session()->flash('success', 'La categoría ha sido eliminada con éxito.');
    }

    /**
     * Get the categories with pagination.
     */
    public function with(): array
    {
        return [
            'categories' => Category::orderBy('name')->paginate(10),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-blue-900 dark:text-white tracking-tight">Gestión de Categorías</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                Administra las clasificaciones de medicamentos, controla parámetros logísticos y sanitarios de almacenamiento.
            </p>
        </div>
        <div>
            <a href="{{ route('categories.create') }}" wire:navigate
                class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-5 py-3 rounded-xl shadow-md transition-all duration-200 ease-in-out gap-2 cursor-pointer transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                </svg>
                <span>Nueva Categoría</span>
            </a>
        </div>
    </div>

    <!-- Alert Success -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-lime-50 border border-lime-200 text-lime-800 rounded-xl flex items-center shadow-sm" role="alert" id="success-alert">
            <svg class="w-5 h-5 mr-2 text-lime-600 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Card Table -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl overflow-hidden">
        @if($categories->isEmpty())
            <div class="p-12 text-center">
                <div class="inline-flex p-4 bg-slate-50 text-slate-400 rounded-full mb-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5M3.75 5.25h16.5m-16.5 13.5h16.5"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-blue-900 mb-1">No hay categorías registradas</h3>
                <p class="text-sm text-slate-500 mb-6">Comienza registrando la primera categoría de medicamentos en el sistema.</p>
                <a href="{{ route('categories.create') }}" wire:navigate
                    class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-4 py-2.5 rounded-lg transition-colors cursor-pointer">
                    Registrar Categoría
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Nombre</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Descripción</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Cadena de Frío</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Control Especial</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-blue-900 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($categories as $category)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-blue-900">{{ $category->name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-600 max-w-xs truncate" title="{{ $category->description }}">
                                        {{ $category->description ?: 'Sin descripción' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($category->is_cold_chain)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                            <span class="w-1.5 h-1.5 mr-1.5 bg-blue-500 rounded-full"></span>
                                            Requerido
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-50 text-slate-500 border border-slate-100">
                                            No aplica
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($category->is_special_control)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                            <span class="w-1.5 h-1.5 mr-1.5 bg-purple-500 rounded-full"></span>
                                            Controlado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-50 text-slate-500 border border-slate-100">
                                            No aplica
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <a href="{{ route('categories.edit', $category) }}" wire:navigate
                                        class="inline-flex items-center text-blue-600 hover:text-blue-900 transition-colors gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.83 21.75a.75.75 0 01-.342.214l-4.8 1.2a.75.75 0 01-.928-.927l1.2-4.8a.75.75 0 01.214-.342l14.86-14.86zm0 0L19.5 7.125"></path>
                                        </svg>
                                        <span>Editar</span>
                                    </a>
                                    <button type="button" 
                                        wire:click="deleteCategory({{ $category->id }})"
                                        wire:confirm="¿Está seguro de que desea eliminar la categoría '{{ $category->name }}'? Esto afectará los medicamentos que pertenecen a ella."
                                        class="inline-flex items-center text-red-600 hover:text-red-900 transition-colors gap-1 cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path>
                                        </svg>
                                        <span>Eliminar</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>
