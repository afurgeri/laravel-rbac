<?php

namespace Modules\Rbac\Concerns;

/**
 * Adds the `is_active` flag to the authenticatable model. New users start active.
 */
trait CanBeDeactivated
{
    public function initializeCanBeDeactivated(): void
    {
        $this->mergeFillable(['is_active']);
        $this->mergeCasts(['is_active' => 'boolean']);

        if (! array_key_exists('is_active', $this->attributes)) {
            $this->attributes['is_active'] = true;
        }
    }

    /**
     * Users stored before the flag existed have no value and count as active.
     */
    public function isDeactivated(): bool
    {
        $isActive = $this->getAttribute('is_active');

        return $isActive !== null && ! $isActive;
    }
}
