<?php

namespace App\Application\Profile\Services;

use App\Application\Profile\Contracts\WeddingProfileService;
use App\Domain\Profile\Exceptions\WeddingProfileAlreadyExistsException;
use App\Domain\Profile\Models\WeddingProfile;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\UploadedFile;

class EloquentWeddingProfileService implements WeddingProfileService
{
    public function __construct(private readonly FilesystemFactory $filesystem) {}

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

        if ($photo instanceof UploadedFile) {
            $disk = $this->photoDisk();
            $newPath = $photo->store(sprintf('wedding-profiles/%d', $user->id), $disk);

            if ($profile->photo_path !== null) {
                $this->filesystem->disk($disk)->delete($profile->photo_path);
            }

            $data['photo_path'] = $newPath;
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
