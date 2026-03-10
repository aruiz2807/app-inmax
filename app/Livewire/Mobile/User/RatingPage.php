<?php

namespace App\Livewire\Mobile\User;

use App\Livewire\Mobile\User\RatingConfirmationPage;
use App\Models\Appointment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class RatingPage extends Component
{
    public $appointment;

    #[Validate('required|min:1|max:5')]
    public $rating = 0;

    #[Validate('required_if:rating,1,2,3')]
    public $comments = '';

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.rating-page');
    }

    public function mount($appointment)
    {
        $this->appointment = Appointment::findOrFail($appointment);
    }

    public function rate(int $value)
    {
        $this->rating = $value;
    }

    public function save()
    {
        $this->validate();

        $this->appointment->update([
            'rating' => $this->rating,
            'comments' => $this->comments,
        ]);

        session()->flash('appointment_rating_id', $this->appointment->id);

        return $this->redirect(RatingConfirmationPage::class);
    }

}
