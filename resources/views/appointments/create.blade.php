@extends('layouts.app')

@section('content')
<div class="min-vh-100 d-flex flex-column booking-page">
    <div class="container py-4 flex-grow-1 booking-container">
        <!-- Header -->
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('appointments.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <div class="flex-grow-1">
                <h1 class="h3 mb-0">Agende sua Consulta</h1>
            </div>
        </div>

        @if(auth()->user()->isAdmin())
        <!-- Patient Selector (admin agenda em nome do paciente) -->
        <div class="card mb-4">
            <div class="card-body">
                <label for="patient-select" class="form-label fw-semibold">Paciente:</label>
                <select class="form-select" id="patient-select" name="patient_id" form="appointment-form" required>
                    <option value="">Selecione o paciente...</option>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" @selected(old('patient_id') == $patient->id)>
                            {{ $patient->user->name }} — CPF {{ $patient->cpf }}
                        </option>
                    @endforeach
                </select>
                @error('patient_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
        @endif

        <!-- Doctor Selector -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label for="doctor-select" class="form-label fw-semibold">Escolha o médico:</label>
                    <select class="form-select" id="doctor-select">
                        <option value="">Carregando médicos...</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-4" id="doctor-card" hidden>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center doctor-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h2 class="h5 mb-1" id="doctor-name">--</h2>
                        <p class="text-muted mb-0 small" id="doctor-specialty">--</p>
                    </div>
                    <span class="badge bg-primary rounded-pill" id="total-slots">0 horários livres</span>
                </div>
            </div>
        </div>

        <!-- Schedule Container -->
        <div id="schedules-container">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2">Carregando horários...</p>
            </div>
        </div>
    </div>

    <div class="fixed-bottom bg-white border-top shadow booking-footer">
        <div class="container py-3 booking-container">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="flex-grow-1">
                    <div class="small text-muted mb-1">Selecionado:</div>
                    <div id="selection-summary" class="fw-bold text-primary" aria-live="polite">
                        Nenhum horário selecionado
                    </div>
                </div>
                <form id="appointment-form" method="POST" action="{{ route('appointments.store') }}" class="d-flex gap-2">
                    @csrf
                    <input type="hidden" name="doctor_id" id="form-doctor-id">
                    <input type="hidden" name="date" id="form-date">
                    <input type="hidden" name="time" id="form-time">
                    <button type="button" class="btn btn-outline-secondary" id="cancel-btn">
                        Cancelar
                    </button>
                    <button type="submit" id="confirm-btn" class="btn btn-primary" disabled>
                        Confirmar agendamento
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style nonce="{{ $cspNonce ?? '' }}">
.py-4 {
    padding-bottom: 100px !important;
}

.booking-page {
    background: #f8f9fa;
}

.booking-container {
    max-width: 900px;
}

.booking-footer {
    z-index: 1000;
}

.doctor-avatar {
    width: 48px;
    height: 48px;
    font-size: 1.25rem;
}

.schedule-btn {
    transition: all 0.2s ease;
}

.schedule-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.schedule-btn.selected {
    background-color: #0ea5e9 !important;
    border-color: #0ea5e9 !important;
    color: white !important;
}

.day-number {
    background-color: #0ea5e9;
    color: white;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    font-weight: 700;
    border-radius: 10px;
}
</style>

<script nonce="{{ $cspNonce ?? '' }}">
let selectedSchedule = null;
let allSchedules = [];

document.addEventListener('DOMContentLoaded', function() {
    loadSchedules();
});

function loadSchedules() {
    const container = document.getElementById('schedules-container');
    const doctorSelect = document.getElementById('doctor-select');

    fetch('/api/schedules/available')
        .then(response => response.json())
        .then(schedules => {
            allSchedules = schedules;

            if (schedules.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhum horário disponível no momento.
                    </div>
                `;
                doctorSelect.innerHTML = '<option value="">Nenhum médico disponível</option>';
                return;
            }

            const grouped = {};
            schedules.forEach(s => {
                if (!grouped[s.doctor_id]) {
                    grouped[s.doctor_id] = {
                        doctor: s.doctor,
                        schedules: []
                    };
                }
                grouped[s.doctor_id].schedules.push(s);
            });

            doctorSelect.innerHTML = '<option value="">Selecione um médico...</option>';
            Object.values(grouped).forEach(group => {
                const option = document.createElement('option');
                option.value = group.doctor.id;
                option.textContent = `${group.doctor.user.name} - ${group.doctor.specialty}`;
                doctorSelect.appendChild(option);
            });

            doctorSelect.addEventListener('change', function() {
                const doctorId = this.value;
                if (doctorId) {
                    showDoctorSchedules(grouped[doctorId]);
                } else {
                    document.getElementById('doctor-card').style.display = 'none';
                    container.innerHTML = '';
                    resetSelection();
                }
            });
        })
        .catch(error => {
            console.error('Erro ao carregar horários:', error);
            document.getElementById('schedules-container').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erro ao carregar horários. Tente novamente.
                </div>
            `;
        });
}

function showDoctorSchedules(group) {
    const container = document.getElementById('schedules-container');
    const doctorCard = document.getElementById('doctor-card');

    document.getElementById('doctor-name').textContent = group.doctor.user.name;
    document.getElementById('doctor-specialty').textContent = group.doctor.specialty;
    document.getElementById('total-slots').textContent = group.schedules.length + ' horários livres';
    document.getElementById('form-doctor-id').value = group.doctor.id;
    doctorCard.style.display = 'block';

    resetSelection();

    const byDate = {};
    group.schedules.forEach(schedule => {
        if (!byDate[schedule.date]) {
            byDate[schedule.date] = [];
        }
        byDate[schedule.date].push(schedule);
    });

    let html = '';
    Object.keys(byDate).forEach(date => {
        const dateParts = date.split('-');
        const year = dateParts[0];
        const month = dateParts[1];
        const day = dateParts[2];
        const dateObj = new Date(year, month - 1, day);
        const dayNames = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        const dayName = dayNames[dateObj.getDay()];

        html += `
            <div class="card mb-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="day-number">${day}</div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0">${dayName}</h5>
                            <small class="text-muted">${day}/${month}/${year}</small>
                        </div>
                        <span class="badge bg-secondary">${byDate[date].length} horários</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
        `;

        byDate[date].forEach(schedule => {
            html += `
                <button type="button"
                        class="btn btn-outline-primary schedule-btn"
                        data-date="${schedule.date}"
                        data-time="${schedule.available_time}"
                        aria-pressed="false">
                    ${schedule.available_time}
                </button>
            `;
        });

        html += `
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;

    document.querySelectorAll('.schedule-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.schedule-btn').forEach(b => {
                b.classList.remove('selected');
                b.setAttribute('aria-pressed', 'false');
            });

            this.classList.add('selected');
            this.setAttribute('aria-pressed', 'true');

            selectedSchedule = {
                date: this.dataset.date,
                time: this.dataset.time
            };

            const dateParts = selectedSchedule.date.split('-');
            const day = dateParts[2];
            const month = dateParts[1];
            const year = dateParts[0];
            const dateObj = new Date(year, month - 1, day);
            const dayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            const dayName = dayNames[dateObj.getDay()];

            document.getElementById('selection-summary').textContent =
                `${dayName}, ${day}/${month}/${year} às ${selectedSchedule.time}`;

            document.getElementById('confirm-btn').disabled = false;

            document.getElementById('form-date').value = selectedSchedule.date;
            document.getElementById('form-time').value = selectedSchedule.time;
        });
    });
}

function resetSelection() {
    selectedSchedule = null;
    document.getElementById('selection-summary').textContent = 'Nenhum horário selecionado';
    document.getElementById('confirm-btn').disabled = true;
    document.getElementById('form-date').value = '';
    document.getElementById('form-time').value = '';
}

document.getElementById('cancel-btn').addEventListener('click', function() {
    window.location.href = '{{ route('appointments.index') }}';
});

document.getElementById('appointment-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json().then(data => ({ ok: response.ok, data })))
    .then(({ ok, data }) => {
        if (ok && data.success) {
            window.location.href = data.redirect || '{{ route('appointments.index') }}';
            return;
        }

        let message = data.message || 'Não foi possível agendar. Tente novamente.';
        if (data.errors) {
            message = Object.values(data.errors).flat().join(' ');
        }
        window.showAppToast(message, 'error');
    })
    .catch(error => {
        console.error('Erro:', error);
        window.showAppToast('Erro ao agendar. Tente novamente.', 'error');
    });
});
</script>
@endsection
