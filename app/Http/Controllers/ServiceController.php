<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Service;

class ServiceController extends Controller
{
    /**
     * Show the services screen.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function show(Request $request)
    {
        $services = Service::all();

        return view('livewire.services.services-page', [
            'request' => $request,
            'services' => $services,
        ]);
    }

    /**
     * Store the service in the DB.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        Validator::make($request->input(), [
            'name' => ['required', 'string', 'max:100'],
        ])->validate();

        Service::create([
            'name' => $request['name'],
            'type' => $request['type'],
        ]);

        session()->flash('notify', [
            'content' => 'Servicio almacenado exitosamente!',
            'type' => 'success',
            'duration' => 4000
        ]);

        return redirect()->route('services');
    }
}
