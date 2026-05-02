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

    #[Validate('required|integer|min:1|max:5')]
    public $rating;

    #[Validate('required_if:rating,1,2,3')]
    public $comments = '';

    public function messages()
    {
        return [
            'rating.required' => 'Por favor seleccione una calificación.',
            'rating.min' => 'Por favor seleccione una calificación.',
            'comments.required_if' => 'Por favor deje un comentario sobre su experiencia.',
        ];
    }

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
