@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
            ← Voltar ao Dashboard
        </a>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Consultas</h4>
                    @if(auth()->user()->role === 'paciente')
                        <a href="{{ route('appointments.available') }}" class="btn btn-success">
                            <i class="fas fa-calendar-check"></i> Ver Horários Disponíveis
                        </a>
                    @endif
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('appointments.create') }}" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i> Agendar Consulta
                        </a>
                    @endif
                </div>

                <div class="card-body">
                    @if($appointments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Médico</th>
                                        <th>Data</th>
                                        <th>Hora</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($appointments as $appointment)
                                    <tr>
                                        <td>{{ $appointment->patient->user->name }}</td>
                                        <td>
                                            {{ $appointment->doctor->user->name }}
                                            <br><small class="text-muted">{{ $appointment->doctor->specialty }}</small>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($appointment->date)->format('d/m/Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($appointment->time)->format('H:i') }}</td>
                                        <td>
                                            @if($appointment->status === 'pendente')
                                                <span class="badge bg-warning">Pendente</span>
                                            @elseif($appointment->status === 'confirmada')
                                                <span class="badge bg-success">Confirmada</span>
                                            @else
                                                <span class="badge bg-danger">Cancelada</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-sm btn-primary" title="Ver detalhes da consulta">
                                                    <i class="fas fa-eye me-1"></i>Ver
                                                </a>

                                                @if($appointment->status === 'pendente')
                                                    @if(auth()->user()->isAdmin() || (auth()->user()->role === 'medico' && auth()->user()->doctor->id === $appointment->doctor_id))
                                                        <form action="{{ route('appointments.confirm', $appointment) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success" title="Confirmar consulta">
                                                                <i class="fas fa-check me-1"></i>Confirmar
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif

                                                @php
                                                    $appointmentDate = \Carbon\Carbon::parse($appointment->date);
                                                    $appointmentTime = \Carbon\Carbon::parse($appointment->time);
                                                    $slotDateTime = $appointmentDate->copy()->setTimeFromTimeString($appointmentTime->format('H:i:s'));
                                                    $minAdvance = auth()->user()->isPaciente() ? now()->addMinutes(30) : now();
                                                    $canCancel = $appointment->status !== 'cancelada' && $slotDateTime->gt($minAdvance);
                                                @endphp
                                                @if($canCancel)
                                                    <form action="{{ route('appointments.cancel', $appointment) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-danger cancel-appointment-btn" data-appointment-id="{{ $appointment->id }}" title="Cancelar consulta">
                                                            <i class="fas fa-times me-1"></i>Cancelar
                                                        </button>
                                                    </form>
                                                @endif

                                                @if($appointment->status === 'confirmada' && $appointment->isAppointmentPast())
                                                    <a href="{{ route('certificates.generate', $appointment) }}" class="btn btn-sm btn-info" title="Gerar certificado PDF">
                                                        <i class="fas fa-file-pdf me-1"></i>PDF
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $appointments->links() }}
                    @else
                        <div class="alert alert-info">
                            Nenhuma consulta encontrada.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.cancel-appointment-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja cancelar esta consulta?')) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('form.d-inline').forEach(function(form) {
        form.addEventListener('submit', function() {
            form.querySelectorAll('button[type="submit"]').forEach(function(btn) {
                btn.disabled = true;
            });
        });
    });
});
</script>
@endsection 