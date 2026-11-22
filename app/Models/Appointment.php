<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'date', 'time', 'status', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class)->withTrashed();
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    public function isAppointmentPast(): bool
    {
        $time = \Carbon\Carbon::parse($this->time)->format('H:i:s');

        return $this->date->copy()->setTimeFromTimeString($time)->isPast();
    }
}
