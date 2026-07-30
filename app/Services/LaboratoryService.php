<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Laboratory;
use Illuminate\Validation\ValidationException;

class LaboratoryService
{
    /**
     * Create a new laboratory and assign it to the authenticated user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Laboratory
    {
        $data['created_by'] = auth()->id();

        return Laboratory::create($data);
    }

    /**
     * Soft delete an existing laboratory and assign the deleter.
     */
    public function delete(Laboratory $laboratory): void
    {
        if ($laboratory->medicines()->exists()) {
            throw ValidationException::withMessages([
                'laboratory' => 'No se puede eliminar el laboratorio porque tiene medicamentos activos asociados.',
            ]);
        }

        $laboratory->update(['deleted_by' => auth()->id()]);
        $laboratory->delete();
    }

    /**
     * Restore a soft-deleted laboratory and clear the deleted_by field.
     */
    public function restore(Laboratory $laboratory): void
    {
        $laboratory->update(['deleted_by' => null]);
        $laboratory->restore();
    }
}
