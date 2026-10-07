<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $email
 * @property string $message
 * @property ContactMessageStatus $visitor_status
 * @property ContactMessageStatus $admin_status
 */
#[Fillable(['email', 'message', 'visitor_status', 'admin_status'])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected $casts = [
        'visitor_status' => ContactMessageStatus::class,
        'admin_status' => ContactMessageStatus::class,
    ];
}
