<?php

namespace App\Application\Vendor\Services;

use App\Application\Location\Contracts\LocationCatalog;
use App\Application\Vendor\Contracts\VendorProfileService;
use App\Domain\Vendor\Exceptions\VendorProfileAlreadyExistsException;
use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;

class EloquentVendorProfileService implements VendorProfileService
{
    public function __construct(private readonly LocationCatalog $locations) {}

    public function create(User $user, array $data): VendorProfile
    {
        if ($user->vendorProfile()->exists()) {
            throw new VendorProfileAlreadyExistsException;
        }

        return $this->save($user, new VendorProfile, $data);
    }

    public function update(User $user, array $data): VendorProfile
    {
        return $this->save($user, $user->vendorProfile()->firstOrFail(), $data);
    }

    private function save(User $user, VendorProfile $profile, array $data): VendorProfile
    {
        if (isset($data['city_ids']) && is_array($data['city_ids'])) {
            $cities = $this->locations->findCities(array_map('intval', $data['city_ids']));
            $data['city_ids'] = array_column($cities, 'id');
            $data['cities'] = array_column($cities, 'name');
            $regionCode = (string) ($data['region_code'] ?? '');
            $region = collect($this->locations->regions((string) ($data['country_code'] ?? '')))
                ->firstWhere('code', $regionCode);
            $data['voivodeship'] = is_array($region) ? $region['name'] : null;
        } elseif (array_key_exists('country_code', $data) && $data['country_code'] === null) {
            $data['region_code'] = null;
            $data['city_ids'] = null;
            $data['cities'] = null;
            $data['voivodeship'] = null;
        }

        $disk = (string) config('filesystems.vendor_media_disk', 'public');
        if (isset($data['images'])) {
            $paths = [];
            foreach ($data['images'] as $file) {
                $paths[] = $file->store("vendors/{$user->id}/images", $disk);
            }$data['image_paths'] = $paths;
            unset($data['images']);
        }

        if (isset($data['price_list'])) {
            $data['price_list_path'] = $data['price_list']->store("vendors/{$user->id}/price-lists", $disk);
            unset($data['price_list']);
        }

        $profile->fill($data);
        $profile->user()->associate($user);
        $profile->save();

        return $profile->refresh();
    }
}
