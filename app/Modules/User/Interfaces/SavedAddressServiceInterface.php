<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\User\Model\User;

interface SavedAddressServiceInterface
{
    /**
     * Get all saved addresses for a customer.
     *
     * @param User $user The currently authenticated user.
     * @return ServiceReturn
     */
    public function getAddresses(User $user): ServiceReturn;

    /**
     * Get a specific saved address.
     *
     * @param User $user The currently authenticated user.
     * @param int $addressId The ID of the address to retrieve.
     * @return ServiceReturn
     */
    public function getAddress(User $user, int $addressId): ServiceReturn;

    /**
     * Create a new saved address.
     *
     * @param User $user The currently authenticated user.
     * @param array $data The address data.
     * @return ServiceReturn
     */
    public function createAddress(User $user, array $data): ServiceReturn;

    /**
     * Update an existing saved address.
     *
     * @param User $user The currently authenticated user.
     * @param int $addressId The ID of the address to update.
     * @param array $data The new address data.
     * @return ServiceReturn
     */
    public function updateAddress(User $user, int $addressId, array $data): ServiceReturn;

    /**
     * Delete a saved address.
     *
     * @param User $user The currently authenticated user.
     * @param int $addressId The ID of the address to delete.
     * @return ServiceReturn
     */
    public function deleteAddress(User $user, int $addressId): ServiceReturn;

    /**
     * Set an address as default.
     *
     * @param User $user The currently authenticated user.
     * @param int $addressId The ID of the address to set as default.
     * @return ServiceReturn
     */
    public function setAsDefault(User $user, int $addressId): ServiceReturn;
}
