<?php

namespace App\Livewire;

use Closure;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StarRating extends Component
{
    public int $rate;
    public int $total = 5;
    public string $empty = '☆';
    public string $filled = '★';
    public string $rating = '';

    /**
     * Initialize the component with data.
     */
    public function mount(int $rate, int $total = 5, string $empty = '☆', string $filled = '★')
    {
        $this->rate = $rate;
        $this->total = $total;
        $this->empty = $empty;
        $this->filled = $filled;

        $this->rating = str_repeat($this->filled, $this->rate)
                      . mb_substr(str_repeat($this->empty, $this->total), $this->rate);
    }

    /**
     * Render the Blade view.
     */
    public function render()
    {
        return view('livewire.star-rating');
    }
}
