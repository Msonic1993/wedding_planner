<?php

namespace App\Application\Profile\Services;

use App\Application\Location\Contracts\LocationCatalog;
use App\Application\Profile\Contracts\WeddingProfileService;
use App\Domain\Profile\Exceptions\WeddingProfileAlreadyExistsException;
use App\Domain\Profile\Models\WeddingProfile;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\UploadedFile;

class EloquentWeddingProfileService implements WeddingProfileService
{
    public function __construct(private readonly FilesystemFactory $filesystem, private readonly LocationCatalog $locations) {}

    public function create(User $user, array $data): WeddingProfile
    {
        if ($user->weddingProfile()->exists()) {
            throw new WeddingProfileAlreadyExistsException;
        }

        return $this->persist($user, new WeddingProfile, $data);
    }

    public function update(User $user, array $data): WeddingProfile
    {
        return $this->persist($user, $user->weddingProfile()->firstOrFail(), $data);
    }

    /** @param array<string, mixed> $data */
    private function persist(User $user, WeddingProfile $profile, array $data): WeddingProfile
    {
        /** @var UploadedFile|null $photo */
        $photo = $data['photo'] ?? null;
        unset($data['photo']);

        if (array_key_exists('city_id', $data)) {
            $data['location'] = $data['city_id'] === null
                ? null
                : ($this->locations->findCity((int) $data['city_id'])['name'] ?? null);
        }

        if ($photo instanceof UploadedFile) {
            $disk = $this->photoDisk();
            $newPath = $photo->store(sprintf('wedding-profiles/%d', $user->id), $disk);

            if ($profile->photo_path !== null) {
                $this->filesystem->disk($disk)->delete($profile->photo_path);
            }

            $data['photo_path'] = $newPath;
            $data['avatar_key'] = null;
        } elseif (array_key_exists('avatar_key', $data) && $data['avatar_key'] !== null && $profile->photo_path !== null) {
            $this->filesystem->disk($this->photoDisk())->delete($profile->photo_path);
            $data['photo_path'] = null;
        }

        $profile->fill($data);
        $profile->user()->associate($user);
        $profile->save();

        return $profile->refresh();
    }

    private function photoDisk(): string
    {
        return (string) config('filesystems.profile_photo_disk', 'public');
    }
}
