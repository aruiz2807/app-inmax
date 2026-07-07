<?php

namespace App\Livewire\Forms;

use App\Models\Policy;
use App\Models\User;
use App\Services\Policies\IndividualPolicyRegistrationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class IndividualPolicyForm extends Form
{
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|email|max:255|unique:users')]
    public $email = '';

    #[Validate('required|string|size:10|unique:users')]
    public $phone = '';

    #[Validate('required|date|before_or_equal:today|after:1900-01-01')]
    public $birth = null;

    #[Validate('nullable|string|size:18')]
    public $curp = null;

    #[Validate('string|max:255')]
    public $passport = '';

    #[Validate('required')]
    public $plan = null;

    #[Validate('nullable|file|mimes:jpg,jpeg,png')]
    public $attachment = null;

    #[Validate('nullable')]
    public $parent_policy = null;

    #[Validate('nullable')]
    public $sales_user = null;

    #[Validate('nullable|array')]
    public $insurance = [];

    public $photo = '/img/user.png';

    #[Validate('string|max:255')]
    public $legal_name = '';

    #[Validate('string|max:255')]
    public $legal_address = '';

    public $legal_relationship_id = null;

    #[Validate('string|max:13')]
    public $cfdi_rfc = '';

    #[Validate('string|max:255')]
    public $cfdi_name = '';

    #[Validate('string|max:5')]
    public $cfdi_postal_code = '';

    #[Validate('nullable')]
    public $cfdi_regime_id = null;

    #[Validate('nullable')]
    public $cfdi_use_id = null;

    public bool $foreigner = false;
    public bool $same_as_user = true;

    public bool $addingMember = false;

    /**
    * Store the individual policy in the DB.
    */
    public function store(
        IndividualPolicyRegistrationService $registrationService,
        ?int $policyPreregistrationId = null
    ): Policy {
        $this->validate();

        $path = $this->attachment ? $this->optimizeAndStoreAttachment() : null;

        return $registrationService->create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth' => $this->birth,
            'curp' => $this->curp,
            'passport' => $this->passport,
            'path' => $path,
            'plan_id' => (int) $this->plan,
            'sales_user_id' => $this->sales_user ? (int) $this->sales_user : null,
            'parent_policy_id' => $this->parent_policy ? (int) $this->parent_policy : null,
            'insurance' => $this->insurance,
            'adding_member' => $this->addingMember,
            'policy_preregistration_id' => $policyPreregistrationId,
            //legal info
            'legal_name' => $this->legal_name,
            'legal_address' => $this->legal_address,
            'legal_relationship_id' => $this->legal_relationship_id,
            'cfdi_rfc' => $this->cfdi_rfc,
            'cfdi_name' => $this->cfdi_name,
            'cfdi_postal_code' => $this->cfdi_postal_code,
            'cfdi_regime_id' => $this->cfdi_regime_id,
            'cfdi_use_id' => $this->cfdi_use_id,
        ]);
    }

    /**
    * Sets the policy to edit.
    */
    public function set(Policy $policy)
    {
        $this->name = $policy->user->name;
        $this->email = $policy->user->email;
        $this->phone = $policy->user->phone;
        $this->birth = $policy->user->birth_date?->format('Y-m-d');
        $this->curp = $policy->user->curp;
        $this->passport = $policy->user->passport;
        $this->plan = (string) $policy->plan_id;
        $this->sales_user = (string) $policy->sales_user_id;
        $this->parent_policy = (string) $policy->parent_policy_id;
        $this->insurance = $policy->insurance;
        $this->photo = $policy->user->photo_url;

        if($this->passport)
        {
            $this->foreigner = true;
        }

        if ($policy->policyLegalInformation) {
            $this->legal_name = $policy->policyLegalInformation->legal_name;
            $this->legal_address = $policy->policyLegalInformation->legal_address;
            $this->legal_relationship_id = $policy->policyLegalInformation->legal_relationship_id;
            $this->cfdi_rfc = $policy->policyLegalInformation->cfdi_rfc;
            $this->cfdi_name = $policy->policyLegalInformation->cfdi_name;
            $this->cfdi_postal_code = $policy->policyLegalInformation->cfdi_postal_code;
            $this->cfdi_regime_id = $policy->policyLegalInformation->cfdi_regime_id;
            $this->cfdi_use_id = $policy->policyLegalInformation->cfdi_use_id;
            $this->same_as_user = ($this->legal_name === $this->name);
        } else {
            $this->legal_name = $this->name;
            $this->legal_address = '';
            $this->legal_relationship_id = null;
            $this->cfdi_rfc = '';
            $this->cfdi_name = '';
            $this->cfdi_postal_code = '';
            $this->cfdi_regime_id = null;
            $this->cfdi_use_id = null;
            $this->same_as_user = true;
        }
    }

    /**
    * Sets the policy to add member.
    */
    public function member(Policy $policy)
    {
        $this->plan = (string) $policy->plan_id;
        $this->parent_policy = (string) $policy->id;
        $this->addingMember = true;
    }

    /**
    * Updates the policy in the DB.
    */
    public function update($policyId)
    {
        $policy = Policy::find($policyId);
        $user = User::find($policy->user_id);

        \Illuminate\Support\Facades\Validator::make([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth' => $this->birth,
            'curp' => $this->curp,
            'passport' => $this->passport,
            'legal_name' => $this->legal_name,
            'legal_address' => $this->legal_address,
            'legal_relationship_id' => $this->legal_relationship_id,
            'cfdi_rfc' => $this->cfdi_rfc,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'size:10', \Illuminate\Validation\Rule::unique('users', 'phone')->ignore($user->id)],
            'birth' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'curp' => ['nullable', 'string', 'size:18'],
            'passport' => ['nullable', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'legal_address' => ['required', 'string', 'max:255'],
            'legal_relationship_id' => ['nullable'],
            'cfdi_rfc' => ['required', 'string', 'max:13'],
        ])->validate();

        if ($this->attachment) {
            \Illuminate\Support\Facades\Validator::make([
                'attachment' => $this->attachment,
            ], [
                'attachment' => ['file', 'mimes:jpg,jpeg,png'],
            ])->validate();
        }

        $path = $this->attachment
            ? $this->optimizeAndStoreAttachment()
            : $user->profile_photo_path;

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth,
            'curp' => $this->curp,
            'passport' => $this->passport,
            'profile_photo_path' => $path,
        ]);

        $policy->update([
            'insurance' => $this->insurance,
            'plan_id' => (int) $this->plan,
            'sales_user_id' => $this->sales_user ? (int) $this->sales_user : null,
            'parent_policy_id' => $this->parent_policy ? (int) $this->parent_policy : null,
        ]);

        $policy->policyLegalInformation()->updateOrCreate(
            ['policy_id' => $policy->id],
            [
                'legal_name' => $this->legal_name,
                'legal_address' => $this->legal_address,
                'legal_relationship_id' => $this->legal_relationship_id,
                'cfdi_rfc' => $this->cfdi_rfc,
                'cfdi_name' => $this->cfdi_name,
                'cfdi_postal_code' => $this->cfdi_postal_code,
                'cfdi_regime_id' => $this->cfdi_regime_id,
                'cfdi_use_id' => $this->cfdi_use_id,
            ]
        );
    }

    private function optimizeAndStoreAttachment()
    {
        $maxBytes = 2 * 1024 * 1024;
        $originalContent = file_get_contents($this->attachment->getRealPath());
        $sourceImage = $originalContent ? imagecreatefromstring($originalContent) : false;

        if ($sourceImage === false) {
            return $this->attachment->store('profile-photos', 'public');
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        $quality = 85;
        $scale = 1.0;
        $optimizedContent = null;

        // First reduce JPEG quality, then reduce dimensions if needed.
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
            return $this->attachment->store('profile-photos', 'public');
        }

        $path = 'profile-photos/' . Str::uuid() . '.jpg';
        Storage::disk('public')->put($path, $optimizedContent);

        return $path;
    }

    /**
     * Calculate age from birth date.
     */
    public function age()
    {
        if (!$this->birth) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($this->birth)->age;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Dynamic validation rules.
     */
    protected function rules()
    {
        $age = $this->age();
        $isRequired = ($age !== null && $age < 18) || ! $this->same_as_user;

        return [
            'legal_relationship_id' => $isRequired ? 'required' : 'nullable',
        ];
    }

}
