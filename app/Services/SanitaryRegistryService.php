<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SanitaryRegistry;
use Illuminate\Validation\ValidationException;

class SanitaryRegistryService
{
    /**
     * Create a new sanitary registry and assign it to the authenticated user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SanitaryRegistry
    {
        $data['registration_number'] = strtoupper(trim($data['registration_number']));
        $data['created_by'] = auth()->id();

        return SanitaryRegistry::create($data);
    }

    /**
     * Update an existing sanitary registry.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SanitaryRegistry $registry, array $data): SanitaryRegistry
    {
        if (isset($data['registration_number'])) {
            $data['registration_number'] = strtoupper(trim($data['registration_number']));
        }
        $data['updated_by'] = auth()->id();
        $registry->update($data);

        return $registry;
    }

    /**
     * Soft delete an existing sanitary registry and assign the deleter.
     */
    public function delete(SanitaryRegistry $registry): void
    {
        if ($registry->medicines()->exists()) {
            throw ValidationException::withMessages([
                'sanitary_registry' => 'No se puede eliminar el registro sanitario porque tiene medicamentos activos asociados.',
            ]);
        }

        $registry->update(['deleted_by' => auth()->id()]);
        $registry->delete();
    }

    /**
     * Restore a soft-deleted sanitary registry and clear the deleted_by field.
     */
    public function restore(SanitaryRegistry $registry): void
    {
        $registry->update(['deleted_by' => null]);
        $registry->restore();
    }
}
