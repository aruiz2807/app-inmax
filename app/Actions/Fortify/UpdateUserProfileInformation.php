<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $this->optimizeAndStoreProfilePhoto($user, $input['photo']);
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }

    private function optimizeAndStoreProfilePhoto(User $user, UploadedFile $photo): void
    {
        $disk = config('jetstream.profile_photo_disk', 'public');
        $path = $this->optimizeAndStoreImage($photo, $disk, 'profile-photos');
        $previous = $user->profile_photo_path;

        $user->forceFill([
            'profile_photo_path' => $path,
        ])->save();

        if ($previous) {
            Storage::disk($disk)->delete($previous);
        }
    }

    private function optimizeAndStoreImage(UploadedFile $photo, string $disk, string $storagePath): string
    {
        $maxBytes = 2 * 1024 * 1024;
        $originalContent = file_get_contents($photo->getRealPath());
        $sourceImage = $originalContent ? imagecreatefromstring($originalContent) : false;

        if ($sourceImage === false) {
            return $photo->storePublicly($storagePath, ['disk' => $disk]);
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        $quality = 85;
        $scale = 1.0;
        $optimizedContent = null;

        // Reduce quality first, then reduce image dimensions if still too large.
        while ($scale >= 0.4) {
            $targetImage = $sourceImage;

            if ($scale < 1.0) {
                $newWidth = max(1, (int) round($originalWidth * $scale));
                $newHeight = max(1, (int) round($originalHeight * $scale));

                $targetImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled(
                    $targetImage,
                    $sourceImage,
                    0,
                    0,
                    0,
                    0,
                    $newWidth,
                    $newHeight,
                    $originalWidth,
                    $originalHeight
                );
            }

            ob_start();
            imagejpeg($targetImage, null, $quality);
            $candidateContent = ob_get_clean();

            if ($targetImage !== $sourceImage) {
                imagedestroy($targetImage);
            }

            if ($candidateContent !== false) {
                $optimizedContent = $candidateContent;

                if (strlen($candidateContent) <= $maxBytes) {
                    break;
                }
            }

            if ($quality > 45) {
                $quality -= 10;
            } else {
                $scale -= 0.1;
                $quality = 75;
            }
        }

        imagedestroy($sourceImage);

        if (! $optimizedContent) {
            return $photo->storePublicly($storagePath, ['disk' => $disk]);
        }

        $path = $storagePath . '/' . Str::uuid() . '.jpg';
        Storage::disk($disk)->put($path, $optimizedContent);

        return $path;
    }
}
