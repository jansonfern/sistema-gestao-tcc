@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>
                        @if(auth()->user()->role === 'admin')
                            Gerenciar Agendas
                        @else
                            Minha Agenda
                        @endif
                    </h4>
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('schedules.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Adicionar Horário
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @if($schedules->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Horário</th>
                                        <th>Médico</th>
                                        <th>Status</th>
                                        @if(auth()->user()->role === 'admin')
                                            <th class="text-end">Ações</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($schedules as $schedule)
                                        <tr data-schedule-id="{{ $schedule->id }}">
                                            <td>{{ \Carbon\Carbon::parse($schedule->date)->format('d/m/Y') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($schedule->available_time)->format('H:i') }}</td>
                                            <td>{{ $schedule->doctor?->user?->name ?? 'Médico removido' }} - {{ $schedule->doctor?->specialty ?? '-' }}</td>
                                            <td>
                                                <span class="badge schedule-status {{ $schedule->is_available ? 'bg-success' : 'bg-danger' }}" style="color: white;">
                                                    {{ $schedule->is_available ? 'Disponível' : 'Ocupado' }}
                                                </span>
                                            </td>
                                            @if(auth()->user()->role === 'admin')
                                                <td class="text-end">
                                                    <div class="d-flex flex-nowrap justify-content-end gap-1">
                                                        <a href="{{ route('schedules.edit', $schedule) }}" class="btn btn-sm btn-warning" title="Editar horário">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
                                                        <form action="{{ route('schedules.toggle-availability', $schedule) }}" method="POST" class="d-inline toggle-schedule-form">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="btn btn-sm {{ $schedule->is_available ? 'btn-secondary' : 'btn-success' }}" title="{{ $schedule->is_available ? 'Marcar como ocupado' : 'Marcar como disponível' }}">
                                                                <i class="fas fa-{{ $schedule->is_available ? 'ban' : 'check' }}"></i>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('schedules.destroy', $schedule) }}" method="POST" class="d-inline delete-schedule-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Excluir horário">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $schedules->links() }}
                    @else
                        <div class="alert alert-info">
                            @if(auth()->user()->role === 'admin')
                                Nenhum horário futuro cadastrado.
                            @else
                                Nenhum horário futuro disponível na sua agenda.
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(() => {
    const url = @json(route('schedules.status'));
    const tbody = document.querySelector('.table-responsive tbody');
    if (!tbody) return;

    tbody.addEventListener('submit', function (e) {
        const form = e.target.closest('.delete-schedule-form, .toggle-schedule-form');
        if (!form) return;
        const message = form.classList.contains('toggle-schedule-form')
            ? 'Deseja alterar a disponibilidade deste horário?'
            : 'Tem certeza que deseja excluir este horário?';
        if (!confirm(message)) {
            e.preventDefault();
        }
    });

    async function refreshSchedules() {
        try {
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;

            const data = await res.json();
            const signature = JSON.stringify(data);
            if (signature === refreshSchedules.lastSignature) return;
            refreshSchedules.lastSignature = signature;

            data.forEach(s => {
                const row = tbody.querySelector(`tr[data-schedule-id="${s.id}"]`);
                if (!row) return;
                const badge = row.querySelector('.schedule-status');
                badge.className = 'badge schedule-status ' + (s.is_available ? 'bg-success' : 'bg-danger');
                badge.textContent = s.is_available ? 'Disponível' : 'Ocupado';
            });
        } catch (e) {
            // silencioso: mantém os dados já renderizados
        }
    }

    refreshSchedules();
    setInterval(refreshSchedules, 15000);
})();
</script>
@endsection 