<?php

namespace App\Livewire\Clerk;

use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class DispensationPage extends Component
{
    public ?array $selectedAppointment = null;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.clerk.dispensation-page');
    }

    public static function appointmentsDataset(): array
    {
        return [
            [
                'id' => 101,
                'patient_name' => 'Juan Perez',
                'patient_photo_url' => null,
                'membership_number' => 'M-000124',
                'prescriber_doctor' => 'Dra. Elena Ruiz',
                'prescriber_doctor_photo_url' => null,
                'prescriber_doctor_rating' => 5,
                'appointment_at' => '2026-04-14 09:30:00',
                'has_prescription' => true,
                'is_dispensed' => false,
                'dispensed_at' => null,
                'prescribed_medications' => [
                    [
                        'name' => 'Paracetamol',
                        'presentation' => 'Tabletas 500 mg',
                        'dose' => '1 tableta cada 8 horas',
                        'quantity' => 12,
                        'notes' => 'Tomar despues de alimentos',
                    ],
                    [
                        'name' => 'Omeprazol',
                        'presentation' => 'Capsulas 20 mg',
                        'dose' => '1 capsula cada 24 horas',
                        'quantity' => 7,
                        'notes' => 'Tomar en ayunas',
                    ],
                ],
            ],
            [
                'id' => 102,
                'patient_name' => 'Maria Gonzalez',
                'patient_photo_url' => null,
                'membership_number' => 'M-000337',
                'prescriber_doctor' => 'Dr. Luis Hernandez',
                'prescriber_doctor_photo_url' => null,
                'prescriber_doctor_rating' => 4,
                'appointment_at' => '2026-04-13 16:10:00',
                'has_prescription' => true,
                'is_dispensed' => true,
                'dispensed_at' => '2026-04-13 17:02:00',
                'prescribed_medications' => [
                    [
                        'name' => 'Amoxicilina',
                        'presentation' => 'Capsulas 500 mg',
                        'dose' => '1 capsula cada 8 horas por 7 dias',
                        'quantity' => 21,
                        'notes' => 'Completar tratamiento',
                    ],
                ],
            ],
            [
                'id' => 103,
                'patient_name' => 'Carlos Ramirez',
                'patient_photo_url' => null,
                'membership_number' => 'M-000441',
                'prescriber_doctor' => 'Dra. Elena Ruiz',
                'prescriber_doctor_photo_url' => null,
                'prescriber_doctor_rating' => 5,
                'appointment_at' => '2026-04-15 11:45:00',
                'has_prescription' => true,
                'is_dispensed' => false,
                'dispensed_at' => null,
                'prescribed_medications' => [
                    [
                        'name' => 'Ibuprofeno',
                        'presentation' => 'Tabletas 400 mg',
                        'dose' => '1 tableta cada 12 horas',
                        'quantity' => 10,
                        'notes' => 'Suspender en caso de malestar gastrico',
                    ],
                    [
                        'name' => 'Loratadina',
                        'presentation' => 'Tabletas 10 mg',
                        'dose' => '1 tableta cada 24 horas',
                        'quantity' => 5,
                        'notes' => 'Preferente por la noche',
                    ],
                ],
            ],
            [
                'id' => 104,
                'patient_name' => 'Ana Lopez',
                'patient_photo_url' => null,
                'membership_number' => 'M-000509',
                'prescriber_doctor' => 'Dr. Miguel Torres',
                'prescriber_doctor_photo_url' => null,
                'prescriber_doctor_rating' => 4,
                'appointment_at' => '2026-04-12 08:20:00',
                'has_prescription' => true,
                'is_dispensed' => true,
                'dispensed_at' => '2026-04-12 08:55:00',
                'prescribed_medications' => [
                    [
                        'name' => 'Metformina',
                        'presentation' => 'Tabletas 850 mg',
                        'dose' => '1 tableta cada 12 horas',
                        'quantity' => 30,
                        'notes' => 'Con alimentos',
                    ],
                ],
            ],
        ];
    }

    #[On('showDispensationDetails')]
    public function openDetails(int $appointmentId): void
    {
        $appointment = collect(static::appointmentsDataset())
            ->where('has_prescription', true)
            ->firstWhere('id', $appointmentId);

        if (! $appointment) {
            return;
        }

        $appointment['appointment_date_label'] = Carbon::parse($appointment['appointment_at'])->format('d/m/Y H:i');
        $appointment['dispensed_at_label'] = filled($appointment['dispensed_at'])
            ? Carbon::parse($appointment['dispensed_at'])->format('d/m/Y H:i')
            : 'Pendiente';

        $this->selectedAppointment = $appointment;
        $this->dispatch('open-dispensation-details-modal');
    }
}
