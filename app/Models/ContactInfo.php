<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $email
 * @property string|null $location
 * @property string|null $linkedin
 * @property string|null $github
 * @property string|null $website
 */
#[Fillable(['email', 'location', 'linkedin', 'github', 'website'])]
class ContactInfo extends Model
{
    //
}
