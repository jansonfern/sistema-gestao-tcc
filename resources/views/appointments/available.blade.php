@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Horários Disponíveis</h4>
                    <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>

                <div class="card-body">
                    @if($availableSlots->count() > 0)
                        @foreach($availableSlots as $date => $slots)
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} 
                                        ({{ \Carbon\Carbon::parse($date)->locale('pt_BR')->dayName }})
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($slots as $slot)
                                            <div class="col-md-4 mb-3">
                                                <div class="card border-primary">
                                                    <div class="card-body text-center">
                                                        <h6 class="card-title">{{ $slot->doctor->user->name }}</h6>
                                                        <p class="card-text text-muted">{{ $slot->doctor->specialty }}</p>
                                                        <p class="card-text">
                                                            <strong>{{ \Carbon\Carbon::parse($slot->available_time)->format('H:i') }}</strong>
                                                        </p>
                                                        <form action="{{ route('appointments.quick-book') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="schedule_id" value="{{ $slot->id }}">
                                                            <button type="submit" class="btn btn-success btn-sm quick-book-btn"
                                                                    data-time="{{ \Carbon\Carbon::parse($slot->available_time)->format('H:i') }}"
                                                                    data-doctor="{{ $slot->doctor->user->name }}">
                                                                <i class="fas fa-calendar-plus"></i> Agendar
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-info">
                            <h5>Nenhum horário disponível</h5>
                            <p>Não há horários disponíveis para os próximos 7 dias. Tente novamente mais tarde ou entre em contato com a clínica.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.quick-book-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const time = this.dataset.time;
            const doctor = this.dataset.doctor;
            if (!confirm(`Confirmar agendamento para ${time} com ${doctor}?`)) {
                e.preventDefault();
            }
        });
    });
});
</script>
@endsection 