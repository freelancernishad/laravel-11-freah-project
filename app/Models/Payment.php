<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'gateway', 'transaction_id', 'currency', 'amount', 'fee',
        'status', 'response_data', 'payment_method', 'payer_email', 'paid_at'
    ];

    protected $casts = [
        'response_data' => 'array', // Cast JSON data to an array
        'paid_at' => 'datetime', // Cast as a datetime
    ];

    // Define relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
