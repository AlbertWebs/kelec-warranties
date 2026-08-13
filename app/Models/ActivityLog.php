<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'type',
        'action',
        'status',
        'query',
        'reference',
        'result_summary',
        'meta',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'warranty_lookup' => 'Warranty lookup',
            'product_lookup' => 'Product lookup',
            'odoo_fetch' => 'Odoo fetch',
            default => ucfirst(str_replace('_', ' ', (string) $this->type)),
        };
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            'found', 'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'not_found' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            'error', 'failed' => 'bg-red-50 text-red-700 ring-red-600/20',
            default => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        };
    }
}
