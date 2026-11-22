<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    /** Gera e baixa o PDF do comprovante, restrito a consulta confirmada e usuário autorizado. */
    public function generate(Appointment $appointment)
    {
        $user = Auth::user();

        $canAccess =
            ($user && $user->isAdmin()) ||
            ($user && $user->isMedico() && $user->doctor && $user->doctor->id === $appointment->doctor_id) ||
            ($user && $user->isPaciente() && $user->patient && $user->patient->id === $appointment->patient_id);

        if (! $canAccess) {
            abort(403);
        }

        if ($appointment->status !== 'confirmada') {
            abort(403, 'Consulta não confirmada.');
        }

        if (! $appointment->isAppointmentPast()) {
            abort(403, 'Comprovante disponível somente após a data e horário da consulta.');
        }

        $appointment->load(['patient.user', 'doctor.user']);

        $certificate = Certificate::updateOrCreate(
            ['appointment_id' => $appointment->id],
            ['pdf_path' => null]
        );
        $certificate->touch();

        $pdf = Pdf::loadView('certificates.pdf', [
            'appointment' => $appointment,
            'certificate' => $certificate,
        ]);

        $filename = 'certificado_consulta_'.$appointment->id.'_'.date('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}