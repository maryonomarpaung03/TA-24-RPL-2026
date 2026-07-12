<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalSubmission extends Model
{
    protected $table = 'final_submissions';

    protected $fillable = [
        'project_id',
        'submitted_by',
        'report_type',
        'report_path',
        'report_name',
        'report_mime',
        'report_link',
        'presentation_link',
        'repo_link',
        'summary',
        'status',
        'lecturer_note',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /** URL laporan akhir, baik yang berupa berkas unggahan maupun tautan. */
    public function getReportUrlAttribute(): ?string
    {
        if ($this->report_type === 'link') {
            return $this->report_link;
        }

        return $this->report_path
            ? asset('storage/'.$this->report_path)
            : null;
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
