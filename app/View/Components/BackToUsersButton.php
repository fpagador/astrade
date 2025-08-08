<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BackToUsersButton extends Component
{
    public $type;
    /**
     * Create a new component instance.
     */
    public function __construct($type = 'mobile')
    {
        $this->type = $type;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.back-to-users-button');
    }
}
